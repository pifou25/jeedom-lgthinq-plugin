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
            return $lgApi->token($_auth);
        }
    }

    /**
     * create the new object:
     * $_config has 4 mandatory keys: 'id' 'type' 'model' 'name'
     */
    public static function CreateEqLogic($_config, $_json = null) {

        if (!lgthinq::assertArrayContains($_config, ['id', 'type', 'model', 'name'])) {
            return null;
        }

        $eqLogic = new lgthinq($_config);
        $eqLogic->save();

        if ($eqLogic->configureFilepath( $_json) === false) {
            // recuperer conf LG
            $param = new LgParameters(self::getApi()->save());
            if (!isset($param->getDevices()[$eqLogic->getProductModel()])) {
                LgLog::warning("No device model {$eqLogic->getProductModel()}");
                return null;
            }
            $eqLogicConf = $param->getDevices()[$eqLogic->getProductModel()];
            // générer le fichier de conf par défaut
            $file = dirname(__FILE__) . self::RESOURCES_PATH . $eqLogic->getProductType()
                    . '.' . $eqLogic->getProductModel() . '.json';
            file_put_contents($file, json_encode($eqLogicConf, JSON_PRETTY_PRINT));
            LgLog::info("Création du fichier de conf $file");
            if (self::isDebug()) {
                LgLog::debug("LgParam config:\n" . $param->getLog());
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
        foreach (self::byType('lgthinq') as $eqLogic) {//parcours tous les équipements du plugin
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
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0) {
            self::refreshData();
        }
    }

    public static function cron10() {
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0) {
            self::refreshData();
        }
    }

    public static function cron15() {
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0) {
            self::refreshData();
        }
    }

    public static function cron30() {
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0) {
            self::refreshData();
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     */

    public static function cronHourly() {
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron30::enable', 'lgthinq', 1) == 0) {
            self::refreshData();
        }
    }

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
     */

    public static function cronDaily() {
        if (config::byKey('functionality::cron::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron5::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron10::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron15::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cron30::enable', 'lgthinq', 1) == 0 ||
                config::byKey('functionality::cronHourly::enable', 'lgthinq', 1) == 0) {
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
        } else if (empty(WideqManager::getPython())) {
            $return['state'] = 'nok';
        } else {
            $return['state'] = WideqManager::check_dependancy();
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
        // $return['url'] = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
        $return['key'] = jeedom::getApiKey();
        $return['ip'] = config::byKey('internalAddr');
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

    /**
     * check that every key of 'example' exists into 'config'
     * @param array $_config
     * @param array $_example
     * @return boolean
     */
    public static function assertArrayContains($_config, $_example){
       $valid = true;
        foreach ($_example as $key) {
            if (!array_key_exists($key, $_config)) {
                LgLog::error("Missing $key in LG response:" . json_encode($_config));
                $valid = false;
            }
        }
        return $valid;
    }
    
    public static function isDebug() {
        if (self::$__debug == null) {
            self::$__debug = ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
        }
        return self::$__debug;
    }

    /*     * *********************Méthodes d'instance************************* */

    /**
     * create default object with id, name, model and type
     * @param array $_config
     */
    private function __construct($_config){
        $this->setEqType_name('lgthinq');
        $this->setIsEnable(1);
        $this->setLogicalId($_config['id']);
        $this->setName($_config['name']);
        $this->setProductModel($_config['model']);
        $this->setProductType($_config['type']);
        $this->setIsVisible(1);
        LgLog::debug('Create LG Object ' . $this->getLogicalId() . ' - ' .
                $this->getName() . ' - ' . $this->getProductModel() . ' - ' .
                $this->getProductType());
        //parent::__construct();
    }

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
            if (is_object($cmds)) {
                $cmds = [$cmds];
            }

            // interroger l'API cloud LG pour rafraichir l'information:
            $infos = lgthinq::getApi()->mon($this->getLogicalId());
            LgLog::debug("monitoring {$this->getName()}" . print_r($infos, true));

            foreach ($cmds as $cmd) {
                if (isset($infos[$cmd->getLogicalId()])) {
                    // maj la commande ...
                    $this->checkAndUpdateCmd($cmd, $infos[$cmd->getLogicalId()]);
//                } else {
//                    LgLog::debug("Pas d'info pour {$cmd->getLogicalId()}");
                }
            }

            LgLog::debug("Refresh {$this->getLogicalId()} avec " . count($cmds) . " commandes.");
        }
    }

    /**
     * Création des commandes de l'objet avec un fichier de configuration au format json
     */
    private function createCommand($_update = false) {

        $this->createDefaultCommands();

        if (false === $this->getFileconf()) {
            self::addEvent(__('Fichier de configuration absent ', __FILE__) . $this->getFileconf());
            return false;
        }
        $device = is_json(file_get_contents(dirname(__FILE__) . self::RESOURCES_PATH . $this->getFileconf()), []);
        if (!is_array($device) || !isset($device['commands'])) {
            LgLog::debug('Config file empty or not a json format');
            return false;
        }
//        if (isset($device['name']) && !$_update) {
//            $this->setName('[' . $this->getLogicalId() . ']' . $device['name']);
//        }
        $this->import($device);
        sleep(1);
        self::addEvent('');
        LgLog::debug('Successfully created commands from config file:' . count($device));
        return true;
    }

    /**
     * commande par défaut pour monitorer l'objet
     */
    private function createDefaultCommands(){
        $info = $this->getCmd(null, 'monitor');
        if (!is_object($info)) {
                $info = new lgthinqCmd();
                $info->setName(__('Monitoring', __FILE__));
        }
        $info->setLogicalId('monitor');
        $info->setEqLogic_id($this->getId());
        $info->setType('info');
        $info->setSubType('string');
        $info->save();

        $refresh = $this->getCmd(null, 'refresh');
        if (!is_object($refresh)) {
                $refresh = new lgthinqCmd();
                $refresh->setName(__('Rafraichir', __FILE__));
        }
        $refresh->setEqLogic_id($this->getId());
        $refresh->setLogicalId('refresh');
        $refresh->setType('action');
        $refresh->setSubType('other');
        $refresh->save();

    }

    /**
     * le fichier de config est au format json
     * il est dans /config/devices/[product_type].[product_model].json
     * par défaut on peut utiliser [product_type].json si celui spécifique au model n'est pas disponible
     */
    private function configureFilepath($_json = null) {
        if ($_json !== null && $this->setFileconf($_json)) {
            LgLog::debug('get confFilePath from configuration ' . $_json);
            return true;
        }
        $model = LgParameters::clean($this->getProductModel());
        $id = $this->getProductType() . '.' . $model . '.json';
        if ($this->setFileconf( $id)) {
            LgLog::debug('get confFilePath with specific model ' . $id);
            return true;
        }
        $id = $this->getProductType() . '.json';
        if ($this->setFileconf( $id)) {
            LgLog::debug('get generic confFilePath for product type ' . $id);
            return true;
        }

        LgLog::info('No json config file for device ' . $this->getProductType() . ' nor ' . $this->getProductModel());
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
    
    public function getFileconf(){
        return $this->getConfiguration('fileconf');
    }
    
    /**
     * this setter returns true if $_fileconf is a correct filename into RESOURCES_PATH
     * @param string $_fileconf
     * @return boolean
     */
    public function setFileconf($_fileconf){
        if(is_file(dirname(__FILE__) . self::RESOURCES_PATH . $_fileconf)){
            $this->setConfiguration('fileconf', $_fileconf);
            return true;
        }else{
            return false;
        }
    }

}

class lgthinqCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preSave() {
        //$this->setLogicalId($this->getConfiguration('instance') . '.' . $this->getConfiguration('class') . '.' . $this->getConfiguration('index'));
        $this->setLogicalId($this->getName());
    }

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {
        LgLog::debug('cmd->execute ' . print_r($_options, true) . "\nfor this = " . print_r($this, true) );

        if ($this->getType() != 'action') {
                return;
        }

        // récupérer l'objet eqLogic de cette commande
        $eqLogic = $this->getEqLogic();
        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir
                // maj la commande 'monitor' avec les infos de monitoring
                $infos = lgthinq::getApi()->mon($this->getLogicalId());
                $eqLogic->checkAndUpdateCmd('monitor', $infos);
                break;

            default:
                LgLog::info('cmd execute ' . $this->getLogicalId());
                break;
        }
    }

    /*     * **********************Getteur Setteur*************************** */
}
