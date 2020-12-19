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

// include /plugins/lgthinq/core/LgLog.class.php

/*
 * Lg Smart Thinq manager for the python Flask server
 * This server require wideq lib.
 * REST API on local http://127.0.0.1:port
 *
 */
class WideqManager {

    // script daemon python
    const WIDEQ_SCRIPT = 'srv.py';

    // regex version de python
    const PYTHON_VERS = '/python (\d+)\.(\d+)\.(\d+)/i';

    /**
     * object WideqAPI.class.php
     */
    private static $wideqApi = null;

    /**
     * répertoire du démon srv.py
     */
    private static $resourcesDir = null;

    public static function getWideqDir() {
        return self::getResourcesDir().'daemon/';
    }
    public static function getResourcesDir(){
        if (self::$resourcesDir == null) {
            self::$resourcesDir = realpath( __DIR__ . '/../../resources/');
        }
        return self::$resourcesDir;        
    }

    private static function checkPythonVersion($python){
        $subject = shell_exec("$python --version");
        if (preg_match(self::PYTHON_VERS, $subject, $matches)){
            return intval($matches[1]) >= 3 && intval($matches[2]) >= 6;
        }else{
            return false;
        }
    }
    
    /**
     * chemin de l'alias python3.7
     * généré par l'install_apt.sh
     */
    private static $pythonBash = null;

    public static function getPython() {
        if (self::$pythonBash == null) {
            // check default version
            if(self::checkPythonVersion('python3')){
                LgLog::debug('python3 alias detected');
                self::$pythonBash = 'python3';
            }else if(self::checkPythonVersion('python3.7')){
                LgLog::debug('python3.7 alias detected');
                self::$pythonBash = 'python3.7';
            }else if(self::checkPythonVersion('python')){
                LgLog::debug('python alias detected');
                self::$pythonBash = 'python';
            }else{
                
                // python.cmd generated by the install_apt script
                self::$pythonBash = file_get_contents(self::getWideqDir() . 'python.cmd');

            }
        }
        return self::$pythonBash;
    }

    /**
     * check python dependancies for Wideq Lib & Flask server
     * @return string 'ok' or 'nok'
     */
    public static function check_dependancy(){
        $deps = shell_exec('pip3 list | grep -Ec "Flask|requests"');
        if ($deps < 4) {
            LgLog::debug("missing pip dependancies ($deps)");
            return 'nok';
        } else {
            return 'ok';
        }
    }
    /**
     * infos about the python daemon
     * check state (nok/ok) if running i.e. when the process exists
     * add 'launchable_message' and 'log'
     */
    public static function daemon_info() {
        $return = [];
        $state = system::ps(self::WIDEQ_SCRIPT);
        $return['state'] = empty($state) ? 'nok' : 'ok';
        if (!empty($state)) {
            $return['log'] = 'nb of processes=' . count($state);
            if (self::$wideqApi == null) {
                self::$wideqApi = lgthinq::getApi();
            }
            try {
                $ping = self::$wideqApi->ping();
                $return = array_merge($return, $ping);
            } catch (\Exception $e) {
                LgLog::error("ping (err {$e->getCode()}): {$e->getMessage()}");
                $return['state'] = 'nok';
            }
            return array_merge($state[0], $return);
        }else{
            LgLog::debug('etat server wideq KO:' . json_encode($state));
            return ['state' => 'nok'];
        }
    }

    /**
     * start daemon: the python flask script server
     */
    public static function daemon_start($daemon_info = []) {

        self::daemon_stop( isset($daemon_info['pid']) ? $daemon_info['pid'] : false);
        LgLog::debug("start server wideq: ___ " . json_encode($daemon_info));
        if ($daemon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $file = self::getWideqDir() . 'wideq/' . self::WIDEQ_SCRIPT;
        $cmd = self::getPython()
            . " $file --port {$daemon_info['port']} "
            . "--key {$daemon_info['key']} --ip {$daemon_info['ip']}";
        if (isset($daemon_info['debug']) && $daemon_info['debug']) {
            $cmd .= ' -v ';
        }
        // echo $! pour récupérer le pid process-id
        $cmd .= ' >> ' . log::getPathToLog('lgthinq_srv') . ' 2>&1 & echo $!;';
        $pid = exec(system::getCmdSudo() . " $cmd");
        LgLog::info("Lancement démon LgThinq : $cmd => pid= {$pid}");

        sleep(3);
        $i = 0;
        while ($i < 3) {
            try {
                $daemon_info = self::daemon_info();
                if ($daemon_info['state'] == 'ok') {
                    break;
                }
            } catch (LgApiException $e) {
                LgLog::debug("Waiting for daemon starting ($i)...(error {$e->getMessage()})");
            }
            LgLog::debug("Waiting for daemon starting ($i)...");

            sleep(2);
            $i++;
        }
        if ($i >= 3) {
            LgLog::error('Impossible de lancer le démon LgThinq, relancer le démon en debug et vérifiez la log', 'unableStartdaemon');
            return false;
        }
        message::removeAll('lgthinq', 'unableStartdaemon');
        LgLog::info('Démon LgThinq démarré');
        return $pid;
    }

    /**
     * stop (kill) the python script server
     */
    public static function daemon_stop($pid = false) {

        try {
            if ($pid !== false) {
                system::kill($pid);
            } else {
                LgLog::warning('no PID; kill the ' . self::WIDEQ_SCRIPT);
                system::kill(self::WIDEQ_SCRIPT);
            }

            sleep(1);
            LgLog::debug('server wideq successfully stoped!');
        } catch (\Exception $e) {
            LgLog::error('Stop Daemon LgThinq : ' . $e->getMessage());
        }
    }

}
