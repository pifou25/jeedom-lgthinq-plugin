<?php

/**
 * compute LG json and transform into jeedom json parameters
 *
 * @author nicolas
 */
class LgParameters {

    private $log = '';
    private $devices = null;

    public static function clean($string) {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }

    public static function getAllConfig(){
        return array_diff(scandir(dirname(__FILE__) . '/../../resources/devices/'), array('.', '..'));
    }
    
    public function __construct($json) {

        if ($this->isIndexArray($json, 'config')) {
            $json = $json['config'];
        }
        if ($this->isIndexArray($json, 'model_info')) {
            $json = $json['model_info'];
        }
        if (empty($json)) {
            $this->log .= "model_info found but empty;\n";
        } else {
            $this->devices = $this->computeDevices($json);
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
                'remoteType' => $type, // binary ou enum
                'isVisible' => 1
            ];
            if ($cmt) {
                $cmd['remark'] = $cmt;
            }
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
