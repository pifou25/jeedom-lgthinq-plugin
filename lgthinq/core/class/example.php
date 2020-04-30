<?php
/*
 * this example use wideq python lib on command line
 */

include 'WideqAPI.class.php';

class config{
	public static function byKey(){return 5025;}
}

$lgApi = new WideqAPI();
// set log level
if(!$lgApi->changeLog('debug')){
	echo 'erreur changement de log';
}

// send GET request for having gateway URL depending language and country
$url = $lgApi->gateway('FR', 'fr-FR');
// display url to loggin
echo json_encode( $url, JSON_PRETTY_PRINT);

echo json_encode( $lgApi->ping(), JSON_PRETTY_PRINT);

// send token url
$tokenUrl = 'https://fr.m.lgaccount.com/login/iabClose?access_token=9d3e9168be83b0e3aef581f9d7d5f47ca91b60b357f9e3ba166d13339a2a5e5e619b9e55a25c6fd17d7938cebc082c6a&refresh_token=22313d72ab473d118b5e3967c6f4640a68bc173da10e97167a18550ee392fe47c3b5472aad464ae91fd76dc227be214b&oauth2_backend_url=https://gb.lgeapi.com/';
$json = $lgApi->token($tokenUrl);

if(isset($json['message'])){
	echo $json['message'];
}
if(isset($json[WideqAPI::TOKEN_KEY])){
	echo WideqAPI::TOKEN_KEY . ' = ' . $json[WideqAPI::TOKEN_KEY];
}

// list of devices
$json = $lgApi->ls();

if(!$json)
	echo 'aucun device détecté!';
else{
	echo json_encode($json, JSON_PRETTY_PRINT);

	if(count($json) > 0 ){
		$device = $json[0];
		// test monitoring the first device
		echo json_encode( $lgApi->mon($device['id']), JSON_PRETTY_PRINT);
	}
}

var_dump( $lgApi->save());
