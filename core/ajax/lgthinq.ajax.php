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
        file_put_contents($dataPath."/jeedom/state.json", lgthinq::getApi()->save());
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
        $lgApi = lgthinq::getApi();
        ajax::success($lgApi->ping());
    }

    if(init('action') == 'renew'){
        $api = lgthinq::renewApi();
        if(!isset($api['auto'])){
            ajax::error('Erreur, serveur local non disponible, '.
                    'vérifiez les paramètres et relancez en debug.', 401);
        }else if($api['auto'] == false){
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

            $lgApi = lgthinq::getApi();
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
        $api = lgthinq::getApi();
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

