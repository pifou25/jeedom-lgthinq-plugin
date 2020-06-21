<?php

include '../../core/class/LgParameters.class.php';

$content = file_get_contents('save.json');

$json = json_decode($content, true, 512, JSON_BIGINT_AS_STRING);

$param = new LgParameters($json);

$eqLogics = [];
foreach ($param->getDevices() as $name => $device) {

    $eqLogics[] = $param->getConfig($device);
}
echo json_encode($eqLogics);
echo "\n *** log debug ****\n" . $param->getLog();
