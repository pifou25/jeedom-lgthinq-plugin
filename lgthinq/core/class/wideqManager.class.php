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

// include /plugins/lgthinq/core/LgLog.class.php
include_file('core', 'LgLog', 'class', 'lgthinq');



/* ****************** TEST TO DELETE *************** */
function generateCallTrace()
{
    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = array();
   
    for ($i = 0; $i < $length; $i++)
    {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }
   
    return "\t" . implode("\n\t", $result);
}
/* ****************** TEST TO DELETE *************** */



/*
 * Lg Smart Thinq manager for the python server
 * REST API on local http://127.0.0.1:port
 * 
 */
class WideqManager {
	
	const WIDEQ_SCRIPT = 'wideqServer.py';
	const WIDEQ_DIR = '/../../3rparty/wideq/';
	
	const PYTHON = '/usr/bin/python3 ';

	private static $wideqApi = null;
	
	/**
	 * infos about the python daemon
	 * check state (nok/ok) running () python version (3.6 mini) ...
	 * add 'launchable_message' and 'log'
	 */
	public static function daemon_info() {
		$return = [];
		$state = system::ps(self::WIDEQ_SCRIPT);
		LgLog::debug('etat server wideq:' . json_encode( $state));
		$return['state'] = empty($state) ? 'nok' : 'ok';
		if(!empty($state)){
			$return['log'] = 'nb of processes='.count($state);
			if(self::$wideqApi == null){
				self::$wideqApi = lgthinq::getApi();
			}
			$return = array_merge( $return, self::$wideqApi->ping());
		}
		
		$return['port'] = config::byKey('port', 'lgthinq', 5025);
		$return['launchable'] = empty($return['port']) ? 'nok' : 'ok';
		if(count($state) > 0){
			$return = array_merge($state[0], $return);
		}
		return $return;
	}
	
	/**
	 * start daemon: the python flask script server
	 */
	public static function daemon_start($_debug = false) {

		self::daemon_stop();
		$daemon_info = self::daemon_info();
		LgLog::debug("start server wideq: $_debug ___ " . json_encode( $daemon_info) . "\n" . generateCallTrace());
		if ($daemon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}

		$cmd = system::getCmdSudo() . self::PYTHON . dirname(__FILE__) . self::WIDEQ_DIR . self::WIDEQ_SCRIPT;
		$cmd .= ' --port ' . $daemon_info['port'];
		if($_debug){
			$cmd .= ' -v ';
		}
		$cmd .= ' >> ' . log::getPathToLog('lgthinq_srv') . ' 2>&1 &';

		LgLog::info( 'Lancement démon LgThinq : ' . $cmd );
		exec($cmd);
		sleep(2);
		$i = 0;
		while ($i < 10) {
			try{
				$daemon_info = self::daemon_info();
				if ($daemon_info['state'] == 'ok') {
					break;
				}
			}catch(LgApiException $e){
				LgLog::debug("Waiting for daemon starting ($i)...");
			}

			sleep(1);
			$i++;
		}
		if ($i >= 10) {
			LgLog::error('Impossible de lancer le démon LgThinq, relancer le démon en debug et vérifiez la log', 'unableStartdaemon');
			return false;
		}
		message::removeAll('lgthinq', 'unableStartdaemon');
		LgLog::info('Démon LgThinq démarré');
	}
	
	/**
	 * stop (kill) the python script server
	 */
	public static function daemon_stop() {

		try {
			system::kill(self::WIDEQ_SCRIPT);
			
			sleep(1);
			LgLog::debug('server wideq successfully stoped!' . "\n" . generateCallTrace());
		} catch (\Exception $e) {
			LgLog::error( 'Stop Daemon LgThinq : ' . $e.getMessage());
			
		}
	}
	
}