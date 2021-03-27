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

\common\Config::obj(__DIR__ . '/config/config.ini');

$return = 1;

if ($argc > 1) {
    $uo = new CurlHeaderOutput\validate\UrlOpt();
    try {
        $uo->exchangeArray(array_slice($argv, 1));
    } catch (\UnexpectedValueException $e) {
        exit(\common\logging\Logger::obj()->writeException($e, -1, TRUE));
    }
    $uo->exchangeArray(array_slice($argv, 1));
    if ($uo->url !== FALSE) {
        [$curlHeaderInfo, $locations, $cookieFile] = CurlHeaderOutput\Main::run($uo->url);
        
        echo implode("\n", $curlHeaderInfo);
        echo "\n";
        echo "Locations:\n\t" . implode("\n\t", $locations) . "\n\n";
        echo "HTTP Cookie Data:\n\t" . implode("\n\t", array_map('trim', file($cookieFile))) . "\n";

        $return = 0;
    }
}

if ($return !== 0) {
    echo "Please supply a valid URL via the --url option";
}



exit($return);
