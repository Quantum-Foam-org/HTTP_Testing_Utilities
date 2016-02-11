<?php

define('PROFILE_LIMIT', 100);
define('DB_DSN', 'mysql:host=localhost;dbname=test_profile');
define('DB_USER_NAME', '');
define('DB_USER_PASS', '');

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

class SqlOpt extends cli\Flag {

    protected $sql1;
    protected $sql2;
    protected $file = -1;
    protected $profile;
    protected $config = array(
        'sql1' => array(FILTER_SANATIZE_STRING),
        'sql2' => array(FILTER_SANATIZE_STRING),
        'file' => array(FILTER_SANATIZE_STRING),
        'profile' => array(FILTER_VALIDATE_INT)
    );
}

$opt = new SqlOpt();

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


$mp = new MySqlProfiler($opt);
$mp->run();
if (strlen($opt->file))
{
    $mp->write();
}

class MysqlProfiler {

    private $sqlOpt;
    private $md5Sql1;
    private $output;

    public function __construct(SqlOpt $opt) {
        $this->sqlOpt = $opt;
        
        $this->md5Sql1 = md5($this->sqlOpt->sql1);
        $this->md5Sql2 = md5($this->sqlOpt->sql2);
        
        
        echo "Using Query SQL1 as " . $this->sqlOpt->sql1 . "\n";
        echo "Using Query SQL2 as " . $this->sqlOpt->sql2 . "\n";
        echo "Using queries ran per profile as " . $this->sqlOpt->profile . "\n\n";
    }

    public function run() {

        $this->output = array(
            $this->md5Sql1 => $this->profileSqlGet($this->sqlOpt->sql1),
            $this->md5Sql2 => $this->profileSqlGet($this->sqlOpt->sql2)
        );

        return $this->output;
    }

    private function profileSqlGet($sql) {
        $output = [];

        $db = db::obj();

        $db->getSth('SET profiling = 1');
        for ($i = 1; $i <= QF_PROFILE_LIMIT; $i++) {
            $db->query($sql);
            $result = $db->fetchAll('SHOW PROFILE');

            foreach ($result as $test) {
                $key = strtolower(str_replace(' ', '_', $test['Status']));
                if (!isset($output[$key])) {
                    $output[$key] = 0;
                }
                $output[$key] += $test['Duration'];
            }
        }
        $db->getSth('SET profiling = 0');
        
        $output = array_map(function($v) {
            return $v / QF_PROFILE_LIMIT;
        }, $output);
        $output['total'] = array_sum($output);
        
        return $output;
    }
    
    
    public function write()
    {
        $this->writeToCSV(1);
        $this->writeToCSV(2);
    }
    
    
    private function writeToCSV($index)
    {
        try
        {
            $md5 = $this->{'md5Sql'.$index};
            $fileName = __DIR__.$this->sqlOpt->outFilePrefix.'_sql'.$index.'.csv';
            
            $f = new SplFileObject($fileName, 'w');
            $f->fputcsv($this->output[$md5]+array('md5Sql'.$index));
            
            foreach ($this->output[$md5] as $row)
            {
                $f->fputcsv($row);
            }
            
            echo "Wrote ".$fileName." for SQL ".$index."\n";
        }
        catch (RuntimeException $e)
        {
            echo $e->getMessage();
            exit($e->severity);
        } finally {
            unset($f, $md5, $fileName);
        }
    }
}
