<?php

namespace HTTPTestingUtilities;

$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require($common_autoload_file);


$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require($php_cli_autoload_file);

require ('./lib/autoload.php');

use common\curl;
use HTTPTestingUtilities\lib\CurlHeaderOutput;
use cli\classes as cli;
use common\logging\Logger;

\common\Config::obj(__DIR__ . '/config/config.ini');

$return = 1;

if ($argc > 1) {
    $uo = new CurlHeaderOutput\validate\UrlOpt();
    try {
        $uo->exchangeArray(array_slice($argv, 1));
    } catch (\UnexpectedValueException $e) {
        exit(Logger::obj()->writeException($e, -1, TRUE));
    }
    $uo->exchangeArray(array_slice($argv, 1));
    if ($uo->url !== FALSE) {
        if(CurlHeaderOutput\Main::run($uo->url) === true) {
            try {
            sprintf("Initial URL: %s\n", $curl->initial_url);
            sprintf("Effective URL: %s\n", $curl->effective_url);
            sprintf("Redirect Count: %s\n", $curl->redirect_count);
            sprintf("Redirect Time: %s\n\n", $curl->redirect_time);
            sprintf("Locations:\n\t%s\n\n", implode("\n\t", $curl->locations));
            sprintf("HTTP Cookie Data:\n\t%s\n", implode("\n\t", array_map('trim', file($curl->cookieFile))));
            } catch(\UnexpectedValueException $ue) {
                exit(Logger::obj()->writeException($ue, -1, true));
            }
        } else {
            echo "Unable to curl the web resource\n";
        }
        
        $return = 0;
    }
}

if ($return !== 0) {
    echo "Please supply a valid URL via the --url option\n";
}



exit($return);
