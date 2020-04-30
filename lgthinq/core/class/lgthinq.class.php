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
	private static $_keysConfig = [];
	
    /*     * ***********************Methode static*************************** */

	/**
	 * generate WideqAPI with jeedom configuration
	 */
	public static function getApi(){
		$token = config::byKey('LgJeedomToken', 'lgthinq');
		if(!empty($token)){
			$headers = [WideqAPI::TOKEN_KEY . ': ' . $token];
		}else{
			$headers = [];
		}
		$port = config::byKey('PortServerLg', 'lgthinq', 5025);
		$debug = ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
		return new WideqAPI( ['port' => $port, 'debug' => $debug, 'headers' => $headers]);
	}
	
    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDaily() {

      }
     */

	
	public static function deamon_info() {
		return WideqManager::daemon_info();
	}
	
	public static function deamon_start($_debug = false) {
		$_debug = $_debug || ( log::convertLogLevel(log::getLogLevel('lgthinq')) == 'debug' );
		$result = WideqManager::daemon_start($_debug);
		// after restart, reinit the token
		$lgApi = self::getApi();
		$auth = config::byKey('LgAuthUrl', 'lgthinq');
		$json = $lgApi->token($auth);
		LgLog::debug('Restart daemon and reinit token: ' . json_encode($json));
		if(isset($json[WideqAPI::TOKEN_KEY])){
			config::save('LgJeedomToken', $json[WideqAPI::TOKEN_KEY], 'lgthinq');
		}else{
			LgLog::error('aucun jeedom token : ' . json_encode($json));
		}

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
        
    }

    public function postSave() {
        
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
    public static function postConfig_LgAuthUrl( $_value) {

		if($_value != self::$_keysConfig['LgAuthUrl']){
			// maj jeedom token
			$lgApi = self::getApi();
			$json = $lgApi->token($_value);
			if(isset($json['jeedom_token'])){
				config::save('LgJeedomToken', $json['jeedom_token'], 'lgthinq');
			}else{
				LgLog::warning('aucun jeedom token : ' . json_encode($json));
			}
		}else{
			LgLog::debug('LgAuthUrl non modifié=' . $_value);
		}


    }

    /*
     * Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
     */
    public static function preConfig_LgAuthUrl( $_value) {
		
		self::$_keysConfig['LgAuthUrl'] = config::byKey('LgAuthUrl', 'lgthinq');
		LgLog::debug('LgAuthUrl avant modif=' . self::$_keysConfig['LgAuthUrl']);
		return $_value;
		
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
