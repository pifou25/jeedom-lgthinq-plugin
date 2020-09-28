<?php

/**
 * compute LG json and transform into jeedom json parameters
 *
 * @author nicolas
 */
class LgParameters {

    private static $log = '';
    private $devices = [];
    private $authUrl = null;
    
    public function __construct($json) {

        $this->authUrl = $this->computeAuthUrl($json);
        if (self::isIndexArray($json, 'config')) {
            $json = $json['config'];
        }
        if (self::isIndexArray($json, 'model_info')) {
            $json = $json['model_info'];
        }
        if (empty($json)) {
            self::$log .= "model_info found but empty;\n";
        } else {
            $this->devices = $this->computeDevices($json);
        }
    }

    public function computeAuthUrl($json){
        // "$authBase/login/iabClose?access_token=$access&refresh_token=$refresh&oauth2_backend_url=$oauthRoot"
        if (self::isIndexArray($json, 'config')) {
            $json = $json['config'];
        }
        $refresh = $access = $authBase = $oauthRoot = '';
        if (self::isIndexArray($json, 'gateway')) {
            $authBase = $json['gateway']['auth_base'];
            $oauthRoot = $json['gateway']['oauth_root'];
        }
        if (self::isIndexArray($json, 'auth')) {
            $refresh = $json['auth']['refresh_token'];
            $access = $json['auth']['access_token'];
        }
        return "$authBase/login/iabClose?access_token=$access&refresh_token=$refresh&oauth2_backend_url=$oauthRoot";        
    }
    
    // get list of commands for the device
    public function getConfig($device) {

        $commands = [];
        $protocol = [];
        if (!self::isIndexArray($device, 'Monitoring')) {
            self::$log .= "\tno monitoring for model ();\n";
        } else if (!self::isIndexArray($device['Monitoring'], 'protocol')) {
            self::$log .= "\tno protocol in monitoring for model ();\n";
        } else {
            $protocol = $device['Monitoring']['protocol'];
        }

        if (!self::isIndexArray($device, 'Value')) {
            self::$log .= "\tno value for model_info () into config;\n";
        } else {

            $commands = $this->getCommands($device['Value'], $protocol);
        }

        return ['name' => $device['Info']['modelName'],
            'commands' => $commands];
    }

    // get every config.model_info.[].Info.modelName
    private function computeDevices($json) {
        $result = [];
        // check every device
        foreach ($json as $value) {
            if (!self::isIndexArray($value, 'Info')) {
                self::$log .= "no model_info for device ();\n";
            } else if (!isset($value['Info']['modelName'])) {
                self::$log .= "no modelName for device ();\n";
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

            $cmt = self::getComment($protocol, $name);
            $type = $cmd['type'];

            self::$log .= "$name: $cmt ($type)\n";
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

    /**
     * public static functions
     */
   
    public static function isIndexArray($arr, $index) {
        if (isset($arr[$index]) && is_array($arr[$index]))
            return true;
        else {
            self::$log .= "\tno $index;\n";
            return false;
        }
    }

    // return $arr[$result] where $arr[$key] = $value
    public static function getInArray($arr, $key, $value, $result) {
        if (!empty($arr))
            foreach ($arr as $arr0) {
                if (isset($arr0[$key]) && $arr0[$key] == $value && isset($arr0[$result])) {
                    return $arr0[$result];
                }
            }
        $count = count($arr);
        self::$log .= "\t $result not found with $key = $value ($count);\n";
        return false;
    }

    // return $protocol[_comment] si $protocol[value] == $value
    public static function getComment($protocol, $value) {
        return self::getInArray($protocol, 'value', $value, '_comment');
    }

    public static function clean($string) {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }

    /**
     * list of every config files into ./resources/devices
     * @return array of json file names
     */
    public static function getAllConfig(){
        return array_diff(scandir(dirname(__FILE__) . '/../../resources/devices/'), array('.', '..'));
    }

    public static function getLog() {
        return self::$log;
    }

    public function getDevices() {
        return $this->devices;
    }

    public function getAuthUrl(){
        return $this->authUrl;
    }
}
