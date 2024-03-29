<?php

/**
 * test wideq python library and LG connexion
 */
 
const JEEDOM_IP = '192.168.1.26';
const JEEDOM_KEY = 'kLbmBWVeQSqbhluECyycGEeGAXXZOahS';
const JEEDOM_PORT = '5025';
const WIDEQ_DIR = 'C:/Users/nicolas/Documents/dev/python/wideq/';

include '../../core/class/LgParameters.class.php';
include '../../core/class/WideqManager.class.php';
include '../../core/class/WideqAPI.class.php';
include 'mock.php';

if(isset($argv) && count($argv) > 1)
	$file = $argv[1];
else
	$file = 'wideq_state.json';

/**
 * check json config file
 */

$file = "../../resources/daemon/$file";
if(file_exists($file)){
	print PHP_OS . " ___ parse $file\n";
}else{
	die( "Le fichier $file n'existe pas !");
}

// decode and parse json config file
$json = json_decode( file_get_contents($file), true, 512, JSON_BIGINT_AS_STRING);
$param = new LgParameters($json);

print "URL found with " . count($param->getDevices()) . " appareils\n"
 . $param->getAuthUrl()."\n";

$eqLogics = [];
foreach ($param->getDevices() as $name => $device) {
	
	$file = LgParameters::clean($name) .'.json';
	if(!file_exists($file)){
		$commands = $param->getConfig($device);
		$data = json_encode($commands, JSON_PRETTY_PRINT);
		file_put_contents($file, $data);
		print "generate $file";
		$eqLogics[$name] = $commands;
	}else{
		print "Fichier de config $file ok.";
	}
}

print json_encode($eqLogics, JSON_PRETTY_PRINT);
print " *** log debug **** \n" . $param->getLog();

print json_encode(LgParameters::getAllConfig(), JSON_PRETTY_PRINT);

$branches = WideqManager::getGithubBranches('https://api.github.com/repos/pifou25/wideq/branches');
print_r($branches);

/**
 * check python version
 */
print "python version = " . WideqManager::getPython();
print "python dependances " . WideqManager::check_dependancy();


$file = WideqManager::getWideqDir() . 'wideq/' . WideqManager::WIDEQ_SCRIPT;
if(!file_exists($file)){
	$file = WIDEQ_DIR  . WideqManager::WIDEQ_SCRIPT;
}
// (add +x at install.php) flag and run the server:
$cmd = sprintf(WideqManager::getPython()
	. " $file --port %s --key %s --ip %s",
	JEEDOM_PORT, JEEDOM_KEY, JEEDOM_IP);
$cmd .= ' -v >> srv.log 2>&1';
print $cmd;

/**
 * launch wideq server
 */
$handle = popen($cmd, 'r');

sleep(5);
echo "'$handle'; " . gettype($handle) . "\n";

/**
 * test API commands 
 */
$_lgApi = new WideqAPI(['url' => 'localhost', 'port' => JEEDOM_PORT, 'debug' => true]);

print_r( $_lgApi->ping());
print "call gateway/{$param->country}/{$param->language}";
print_r( $_lgApi->gateway( $param->country, $param->language));
print_r( $_lgApi->token( $param->getAuthUrl()));
$json =  $_lgApi->ls();
print_r( $json);
print count($json) . " objet(s) détectés)";
if(count($json) > 0){
	foreach($json as $id => $data){
		print("monitoring " . $data['alias']);
		print_r( $_lgApi->mon( $id));
		break;
	}
}

print "Script terminé - Ctrl+C pour fermer.";
pclose($handle);

