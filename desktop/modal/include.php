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

try {

    // include /plugins/lgthinq/core/WideqAPI.class.php
    include_file('core', 'lgthinq', 'class', 'lgthinq');

    // include /plugins/lgthinq/core/LgParameters.class.php
    include_file('core', 'LgParameters', 'class', 'lgthinq');

    // lister les objets connectes et synchroniser
    $lgApi = lgthinq::getApi();
    $msg = '';
    try {
        $lgObjects = $lgApi->ls();
    } catch (LgApiException $e) {
        $msg .= $e->getMessage();
        lgthinq::initToken();
        $lgObjects = $lgApi->ls();
    }

    if (empty($lgObjects)) {
        $msg .= 'No object found... auth required.';
    } else {

        // objets deja créés dans jeedom
        $jeedomObjects = lgthinq::byType('lgthinq');
        $msg .= sprintf('Synchroniser les objets LG (%s LG) et Jeedom (%s jeedom)', count($lgObjects), count($jeedomObjects));
        foreach ($jeedomObjects as $eqLogic) {
            $eqId = $eqLogic->getLogicalId();
            $msg .= "\n'$eqId' ... ";
            // valoriser les objets deja present
            if (isset($lgObjects[$eqId])) {
                $lgObjects[$eqLogic->getLogicalId()]['eqLogic'] = $eqLogic;
            } else {
                LgLog::info('Objet Jeedom fantôme: ' . $eqLogic->getName() . '-' .
                        $eqLogic->getProductModel() . '-' . $eqLogic->getProductType() . '-' . $eqLogic->getLogicalId());
            }
        }
        
        $param = new LgParameters(lgthinq::getApi()->save());
        $devices = array_keys(LgParameters::getDevices());
        if(lgthinq::isDebug()){
            $msg .= json_encode($devices, JSON_PRETTY_PRINT);
        }

        // creer les nouveaux objets decouverts
//        $nbCreated = 0;
//        $created = [];
//        foreach ($lgObjects as &$lgObj) {
//            if (!isset($lgObj['eqLogic'])) {
//                LgLog::debug('create object with ' . json_encode($lgObj));
//                // create any missing object
//                $eqLogic = lgthinq::CreateEqLogic($lgObj);
//                if ($eqLogic !== null) {
//                    $nbCreated++;
//                    $lgObj['eqLogic'] = $eqLogic;
//                    $lgObj['created'] = true;
//                    $created[] = $lgObj['id'];
//                }
//            }
//        }
//
//        $msg .= ", ($nbCreated objets créés)\n";
//
//        if (!empty($created)) {
//            foreach ($created as $id) {
//                $json = $lgApi->mon($id);
//                $msg .= json_encode($json, JSON_PRETTY_PRINT);
//                $save = $lgApi->save();
//
//                if (is_array($save) && isset($save['config']['model_info'])) {
//                    $count = is_array($save['config']['model_info']) ? count($save['config']['model_info']) : 'N/A';
//                    $msg .= "\n\t(infos $count)\n" . json_encode($save['config']['model_info'], JSON_PRETTY_PRINT);
//                } else {
//                    $msg .= json_encode($save, JSON_PRETTY_PRINT);
//                }
//            }
//        }

        // $msg .= json_encode($lgObjects, JSON_PRETTY_PRINT);
        // $msg .= "\n".json_encode(WideqAPI::$requests, JSON_PRETTY_PRINT);
    }
    ?>

    <h4>{{Synchroniser}}</h4>

    <form class="form-horizontal" id="LgSynchronize">
        <fieldset>
            <legend>{{Liste des objets détectés}}</legend>
            <div class="form-group">
    <?php
    foreach ($lgObjects as $obj) {
        $checked = (isset($obj['eqLogic'])) ? '' :' checked="checked"';
        ?>
            <div class="col-lg-3">
            <?php 
            // LG device checked if not defined on jeedom
            echo <<<EOT
                <input type="checkbox" name="selected[]" id="{$obj['id']}" value="{$obj['id']}" $checked />
                <label for="{$obj['id']}"> {$obj['name']} ( {$obj['model']} ) </for>
EOT;
                // if not defined: list of all available LG config
                if(!empty($checked)){
            ?>
            </div>
            <div class="col-lg-3">
            <?php 
                sprintf("<select id=\"lg%s\" name=\"lg%s\">", $obj['id'], $obj['id']);
                foreach($devices as $device){
                    sprintf("\t\t<option value=\"%s\">%s</option>\n", $device, $device);
                }
                echo '</select>';
                }
            ?>
            </div>
            <?php } ?>
            </div>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="bt_synchro" target="_blank"><i class="far fa-check-circle icon-white"></i> {{Enregistrer}}</a>
            </div>

        </fieldset>
    </form>

    <p id="lgLog"><pre>
    <?php echo $msg; ?></pre>
    </p>
    <?php
} catch (\Exception $e) {
    LgLog::error(displayException($e));
    $msg .= displayException($e);
    echo $msg;
}
?>

<script>
$( function(){
    $('#bt_synchro').on('click',function(){
        $.post({
            url: 'plugins/lgthinq/core/ajax/lgthinq.ajax.php?action=synchro',
            data: $('#LgSynchronize').serialize(),
            dataType: 'json',
            global: false,
            error: function (request, status, error) {
                bootbox.alert('error: ' + request.responseText + ' - ' + error + '(' + status +')');
            },
            success: function (data, textStatus) {
                if(data['state'] === 'ok'){
                    console.log(data['result']);
                    bootbox.alert('message is: ' + data['result']);
                }else{
                    bootbox.alert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
                }
            }

        });
    });
});
</script>