<?php
$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require ($common_autoload_file);

$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require ($php_cli_autoload_file);

use common\curl\Main as curl;
use cli\classes as cli;
use common\db\Main as db;

\common\Config::obj(__DIR__ . '/config/config.ini');

class UrlOpt extends cli\Flag
{

    protected $startUrl;

    protected $config = array(
        'startUrl' => array(
            FILTER_VALIDATE_URL
        )
    );
}

try {
    $uo = new \UrlOpt();
    $uo->exchangeArray(array_slice($_SERVER['argv'], 1));
} catch (\UnexpectedValueException | \ArgumentCountError $e) {
    \common\logging\Logger::obj()->writeException($e);
    echo $e->getMessage()."\n";
}

if ($uo->startUrl !== null) {
    
    define('DB_DSN', 'mysql:host=127.0.0.1;dbname=mysql_profile');
    define('DB_USER_NAME', 'mysql_profile');
    define('DB_USER_PASS', 'asjhAS78235rtjA%^23Sf');

    class SpiderHTTPUrl
    {

        private $getContentLimit = 0;

        private $contentLimit = 0;

        private $previousUrls = [];
        
        private $whiteListExtension = [];
        
        private $unkownExtension = [];

        public function __construct()
        {
            $this->contentLimit = \common\Config::obj()->system['contentLength'];
            
            $this->whiteListExtension = \common\Config::obj()->system['whiteListExtension'];
        }

        public function runCurl($url)
        {
            $ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';
            
            $curl = new curl(FALSE);
            
            $cookieFile = __DIR__ . '/http_cookies.txt';
            touch($cookieFile);
            
            $curl->create();
            $curl->addOption(CURLOPT_URL, $url);
            $curl->addOption(CURLOPT_RETURNTRANSFER, TRUE);
            $curl->addOption(CURLOPT_FOLLOWLOCATION, TRUE);
            $curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
            $curl->addOption(CURLOPT_USERAGENT, $ua);
            
            $curl->run();
            
            \common\logging\Logger::obj()->write('Spidered ' . $url);
            
            return $curl;
        }

        public function getContent($content = null)
        {
            global $uo;
            
            if ($this->getContentLimit >= $this->contentLimit) {
                return FALSE;
            } else {
                $this->getContentLimit ++;
                
                $d = new DomDocument();
                @$d->loadHTML($content);
                $dx = new DOMXPath($d);
                foreach ($d->getElementsByTagName('a') as $url) {
                    $href = $url->getAttribute('href');
                    
                    if (isset($href) && strlen($href)) {
                        if (! filter_var($href, FILTER_VALIDATE_URL)) {
                            $href = $uo->startUrl . $href;
                        } else {
                            if (parse_url($href, PHP_URL_HOST) !== parse_url($uo->startUrl, PHP_URL_HOST)) {
                                continue;
                            }
                        }
                        if (in_array($href, $this->previousUrls)) {
                            continue;
                        }
                        
                        $this->previousUrls[] = $href;
                        
                        $path = pathinfo($href, PATHINFO_EXTENSION);
                        if (strlen($path) > 0 && !($wle = array_search($path, $this->whiteListExtension))) {
                                $this->unkownExtension[] = $path;
                                continue;
                        }
                        
                        $curl = $this->runCurl($href);
                        
                        
                        try {
                            $db = db::obj();
                        } catch (\PDOException $pe) {
                            exit(\common\logging\Logger::obj()->writeException($pe));
                        }
                        $info = array_filter($curl->info()[0], function ($v) {
                            if (in_array($v[0], array(
                                CURLINFO_EFFECTIVE_URL,
                                CURLINFO_HTTP_CODE,
                                CURLINFO_TOTAL_TIME,
                                CURLINFO_REDIRECT_COUNT,
                                CURLINFO_REQUEST_SIZE,
                                CURLINFO_CONTENT_TYPE
                            ), TRUE)) {
                                $result = TRUE;
                            } else {
                                $result = FALSE;
                            }
                            return $result;
                        });
                        try {
                            $db->insert('test_data', array(
                                'url' => $info[CURLINFO_EFFECTIVE_URL][1],
                                'http_status_code' => $info[CURLINFO_HTTP_CODE][1],
                                'response_time' => $info[CURLINFO_TOTAL_TIME][1],
                                'redirect_count' => $info[CURLINFO_REDIRECT_COUNT][1],
                                'response_length' => $info[CURLINFO_REQUEST_SIZE][1],
                                'content_type' => $info[CURLINFO_CONTENT_TYPE][1]
                            ));
                        } catch (\RuntimeException | \Error $e) {
                            exit(\common\logging\Logger::obj()->writeException($e));
                        }
                        $output = $curl->getOutput()[0][1];
                        $curl->close();
                        if ($this->getContent($output) === FALSE)
                            break;
                    }
                }
                
                return TRUE;
            }
        }
        
        public function getunkownExtension() {
            if (!empty($this->unkownExtension)) {
                \common\logging\Logger::obj()->write('Begin Unkown Extensions');
                foreach ($this->unkownExtension as $ue) {
                    \common\logging\Logger::obj()->write($ue);
                }
                \common\logging\Logger::obj()->write('End Unkown Extensions');
            }
        }
    }
    
    $spc = new \SpiderHTTPUrl();
    $curl = $spc->runCurl($uo->startUrl);
    $spc->getContent($curl->getOutput()[0][1]);
    $curl->close();
    $spc->getunkownExtension();
}