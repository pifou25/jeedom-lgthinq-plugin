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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

// include /plugins/lgthinq/core/LgLog.class.php
include_file('core', 'LgLog', 'class', 'lgthinq');

// include /plugins/lgthinq/core/WideqManager.class.php
include_file('core', 'WideqManager', 'class', 'lgthinq');

// include /plugins/lgthinq/core/WideqAPI.class.php
include_file('core', 'WideqAPI', 'class', 'lgthinq');


class lgthinq extends eqLogic {
    /*     * *************************Attributs****************************** */

	/**
	 * les attributs précédés de $_ ne sont pas sauvegardé en base
	 */

	// private static $_keysConfig = [];

	private static $_lgApi = null;

	private const RESOURCES_PATH = '/../../resources/devices/';

    /*     * ***********************Methode static*************************** */

	/**
	 * generate WideqAPI with jeedom configuration
	 */
	public static function getApi(){
		if(self::$_lgApi == null){
			$token = config::byKey('LgJeedomToken', 'lgthinq');
			if(!empty($token)){
				$headers = [WideqAPI::TOKEN_KEY . ': ' . $token];
			}else{
				$headers = [];
			}
			$port = config::byKey('PortServerLg', 'lgthinq', 5025);
			$url = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
			$debug = ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
			$arr = ['port' => $port, 'url' => $url, 'debug' => $debug, 'headers' => $headers];
			self::$_lgApi = new WideqAPI( $arr );
		}
		return self::$_lgApi;
	}

	/**
	 * renew the token with wideq lib server
	 */
	public static function initToken( $_auth = false){

		$lgApi = self::getApi();
		// first init gateway, then token
		$lang = config::byKey('LgLanguage', 'lgthinq');
		$country = config::byKey('LgCountry', 'lgthinq');
		$url = $lgApi->gateway( $country, $lang);
		if(!isset($url['url'])){
			$msg = "call LgThinq gateway $lang $country fails! " + $url['message'];
			LgLog::error($msg);
			return $msg;
		}else{
			if( $_auth === false){
				$_auth = config::byKey('LgAuthUrl', 'lgthinq');
			}
			$json = $lgApi->token($_auth);
			if(isset($json[WideqAPI::TOKEN_KEY])){
				config::save('LgJeedomToken', $json[WideqAPI::TOKEN_KEY], 'lgthinq');
				return true;
			}else{
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
	public static function CreateEqLogic($_config){

		 $valid = true;
		 foreach( ['id', 'type', 'model', 'name'] as $key){
			 if(!isset($_config[$key])){
				 LgLog::error("Missing $key in LG response:" . json_encode($_config));
				 $valid = false;
			 }
		 }
		 if($valid){

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
			// générer les commandes
			$eqLogic->createCommand();

			return $eqLogic;
		 }else{
			 return null;
		 }
	}

	/**
	 * refresh any object sensors values: TODO
	 */
	 private static function refreshData(){
		 LgLog::debug('refresh LG data');
	 }

	/**
	 * refresh list of connected LG object
	 */
	private static function refreshListObjects(){
	}

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
      public static function cron() {
		self::refreshData();
      }

      public static function cron5() {
		self::refreshData();
      }

      public static function cron10() {
		self::refreshData();
      }

      public static function cron15() {
		self::refreshData();
      }

      public static function cron30() {
		self::refreshData();
      }

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
     */
      public static function cronHourly() {
		self::refreshData();

      }

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
     */
      public static function cronDaily() {
		self::refreshData();

      }

		/**
		 * gestion des dépendances du plugin
		 */
		public static function dependancy_install() {
				log::remove(__CLASS__.'_update');
				return [
					'script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder(__CLASS__) . '/dependency',
				 'log' => log::getPathToLog(__CLASS__.'_update')];
		}

		public static function dependancy_info() {
			$return = [];
			$return['log'] = log::getPathToLog(__CLASS__.'_update');
			$return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependency';
			if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
				$return['state'] = 'in_progress';
			} else {
				if (exec(system::getCmdSudo() . system::get('cmd_check') . '-Ec "python3\-requests"') < 1) {
		 			LgLog::debug('missing python3');
		 			$return['state'] = 'nok';
				} elseif (exec(system::getCmdSudo() . 'pip3 list | grep -Ec "wideq|Flask|requests"') < 5) {
					LgLog::debug('missing pip dependancies');
						$return['state'] = 'nok';
				} else {
					$return['state'] = 'ok';
				}
			}
			return $return;
		}


/**
 * gestion du daemon LgThinq
 */
	public static function deamon_info() {
		return WideqManager::daemon_info();
	}

	public static function deamon_start($_debug = false) {
		$_debug = $_debug || ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
		$result = WideqManager::daemon_start($_debug);
		// after restart, reinit the token
		self::initToken();
		LgLog::debug('Restart daemon and reinit token');

		return $result;
	}

	public static function deamon_stop() {
		return WideqManager::daemon_stop();
	}


    /*     * *********************Méthodes d'instance************************* */

	/**
	 * Création des commandes de l'objet avec un fichier de configuration au format json
	 */
	private function createCommand($_update = false) {

		if (false === $this->getConfFilePath()) {
			event::add('jeedom::alert', [
				'level' => 'warning',
				'page' => 'lgthinq',
				'message' => __('Fichier de configuration absent ', __FILE__) . $this->getConfFilePath(),
			]);
			return;
		}
		$device = is_json(file_get_contents(dirname(__FILE__) . self::RESOURCES_PATH . $this->getConfFilePath()), []);
		if (!is_array($device) || !isset($device['commands'])) {
			LgLog::debug('Config file empty or not a json format');
			return true;
		}
		if (isset($device['name']) && !$_update) {
			$this->setName('[' . $this->getLogicalId() . ']' . $device['name']);
		}
		$this->import($device);
		sleep(1);
		event::add('jeedom::alert', [
			'level' => 'warning',
			'page' => 'lgthinq',
			'message' => '',
		]);
		LgLog::debug('Successfully created commands from config file:' . count($device));
	}

	/**
	 * le fichier de config est au format json
	 * il est dans /config/devices/[product_type].[product_model].json
	 * par défaut on peut utiliser [product_type].json si celui spécifique au model n'est pas disponible
	 */
	private function getConfFilePath() {
		if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $this->getConfiguration('fileconf'))) {
			LgLog::debug('get confFilePath from configuration '. $this->getConfiguration('fileconf'));
			return $this->getConfiguration('fileconf');
		}
		$id = $this->getConfiguration('product_type') . '.' . $this->getConfiguration('product_model') . '.json';
		if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $id)) {
			LgLog::debug('get confFilePath with specific model '. $id);
			return $id;
		}
		$id = $this->getConfiguration('product_type') . '.json';
		if (is_file(dirname(__FILE__) . self::RESOURCES_PATH . $id)) {
			LgLog::debug('get generic confFilePath for product type '. $id);
			return $id;
		}

		LgLog::warning('No json config file for device ' . $this->getConfiguration('product_type') . ' nor ' . $this->getConfiguration('product_model'));
		return false;
	}

    public function preInsert() {
        LgLog::debug("preInsert LgThinq");
    }

    public function postInsert() {
        LgLog::debug("postInsert LgThinq");
    }

    public function preSave() {
        LgLog::debug("preSave LgThinq");
    }

    public function postSave() {
		LgLog::debug("postSave LgThinq");
    }

    public function preUpdate() {

    }

    public function postUpdate() {

    }

    public function preRemove() {

    }

    public function postRemove() {

    }

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
    public static function preConfig_LgAuthUrl( $_newValue) {

		$_oldValue = config::byKey('LgAuthUrl', 'lgthinq');
		if($_newValue != $_oldValue){
			// maj jeedom token
			$json = self::initToken( $_newValue);
		}else{
			LgLog::debug('LgAuthUrl non modifié=' . $_newValue);
		}

		// try{
			// self::refreshListObjects();
		// }catch(\Throwable | \Exception $e){
			// LgLog::error(displayException($e));
		// }

		return $_newValue;
    }

    /*     * **********************Getteur Setteur*************************** */
	public function getProductType(){
		return $this->getConfiguration('product_type');
	}
	public function setProductType($_productType){
		$this->setConfiguration('product_type', $_productType);
	}

	public function getProductModel(){
		return $this->getConfiguration('product_model');
	}
	public function setProductModel($_productModel){
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

		$eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this

		switch ($this->getLogicalId()) {	//vérifie le logicalid de la commande
			case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .

				// interroger l'API cloud LG pour rafraichir l'information:
				$info = lgthinq::getApi()->mon($eqlogic->getLogicalId());
				// maj la commande ...
				$eqlogic->checkAndUpdateCmd('story', $info); // on met à jour la commande avec le LogicalId "story"  de l'eqlogic

				LgLog::debug('cmd refresh ' . $eqLogic . ' --- ' . json_encode($info));
				break;

			default:
				LgLog::debug('cmd execute ' . $this->getLogicalId() . '-' . $eqLogic);
				break;
		}

    }

    /*     * **********************Getteur Setteur*************************** */
}
