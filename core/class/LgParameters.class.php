<?php

/**
 * compute LG json and transform into jeedom json parameters
 * Helper methods for LgThinq jeedom plugin
 * 
 * @author pifou25
 */
class LgParameters {

    private static $log = '';
    
    /**
     * json decoded array
     * @var array
     */
    private $devices = [];
    
    /**
     * the LG auth URL
     * @var string
     */
    private $authUrl = null;
    
    /**
     * LG account language-country
     * @var string 5 char (fr-FR)
     */
    public $language = null;
    
    /**
     * LG account country
     * @var string 2 char (FR)
     */
    public $country = null;

    /**
     * decode LG json response
     * @param array $json
     */
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
            $this->devices = self::computeDevices($json);
        }
    }

    public function getDevices() {
        return $this->devices;
    }

    public function getAuthUrl() {
        return $this->authUrl;
    }

    /**
     * compute json, build the LG account auth URL:
     * "[authBase]/login/iabClose?access_token=[access]&refresh_token=[refresh]&oauth2_backend_url=[oauthRoot]"
     * @param array $json
     * @return string
     */
    public function computeAuthUrl($json) {
        if (self::isIndexArray($json, 'config')) {
            $json = $json['config'];
        }
        $refresh = $access = $authBase = $oauthRoot = '';
        if (self::isIndexArray($json, 'gateway')) {
            $authBase = $json['gateway']['auth_base'];
            $oauthRoot = $json['gateway']['oauth_root'];
            $this->language = $json['gateway']['language'];
            $this->country = $json['gateway']['country'];
        }
        if (self::isIndexArray($json, 'auth')) {
            $refresh = $json['auth']['refresh_token'];
            $access = $json['auth']['access_token'];
        }
        return "$authBase/login/iabClose?access_token=$access&refresh_token=$refresh&oauth2_backend_url=$oauthRoot";
    }

    /**
     * get every config.model_info.[].Info.modelName
     * @param array $json
     * @return array
     */
    private static function computeDevices($json) {
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

    /**
     * check every command or info
     * @param array $device
     * @param array $protocol
     * @return array
     */
    private static function getCommands($device, $protocol = []) {
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
     * dans le json LG: convertir chaque ['value'] en une commande "info"
     * ajouter les commandes "action" depuis ['Config']['visibleItems']
     * @param array $lg
     * @return array
     */
    public static function convertLgToJeedom($lg){
        $config = self::getConfigInfos($lg);
        $config['commands'] = array_merge( $config['commands'], self::getConfigActions($lg));
        return $config;
    }

    /**
     * get list of info commands for the device
     * @param array $device
     * @return array
     */
    public static function getConfigInfos($device) {

        $commands = [];
        $protocol = self::getProtocol($device);
        if (!self::isIndexArray($device, 'Value')) {
            self::$log .= "\tno value for model_info () into config;\n";
        } else {
            $commands = self::getCommands($device['Value'], $protocol);
        }

        return ['name' => $device['Info']['modelName'],
            'commands' => $commands];
    }

    /**
     * get list of action commands for the device
     * @param array $device
     * @return array
     */
    public static function getConfigActions($device) {

        $commands = [];
        $protocol = self::getProtocol($device);

        if (!self::isIndexArray($device, 'Config')) {
            self::$log .= "\tno Config for model_info () into config;\n";
        } else if (!self::isIndexArray($device['Config'], 'visibleItems')) {
            self::$log .= "\tno visibleItems for model_info () into config;\n";
        } else {
            foreach($device['Config']['visibleItems'] as $key => $value){
                $name = $value['Feature'];
                $cmt = self::getComment($protocol, $name);
                self::$log .= "action $name: $cmt\n";
                $action = ['name' => "set$name",
                    'type' => 'action',
                    'subType' => 'other',
                    'isVisible' => 1
                ];
                if ($cmt) {
                    $action['remark'] = $cmt;
                }
                $commands[] = $action;
            }
        }

        return $commands;
    }

    /**
     * extract Monitoring-protocol from device config
     * @param type $device
     */
    public static function getProtocol($device){
        if (!self::isIndexArray($device, 'Monitoring')) {
            self::$log .= "\tno monitoring for model ();\n";
        } else if (!self::isIndexArray($device['Monitoring'], 'protocol')) {
            self::$log .= "\tno protocol in monitoring for model ();\n";
        } else {
            return $device['Monitoring']['protocol'];
        }
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

    /**
     * sanitize string, remove special chars, space
     * @param string $string
     * @return string
     */
    public static function clean($string) {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-_]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }

    /**
     * check that every key of 'example' exists into 'config'
     * @param array $_config
     * @param array $_example
     * @return boolean
     */
    public static function assertArrayContains($_config, $_example) {
        $valid = true;
        foreach ($_example as $key) {
            if (!array_key_exists($key, $_config)) {
                LgLog::error("Missing $key in LG response:" . json_encode($_config));
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * add some new keys
     * @param type $_config
     * @param type $_mapper
     * @return type
     */
    public static function mapperArray($_config, $_mapper) {

        if (LgParameters::assertArrayContains($_config, array_keys($_mapper))) {
            foreach ($_mapper as $key => $value) {
                $_config[$value] = $_config[$key];
            }
        }
        return $_config;
    }

    /**
     * list of every config files into ./resources/devices
     * @return array of json file names
     */
    public static function getAllConfig() {
        return array_diff(scandir(dirname(__FILE__) . '/../../resources/devices/'), array('.', '..'));
    }

    public static function getLog() {
        return self::$log;
    }

    /**
     * list wideq branches on github
     * @param string $url: https://api.github.com/repos/[user]/[repo]/branches
     * @return array
     */
    public static function getGithubBranches($url){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $json = curl_exec($ch);
        $branches = [];
        if(!$json) {
            $branches['error'] = curl_error($ch);
        } else if(empty($json)){
            $branches['error'] = 'empty response';
        }else{
            $json = json_decode($json, true, 512, JSON_BIGINT_AS_STRING);
              foreach($json as $data){
                      $branches[] = $data['name'];
            }
        }
        curl_close($ch);
        return $branches;
    }

    /**
     * search file name into $url with the $regex, then copy $url at $dest with $name
     * @param string $url source to copy
     * @param string $regex to capture the name
     * @param string $dest destination top copy file
     * @return true if success; otherwise: error message
     */
    public static function copyDataRegex($url, $regex, $dest) {
        $found = preg_match($regex, $url, $matches);
        if ($found) {
            return self::copyData($url, $matches[1], $dest);
        }
        return "Copy error: no matche $regex";
    }

    /**
     * copy $url file into $dest/$name. create $dest directory if it doesn't exists.
     * doesn't overwrite if file exists.
     * @param string $url source to copy
     * @param string $name 
     * @param string $dest : directory destination
     * @return boolean or error message
     */
    public static function copyData($url, $name, $dest) {
        if(file_exists($dest . $name))
            return true;
        if (!is_dir($dest))
            if (!mkdir($dest, 0777, true))
                return "unable to create dir $dest";
        $data = file_get_contents($url);
        if($data === false)
            return "Erreur lors de la lecture du fichier $url";
        if (file_put_contents($dest . $name, $data) === false)
            return "Erreur lors de l'écriture vers $dest$name";
        return true;
    }

    /**
     * scan and zip every files, and download it.
     * @param array $dirs = ['lg/', 'jeedom/', 'lang/']
     */
    public static function zipConfig($dirs, $tmp_file = '/tmp/lgthinq.zip'){
        $i = 0; $nb = 0; $err = 0;
        $zip = new ZipArchive;
        if ($zip->open($tmp_file,  ZipArchive::CREATE)) {
            foreach($dirs as $dir){
                foreach(scandir($dir) as $file){
                    $nb++;
                    if ($zip->addFile("$dir/$file"))
                        $i++;
                    else
                        $err++;
                }
            }
            $zip->close();
            header('Content-disposition: attachment; filename=lgthinq.zip');
            header('Content-type: application/zip');
            readfile($tmp_file);
            return "Archive created! $nb files, $i added, $err errors";
       } else {
           return "Failed to open $tmp_file!";
       }
    }
    
}
