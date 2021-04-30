<?php

namespace HTTPTestingUtilities\tests;

use common\logging\Logger as Logger;
use HTTPTestingUtilities\lib\network\validate;

class NetworkCIDRTest {
    
    public function testNetworkCIDRValidateBadIp() : bool {
        $ipTest = false;
        
        $networkCIDR = new validate\NetworkCIDR();
        
        try {
            $networkCIDR->ip = '101010101';
        } catch(\UnexpectedValueException $e) {
            \common\logging\Logger::obj()->writeException($e);
            
            $ipTest = true;
        }
        
        return $ipTest;
    }
    
    public function testNetworkCIDRValidateGoodIp() : bool {
        $ipTest = true;
        
        $networkCIDR = new validate\NetworkCIDR();
        
        try {
            $networkCIDR->ip = '10.10.1.1';
        } catch(\UnexpectedValueException $e) {
            \common\logging\Logger::obj()->writeException($e);
            
            $ipTest = false;
        }
        
        return $ipTest;
    }
    
    public function testNetworkCIDRValidateBadCIDR() : bool {
        $CIDRTest = false;
        
        $networkCIDR = new validate\NetworkCIDR();
        
        try {
            $networkCIDR->cidr = '33';
        } catch(\UnexpectedValueException $e) {
            \common\logging\Logger::obj()->writeException($e);
            
            $CIDRTest = true;
        }
        
        return $CIDRTest;
    }
    
    public function testNetworkCIDRValidateGoodCIDR() : bool {
        $CIDRTest = true;
        
        $networkCIDR = new validate\NetworkCIDR();
        
        try {
            $networkCIDR->cidr = '2';
        } catch(\UnexpectedValueException $e) {
            \common\logging\Logger::obj()->writeException($e);
            
            $CIDRTest = false;
        }
        
        return $CIDRTest;
    }
}

