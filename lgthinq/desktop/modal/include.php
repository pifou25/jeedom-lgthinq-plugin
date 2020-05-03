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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

// include /plugins/lgthinq/core/WideqAPI.class.php
include_file('core', 'lgthinq', 'class', 'lgthinq');

$api = lgthinq::getApi();
$objects = $api->ls();
$msg = json_encode($objects, JSON_PRETTY_PRINT);
$msg .= "\n".json_encode(WideqAPI::$requests, JSON_PRETTY_PRINT);
$msg .= "\n". serialize($api);

?>

<h4>{{Inclure un nouvel objet}}</h4>

<form class="form-horizontal">
    <fieldset>
		<legend>{{Liste des objets détectés}}</legend>
        <div class="form-group">
<?php foreach($objects as $obj){ ?>
            <div class="col-lg-4">
<?php echo <<<EOT
			<input type="checkbox" name="selected[]" id="{$obj['id']}" value="{$obj['id']}" />
			<label for="{$obj['id']}"> {$obj['name']} ( {$obj['model']} ) </for>
EOT;
?>
            </div>
<?php 	} ?>
        </div>

        <div class="form-group">
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="lg_AddLgthinq"><i class="far fa-check-circle icon-white"></i> {{Inclure}}</a>
            </div>
        </div>

  </fieldset>
</form>

<p id="lgLog"><pre>
<?php echo $msg; ?></pre>
</p>

<script>
$( function(){
	$('#lg_AddLgthinq').on('click',function(){
		console.log('addCmdToTable');
		$.post({
			url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php',
			data: {'action': 'addEqLogic', 'id': $('input[type=checkbox]').val()},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				handleAjaxError(request, status, error, $('#divAjaxAlert'));
			},
			success: function (data, textStatus) {
				if(data['state']=='ok'){
					console.log(data['result']);
				}else{
					$('#divAjaxAlert').showAlert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
				}
			}
			
		});
	});
});

</script>
