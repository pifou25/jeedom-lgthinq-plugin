<?php

include '../../core/class/LgParameters.class.php';

if(isset($argv) && count($argv) > 1)
	$file = $argv[1];
else
	$file = 'save.json';

echo "parse $file\n";

$json = json_decode( file_get_contents($file), true, 512, JSON_BIGINT_AS_STRING);
$param = new LgParameters($json);

echo $param->getAuthUrl()."\n";

$eqLogics = [];
foreach ($param->getDevices() as $name => $device) {
	
	$file = LgParameters::clean($name) .'.json';
	if(!file_exists($file)){
		$commands = $param->getConfig($device);
		$data = json_encode($commands, JSON_PRETTY_PRINT);
		file_put_contents($file, $data);
		echo "generate $file\n";
		$eqLogics[$name] = $commands;
	}
}

echo json_encode($eqLogics, JSON_PRETTY_PRINT);
echo "\n *** log debug ****\n" . $param->getLog();

echo json_encode(LgParameters::getAllConfig(), JSON_PRETTY_PRINT);