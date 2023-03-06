<?php
namespace com\jeedom\plugins\lgthinq;

/**
 * LgThinqApi manage the Wideq Api and daemon.
 * Helper methods for LgThinq jeedom plugin
 * 
 * @author pifou25
 */
class LgThinqApi {
    
    private static $_lgApi = null;

    /**
     * Timestamp of last successfull daemon check.
     * @var float
     */
    private static $_lastCheckTime = null;

    /**
     * cache daemon state (ok/ko)
     * @var string
     */
    private static $_daemonState = null;

    /**
     * generate WideqAPI with jeedom configuration
     * refresh token if needed.
     */
    public static function getApi() {
        if (self::$_lgApi == null) {
            $port = \config::byKey('PortServerLg', 'lgthinq', 5025);
            $url = \config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
            $arr = ['port' => $port, 'url' => $url, 'debug' => \lgthinq::isDebug()];
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
        $country = \config::byKey('LgCountry', 'lgthinq');
        $lang = \config::byKey('LgLanguage', 'lgthinq');
        $api->gateway($country, $lang);
        $auth = \config::byKey('LgAuthUrl', 'lgthinq');
        LgLog::debug("refresh LG token with $auth");
        $ret = $api->token($auth);
        if(isset($ret['auth']) && $ret['auth'] == false){
            LgLog::error( __("Erreur de mise à jour du token LG!", __FILE__));
            return false;
        }
        return $api;
    }

    
    public static function dependancy_info() {
        $return = [];
        $return['log'] = LgLog::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = \jeedom::getTmpFolder(__CLASS__) . '/dependency';
        if (file_exists(\jeedom::getTmpFolder(__CLASS__) . '/dependency')) {
            $return['state'] = 'in_progress';
        } else if (empty(WideqManager::getPython())) {
            $return['state'] = 'nok';
        } else {
            $return['state'] = WideqManager::check_dependancy();
            \config::save('PythonLg', WideqManager::getPython());
        }
        return $return;
    }

    /**
     * gestion du daemon LgThinq:
     * on peut configurer
     * PortServerLg = le port - 5025 par défaut
     * UrlServerLg = l'url - http://127.0.0.1 par défaut
     */
    public static function daemon_info() {
        if(self::$_lastCheckTime !== null && time() - self::$_lastCheckTime < 10){
            // don't check every second
            LgLog::debug(__('infos Demon en cache depuis: ', __FILE__) . (time() - self::$_lastCheckTime));
            return self::$_daemonState;
        }
        $return = WideqManager::daemon_info();
        $return['pid'] = \config::byKey('PidLg', 'lgthinq');
        $return['port'] = \config::byKey('PortServerLg', 'lgthinq', 5025);
        // $return['url'] = config::byKey('UrlServerLg', 'lgthinq', 'http://127.0.0.1');
        $return['key'] = \jeedom::getApiKey();
        $return['ip'] = 'http://' . \config::byKey('internalAddr'); // jeedom internal IP
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
    public static function daemon_start($_debug = false) {
        $daemon_info = self::daemon_info();
        $daemon_info['debug'] = $_debug || \lgthinq::isDebug();
        $result = WideqManager::daemon_start($daemon_info);

        if ($result !== false) {
            // sauver le PID du daemon
            \config::save('PidLg', $result, 'lgthinq');
            LgLog::debug( __('Redémarrage du démon, id: ', __FILE__) . $result);
        }
        return $result;
    }

}