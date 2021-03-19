<?php
$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require ($common_autoload_file);

$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require ($php_cli_autoload_file);

require ('./lib/autoload.php');

use \common\curl\Main as curl;
use \cli\classes as cli;
use \common\db\PDO\Main as PDO;
use \lib\db\MySQL as local_MySQL;
#use \common\db\Mongo as Mongo;
use \common\url\Main as url;

\common\Config::obj(__DIR__ . '/config/config.ini');

class UrlOpt extends cli\Flag
{
    protected $startUrl;
    
    protected $db;

    protected $config =[
        'startUrl' => 
        [
            FILTER_VALIDATE_URL
        ],
        'db'=> 
        [
            FILTER_VALIDATE_REGEXP, 
            [
                'options' => ['regexp' => '/^mysql$/']
            ]
        ]
    ];
    
    public function offsetSet($offset, $value) {
        parent::offsetSet($offset, $value);
        if ($offset === 'startUrl') {
            try {
                $this->$offset = new url($this->$offset);
            } catch(\UnexpectedValueException $ue) {
                exit(\common\logging\Logger::obj()->writeException($ue, -1, TRUE));
            }
        }
    }
}

try {
    $uo = new \UrlOpt();
    $uo->exchangeArray(array_slice($_SERVER['argv'], 1));
} catch (\UnexpectedValueException | \ArgumentCountError $e) {
    exit(\common\logging\Logger::obj()->writeException($e, -1, TRUE));
}

if ($uo->startUrl !== null) {
    

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

        public function runCurl(url $url) : curl
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

        public function getContent(string $content = null) : bool
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
                        try {
                            $hrefUrl = new url($href);
                        } catch (\UnexpectedValueException $ue) {
                            \common\logging\Logger::obj()->writeDebug($ue->getMessage());
                            $hrefUrl = FALSE;
                        }
                        if ($hrefUrl === FALSE) {
                            try {
                                $hrefUrl = new url($uo->startUrl .'/' . $href);
                            } catch (\UnexpectedValueException $ue) {
                                \common\logging\Logger::obj()->writeDebug($ue->getMessage());
                                continue;
                            }
                        } else {
                            if ($hrefUrl->host !== $uo->startUrl->host) {
                                continue;
                            }
                        }
                        if (in_array((string)$hrefUrl, $this->previousUrls)) {
                            continue;
                        }
                        
                        $this->previousUrls[] = (string)$hrefUrl;
                        
                        $path = pathinfo($hrefUrl, PATHINFO_EXTENSION);
                        if (strlen($path) > 0 && !($wle = array_search($path, $this->whiteListExtension))) {
                                $this->unkownExtension[] = $path;
                                continue;
                        }
                        
                        $curl = $this->runCurl($hrefUrl);
                        
                        
                        
                        
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
                        if ($uo->db === 'mysql') {
                            try {
                                $db = PDO::obj();
                            } catch (\PDOException $pe) {
                                exit(\common\logging\Logger::obj()->writeException($pe, -1, TRUE));
                            }
                            
                            $dbModel = new local_MySQL\SpiderModel();
                        } else if ($uo->db === 'mongo') {
                            try {
                                $db = Mongo::obj();
                            } catch (\MongoDB\Driver\Exception\InvalidArgumentException  | \MongoDB\Driver\Exception\RuntimeException $pe) {
                                exit(\common\logging\Logger::obj()->writeException($pe, -1, TRUE));
                            }
                            $dbModel = new Mongo\MongoModel();
                        }
                        
                        try {
                            $dbModel->url = $info[CURLINFO_EFFECTIVE_URL][1];
                            $dbModel->http_status_code = $info[CURLINFO_HTTP_CODE][1];
                            $dbModel->response_time = $info[CURLINFO_TOTAL_TIME][1];
                            $dbModel->redirect_count = $info[CURLINFO_REDIRECT_COUNT][1];
                            $dbModel->response_length = $info[CURLINFO_REQUEST_SIZE][1];
                            $dbModel->content_type = $info[CURLINFO_CONTENT_TYPE][1];
                        } catch (\UnexpectedValueException $e) {
                            exit(\common\logging\Logger::obj()->writeException($e));
                        }

                        $result = $dbModel->insert();

                        if ($uo->db === 'mysql' && !is_int($result)) {
                            exit(\common\logging\Logger::obj()->write(
                                    'Could not insert spider information into MySQL database', -1));
                        } else if ($uo->db === 'mongo' && 
                                $result instanceOf WriteResult &&
                                (
                                        $result->getInsertedCount() !== 1 || 
                                        count($result->getWriteErrors()) > 0
                                )
                        ) {
                            exit(\common\logging\Logger::obj()->write(
                                    'Could not insert spider information into Mongo database', -1));
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
} else {
    echo "Please supply a valid URL via the --startUrl option";
}
