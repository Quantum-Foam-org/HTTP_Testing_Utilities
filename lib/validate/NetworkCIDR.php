<?php

namespace lib\validate;

use \common\obj as obj;

class NetworkCIDR extends obj\Config {
	protected $ip;
        protected $cidr;

	protected $config = array(
			'cidr' => array(FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 32)),
                        'ip' => array(FILTER_VALIDATE_IP, array(FILTER_FLAG_IPV4))
	);
}
