<?php

$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require($common_autoload_file);


$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require($php_cli_autoload_file);

use \common\curl\Main as curl;
use \cli\classes as cli;
use \common\db\Main as db;

\common\Config::obj(__DIR__ . '/config/config.ini');

class UrlOpt extends cli\Flag {

    protected $startUrl;
    protected $config = array(
        'startUrl' => array(FILTER_VALIDATE_URL)
    );

}

$uo = new \UrlOpt();
$uo->exchangeArray(array_slice($argv, 1));

if ($uo->startUrl !== null) {

    define('DB_DSN', 'mysql:host=localhost;dbname=mysql_profile');
    define('DB_USER_NAME', 'mysql_profile');
    define('DB_USER_PASS', '123jasfuaf835');


    $ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';

    $curl = new curl(FALSE);

    $cookieFile = __DIR__ . '/http_cookies.txt';
    touch($cookieFile);

    $curl->create();

    $curl->addOption(CURLOPT_URL, $uo->startUrl);
    $curl->addOption(CURLOPT_RETURNTRANSFER, TRUE);
    $curl->addOption(CURLOPT_FOLLOWLOCATION, TRUE);
    $curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
    $curl->addOption(CURLOPT_USERAGENT, $ua);

    $curl->run();

    class SpiderHTTPUrl {

        private static $getContentLimit = 0;
        private static $previousUrls = [];
        
        function getContent(\common\curl\Main $curl) {
            global $uo;
            
            if (self::$getContentLimit >= 50000) {
                return FALSE;
            } else {
                self::$getContentLimit++;

                $d = new DomDocument();
                @$d->loadHTML($curl->getOutput()[0][1]);
                $dx = new DOMXPath($d);
                foreach ($d->getElementsByTagName('a') as $url) {
                    $href = $url->getAttribute('href');
                    if (isset($href) && strlen($href)) {
                        if (!filter_var($href, FILTER_VALIDATE_URL)) {
                            $href = $uo->startUrl . $href;
                        }
                        if (in_array($href, self::$previousUrls) || pathinfo($href, PATHINFO_EXTENSION) === 'iso')
                        {
                            continue;
                        }
                        $curl->addOption(CURLOPT_URL, $href);
                        $curl->run();
                        self::$previousUrls[] = $href;
                        
                        $db = db::obj();
                        
                        $info = array_filter($curl->info()[0], function($v) {
                            if (in_array($v[0], array(
                                        CURLINFO_EFFECTIVE_URL,
                                        CURLINFO_HTTP_CODE,
                                        CURLINFO_TOTAL_TIME,
                                        CURLINFO_REDIRECT_COUNT,
                                        CURLINFO_REQUEST_SIZE,
                                        CURLINFO_CONTENT_TYPE), TRUE)) {
                                $result = TRUE;
                            } else {
                                $result = FALSE;
                            }
                            return $result;
                        });
                        $db->insert('test_data', array(
                          'url' => $info[CURLINFO_EFFECTIVE_URL][1],
                          'http_status_code' => $info[CURLINFO_HTTP_CODE][1],
                          'response_time' => $info[CURLINFO_TOTAL_TIME][1],
                          'redirect_count' => $info[CURLINFO_REDIRECT_COUNT][1],
                          'response_length' => $info[CURLINFO_REQUEST_SIZE][1],
                          'content_type' => $info[CURLINFO_CONTENT_TYPE][1],
                          ));
                    }
                    if (self::getContent($curl) === FALSE)
                        break;
                }

                return TRUE;
            }
        }

    }

    $spc = new \SpiderHTTPUrl();

    $spc->getContent($curl);

    $curl->close();
}
