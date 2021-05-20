<?php 

namespace HTTPTestingUtilities\lib\CurlHTTPWebSpider\filters;

class SanResponseBody {
	public static function validate($responseBody) {
		return str_replace(chr(0), '', $responseBody);
	}
}

?>