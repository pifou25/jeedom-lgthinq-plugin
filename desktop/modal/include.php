<?php

use com\jeedom\plugins\lgthinq\LgThinqApi;
use com\jeedom\plugins\lgthinq\LgParameters;
use com\jeedom\plugins\lgthinq\WideqAPI;
use com\jeedom\plugins\lgthinq\LgLog;

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
    $lgApi = LgThinqApi::getApi();
    $msg = '';
    $lgObjects = $lgApi->ls();
    // $param = new LgParameters($lgApi->save());

    if (empty($lgObjects)) {
        $msg .= 'Aucun objet détecté... authentification requise.';
    } else {
        // objets deja créés dans jeedom
        $jeedomObjects = lgthinq::byType('lgthinq');
        $msg .= sprintf('Synchroniser les objets LG (%s LG) et Jeedom (%s jeedom)', count($lgObjects), count($jeedomObjects));
        foreach ($jeedomObjects as $eqLogic) {
            $id = $eqLogic->getLogicalId();
            $msg .= "\n'$id' ... ";
            // valoriser les objets deja present
            if (isset($lgObjects[$id])) {
                $lgObjects[$id]['jeedom'] = $eqLogic;
            } else {
                LgLog::info('Objet Jeedom fantôme: ' . $eqLogic->getName() . '-' .
                        $eqLogic->getProductModel() . '-' . $eqLogic->getProductType() . '-' . $eqLogic->getLogicalId());
            }
        }

    }
    if(lgthinq::isDebug()){
        $msg .= json_encode(WideqAPI::getRequests(), JSON_PRETTY_PRINT);
    }
    ?>

<h2><?php printf('Synchroniser les objets LG (%s LG) et Jeedom (%s jeedom)', count($lgObjects), count($jeedomObjects)) ?></h2>

    <form class="form-horizontal" id="LgSynchronize">
        <fieldset>
            <legend>{{Liste des objets LG détectés}}</legend>
            <div class="form-group">
    <?php
    foreach ($lgObjects as $id => $obj) {
        $checked = (isset($obj['jeedom'])) ? '' :' checked="checked"';
        $msg .= json_encode($obj, JSON_PRETTY_PRINT) ."\n";
        ?>
            <div class="col-lg-5">
            <?php 
            // LG device checked if not defined on jeedom
            echo <<<EOT
                <input type="checkbox" name="selected[$id]" id="$id" value="$id" $checked />
                <label for="$id"> {$obj['name']} ( {$obj['model']} ) </label>
EOT;
            // LG device checked if not defined on jeedom
            echo <<<EOT
                <div>
                <h4 class="toggleTouch" id="toggle$id">Propriétés [ouvrir]</h4>
                <p style="display: none;" id="ztoggle$id">
EOT;
                foreach($lgApi->info($id) as $key => $value){
                    if(is_string($value)){
                        if(substr( $value, 0, 4 ) === "http"){
                            $value = "<a href=\"$value\">[download]</a>";
                        }
                    }else if(is_object($value)){
                        $value = get_class($value);
                    }
                    echo "<b>$key</b> : $value<br/>\n";
                }
                echo "</p></div>\n";
                
                // if not defined: list of all available LG config
                if(!empty($checked)){
            ?>
                <label for="lg<?= $id ?>">Selectionner Configuration :</label>
                <select id="lg<?= $id ?>" name="lg<?= $id ?>">
                    <option value="<?= lgthinq::DEFAULT_VALUE ?>">Automatique</option>
                    <option value="">Ignorer</option>
            <?php 
                foreach(LgParameters::getAllConfig() as $device){
                    printf("\t\t<option value=\"%s\">%s</option>\n", $device, $device);
                }
            ?>
                </select>
            <?php } // empty($checked) ?>
            </div>
            <?php } // foreach $obj ?>
            </div>
            <div class="col-lg-2">
                <a class="btn btn-success btn-xs" id="bt_synchro"><i class="far fa-check-circle icon-white"></i> {{Synchroniser}}</a>
            </div>

        </fieldset>
    </form>
<!--
    <p id="lgLog"><pre>
    <?php echo $msg; ?></pre>
    </p>
-->
    <?php
} catch (\Exception $e) {
    $msg .= displayException($e);
    echo '<pre>' . $e . "\n\n$msg" . '</pre>';
    LgLog::error(displayException($e));
}
?>

<script>
$( function(){
    //Hide/show properties list
    $('.toggleTouch').on('click', function() {
        var id = $(this).attr('id');
        $('#z' + id).toggle();
    });

    $('#bt_synchro').on('click',function(){
        $.showLoading();
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
            },
            complete: function(){
               $.hideLoading();
            }
        });
    });
});
</script>