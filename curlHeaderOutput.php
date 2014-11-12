<?php

$ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';
$fileHeader = fopen(__DIR__.'/http_headers.txt', 'w+');
$cookieFile = __DIR__.'/http_cookies.txt';

if (isset($argv[1]) && ($url = filter_var($argv[1], FILTER_VALIDATE_URL))) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_WRITEHEADER, $fileHeader);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
	curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	
	$response = curl_exec($ch);
	
	$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	$redirectCount = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
	$redirectTime = curl_getinfo($ch, CURLINFO_REDIRECT_TIME);
	
	rewind($fileHeader);
	$locations = [];
	while(($row = fgets($fileHeader)) !== FALSE) {
		if (stripos($row, 'location:') === 0) {
			$locations[] = str_ireplace('location:', '', trim($row));
		}
	}
	curl_close($ch);
	unset($ch, $row, $location);
	
	echo "Initial URL: {$url}\n";
	echo "Effective URL: {$effectiveUrl}\n";
	echo "Redirect Count: {$redirectCount}\n";
	echo "Redirect Time: {$redirectTime}\n";
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
