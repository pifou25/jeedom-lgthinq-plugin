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
<form class="form-horizontal">
    <fieldset>
		<legend>{{Authentification}} Vous devez sélectionner la langue et le code pays pour vous identifier sur le portail LG Smart Thinq:</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Langage}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="LgLanguage" id="LgLanguage" placeHolder="FR" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Country}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="LgCountry" id="LgCountry">
                    <option value="value1">fr_FR</option>
                    <option value="value2">en_US</option>
                </select>
            </div>
        </div>
		<a class="btn btn-success btn-xs pull-right" id="bt_AuthLgThinq"><i class="far fa-check-circle icon-white"></i> {{Authentication}}</a>
		
  </fieldset>
    <fieldset>
		<legend>{{Server Parameters}}</legend>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Port Server Local}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="PortServerLg" placeholder="5025" />
            </div>
        </div>
  </fieldset>
</form>


<script>
$( function(){
	$('#bt_AuthLgThinq').on('click',function(){
		bootbox.confirm('{{Vous allez être redirigé vers le portail LG Smart Thinq correspondant à votre région/langue.}}', function (result) {
			if (result) {
				url = 'index.php?v=d&plugin=lgthinq&modal=auth&lang=' + $('#LgLanguage').value() + '&country=' + $('#LgCountry').value()
				$('#md_modal2').dialog({title: "{{Authentification LG Account}}"});
				$('#md_modal2').load(url).dialog('open');
			}
		});
	});
});
</script>
