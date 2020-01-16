<?php

namespace lib\network;

use lib\validate\NetworkCIDR as NetworkCIDR;

class CIDR {
    
    const MAX_CIDR = 32;
    private $ip;
    private $cidr;
    private $subnetMaskIp;
    private $networkIp;
    private $hostIp;
    private $numHosts;
    private $firstHostIp;
    private $lastHostIp;
    
    public function __construct(string $ipAddress, int $cidr) {
        $this->networkCIDR = new NetworkCIDR();
        try {
            $this->networkCIDR->ip = $ipAddress;
            $this->networkCIDR->cidr = $cidr;
        } catch(\UnexpectedValueException $e) {
            exit(\common\logging\Logger::obj()->writeException($e));
        }
        
        $this->setIp();
        $this->setSubnetMaskCIDR();
        $this->setNetwork();
        $this->setHost();
        $this->setNumberOfHosts();
        $this->setFirstHost();
        $this->setLastHost();
    }
    
    private function setIp() : void {
        $this->ip['octetString'] = $this->networkCIDR->ip;
        $this->ip['octetArray'] = explode('.', $this->ip['octetString']);
        $this->ip['binArray'] = array_map([$this, 'binArray'], $this->ip['octetArray']);
        $this->ip['binString'] = implode("", $this->ip['binArray']);
    }
    
    private function setNetwork() : void {
        $this->networkIp = $this->getIpProperty($this->subnetMaskIp['binString'] & $this->ip['binString']);
    }
    
    private function setHost() : void {
        $invBinSubnetMask = '';
        
        for($i = 0; $i < strlen($this->subnetMaskIp['binString']); $i++) {
            if ($this->subnetMaskIp['binString'][$i] === '1') {
                $invBinSubnetMask .= '0';
            } else {
                $invBinSubnetMask .= '1';
            }
        }
                
        $this->hostIp = $this->getIpProperty($this->ip['binString'] & $invBinSubnetMask);
    }
    
    private function setNumberOfHosts() : void {
        $this->numHosts = pow(2, (self::MAX_CIDR - $this->cidr));
    }
    
    private function setFirstHost() : void {
        $this->firstHostIp = $this->getIpProperty(str_pad(decbin(bindec($this->networkIp['binString'])+1), self::MAX_CIDR, 0, STR_PAD_LEFT));
    }
    
    private function setLastHost() : void {
        $this->lastHostIp = $this->getIpProperty(str_pad(decbin(bindec($this->networkIp['binString'])+$this->numHosts), self::MAX_CIDR, 0, STR_PAD_LEFT));
    } 
    
    private function setSubnetMaskCIDR() : void {
        $this->cidr = $this->networkCIDR->cidr;
        $this->subnetMaskIp = $this->getIpProperty(str_pad(str_repeat(1, $this->cidr), self::MAX_CIDR, 0, STR_PAD_RIGHT));
    }
    
    private function getIpProperty(string $binString) : array {
        $binArray = str_split($binString, 8);
        $octetArray = array_map('bindec', $binArray);
        $octetString = implode('.', $octetArray);
        
        return ['binString' => $binString, 
            'binArray' => $binArray, 
            'octetArray' => $octetArray, 
            'octetString' => $octetString];
    }

    public function __get($name) {
        if (!property_exists($this, $name)) {
            throw new \UnexpectedValueException(sprintf('Property %s not found', $name));
        }
        
        return $this->$name;
    }
    
    private function binArray(string $num) : string {
        return str_pad(decbin($num), 8, 0, STR_PAD_LEFT);
    }
}