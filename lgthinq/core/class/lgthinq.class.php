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
			$debug = ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
			$arr = ['port' => $port, 'debug' => $debug, 'headers' => $headers];
			//LgLog::debug(json_encode($arr, JSON_PRETTY_PRINT));
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
	 * create the new object
	 */
	public static function CreateEqLogic($_config){
	
LgLog::debug('new lgthinq');	
		$eqLogic = new lgthinq();
		$eqLogic->setEqType_name('lgthinq');
		$eqLogic->setIsEnable(1);
		$eqLogic->setLogicalId($_config['id']);
		if (isset($_config['model']) && trim($_config['model']) != '') {
			$eqLogic->setName($eqLogic->getLogicalId() . ' ' . $_config['model']);
		} else {
			$eqLogic->setName('Device ' . $eqLogic->getLogicalId());
		}
		$eqLogic->setConfiguration('product_name', $_config['name']);
		$eqLogic->setConfiguration('product_type', $_config['type']);
		$eqLogic->setIsVisible(1);
LgLog::debug('before saving lgthinq: ' . serialize($eqLogic));
		$eqLogic->save();
		//$eqLogic = openzwave::byId($eqLogic->getId());
		// TODO
		//$eqLogic->createCommand(false, $_config);
LgLog::debug('return lgthinq');
		return $eqLogic;
	}
	
	/**
	 * refresh any object sensors values
	 */
	 private static function refreshData(){
		 LgLog::debug('refresh LG data');
	 }
	
	/**
	 * refresh any object sensors values
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

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        LgLog::debug("preSave $this");
    }

    public function postSave() {
		LgLog::debug("postSave $this");
        
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
        
    }

    /*     * **********************Getteur Setteur*************************** */
}
