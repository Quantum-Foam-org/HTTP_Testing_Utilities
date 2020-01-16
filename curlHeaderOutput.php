<?php



$common_php_dir = '../php_common';
$common_autoload_file = $common_php_dir.'/autoload.php';
require($common_autoload_file);


$php_cli_dir = '../php_cli';
$php_cli_autoload_file = $php_cli_dir.'/autoload.php';
require($php_cli_autoload_file);




use \common\curl\Main as curl;
use \cli\classes as cli;



\common\Config::obj(__DIR__.'/config/config.ini');



class UrlOpt extends cli\Flag {
	protected $url;

	protected $config = array(
			'url' => array(FILTER_VALIDATE_URL)
	);
}


$ua = 'Mozilla/5.0 (Android; Mobile; rv:30.0) Gecko/30.0 Firefox/30.0';
$fileHeader = fopen(__DIR__.'/http_headers.txt', 'w+');
$cookieFile = __DIR__.'/http_cookies.txt';
touch($cookieFile);

$return = 1;

if ($argc > 1) {
	$uo = new \UrlOpt();
        try {
            $uo->exchangeArray(array_slice($argv, 1));
        } catch(\UnexpectedValueException $e) {
            exit(\common\logging\Logger::obj()->writeException($e, -1, TRUE));
        }
	$uo->exchangeArray(array_slice($argv, 1));
	if ($uo->url !== FALSE) {
		
			$curl = new curl(FALSE);
			
			$curl->create();
			
			$curl->addOption(CURLOPT_URL, $uo->url);
			$curl->addOption(CURLOPT_RETURNTRANSFER, TRUE);
			$curl->addOption(CURLOPT_FOLLOWLOCATION, TRUE);
			$curl->addOption(CURLOPT_WRITEHEADER, $fileHeader);
			$curl->addOption(CURLOPT_COOKIEJAR, $cookieFile);
			$curl->addOption(CURLOPT_USERAGENT, $ua);
			
			$curl->run();
			$response = $curl->getOutput();
			
			$info = $curl->info();
			$info = array_filter($info[0], function($v) {
				if (in_array($v[0], array(CURLINFO_EFFECTIVE_URL, CURLINFO_REDIRECT_COUNT, CURLINFO_REDIRECT_TIME), TRUE)) { 
					$result =  TRUE; } else { $result = FALSE; } return $result;
			});
			$curl->close();
			
			rewind($fileHeader);
			$locations = [];
			while(($row = fgets($fileHeader)) !== FALSE) {
				if (stripos(trim($row), 'location:') === 0) {
					$locations[] = trim(str_ireplace('location:', '', $row));
				}
			}
			unset($row);
			
			echo "Initial URL: {$uo->url}\n";
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
	}
}

if ($return !== 0) {
	echo "Please supply a valid URL via the --url option";
}

fclose($fileHeader);
unset($fileHeader, $cookieFile);


exit($return);
