<?php

include '../../core/class/LgParameters.class.php';

$content = file_get_contents('save2.json');

$json = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

$param = new LgParameters($json);

$eqLogics = [];
foreach ($param->getDevices() as $name => $device) {
	
	$file = LgParameters::clean($name) .'.json';
	if(!file_exists($file)){
		$commands = $param->getConfig($device);
		$data = json_encode($commands, JSON_PRETTY_PRINT);
		file_put_contents($file, $data);
		$eqLogics[$name] = $commands;
	}
}
echo json_encode($eqLogics, JSON_PRETTY_PRINT);
echo "\n *** log debug ****\n" . $param->getLog();

echo json_encode(LgParameters::getAllConfig(), JSON_PRETTY_PRINT);