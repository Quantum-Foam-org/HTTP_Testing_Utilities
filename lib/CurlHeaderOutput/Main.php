<?php

namespace HTTPTestingUtilities\lib\CurlHeaderOutput;

use common\curl;

class Main {

    public static function run(string $url) : array {
        
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

        $curl->run();
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

        $curlInfo[] = sprintf("Initial URL: %s\n", $url);
        foreach ($info as $i) {
            switch ($i[0]) {
                case CURLINFO_EFFECTIVE_URL:
                    $text = 'Effective URL';
                    break;
                case CURLINFO_REDIRECT_COUNT:
                    $text = 'Redirect Count';
                    break;
                case CURLINFO_REDIRECT_TIME;
                    $text = 'Redirect Time';
                    break;
            }
            $curlInfo[] = sprintf("%s: %s\n", $text, $i[1]);
        }
        
        
        fclose($fileHeader);
        
        return [$curlInfo, $locations, $cookieFile];
    }

}
