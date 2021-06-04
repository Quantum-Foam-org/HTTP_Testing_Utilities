<?php

namespace HTTPTestingUtilities\lib\curlHTTPWebSpider;

use common\Config;
use common\curl\Main as curl;
use common\url\Main as url;
use common\db\PDO\Main as PDO;
#use \common\db\Mongo as Mongo;
use common\logging\Logger;
use HTTPTestingUtilities\lib\CurlHTTPWebSpider\db\MySQL as local_MySQL;
use common\db\DbModelInterface;
use common\collections\db as DataStorage;

class Main {
    private $getContentLimit = 0;
    private $contentLimit = 0;
    private $previousUrls = [];
    private $whiteListExtension = [];
    private $binaryContentTypes = [];
    private $unkownExtension = [];

    public function __construct() {
        $this->contentLimit = Config::obj()->system['contentLength'];

        if (!empty(Config::obj()->system['whiteListExtension'])) {
            $this->whiteListExtension = Config::obj()->system['whiteListExtension'];
        }
        if (!empty(Config::obj()->system['binaryContentTypes'])) {
            $this->binaryContentTypes = Config::obj()->system['binaryContentTypes'];
        }
    }

    public function runCurl(url $url): curl {
        $ua = 'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0';

        $curl = new curl(false);

        $cookieFile = __DIR__ . '/http_cookies.txt';
        touch($cookieFile);

        $curl->create();
        $curl->addOption(CURLOPT_URL, $url);
        $curl->addOption(CURLOPT_RETURNTRANSFER, true);
        $curl->addOption(CURLOPT_FOLLOWLOCATION, true);
        $curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
        $curl->addOption(CURLOPT_USERAGENT, $ua);

        $curl->run();

        Logger::obj()->write('Spidered ' . $url);

        return $curl;
    }

    public function getContent(string $content = null): bool {
        global $uo;

        if ($this->getContentLimit >= $this->contentLimit) {
            return FALSE;
        } else {
            $this->getContentLimit++;

            $d = new \DomDocument();
            @$d->loadHTML($content);
            $dx = new \DOMXPath($d);
            
            $dbStorage = $this->getDbStorage();
            
            foreach ($d->getElementsByTagName('a') as $i => $url) {
                $href = $url->getAttribute('href');

                if (isset($href) && strlen($href)) {
                    try {
                        $hrefUrl = new url($href);
                    } catch (\UnexpectedValueException $ue) {
                        Logger::obj()->writeDebug($ue->getMessage());
                        $hrefUrl = FALSE;
                    }
                    if ($hrefUrl === FALSE) {
                        try {
                            $hrefUrl = new url($uo->startUrl . '/' . $href);
                        } catch (\UnexpectedValueException $ue) {
                            Logger::obj()->writeDebug($ue->getMessage());
                            continue;
                        }
                    } else {
                        if ($hrefUrl->host !== $uo->startUrl->host) {
                            continue;
                        }
                    }
                    if (in_array((string) $hrefUrl, $this->previousUrls)) {
                        continue;
                    }

                    $this->previousUrls[] = (string) $hrefUrl;

                    $path = pathinfo($hrefUrl, PATHINFO_EXTENSION);
                    if (empty($this->whiteListExtension) || 
                            (
                            strlen($path) > 0 && 
                            array_search(
                                    $path, 
                                    $this->whiteListExtension) === false
                            )
                    ) {
                        $this->unkownExtension[] = $path;
                        continue;
                    }

                    $curl = $this->runCurl($hrefUrl);
                    
                    $this->setSpideredInformation($curl, $dbStorage);
                    
                    $curl->close();
                }
            }
            
            $dbStorage->insert();
            
            return TRUE;
        }
    }
    
    private function setSpideredInformation(
            curl $curl, 
            DataStorage\AbstractDbModelStorage $dbStorage
    ) : void {
        $dbModel = $this->getDbModel();
                    
        $info = $this->getCurlInfo($curl);
        
        try {
            $dbModel->url = $info[CURLINFO_EFFECTIVE_URL][1];
            $dbModel->http_status_code = $info[CURLINFO_HTTP_CODE][1];
            $dbModel->response_time = $info[CURLINFO_TOTAL_TIME][1];
            $dbModel->redirect_count = $info[CURLINFO_REDIRECT_COUNT][1];
            $dbModel->response_length = $info[CURLINFO_REQUEST_SIZE][1];
            $dbModel->content_type = $info[CURLINFO_CONTENT_TYPE][1];
            if (!empty($this->binaryContentTypes) && 
                    in_array($dbModel->content_type, $this->binaryContentTypes)) {
                $dbModel->binary_response_body = $curl->getOutput()[0][1];
            } else {
                $dbModel->response_body = $curl->getOutput()[0][1];
            }
        } catch (\UnexpectedValueException | \OutOfBoundsException $e) {
            exit(Logger::obj()->writeException($e));
        }
        
        $dbStorage[] = $dbModel;
    }

    private function getCurlInfo(curl $curl): array {
        return array_filter($curl->info()[0], function ($v) {
                        if (in_array($v[0], array(
                                    CURLINFO_EFFECTIVE_URL,
                                    CURLINFO_HTTP_CODE,
                                    CURLINFO_TOTAL_TIME,
                                    CURLINFO_REDIRECT_COUNT,
                                    CURLINFO_REQUEST_SIZE,
                                    CURLINFO_CONTENT_TYPE
                                        ), TRUE)) {
                            $result = TRUE;
                        } else {
                            $result = FALSE;
                        }
                        return $result;
                    });
    }
    
    private function getDbModel() : DbModelInterface {
        global $uo;
        
        if ($uo->db === 'mysql') {
            try {
                $db = PDO::obj();
            } catch (\PDOException $pe) {
                exit(Logger::obj()->writeException($pe, -1, TRUE));
            }

            $dbModel = new local_MySQL\CurlHTTPWebSpiderModel();
        } else if ($uo->db === 'mongo') {
            try {
                $db = Mongo::obj();
            } catch (\MongoDB\Driver\Exception\InvalidArgumentException | \MongoDB\Driver\Exception\RuntimeException $pe) {
                exit(Logger::obj()->writeException($pe, -1, TRUE));
            }

            $dbModel = new Mongo\CurlHTTPWebSpiderModel();
        }

        return $dbModel;
    }
    
    private function getDbStorage() {
        global $uo;

        if ($uo->db === 'mysql') {
            $dbStorage = new DataStorage\MySQL\DbModelStorage();
        } else if ($uo->db === 'mongo') {
            $dbStorage = new DataStorage\Mongo\DbModelStorage();
        }

        return $dbStorage;
    }
    
    public function getunkownExtension() {
        if (!empty($this->unkownExtension)) {
            Logger::obj()->write('Begin Unkown Extensions');

            foreach ($this->unkownExtension as $ue) {
                Logger::obj()->write($ue);
            }

            Logger::obj()->write('End Unkown Extensions');
        }
    }
}