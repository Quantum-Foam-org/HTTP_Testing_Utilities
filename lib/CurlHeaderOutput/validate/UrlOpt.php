<?php

namespace HTTPTestingUtilities\lib\CurlHeaderOutput\validate;

use cli\classes as cli;

class UrlOpt extends cli\Flag {
	protected $url;

	protected $config = array(
			'url' => array(FILTER_VALIDATE_URL)
	);
}