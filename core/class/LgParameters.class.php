<?php

/**
 * compute LG json and transform into jeedom json parameters
 *
 * @author nicolas
 */
class LgParameters {

    private $log = '';
    private $devices = null;

    public function __construct($json) {

        if (!$this->isIndexArray($json, 'config')) {
            $this->log .= "no config;\n";
        } else if (!$this->isIndexArray($json['config'], 'model_info')) {
            $this->log .= "no model_info into config;\n";
        } else if (empty($json['config']['model_info'])) {
            $this->log .= "model_info found but empty;\n";
        } else {
            $this->devices = $this->computeDevices($json['config']['model_info']);
        }
    }

    // get list of commands for the device
    public function getConfig($device) {

        $commands = [];
        $protocol = [];
        if (!$this->isIndexArray($device, 'Monitoring')) {
            $this->log .= "\tno monitoring for model ();\n";
        } else if (!$this->isIndexArray($device['Monitoring'], 'protocol')) {
            $this->log .= "\tno protocol in monitoring for model ();\n";
        } else {
            $protocol = $device['Monitoring']['protocol'];
        }

        if (!$this->isIndexArray($device, 'Value')) {
            $this->log .= "\tno value for model_info () into config;\n";
        } else {

            $commands = $this->getCommands($device['Value'], $protocol);
        }

        return ['name' => $device['Info']['modelName'],
            'commands' => $commands];
    }

    private function isIndexArray($arr, $index) {
        if (isset($arr[$index]) && is_array($arr[$index]))
            return true;
        else {
            $this->log .= "\tno $index;\n";
            return false;
        }
    }

    // return $arr[$result] where $arr[$key] = $value
    private function getInArray($arr, $key, $value, $result) {
        if (!empty($arr))
            foreach ($arr as $arr0) {
                if (isset($arr0[$key]) && $arr0[$key] == $value) {
                    return $arr0[$result];
                }
            }
        $count = count($arr);
        $this->log .= "\t $result not found with $key = $value ($count);\n";
        return false;
    }

    // return $protocol[_comment] si $protocol[value] == $value
    private function getComment($protocol, $value) {
        return $this->getInArray($protocol, 'value', $value, '_comment');
    }

    // get every config.model_info.[].Info.modelName
    private function computeDevices($json) {
        $result = [];
        // check every device
        foreach ($json as $value) {
            if (!$this->isIndexArray($value, 'Info')) {
                $this->log .= "no model_info for device ();\n";
            } else if (!isset($value['Info']['modelName'])) {
                $this->log .= "no modelName for device ();\n";
            } else {
                $result[$value['Info']['modelName']] = $value;
            }
        }
        return $result;
    }

    // check every command or info
    private function getCommands($device, $protocol = []) {
        $commands = [];
        foreach ($device as $name => $cmd) {

            $cmt = $this->getComment($protocol, $name);
            $type = $cmd['type'];

            $this->log .= "$name: $cmt ($type)\n";
            $cmd = ['name' => $name,
                'type' => 'info',
                'subType' => 'string',
                'remark' => $cmt,
                'remoteType' => $type, // binary ou enum
                'isVisible' => 1
            ];
            $commands[] = $cmd;
        }
        return $commands;
    }

    public function getLog() {
        return $this->log;
    }
    public function getDevices() {
        return $this->devices;
    }

}
