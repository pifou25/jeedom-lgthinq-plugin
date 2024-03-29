<?php
namespace com\jeedom\plugins\lgthinq;

use \system;

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
    // script jeedom monitoring
    const JEEDOM_SCRIPT = 'jeedom.py';

    // regex version de python
    const PYTHON_VERS = '/python (\d+)\.(\d+)\.(\d+)/i';

    /**
     * répertoire du démon srv.py
     */
    private static $resourcesDir = null;

    public static function getWideqDir() {
        return self::getResourcesDir().'wideq/';
    }
    public static function getResourcesDir(){
        if (self::$resourcesDir == null) {
            self::$resourcesDir = realpath( __DIR__ . '/../../resources/').'/';
        }
        return self::$resourcesDir;        
    }

    /**
     * infos about the python daemon
     * check state (nok/ok) if running i.e. when the process exists
     * add 'launchable_message' and 'log'
     */
    public static function daemon_info() {
        $state = system::ps(self::WIDEQ_SCRIPT);
        $return = ['state' => empty($state) ? 'nok' : 'ok'];
        if (!empty($state)) {
            $return['log'] = 'nb of processes=' . count($state);
            try {
                $ping = LgThinqApi::getApi()->ping();
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
            throw new \Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $file = self::getWideqDir() . self::WIDEQ_SCRIPT;
        $cmd = "python3 $file --port {$daemon_info['port']} "
            . "--key {$daemon_info['key']} --ip {$daemon_info['ip']}";
        if (isset($daemon_info['debug']) && $daemon_info['debug']) {
            $cmd .= ' -v ';
        }
        // echo $! pour récupérer le pid process-id
        $cmd .= ' >> ' . LgLog::getPathToLog('lgthinq_srv') . ' 2>&1 & echo $!;';
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
        \message::removeAll('lgthinq', 'unableStartdaemon');
        LgLog::info("Démon LgThinq démarré (pid={$pid})");
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

    /**
     * command to refresh every detected and enabled commands.
     * jeedom.py use both jeedom RPC json API and wideq LG lib to monitoring devices.
     * -- ne marche pas --
     * @param type $ip
     * @param type $key
     */
    public static function refreshAll($ip, $key, $id = false){
        $file = self::getWideqDir() . self::JEEDOM_SCRIPT;
        $cmd = "python3 $file --ip $ip --key $key" . ($id ? " --id $id" : '');
        // echo $! pour récupérer le pid process-id
        $cmd .= ' >> ' . LgLog::getPathToLog('lgthinq_cron') . ' 2>&1 & echo $!;';
        $pid = exec(system::getCmdSudo() . " $cmd");
        LgLog::info("Refresh every commands => pid= {$pid}");
    }
}
