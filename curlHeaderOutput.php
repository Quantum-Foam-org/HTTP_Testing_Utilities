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
        $cho = new CurlHeaderOutput\Main();
        if($cho->run($uo->url) === true) {
            try {
            printf("Initial URL: %s\n", $cho->initial_url);
            printf("Effective URL: %s\n", $cho->effective_url);
            printf("Redirect Count: %s\n", $cho->redirect_count);
            printf("Redirect Time: %s\n\n", $cho->redirect_time);
            printf("Locations:\n\t%s\n\n", implode("\n\t", $cho->locations));
            printf("HTTP Cookie Data:\n\t%s\n", implode("\n\t", array_map('trim', file($cho->cookie_file))));
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
