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

function lgthinq_install() {
        $file = WideqManager::getWideqDir() . WideqManager::WIDEQ_LAUNCHER
                . ' ' . WideqManager::getWideqDir() . 'check.sh ';
    exec(system::getCmdSudo() . " chmod +x $file");
    LgLog::info('install lgThinq plugin - set +x flag ok');
}

function lgthinq_update() {
        $file = WideqManager::getWideqDir() . WideqManager::WIDEQ_LAUNCHER
                . ' ' . WideqManager::getWideqDir() . 'check.sh ';
    exec(system::getCmdSudo() . " chmod +x $file");
    LgLog::info('update lgThinq plugin - set +x flag ok');
}

function lgthinq_remove() {
    LgLog::info('remove lgThinq plugin');
}
