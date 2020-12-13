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

    /**
     * script pour l'inclusion / synchronisation des objets LGThinq
     */
    
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
        $msg .= $e->getMessage() . ' reinit token...';
        lgthinq::initToken();
        $lgObjects = $lgApi->ls();
    }

    if (empty($lgObjects)) {
        $msg .= 'Aucun objet détecté... authentification requise.';
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

    }
    ?>

<h2><?php printf('Synchroniser les objets LG (%s LG) et Jeedom (%s jeedom)', count($lgObjects), count($jeedomObjects)) ?></h2>

    <form class="form-horizontal" id="LgSynchronize">
        <fieldset>
            <legend>{{Liste des objets LG détectés}}</legend>
            <div class="form-group">
    <?php
    foreach ($lgObjects as $obj) {
        $checked = (isset($obj['eqLogic'])) ? '' :' checked="checked"';
        ?>
            <div class="col-lg-5">
            <?php 
            // LG device checked if not defined on jeedom
            echo <<<EOT
                <label for="{$obj['deviceId']}">
                <img src="{$obj['smallImageUrl']}" /><br/>
                <input type="checkbox" name="selected[]" id="{$obj['deviceId']}" value="{$obj['deviceId']}" $checked />
                {$obj['alias']} ( {$obj['modelNm']} ) </label>
                <div>
                <h3>Propriétés</h3>
                <p>
EOT;
                foreach($obj as $key => $value){
                    if(substr( $value, 0, 4 ) === "http"){
                        $value = "<a href=\"$value\">[download]</a>";
                    }
                    echo "<b>$key</b> : $value<br/>\n";
                }
                echo "</p></div>\n";
                
                // if not defined: list of all available LG config
                if(!empty($checked)){
            ?>
            </div>
            <div class="col-lg-2">
                <label for="lg<?= $obj['deviceId'] ?>">Selectionner Configuration :</label>
                <select id="lg<?= $obj['deviceId'] ?>" name="lg<?= $obj['deviceId'] ?>">
                    <option value="">ignore</option>
            <?php 
                foreach(LgParameters::getAllConfig() as $device){
                    printf("\t\t<option value=\"%s\">%s</option>\n", $device, $device);
                }
            ?>
                </select>
            <?php } // foreach $device ?>
            </div>
            <?php } // foreach $obj ?>
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
    $msg .= displayException($e);
    echo '<pre>' . $e . "\n\n$msg" . '</pre>';
    LgLog::error(displayException($e));
}
?>

<script>
$( function(){
    $('#bt_synchro').on('click',function(){
        $.ajax({
            type: 'POST',
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
                    bootbox.alert(data['result']);
                }else{
                    bootbox.alert({message: data['state'] + ' : ' + data['result'], level: 'danger'});;
                }
            }

        });
    });
});
</script>