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

	if(init('action') == 'refreshToken'){
		LgLog::debug("call auth with param (" . json_encode($_POST) . ')');
		$lgApi = lgthinq::getApi();
		$json = $lgApi->token(init('auth'));
		if(isset($json[WideqAPI::TOKEN_KEY])){
			config::save('LgJeedomToken', $json[WideqAPI::TOKEN_KEY], 'lgthinq');
			ajax::success($json);
		}else{
			ajax::error('aucun jeedom token : ' . json_encode($json), 401);
		}
	}


    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

