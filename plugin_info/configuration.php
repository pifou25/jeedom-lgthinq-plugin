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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal" id="LgThinqForm">
    <fieldset>
		<legend>{{Authentification}} Step 1: Sélectionner la langue et le code pays</legend>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Country}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="LgCountry" id="LgCountry" placeholder="FR" />
            </div>
            <label class="col-lg-3 control-label">{{Language}}</label>
            <div class="col-lg-2">
                <input type="text" class="configKey form-control" data-l1key="LgLanguage" id="LgLanguage" list="LgLangList">
				<datalist id="LgLangList">
                    <option>fr-FR</option>
                    <option>fr-CA</option>
                    <option>en-US</option>
                    <option>en-UK</option>
                </datalist>
            </div>
            <div class="col-lg-2">
				<a class="btn btn-success btn-xs" id="bt_AuthLgThinq"><i class="far fa-check-circle icon-white"></i> {{Gateway}}</a>
				<input type="hidden" class="configKey form-control" data-l1key="LgGateway" id="LgGateway" placeholder="url pour login LG" disabled />
            </div>
        </div>
        <div class="form-group">
		<legend>{{Step 2: Authentification sur le portail LG Account:}}</legend>
            <label class="col-lg-3 control-label">{{Cliquez ici pour ouvrir LG Account portal}}</label>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="bt_gateway" target="_blank"><i class="far fa-check-circle icon-white"></i> {{LG Account Login}}</a>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Copiez / collez l'URL de redirection LG ici:}}</label>
            <div class="col-lg-6">
                <input class="configKey form-control" data-l1key="LgAuthUrl" id="LgAuthUrl" placeholder="url avec un token ..." />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Refresh Token :}}</label>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="bt_refreshToken"><i class="far fa-check-circle icon-white"></i> {{Refresh Token}}</a>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Jeedom Token}}</label>
            <div class="col-lg-6">
                <input class="configKey form-control" data-l1key="LgJeedomToken" id="LgJeedomToken" disabled />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Ping du serveur plugin :}}</label>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="bt_pingLgthinq"><i class="far fa-check-circle icon-white"></i> {{Ping ?}}</a>
            </div>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Download Configuration du Démon:}}</label>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="lg_DownloadLgthinq"><i class="far fa-check-circle icon-white"></i> {{Download}}</a>
            </div>
        </div>
		
  </fieldset>
    <fieldset>
		<legend>{{Server Parameters}}</legend>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Port Server Local}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="PortServerLg" placeholder="5025" />
            </div>
            <label class="col-lg-3 control-label">{{URL Server Local}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="UrlServerLg" placeholder="http://127.0.0.1" />
            </div>
        </div>
  </fieldset>
</form>

<div id='divAjaxAlert' style="display: none;"></div>

<script>
$( function(){
	$('#bt_AuthLgThinq').on('click',function(){
		// validate form
		var regPays = /^[A-Z]{2}$/;
		var regLang = /^[a-z]{2}-[A-Z]{2}$/;
		$('#LgCountry').val( $('#LgCountry').val().toUpperCase() );
		if(!regPays.test($('#LgCountry').val())){
			$('#divAjaxAlert').showAlert({message: 'Le Pays doit être 2 lettres! (' + $('#LgCountry').val() + ')', level: 'info'});
		}else if(!regLang.test( $('#LgLanguage').val() )){
			$('#divAjaxAlert').showAlert({message: 'La langue doit être au une combinaison de langue-pays (2 lettres minuscule)-(2 MAJUSCULES) (' + $('#LgLanguage').val() + ')', level: 'info'});
		}else{
			$('#divAjaxAlert').hide();
			/**
			 * get URL gateway with lang and country code
			 */
			$.ajax({
				type: 'POST',
				url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php',
				data: {
					action: 'getGateway',
					lang: $('#LgLanguage').val(),
					country: $('#LgCountry').val(),
				},
				dataType: 'json',
				global: false,
				error: function (request, status, error) {
					handleAjaxError(request, status, error, $('#divAjaxAlert'));
				},
				success: function (data, textStatus) {
					if(data['state']=='ok'){
						$('#LgGateway').val( data['result']['url']);
						$('#bt_gateway').attr('href', data['result']['url']);
						$('#LgAuthUrl').focus();
						var win = window.open(data['result']['url'], '_blank');
						if (win) {
							//Browser has allowed it to be opened
							win.focus();
						} else {
							//Browser has blocked it
							$('#divAjaxAlert').showAlert({message: 'popup bloquée, cliquez sur le lien "Lg Account Login" pour vous identifier sur le Cloud LG, puis copiez l\'URL', level: 'info'});
						}
						
					}else{
						$('#divAjaxAlert').showAlert({message: data['state'] + ' : ' + data['result'], level: 'danger'});
					}
				}
			});
		}
	});

 	$('#bt_refreshToken').on('click',function(){
		$('#divAjaxAlert').hide();
		$.post({
			url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php',
			data: {'action': 'refreshToken', 'auth': $('#LgAuthUrl').val()},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				handleAjaxError(request, status, error, $('#divAjaxAlert'));
			},
			success: function (data, textStatus) {
				if(data['state']=='ok'){
					$('#LgJeedomToken').val( data['result']['jeedom_token']);
					console.log(data['result']);
				}else{
					$('#divAjaxAlert').showAlert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
				}
			}
			
		});
	});


 	$('#bt_pingLgthinq').on('click',function(){
		$('#divAjaxAlert').hide();
		$.post({
			url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php',
			data: {'action': 'ping'},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				handleAjaxError(request, status, error, $('#divAjaxAlert'));
			},
			success: function (data, textStatus) {
				if(data['state']=='ok'){
					var date = new Date(Number.parseFloat(data['result']['starting']) * 1000);
					bootbox.alert('LgThinq plugin server ok, running since ' + date + ', token config is ' + data['result']['jeedom_token']);
					console.log(data['result']);
				}else{
					$('#divAjaxAlert').showAlert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
				}
			}
			
		});
	});


 	$('#lg_DownloadLgthinq').on('click',function(){
		$('#divAjaxAlert').hide();
		$.post({
			url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php',
			data: {'action': 'download'},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				handleAjaxError(request, status, error, $('#divAjaxAlert'));
			},
			success: function (data, textStatus) {
				if(data['state']=='ok'){
					console.log(data['result']);
					bootbox.alert('message is: ' + data['result']);
				}else{
					$('#divAjaxAlert').showAlert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
				}
			}
			
		});
	});


});

</script>
