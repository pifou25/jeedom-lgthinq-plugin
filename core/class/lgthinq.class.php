<?php

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

// simple autoloader with namespaced classes
define('AUTOLOADER_PATTERN', '/com\\\\jeedom\\\\plugins\\\\(\w*)\\\\(\w*)/');
define('AUTOLOADER_REPLACE', '/var/www/html/plugins/$1/core/class/$2.class.php');
spl_autoload_register(function ($class) {
    $file = preg_replace(AUTOLOADER_PATTERN, AUTOLOADER_REPLACE, $class );
    if (is_file($file)) {
        require_once $file;
    }else if($class != $file){
        LgLog::error("$file was NOT found ! please check $class");
    }
});

use com\jeedom\plugins\lgthinq\LgApiException;
use com\jeedom\plugins\lgthinq\LgParameters;
use com\jeedom\plugins\lgthinq\WideqManager;
use com\jeedom\plugins\lgthinq\LgThinqApi;
use com\jeedom\plugins\lgthinq\LgTranslate;
use com\jeedom\plugins\lgthinq\LgLog;

class lgthinq extends eqLogic {
    
    /*     * *************************Attributs****************************** */

    /**
     * les attributs précédés de $_ ne sont pas sauvegardé en base
     * 
     * /!\ $_debug existe déjà dans EqLogic. 
     * ici on utilise $__debug pour éviter toute confusion.
     */
    private static $__debug = null;

    const DEFAULT_VALUE = 'Default';

    /*     * ***********************Methode static*************************** */


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
        LgLog::debug(__('Mise à jour des informations de tous les appareils LG', __FILE__));
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

    public static function deamon_info() {
        return LgThinqApi::daemon_info();
    }

    public static function deamon_start($_debug = false) {
        return LgThinqApi::daemon_start($_debug || self::isDebug());
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
    private function __construct($_config = [], $_model = []){
        if(empty($_config) || empty($_model)){
          // args are mandatory, return without action if no args provided
          return null;
        }
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
        LgLog::debug(sprintf(__('Création des objets LG %s - %s - %s - %s ', __FILE__),
         $this->getLogicalId(), $this->getName(), $this->getProductModel(), $this->getProductType()));

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
            $infos = LgThinqApi::getApi()->mon($this->getLogicalId());
            LgLog::debug("monitoring {$this->getName()}" . print_r($infos, true));

            $nb = 0;
            foreach ($cmds as $cmd) {
                if ($cmd->getType() != 'action'){
                    if(isset($infos[$cmd->getLogicalId()])) {
                        // maj la commande info ...
                        $this->checkAndUpdateCmd($cmd, $infos[$cmd->getLogicalId()]);
                        $nb++;
                    } else {
                        LgLog::warning(__("Pas d'info pour:", __FILE__)
                         . $cmd->getLogicalId() . " ({$cmd->getType()})");
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

        LgLog::debug(__("'createCommand' Vérification de la configuration json... ", __FILE__)
         . $this->getLogicalId());
        if (!file_exists( $this->getFileconf())) {
            self::addEvent(__('Fichier de configuration absent ', __FILE__) . $this->getFileconf());
            return false;
        }
        $device = is_json(file_get_contents( $this->getFileconf()), []);
        if (!is_array($device) || empty($device)) {
            LgLog::debug(__('Fichier de config Json vide ou pas au format: ', __FILE__) . $this->getFileconf());
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

        LgLog::debug(__('Démarrer importation des commandes pour: ', __FILE__) . $this->getLogicalId());
        $this->import($device);
        sleep(1);
        self::addEvent('');
        LgLog::debug(LgTranslate::tr('Successfull import ID %s nb commands: %s', __FILE__,
             $this->getLogicalId(), count($device)));
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
            LgLog::debug(__('Image non trouvée: ', __FILE__) .$result);
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
                LgLog::info(__('Execute Commande ', __FILE__) . $this->getLogicalId());
                break;
        }
        return $result;
    }

    /*     * **********************Getteur Setteur*************************** */
}
