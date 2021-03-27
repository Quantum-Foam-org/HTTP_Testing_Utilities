<?php

namespace HTTPTestingUtilities\lib\network\validate;

use common\obj as obj;

class NetworkCIDR extends obj\Config {

    protected $ip;
    protected $cidr;
    protected $config = [
        'cidr' => [
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 32
                ]
            ]
        ],
        'ip' => [
            FILTER_VALIDATE_IP,
            [
                'flags' => [
                    FILTER_FLAG_IPV4
                ]
            ]
        ]
    ];

}
