<?php

namespace HTTPTestingUtilities\lib\MySQLProfiler;


use common\db\Main as db;

class MysqlProfiler {

    private $sqlOpt;
    private $md5Sql1;
    private $md5Sql2;
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
        for ($i = 1; $i <= $this->sqlOpt->profile; $i++) {
            try {
                $db->query($sql);
            } catch (\PDOException $pe) {
                exit(\common\logging\Logger::obj()->writeException($pe, -1, TRUE));
            }
            $result = $db->fetchAll('SHOW PROFILE');

            foreach ($result as $test) {
                $key = strtolower(str_replace(' ', '_', $test['Status']));
                if (!isset($output[$i][$key])) {
                    $output[$i][$key] = 0;
                }
                $output[$i][$key] += $test['Duration'];
            }
        }
        $db->getSth('SET profiling = 0');
        
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
            $fileName = __DIR__.'/'.$this->sqlOpt->file.'_sql'.$index.'.csv';
            if (!\file_exists($fileName)) {
                $f = new SplFileObject($fileName, 'w');
                $f->fputcsv(array_keys($this->output[$md5][1]));
                
                foreach ($this->output[$md5] as $csvRow) {
                    $f->fputcsv($csvRow);
                }
                
                echo "Wrote ".$fileName." for SQL ".$index."\n";
            } else {
                echo "Filename ".$fileName." already exists";
            }
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
