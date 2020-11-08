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
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    // include /plugins/lgthinq/core/lgthinq.class.php
    include_file('core', 'lgthinq', 'class', 'lgthinq');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') == 'download') {
        header('Content-disposition: attachment; filename=file.json');
        header('Content-type: application/json');
        $lgApi = lgthinq::getApi();
        exit(json_encode($lgApi->save(), JSON_PRETTY_PRINT));
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    if (init('action') == 'log') {
        $log = init('log');
        LgLog::info("ajax log:$log");
        ajax::success(['result' => true]);
    }

    if (init('action') == 'ping') {
        $lgApi = lgthinq::getApi();
        ajax::success($lgApi->ping());
    }

    if (init('action') == 'getGateway') {
        $lang = init('lang');
        $country = init('country');

        if (empty($lang)) {
            ajax::error('Erreur, vous devez renseigner la langue (ex: FR)', 401);
        } else if (empty($country)) {
            ajax::error('Erreur, vous devez renseigner le pays (ex: fr_FR)', 401);
        } else {

            $lgApi = lgthinq::getApi();
            $url = $lgApi->gateway($country, $lang);

            LgLog::debug("call gateway $lang $country with result (" . json_encode($url) . ')');
            if (!isset($url['url'])) {
                LgLog::error("call LgThinq gateway $lang $country fails!");
                ajax::error('getGateway error: ' + $url['message'], 401);
            } else {
                ajax::success($url);
            }
        }
    }

    if (init('action') == 'synchro') {
        //ajax::error(json_encode($_POST), 401);
        $api = lgthinq::getApi();
        $objects = $api->ls();
        $selected = init('selected');
        if (empty($objects) || empty($selected)) {
            ajax::error("Aucun objet LG connecté, ou aucun sélectionné.", 401);
        } else {
            $counter = 0;
            foreach ($selected as $id) {
                $obj = init('lg' . $id);
                if (empty($obj)) {
                    ajax::error("Aucun objet $obj" . json_encode($_POST), 401);
                } else if (!isset($objects[$id])) {
                    ajax::error("Objet id=$id introuvable..." . json_encode($_POST), 401);
                } else {
                    LgLog::debug("map $id sur $obj");
                    $eq = lgthinq::CreateEqLogic($objects[$id], $obj);
                    $counter++;
                }
            }
            ajax::success("$counter objets configurés ! Rechargez la page (F5) pour voir les nouveaux objets.");
        }
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

