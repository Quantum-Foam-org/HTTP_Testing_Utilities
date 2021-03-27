<?php

namespace HTTPTestingUtilities;

$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require ($common_autoload_file);

$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require ($php_cli_autoload_file);

require ('./lib/autoload.php');

use common\Config;
use HTTPTestingUtilities\lib\WWWScraper;

Config::obj(__DIR__ . '/config/config.ini');

try {
    $uo = new WebSpider\validate\UrlOpt();
    $uo->exchangeArray(array_slice($_SERVER['argv'], 1));
} catch (\UnexpectedValueException | \ArgumentCountError $e) {
    exit(\common\logging\Logger::obj()->writeException($e, -1, TRUE));
}

if ($uo->startUrl !== null) {
    $wsm = new WWWScraper\Main();
    $curl = $wsm->runCurl($uo->startUrl);
    $wsm->getContent($curl->getOutput()[0][1]);
    $curl->close();
    $wsm->getunkownExtension();
} else {
    echo "Please supply a valid URL via the --startUrl option";
}
