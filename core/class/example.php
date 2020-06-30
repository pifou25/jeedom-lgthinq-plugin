<?php
/*
 * this example use wideq python lib on command line
 */

class log{
	public static function add(){print_r( func_get_args());}
	public static function getPathToLog(){ return '/var/www/html/logs/';}
}

include 'LgLog.class.php';
include 'WideqAPI.class.php';
include 'WideqManager.class.php';


// $token = '64e0a91f-29d9-4ca7-aa44-62e9467cd4f0';
// $lgApi = new WideqAPI(['headers' => [ "jeedom_token: $token" ]]);

$lgApi = new WideqAPI();

// check error catching
try{
	if(!$lgApi->changeLog('aie')){
		echo 'erreur changement de log';
	}
}catch(LgApiException $e){
	echo $e;
}
// set log level
if(!$lgApi->changeLog('debug')){
	echo 'erreur changement de log';
}

echo json_encode( $lgApi->ping(), JSON_PRETTY_PRINT);

// send GET request for having gateway URL depending language and country
$url = $lgApi->gateway('FR', 'fr-FR');
// display url to loggin
//echo json_encode( $url, JSON_PRETTY_PRINT);

//echo json_encode( $lgApi->ping(), JSON_PRETTY_PRINT);

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
	//echo json_encode($json, JSON_PRETTY_PRINT);

	if(count($json) > 0 ){
    foreach($json as $id => $data){
  		// test monitoring the first device
  		$mon = $lgApi->mon($id);
      echo "\n ************************ \n\n monitoring {$data['name']} {$data['model']} {$data['type']}\n\n ************************\n";
  		echo json_encode( $mon, JSON_PRETTY_PRINT);

    }
	}
}

$lgApi->save();
try{
	echo json_encode( $lgApi->fail(), JSON_PRETTY_PRINT);
}catch(LgApiException $e){
	echo "\n" . $e ."\n";
}
echo "\nlast request = \n" . json_encode( WideqAPI::getRequests()[count(WideqAPI::getRequests())-1], JSON_PRETTY_PRINT);
// https://eic.lgthinq.com:46030/api/webContents/modelJSON?modelName=1REB1GLPX1___&countryCode=WW&contentsId=JS0213025419441577&authKey=thinq
