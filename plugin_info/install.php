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

// include /plugins/lgthinq/core/LgLog.class.php
include_file('core', 'LgLog', 'class', 'lgthinq');

function initLgthinqParameters() {

    // initialiser le plugin avec ces valeurs
    $defaultParams = ['PortServerLg' => '5025',
        'UrlServerLg' => 'http://127.0.0.1',
        'LgLanguage' => 'fr-FR',
        'LgCountry' => 'FR',
        'WideqLibLg' => 'master'];

    foreach ($defaultParams as $key => $value) {
        if (config::byKey($key, 'lgthinq', '') == '') {
            config::save($key, $value, 'lgthinq');
        }
    }
    $dir = lgthinq::getDataPath();
    if(!is_dir($dir)){
        $dir = realpath($dir);
        if (!mkdir($dir, 0777, true))
            LgLog::error("unable to create dir '$dir'");
        else
            LgLog::debug('create dir ' . $dir);
    }
}

function lgthinq_install() {
    LgLog::info('install lgThinq plugin - set +x flag ok');
    initLgthinqParameters();
}

function lgthinq_update() {
    LgLog::info('update lgThinq plugin - set +x flag ok');
    initLgthinqParameters();
}

function lgthinq_remove() {
    LgLog::info('remove lgThinq plugin');
}
