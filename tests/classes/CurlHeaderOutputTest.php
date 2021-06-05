<?php


namespace HTTPTestingUtilities\tests;

use common\logging\Logger as Logger;
use HTTPTestingUtilities\lib\CurlHeaderOutput\Main;
use HTTPTestingUtilities\lib\CurlHeaderOutput\validate\UrlOpt;

class CurlHeaderOutputTest {
    private $cho;
    private $testUrl = 'http://www.openbsd.org';
    
    public function setUp() : void {
        $this->cho = new Main();
        
        $this->cho->run($this->testUrl);
    }
    
    public function testUrlOpt() : bool {
        $uo = new UrlOpt();
        try {
            $uo->url = $this->testUrl;
            $result = true;
        } catch (\OutOfBoundsException | \UnexpectedValueException | \RuntimeException $e) {
            $result = false;
            Logger::obj()->writeException($e);
        }
        
        return $result;
    }
    
        public function testFailUrlOpt() : bool {
        $uo = new UrlOpt();
        try {
            $uo->url = 'http://';
            $result = false;
        } catch (\OutOfBoundsException | \UnexpectedValueException | \RuntimeException $e) {
            $result = true;
            Logger::obj()->writeException($e);
        }
        
        return $result;
    }
    
    public function testHasHeaderFile() : bool {
        return count($this->cho->header_file) > 0;
    }
    
    public function testHasCookieFile() : bool {
        return count($this->cho->cookie_file) > 0;
    }
    
    public function testHasInitialUrl() : bool {
        return $this->cho->initial_url === $this->testUrl;
    }
    
    public function testPropertyAccessible() : bool {
        
        try {
            $this->cho->effective_url;
            $this->cho->redirect_count;
            $this->cho->redirect_time;
            
            $result = true;
        } catch (UnexpectedValueException $e) {
            $result = false;
            Logger::obj()->writeException($e);
        }
        
        return $result;
    }
    
    public function testPropertyInaccessible() : bool {
        
        try {
            $this->cho->fake_property;
            
            $result = false;
        } catch (\UnexpectedValueException $e) {
            $result = true;
            Logger::obj()->writeException($e);
        }
        
        return $result;
    }
}

