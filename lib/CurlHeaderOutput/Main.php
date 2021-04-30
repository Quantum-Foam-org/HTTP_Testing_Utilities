<?php

namespace HTTPTestingUtilities\lib\CurlHeaderOutput;

use common\curl;

class Main {
    
    private $curlInfo;

    public static function run(string $url) : bool {
        
        $ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';
        $fileHeader = fopen(__DIR__ . '/http_headers.txt', 'w+');
        $cookieFile = __DIR__ . '/http_cookies.txt';
        touch($cookieFile);

        $curl = new curl\Main(FALSE);

        $curl->create();

        $curl->addOption(CURLOPT_URL, $url);
        $curl->addOption(CURLOPT_RETURNTRANSFER, TRUE);
        $curl->addOption(CURLOPT_FOLLOWLOCATION, TRUE);
        $curl->addOption(CURLOPT_WRITEHEADER, $fileHeader);
        $curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
        $curl->addOption(CURLOPT_USERAGENT, $ua);

        $curlResult = $curl->run();
        $response = $curl->getOutput();

        $info = $curl->info();
        $info = array_filter($info[0], function($v) {
            if (in_array($v[0], array(CURLINFO_EFFECTIVE_URL, CURLINFO_REDIRECT_COUNT, CURLINFO_REDIRECT_TIME), TRUE)) {
                $result = TRUE;
            } else {
                $result = FALSE;
            } return $result;
        });
        $curl->close();

        rewind($fileHeader);
        $locations = [];
        while (($row = fgets($fileHeader)) !== FALSE) {
            if (stripos(trim($row), 'location:') === 0) {
                $locations[] = trim(str_ireplace('location:', '', $row));
            }
        }
        unset($row);

        $this->curlInfo['initial_url'] = $url;
        foreach ($info as $i) {
            switch ($i[0]) {
                case CURLINFO_EFFECTIVE_URL:
                    $this->curlInfo['effective_url'] = $i[1];
                    break;
                case CURLINFO_REDIRECT_COUNT:
                    $this->curlInfo['redirect_count'] = $i[1];
                    break;
                case CURLINFO_REDIRECT_TIME;
                    $this->curlInfo['redirect_time'] = $i[1];
                    break;
            }
        }
        
        
        fclose($fileHeader);
        
        $this->curlInfo['locations'] = $locations;
        $this->curlInfo['cookie_file'] = $cookieFile;
        $this->curlInfo['header_file'] = $fileHeader;
        
        return (bool)$curlResult;
    }
    
    public function __get($name) {
        if (!isset($this->curlInfo[$name])) {
            throw new \UnexpectedValueException(sprintf('Property %s not found', $name));
        }
        
        return $this->curlInfo[$name];
    }

}
