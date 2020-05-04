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
            <label class="col-lg-4 control-label">{{LG Account Url}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="LgObject" id="LgObject">
				<?php foreach($objects as $obj){
					printf( '<option value="%s">%s</option>\n', $obj[0], $obj[1]);
				} ?>
                </select>

            </div>
        </div>
  </fieldset>
</form>

<p id="lgLog"><pre>
<?php echo $msg; ?></pre>
</p>

<script>
$( function(){
	$('#LgThinqAuthentication').on('load',function(){
		$('#lgLog').append( $('#LgThinqAuthentication').attr('src') + "\n");
		$('#LgAccountUrl').val( $('#LgThinqAuthentication').attr('src') );
	});
});

</script>
