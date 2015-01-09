<?php

$common_php_dir = '../php_common';
$autoload_file = $common_php_dir.'/autoload.php';

require($autoload_file);

use \common\curl\Main as curl;


$ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';
$fileHeader = fopen(__DIR__.'/http_headers.txt', 'w+');
$cookieFile = __DIR__.'/http_cookies.txt';

if (isset($argv[1]) && ($url = filter_var($argv[1], FILTER_VALIDATE_URL))) {
	$curl = new curl(FALSE);
	
	$curl->create();
	
	$curl->addOption(CURLOPT_URL, $url);
	$curl->addOption(CURLOPT_RETURNTRANSFER, TRUE);
	$curl->addOption(CURLOPT_FOLLOWLOCATION, TRUE);
	$curl->addOption(CURLOPT_WRITEHEADER, $fileHeader);
	$curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
	$curl->addOption(CURLOPT_USERAGENT, $ua);
	
	$curl->run();
	$response = $curl->getOutput();
	
	$info = array_filter($curl->info(), function($v) { 
		if (in_array($v[0], array(CURLINFO_EFFECTIVE_URL, CURLINFO_REDIRECT_COUNT, CURLINFO_REDIRECT_TIME), TRUE)) { 
			$result =  TRUE; } else { $result = FALSE; } return $result;
	});
	
	rewind($fileHeader);
	$locations = [];
	while(($row = fgets($fileHeader)) !== FALSE) {
		if (stripos($row, 'location:') !== FALSE) {
			$locations[] = trim(str_ireplace('location:', '', $row));
		}
	}
	unset($row);
	
	echo "Initial URL: {$url}\n";
	foreach ($info as $i) {
		switch ($i[0]) {
			case CURLINFO_EFFECTIVE_URL:
				$text = 'Effective URL:';
				break;
			case CURLINFO_REDIRECT_COUNT:
				$text = 'Redirect Count:';
				break;
			case CURLINFO_REDIRECT_TIME;
				$text = 'Redirect Time:';
				break;
		}
		echo $text.' '.$i[1]."\n";
	}
	echo "Locations:\n\t".implode("\n\t", $locations)."\n";
	echo "HTTP Cookie Data:\n\t".implode("\n\t", array_map('trim', file($cookieFile)))."\n";
	
	unset($locations, $effectiveUrl, $rediriectCount, $redirectTime);
 	
	$return = 0;
} else {
	echo "Please supply a valid URL";
	$return = 1;
}


fclose($fileHeader);

unset($fileHeader, $cookieFile);

exit($return);
