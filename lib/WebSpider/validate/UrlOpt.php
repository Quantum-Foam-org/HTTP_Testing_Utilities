<?php

namespace HTTPTestingUtilities\lib\WebSpider;

use cli\classes as cli;
use common\url\Main as url;

class UrlOpt extends cli\Flag
{
    protected $startUrl;
    
    protected $db;

    protected $config =[
        'startUrl' => 
        [
            FILTER_VALIDATE_URL
        ],
        'db'=> 
        [
            FILTER_VALIDATE_REGEXP, 
            [
                'options' => ['regexp' => '/^mysql$/']
            ]
        ]
    ];
    
    public function offsetSet($offset, $value) {
        parent::offsetSet($offset, $value);
        if ($offset === 'startUrl') {
            try {
                $this->$offset = new url($this->$offset);
            } catch(\UnexpectedValueException $ue) {
                exit(\common\logging\Logger::obj()->writeException($ue, -1, TRUE));
            }
        }
    }
}