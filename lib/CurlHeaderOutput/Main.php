<?php

namespace HTTPTestingUtilities\lib\CurlHeaderOutput;

use common\curl;

class Main {
    
    private $curlInfo = [
        'locations' => [],
        'cookie_file' => [],
        'header_file' => [],
        'initial_url' => '',
        'effective_url' => '',
        'redirect_count' => '',
        'redirect_time' => '',
        
    ];

    public function run(string $url) : bool {
        
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
        #$response = $curl->getOutput();
        
        $info = array_filter($curl->info()[0], function($v) {
            if (in_array($v[0], array(CURLINFO_EFFECTIVE_URL, CURLINFO_REDIRECT_COUNT, CURLINFO_REDIRECT_TIME), TRUE)) {
                $result = TRUE;
            } else {
                $result = FALSE;
            } return $result;
        });
        $curl->close();

        rewind($fileHeader);
        while (($row = fgets($fileHeader)) !== FALSE) {
            if (stripos(trim($row), 'location:') === 0) {
                $this->curlInfo['locations'][] = trim(str_ireplace('location:', '', $row));
            }
            $this->curlInfo['header_file'][] = $row;
        }
        unset($row);
        
        fclose($fileHeader);

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
        
        $this->curlInfo['cookie_file'] = array_map('trim', file($cookieFile));
        
        return (bool)$curlResult;
    }
    
    public function __get($name) {
        if (!isset($this->curlInfo[$name])) {
            throw new \UnexpectedValueException(sprintf('Property %s not found', $name));
        }
        
        return $this->curlInfo[$name];
    }

}
