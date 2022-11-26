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
     * 
     * /!\ $_debug existe déjà dans EqLogic. 
     * ici on utilise $__debug pour éviter toute confusion.
     */
    private static $_lgApi = null;
    private static $__debug = null;

    const DEFAULT_VALUE = 'Default';

    /**
     * Timestamp of last successfull daemon check.
     * @var type long
     */
    private static $_lastCheckTime = null;
    private static $_daemonState = null;

    /*     * ***********************Methode static*************************** */

    /**
     * generate WideqAPI with jeedom configuration
     * refresh token if needed.
     */
    public static function getApi() {
        if (self::$_lgApi == null) {
            $port = config::byKey('PortServerLg', 'lgthinq', 5025);
            $url = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
            $arr = ['port' => $port, 'url' => $url, 'debug' => self::isDebug()];
            self::$_lgApi = new WideqAPI($arr);
            // check auth
            $ping = self::$_lgApi->ping();
            if(isset($ping['auth']) && $ping['auth'] == false){
                self::renewApi(self::$_lgApi);
            }
        }
        return self::$_lgApi;
    }

    public static function renewApi($api = null){
        if($api == null){
            $api = self::getApi();
        }
        // renew LG gateway and auth
        $country = config::byKey('LgCountry', 'lgthinq');
        $lang = config::byKey('LgLanguage', 'lgthinq');
        $api->gateway($country, $lang);
        $auth = config::byKey('LgAuthUrl', 'lgthinq');
        LgLog::debug("refresh LG token with $auth");
        $ret = $api->token($auth);
        if(isset($ret['auth']) && $ret['auth'] == false){
            LgLog::error("refresh LG token fails!");
        }
        return $api;
    }

    /**
     * create the new object:
     * $_config has 4 mandatory keys: 'id' 'type' 'model' 'name'
     */
    public static function CreateEqLogic($_config, $_model = self::DEFAULT_VALUE) {

        $eqLogic = new lgthinq($_config, $_model);
        $eqLogic->save();

        // générer les commandes
        $eqLogic->createCommand();

        return $eqLogic;
    }

    /**
     * refresh all object sensors values.
     * triggered by cron
     */
    public static function refreshData() {
        LgLog::debug('refresh LG data for all devices');
        foreach (self::byType('lgthinq', true) as $eqLogic) {
            try{
                $eqLogic->RefreshCommands();
            }catch(LgApiException $e){
                LgLog::error($e->getMessage());
            }
        }
        return true;
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
            'script' => WideqManager::getResourcesDir() . 'install_#stype#.sh '
            . jeedom::getTmpFolder(__CLASS__) . '/dependency',
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
            config::save('PythonLg', WideqManager::getPython());
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
        if(self::$_lastCheckTime !== null && time() - self::$_lastCheckTime < 10){
            // don't check every second
            LgLog::debug('cache daemon info since ' . (time() - self::$_lastCheckTime));
            return self::$_daemonState;
        }
        $return = WideqManager::daemon_info();
        $return['pid'] = config::byKey('PidLg', 'lgthinq');
        $return['port'] = config::byKey('PortServerLg', 'lgthinq', 5025);
        // $return['url'] = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
        $return['key'] = jeedom::getApiKey();
        $return['ip'] = 'http://' . config::byKey('internalAddr'); // jeedom internal IP
        $return['launchable'] = empty($return['port']) ? 'nok' : 'ok';
        // caching result if state is ok
        if(isset($return['state']) && $return['state'] == 'ok'){
            self::$_lastCheckTime = time();
            self::$_daemonState = $return;
        }
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
            LgLog::debug('Restart daemon, id=$result');
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

    /**
     * create default object with id, name, model and type
     * @param array $_config
     */
    private function __construct($_config, $_model){
        if (!LgParameters::assertArrayContains($_config, ['id', 'type', 'model', 'name'])) {
            return null;
        }

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

        if(!empty($_model)){
            // download images and json config from LG cloud
            LgParameters::downloadAndCopyDataModel($_config['id'], $_model, $this->getFileconf());
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
            $infos = self::getApi()->mon($this->getLogicalId());
            LgLog::debug("monitoring {$this->getName()}" . print_r($infos, true));

            $nb = 0;
            foreach ($cmds as $cmd) {
                if ($cmd->getType() != 'action'){
                    if(isset($infos[$cmd->getLogicalId()])) {
                        // maj la commande info ...
                        $this->checkAndUpdateCmd($cmd, $infos[$cmd->getLogicalId()]);
                        $nb++;
                    } else {
                        LgLog::warning("Pas d'info pour {$cmd->getLogicalId()} ({$cmd->getType()})");
                    }
                }
            }
            LgLog::debug("Refresh {$this->getLogicalId()} avec " . count($cmds)
                    . " commandes et $nb maj.");
        }
    }

    /**
     * Création des commandes de l'objet avec un fichier de configuration au format json
     */
    private function createCommand() {

        LgLog::debug("check createCommand json config... " . $this->getLogicalId());
        if (!file_exists( $this->getFileconf())) {
            self::addEvent(__('Fichier de configuration absent ', __FILE__) . $this->getFileconf());
            return false;
        }
        $device = is_json(file_get_contents( $this->getFileconf()), []);
        if (!is_array($device) || empty($device)) {
            LgLog::debug('Json Config fichier vide ou pas au format json: ' . $this->getFileconf());
            return false;
        }

        // add default 'refresh' command
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

        LgLog::debug("Start import commands for " . $this->getLogicalId());
        $this->import($device);
        sleep(1);
        self::addEvent('');
        LgLog::debug('Successfull import ID ' . $this->getLogicalId() . 
                ' nb commands: ' . count($device));
        return true;
    }

//    public function preInsert() {}
//    public function postInsert() {}
//    public function preSave() {}
//    public function postSave() {}
//    public function preUpdate() {}
//    public function postUpdate() {}
//    public function preRemove() {}
//    public function postRemove() {}

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
//    public static function preConfig_LgAuthUrl($_newValue) {
//        return $_newValue;
//    }

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
        return LgParameters::getResourcesPath() . $this->getLogicalId() . '.json';
    }
    
    public function getImage(){
        $result = LgParameters::getDataPath().'smallImg/'. $this->getLogicalId().'.png';
        if(!file_exists($result)){
            LgLog::debug("img not found: $result");
            $plugin = plugin::byId($this->getEqType_name());
            return $plugin->getPathImgIcon();
        }else{
            return str_replace('/var/www/html/', '', $result);
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
     * Non obligatoire permet de demander de ne pas supprimer les commandes
     *  même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
     *       public function dontRemoveCmd() {
     *       return true;
     *       }
     */

    public function execute($_options = array()) {
        // récupérer l'objet eqLogic de cette commande
        $eqLogic = $this->getEqLogic();
        LgLog::debug("cmd->execute {$this->getType()} {$this->getLogicalId()} opt='" .
                print_r($_options, true) . "' {$eqLogic->getLogicalId()}");
        if ($this->getType() != 'action') {
            return;
        }
        $result = 'ko';
        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
            case 'refresh': // LogicalId de la commande rafraîchir
                $return = $eqLogic->RefreshCommands();
                break;

            default:
                // add $api->set($id, $cmd) ou set($id, $cmd, $value)
                LgLog::info('cmd execute ' . $this->getLogicalId());
                break;
        }
        return $result;
    }

    /*     * **********************Getteur Setteur*************************** */
}
