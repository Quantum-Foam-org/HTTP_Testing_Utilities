<?php

namespace lib\validate;

use cli\classes as cli;

class NetworkCLIOpt extends cli\Flag {
	protected $cidr;
        protected $ip;
        
	protected $config = array(
			'cidr' => array(FILTER_VALIDATE_INT, 
                            array('options' => array('min_range' => 0, 'max_range' => 32)), 
                            'message' => 'CIDR Values must be between 0 and 32'),
                        'ip' => array(FILTER_VALIDATE_IP, array('flags' => array(FILTER_FLAG_IPV4)))
	);
}