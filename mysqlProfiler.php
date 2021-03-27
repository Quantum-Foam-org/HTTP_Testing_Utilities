<?php

namespace HTTPTestingUtilities;

define('PROFILE_LIMIT', 100);

$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir . '/autoload.php';
require($common_autoload_file);

$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir . '/autoload.php';
require($php_cli_autoload_file);

use HTTPTestingUtilities\lib\MySQLProfiler;

\common\Config::obj(__DIR__ . '/config/config.ini');

$opt = new MySQLProfiler\validate\SqlOpt();

$opt->exchangeArray(array_slice($argv, 1));

if (!$opt->sql1) {
    echo "Set --sql1 to continue\n";
    exit(-1);
}
if (!$opt->sql2) {
    echo "Set --sql2 to continue\n";
    exit(-2);
}

if (!$opt->profile) {
    echo "Set --profile limit to not use the default of ". PROFILE_LIMIT ."\n";
}

if ($opt->profile <= 0) {
    $opt->profile = PROFILE_LIMIT;
    echo "Using the default profile limit of " . PROFILE_LIMIT . "\n";
}

if ($opt->file === -1 || $opt->file === FALSE)
{
    echo "You can set a file by using --file that will store the contents of the query into a file\n";
}


$mp = new MySQLProfiler\Main($opt);
$mp->run();
if (is_string($opt->file))
{
    $mp->write();
}