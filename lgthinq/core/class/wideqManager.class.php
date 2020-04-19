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

require_once __DIR__  . '/../../../../core/php/core.inc.php';


/*
 * Lg Smart Thinq manager for the python server
 * REST API on local http://127.0.0.1:port
 * 
 */

class WideqManager {
	
	const WIDEQ_SCRIPT = 'wideqServer.py');
	const WIDEQ_DIR = '/../../3rdparty/wideq/');
	
	const PYTHON = '/usr/bin/python3 ');

	/**
	 * infos about the python daemon
	 * check state (nok/ok) running () python version (3.6 mini) ...
	 */
	public static function daemon_info() {
		$return = [];
		$return['state'] = 'ok';
		$return['launchable'] = 'ok';
		$return['port'] = config::byKey('port', 'lgthinq', 5025);

		return $return;
	}
	
	/**
	 * start daemon: the python flask script server
	 */
	public static function daemon_start($_debug = false) {
		self::daemon_stop();
		$daemon_info = self::daemon_info();
		if ($daemon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$port = config::byKey('port', 'lgthinq', 5025);

		$cmd = system::getCmdSudo() . PYTHON . dirname(__FILE__) . WIDEQ_DIR . WIDEQ_SCRIPT;
		$cmd .= ' --port ' . $port;
		if($_debug){
			$cmd .= ' -v ';
		}
		
		log::add('lgthinq', 'info', 'Lancement démon LgThinq : ' . $cmd);
		exec($cmd . ' >> ' . log::getPathToLog('lgthinq') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$daemon_info = self::daemon_info();
			if ($daemon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('lgthinq', 'error', 'Impossible de lancer le démon LgThinq, relancer le démon en debug et vérifiez la log', 'unableStartdaemon');
			return false;
		}
		message::removeAll('lgthinq', 'unableStartdaemon');
		log::add('lgthinq', 'info', 'Démon LgThinq lancé');
	}
	
	/**
	 * stop (kill) the python script server
	 */
	public static function daemon_stop() {
		try {
			system::kill(WIDEQ_SCRIPT);
			
			sleep(1);
		} catch (\Exception $e) {
			log::add('lgthinq', 'error', 'Stop Daemon LgThinq : ' . $e.getMessage());
			
		}
	}
	
}