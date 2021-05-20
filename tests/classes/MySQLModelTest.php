<?php


namespace HTTPTestingUtilities\tests;

use common\logging\Logger as Logger;
use HTTPTestingUtilities\lib\CurlHTTPWebSpider\db\MySQL\CurlHTTPWebSpiderModel;

class MySQLModelTest {
    
    /**
     * Tests that responseBody content that is validated matches the responseBody 
     * downloaded with CURL
     * @return bool
     */
    public function testResponseBodyFilter() : bool {
        $responseBodyFilter = false;
        
        $webSpiderModel = new CurlHTTPWebSpiderModel();
        
        $responseBody = <<<EOT
<html>
    <body>
        <div>HTML content</div>
    </body>
</html>   
EOT;
        try {
            $webSpiderModel->response_body = $responseBody;
            $responseBodyFilter = $webSpiderModel->response_body === $responseBody;
        } catch(\UnexpectedValueException $e) {
            Logger::obj()->writeException($e);
        }
        
        return $responseBodyFilter;
    }
}

