<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

// include /plugins/lgthinq/core/LgLog.class.php
include_file('core', 'LgLog', 'class', 'lgthinq');

// include /plugins/lgthinq/core/WideqManager.class.php
include_file('core', 'WideqManager', 'class', 'lgthinq');

// include /plugins/lgthinq/core/WideqAPI.class.php
include_file('core', 'WideqAPI', 'class', 'lgthinq');

// require_once '/plugins/lgthinq/core/LgParameters.class.php';
include_file('core', 'LgParameters', 'class', 'lgthinq');

class lgthinq extends eqLogic {
    /*     * *************************Attributs****************************** */

    /**
     * les attributs précédés de $_ ne sont pas sauvegardé en base
     */
    // private static $_keysConfig = [];

    private static $_lgApi = null;
    private static $__debug = null;
    private static $_destruct = false;

    const RESOURCES_PATH = '/../../resources/devices/';

    /*     * ***********************Methode static*************************** */

    /**
     * generate WideqAPI with jeedom configuration
     */
    public static function getApi() {
        if (self::$_lgApi == null) {
            $token = config::byKey('LgJeedomToken', 'lgthinq');
            if (!empty($token)) {
                $headers = [WideqAPI::TOKEN_KEY . ': ' . $token];
            } else {
                $headers = [];
            }
            $port = config::byKey('PortServerLg', 'lgthinq', 5025);
            $url = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
            $arr = ['port' => $port, 'url' => $url, 'debug' => self::isDebug(), 'headers' => $headers];
            self::$_lgApi = new WideqAPI($arr);
        }
        return self::$_lgApi;
    }

    /**
     * renew the token with wideq lib server
     */
    public static function initToken($_auth = false) {

        $lgApi = self::getApi();
        // first init gateway, then token
        $lang = config::byKey('LgLanguage', 'lgthinq');
        $country = config::byKey('LgCountry', 'lgthinq');
        $url = $lgApi->gateway($country, $lang);
        if (!isset($url['url'])) {
            $msg = "call LgThinq gateway $lang $country fails! " + $url['message'];
            LgLog::error($msg);
            return $msg;
        } else {
            if ($_auth === false) {
                $_auth = config::byKey('LgAuthUrl', 'lgthinq');
            }
            $json = $lgApi->token($_auth);
            if (isset($json[WideqAPI::TOKEN_KEY])) {
                config::save('LgJeedomToken', $json[WideqAPI::TOKEN_KEY], 'lgthinq');
                return true;
            } else {
                $msg = 'aucun jeedom token : ' . json_encode($json);
                LgLog::error($msg);
                return $msg;
            }
        }
    }

    /**
     * create the new object:
     * $_config has 4 mandatory keys: 'id' 'type' 'model' 'name'
     */
    public static function CreateEqLogic($_config) {

        $valid = true;
        foreach (['id', 'type', 'model', 'name'] as $key) {
            if (!isset($_config[$key])) {
                LgLog::error("Missing $key in LG response:" . json_encode($_config));
                $valid = false;
            }
        }
        if (!$valid) {
            return null;
        }

        $eqLogic = new lgthinq();
        $eqLogic->setEqType_name('lgthinq');
        $eqLogic->setIsEnable(1);
        $eqLogic->setLogicalId($_config['id']);
        $eqLogic->setName($_config['name']);
        $eqLogic->setProductModel($_config['model']);
        $eqLogic->setProductType($_config['type']);
        $eqLogic->setIsVisible(1);
        $eqLogic->save();
        LgLog::debug('Create LG Object ' . $eqLogic->getLogicalId() . ' - ' .
                $eqLogic->getName() . ' - ' . $eqLogic->getProductModel() . ' - ' . $eqLogic->getProductType());

        // nécessaire de recharger le $eqLogic ??
        //$eqLogic = lgthinq::byId($eqLogic->getId());

        if ($eqLogic->getConfFilePath() === false) {
            // recuperer conf LG
            $param = new LgParameters(self::getApi()->save());
            if (!isset($param->getDevices()[$eqLogic->getProductModel()])) {
                LgLog::warning("No device model {$eqLogic->getProductModel()}");
                return null;
            }
            $eqLogicConf = $param->getDevices()[$eqLogic->getProductModel()];
            // générer le fichier de conf par défaut
            $file = dirname(__FILE__) . self::RESOURCES_PATH . $eqLogic->getConfiguration('product_type')
                    . '.' . $eqLogic->getConfiguration('product_model') . '.json';
            file_put_contents($file, json_encode($eqLogicConf, JSON_PRETTY_PRINT));
            LgLog::info("Création du fichier de conf $file");
            if (self::isDebug()) {
                $log = $param->getLog();
                LgLog::debug("LgParam config:\n $log");
            }
        }
        // générer les commandes
        $eqLogic->createCommand();

        return $eqLogic;
    }

    /**
     * refresh any object sensors values
     */
    private static function refreshData() {
        LgLog::debug('refresh LG data for all devices');
        foreach (self::byType('lgthinq') as $eqLogic) {//parcours tous les équipements du plugin vdm
            $eqLogic->RefreshCommands();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */

    public static function cron() {
        self::refreshData();
    }

    public static function cron5() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0){
            self::refreshData();
        }
    }

    public static function cron10() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 || 
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 ){
            self::refreshData();
        }
    }

    public static function cron15() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 || 
                config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 ){
            self::refreshData();
        }
    }

    public static function cron30() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 || 
                config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0 ){
            self::refreshData();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     */
    public static function cronHourly() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0 || 
                config::byKey('functionality::cron30::enable', 'lgthinq', 1) == 0 ){
            self::refreshData();
        }
    }

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
     */
    public static function cronDaily() {
        if(config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0 || 
           config::byKey('functionality::cron30::enable', 'lgthinq', 1) == 0 || 
                config::byKey('functionality::cronHourly::enable', 'lgthinq', 1) == 0 ){
            self::refreshData();
        }
    }

    /**
     * gestion des dépendances du plugin
     */
    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return [
            'script' => WideqManager::getResourcesDir() . 'install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency',
            'log' => log::getPathToLog(__CLASS__ . '_update')];
    }

    public static function dependancy_info() {
        $return = [];
        $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
        if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
            $return['state'] = 'in_progress';
        } else {
            // run into docker container ?
            // if(exec(system::getCmdSudo() . 'cat /proc/1/cgroup | grep -c "/docker/"') > 0)

            $return['state'] = 'ok';
            $pythonVersion = WideqManager::getPython();
            if ($pythonVersion === false) {
                $return['state'] = 'nok';
            }

            if ($return['state'] == 'ok') {
                // check dependencies
                $daemonDir = WideqManager::getWideqDir(); // '/../../resources/daemon/';
                $deps = shell_exec("${daemonDir}check.sh");
                if ($deps < 5) {
                    LgLog::debug("missing pip dependancies ($deps) (${daemonDir}check.sh)");
                    $return['state'] = 'nok';
                } else {
                    $return['state'] = 'ok';
                }
            }
        }
        return $return;
    }

    /**
     * gestion du daemon LgThinq:
     * on peut configurer
     * PortServerLg = le port - 5025 par défaut
     * UrlServerLg = l'url - http://127.0.0.1 par défaut
     */
    public static function deamon_info() {
        $return = WideqManager::daemon_info();
        $return['pid'] = config::byKey('PidLg', 'lgthinq');
        $return['port'] = config::byKey('PortServerLg', 'lgthinq', 5025);
        $return['url'] = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
        $return['launchable'] = empty($return['port']) ? 'nok' : 'ok';
        return $return;
    }

    /**
     * rechercher les param de config jeedom et lancer le serveur
     */
    public static function deamon_start($_debug = false) {
        $daemon_info = self::deamon_info();
        $daemon_info['debug'] = $_debug || self::isDebug();
        $result = WideqManager::daemon_start($daemon_info);

        if ($result !== false) {
            // sauver le PID du daemon
            config::save('PidLg', $result, 'lgthinq');
            // after restart, reinit the token
            self::initToken();
            LgLog::debug('Restart daemon and reinit token');
        }

        return $result;
    }

    public static function deamon_stop() {
        return WideqManager::daemon_stop();
    }

    private static function addEvent($message, $level = 'warning') {
        event::add('jeedom::alert', [
            'level' => $level,
            'page' => 'lgthinq',
            'message' => $message
        ]);
    }

    public static function isDebug() {
        if (self::$__debug == null) {
            self::$__debug = ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
        }
        return self::$__debug;
    }

    /*     * *********************Méthodes d'instance************************* */

    public function __destruct() {
        if (!self::$_destruct) {
            self::$_destruct = true;
            if (self::$__debug === true) {
                $lgApi = self::getApi();
                LgLog::debug(json_encode($lgApi::getRequests()));
            }
        }
    }

    public function RefreshCommands() {
        if ($this->getIsEnable() == 1) {//vérifie que l'équipement est actif
            
            // list toutes les commandes
            $cmds = $this->getCmd();
            if(is_object($cmds)){
                $cmds = [$cmds];
            }
            
            // interroger l'API cloud LG pour rafraichir l'information:
            $infos = lgthinq::getApi()->mon($this->getLogicalId());
            
            foreach($cmds as $cmd){
                if(isset($infos[$cmd->getLogicalId()])){
                    // maj la commande ...
                    $this->checkAndUpdateCmd( $cmd, $infos[$cmd->getLogicalId()]);
                }else{
                    LgLog::debug("Pas d'info pour {$cmd->getLogicalId()}");
                }
            }

            LgLog::debug("Refresh {$this->getLogicalId()} avec " . count($cmds) . " commandes.");
        }
    }

    /**
     * Création des commandes de l'objet avec un fichier de configuration au format json
     */
    private function createCommand($_update = false) {

        if (false === $this->getConfFilePath()) {
            self::addEvent(__('Fichier de configuration absent ', __FILE__) . $this->getConfFilePath());
            return false;
        }
        $device = is_json(file_get_contents(dirname(__FILE__) . self::RESOURCES_PATH . $this->getConfFilePath()), []);
        if (!is_array($device) || !isset($device['commands'])) {
            LgLog::debug('Config file empty or not a json format');
            return false;
        }
        if (isset($device['name']) && !$_update) {
            $this->setName('[' . $this->getLogicalId() . ']' . $device['name']);
        }
        $this->import($device);
        sleep(1);
        self::addEvent('');
        LgLog::debug('Successfully created commands from config file:' . count($device));
        return true;
    }

    /**
     * le fichier de config est au format json
     * il est dans /config/devices/[product_type].[product_model].json
     * par défaut on peut utiliser [product_type].json si celui spécifique au model n'est pas disponible
     */
    private function getConfFilePath() {
        if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $this->getConfiguration('fileconf'))) {
            LgLog::debug('get confFilePath from configuration ' . $this->getConfiguration('fileconf'));
            return $this->getConfiguration('fileconf');
        }
        $model = LgParameters::clean($this->getConfiguration('product_model'));
        $id = $this->getConfiguration('product_type') . '.' . $model . '.json';
        if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $id)) {
            $this->setConfiguration('fileconf', $id);
            LgLog::debug('get confFilePath with specific model ' . $id);
            return $id;
        }
        $id = $this->getConfiguration('product_type') . '.json';
        if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $id)) {
            $this->setConfiguration('fileconf', $id);
            LgLog::debug('get generic confFilePath for product type ' . $id);
            return $id;
        }

        LgLog::info('No json config file for device ' . $this->getConfiguration('product_type') . ' nor ' . $this->getConfiguration('product_model'));
        return false;
    }

//    public function preInsert() {
//        LgLog::debug("preInsert LgThinq");
//    }
//
//    public function postInsert() {
//        LgLog::debug("postInsert LgThinq");
//    }
//
//    public function preSave() {
//        LgLog::debug("preSave LgThinq");
//    }
//
//    public function postSave() {
//        LgLog::debug("postSave LgThinq");
//    }
//    public function preUpdate() {
//
//    }
//
//    public function postUpdate() {
//
//    }
//
//    public function preRemove() {
//
//    }
//
//    public function postRemove() {
//
//    }

    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*
     * déclencher une action après modification de variable de configuration
     */

    // public static function postConfig_LgAuthUrl( $_value) {}

    /*
     * action avant modification de variable de configuration LgAuthUrl:
     * envoyer le nouveau token LgAuthUrl au serveur
     */
    public static function preConfig_LgAuthUrl($_newValue) {

        $_oldValue = config::byKey('LgAuthUrl', 'lgthinq');
        if ($_newValue != $_oldValue) {
            // maj jeedom token
            $json = self::initToken($_newValue);
            LgLog::debug('initToken=' . $json);
        } else {
            LgLog::debug('LgAuthUrl non modifié=' . $_newValue);
        }

        // try{
        // self::refreshListObjects();
        // }catch(\Throwable | \Exception $e){
        // LgLog::error(displayException($e));
        // }

        return $_newValue;
    }

    /*     * ********************** Getter Setter *************************** */

    public function getProductType() {
        return $this->getConfiguration('product_type');
    }

    public function setProductType($_productType) {
        $this->setConfiguration('product_type', $_productType);
    }

    public function getProductModel() {
        return $this->getConfiguration('product_model');
    }

    public function setProductModel($_productModel) {
        $this->setConfiguration('product_model', $_productModel);
    }

}

class lgthinqCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {

        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe lgthinq
                // maj la commande ...
                $this->getEqLogic()->RefreshCommands(); // on met à jour toutes les commandes de l'eqLogic
                break;

            default:
                LgLog::debug('cmd execute ' . $this->getLogicalId());
                break;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
