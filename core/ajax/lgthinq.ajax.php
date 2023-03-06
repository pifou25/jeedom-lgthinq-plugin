<?php

use com\jeedom\plugins\lgthinq\LgParameters;
use com\jeedom\plugins\lgthinq\LgThinqApi;
use com\jeedom\plugins\lgthinq\LgLog;

try {
    require_once __DIR__ . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    // include /plugins/lgthinq/core/lgthinq.class.php
    include_file('core', 'lgthinq', 'class', 'lgthinq');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') == 'download') {
        $dataPath = realpath(LgParameters::getDataPath());
        // save json config file
        $txt = json_encode(LgThinqApi::getApi()->save(), JSON_PRETTY_PRINT);
        file_put_contents($dataPath."/jeedom/state.json", $txt);
        // save pip context
        $ret = exec(system::getCmdSudo() . "pip freeze > $dataPath/jeedom/freeze.txt", $output, $result);
        // and zip all config
        $msg = LgParameters::zipConfig(["$dataPath/lg", "$dataPath/jeedom", "$dataPath/lang"], 
        $dataPath . '/lgthinq.zip');
        LgLog::info($msg);
        // ajax::success($msg);
        die();
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    if (init('action') == 'log') {
        $log = init('log');
        LgLog::info("ajax log:$log");
        ajax::success($log);
    }

    if (init('action') == 'ping') {
        $lgApi = LgThinqApi::getApi();
        ajax::success($lgApi->ping());
    }

    if(init('action') == 'renew'){
        $lgApi = LgThinqApi::renewApi();
        if(!isset($lgApi) || $lgApi === null){
            ajax::error('Erreur, serveur local non disponible, '.
                    'vérifiez les paramètres et relancez en debug.', 401);
        }else if($lgApi === false){
            ajax::error('Erreur, authentification LG incorrecte.', 401);
        }else{
            ajax::success('Serveur up et authentification LG OK.');
        }
    }
    
    if (init('action') == 'getGateway') {
        $lang = init('lang');
        $country = init('country');

        if (empty($lang)) {
            ajax::error('Erreur, vous devez renseigner la langue (ex: FR)', 401);
        } else if (empty($country)) {
            ajax::error('Erreur, vous devez renseigner le pays (ex: fr-FR)', 401);
        } else {

            $lgApi = LgThinqApi::getApi();
            $url = $lgApi->gateway($country, $lang);

            LgLog::debug("call gateway $lang $country with result (" . json_encode($url) . ')');
            if (!isset($url['url'])) {
                LgLog::error("call LgThinq gateway $lang $country fails!");
                ajax::error('getGateway error: ' . $url['message'], 401);
            } else {
                ajax::success($url);
            }
        }
    }

    if (init('action') == 'synchro') {
        
        LgLog::debug('synchro lgthinq ajax request:' . json_encode($_POST));
        $configs = init('configs');
        $api = LgThinqApi::getApi();
        $objects = $api->ls();
        $selected = init('selected');
        $msg = '';
        $_save = $api->save();
        $_params = new LgParameters($_save);
        if (empty($objects) || empty($selected)) {
            ajax::error("Aucun objet LG connecté, ou aucun sélectionné.", 401);
        } else {
            $counter = 0;
            foreach ($selected as $id => $value) {
                $config = init('lg' . $id);
                foreach($_params->getDevices() as $id => $dev){
                    file_put_contents( "copy.dev.$id.log", print_r(LgParameters::getConfigInfos($dev), true));
                }
                
                if (empty($objects[$id]) || empty($config)) {
                    $msg .= "Objet id=$id ignoré ($config).\n";
                } else {
                    if($config == lgthinq::DEFAULT_VALUE){
                        $config = $api->info($id);
                    }
                    LgLog::debug("map $id sur $value, nb of infos = " . count($config));
                    // $value est ignoré, toujours la même config appliquée
                    $eq = lgthinq::CreateEqLogic($objects[$id], $config);
                    $counter++;
                }
            }
            ajax::success("$counter objets configurés ! Rechargez la page (F5) pour voir les nouveaux objets.\n $msg");
        }
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    $message = 'Ajax fatal error:' . displayException($e);
    if (!DEBUG && lgthinq::isDebug()) {
            $message .= '<a class="pull-right bt_errorShowTrace cursor">Show traces</a>';
            $message .= '<br/><pre class="pre_errorTrace" style="display : none;">' . print_r($e->getTrace(), true) . '</pre>';
    }
    LgLog::error($message);
    ajax::error($message, $e->getCode());
}

