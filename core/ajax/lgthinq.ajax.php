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

    ajax::init();

	if(init('action') == 'log'){
		$log = init('log');
		LgLog::info("ajax log:$log");
		ajax::success(['result' => true]);
	}

	if(init('action') == 'ping'){
		$lgApi = lgthinq::getApi();
		ajax::success($lgApi->ping());
	}

	if (init('action') == 'getGateway') {
		$lang = init('lang');
		$country = init('country');

    if(empty($lang)){
			ajax::error('Erreur, vous devez renseigner la langue (ex: FR)', 401);
    }else if(empty($country)){
      ajax::error('Erreur, vous devez renseigner le pays (ex: fr_FR)', 401);
    }else{

  		$lgApi = lgthinq::getApi();
  		$url = $lgApi->gateway( $country, $lang);

  		LgLog::debug("call gateway $lang $country with result (" . json_encode($url) . ')');
  		if(!isset($url['url'])){
  			LgLog::error("call LgThinq gateway $lang $country fails!");
  			ajax::error('getGateway error: ' + $url['message'], 401);
  		}else{
  			ajax::success($url);
  		}
    }
	}

	if(init('action') == 'refreshToken'){
		LgLog::debug("call auth with param (" . json_encode($_POST) . ')');
		// first init gateway, then token
		$auth = init('auth');
		if(empty($auth)){
			$auth = config::byKey('LgAuthUrl', 'lgthinq');
		}
    if(empty($auth)){
			LgLog::error("refresh token: URL ne peut pas être vide ($auth)");
			ajax::error("refresh token: URL ne peut pas être vide ($auth)", 401);
		}
		$result = lgthinq::initToken($auth);

		if($result !== true){
			LgLog::error($result);
			ajax::error($result, 401);
		}else{
			ajax::success('Token success');
		}

	}

	if(init('action') == 'download'){
		$lgApi = lgthinq::getApi();
		ajax::success($lgApi->save());
	}

	if(init('action') == 'addEqLogic'){
		$id = init('id');
		$api = lgthinq::getApi();
		$objects = $api->ls();
		if(empty($objects) || empty($id)){
			ajax::error("No object or id ($object) ($id)", 401);
		}else{
			foreach($objects as $obj){
				if($obj['id'] == $id){
					LgLog::debug("add object (" . json_encode($config) . ')');
					$eq = lgthinq::CreateEqLogic($config);
					ajax::success('object added');
				}
			}
			ajax::error("object with id not found ($id)", 401);
		}
	}


    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
