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


$msg = 'Hello... ';

try{

// include /plugins/lgthinq/core/WideqAPI.class.php
include_file('core', 'lgthinq', 'class', 'lgthinq');

	
	$lgApi = lgthinq::getApi();
	$lgObjects = $lgApi->ls();
	if(!is_array($lgObjects) || !isset($lgObjects[0]['id'])) 
		$msg = 'No object found... ';

	$jeedomObjects = lgthinq::byType('lgthinq');
	LgLog::debug(sprintf('refresh LG objects (%s LG) (%s jeedom)', count($lgObjects), count($jeedomObjects)));
	foreach($lgObjects as $lgObj){
	 
		 $found = false;
		 $eqLogic = null;
		 foreach($jeedomObjects as $eqLogic){
			 if($eqLogic->getLogicalId() == $lhObj['id']){
				 $found = true;
				 continue;
			 }
		 }
			 
		 if(!$found){
			 // create any missing object
			 LgLog::debug('create object with ' . json_encode($lgObj));
			 $json =  ["id" => "33d29e50-7196-11e7-a90d-b4e62a6453b5",
					 "model" => "1REB1GLPX1___",
					 "name" => "R\u00e9frig\u00e9rateur",
					 "type" => "REFRIGERATOR"
			 ];

			 LgLog::debug('create object FOR TEST with ' . json_encode($json));
			 $eqLogic = lgthinq::CreateEqLogic($lgObj);
		 }
		 
		 LgLog::debug(serialize($eqLogic));
	}


	$msg .= json_encode($lgObjects, JSON_PRETTY_PRINT);
	$msg .= "\n".json_encode(WideqAPI::$requests, JSON_PRETTY_PRINT);
	$msg .= "\n". serialize($lgApi);

?>

<h4>{{Synchroniser}}</h4>

<form class="form-horizontal">
    <fieldset>
		<legend>{{Liste des objets détectés}}</legend>
        <div class="form-group">
<?php foreach($lgObjects as $obj){ ?>
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

<?php

}catch(\Throwable | \Exception $e){
	LgLog::error(displayException($e));
	$msg .= displayException($e);
}
