<?php
namespace HTTPTestingUtilities\lib\MySQLProfiler\validate;

use cli\classes as cli;
 
class SqlOpt extends cli\Flag {

    protected $sql1;
    protected $sql2;
    protected $file = -1;
    protected $profile;
    protected $config = array(
        'sql1' => array(FILTER_SANITIZE_STRING, array('flags' => FILTER_FLAG_NO_ENCODE_QUOTES)),
        'sql2' => array(FILTER_SANITIZE_STRING,  array('flags' => FILTER_FLAG_NO_ENCODE_QUOTES)),
        'file' => array(FILTER_SANITIZE_STRING),
        'profile' => array(FILTER_VALIDATE_INT)
    );
}

