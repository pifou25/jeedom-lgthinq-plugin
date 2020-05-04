<?php

/* This file is part of Plugin openzwave for jeedom.
*
* Plugin openzwave for jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Plugin openzwave for jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Plugin openzwave for jeedom. If not, see <http://www.gnu.org/licenses/>.
*/


/**
 * this class extends jeedom core/class/log.class.php
 * and add convenient methods for this plugin
 */
class LgLog extends log {

	public static function debug($_message, $_logicalId = '', $_loggerSuffix = ''){
		parent::add("lgthinq$_loggerSuffix", 'debug', $_message, $_logicalId);
	}

	public static function info($_message, $_logicalId = '', $_loggerSuffix = ''){
		parent::add("lgthinq$_loggerSuffix", 'info', $_message, $_logicalId);
	}

	public static function warning($_message, $_logicalId = '', $_loggerSuffix = ''){
		parent::add("lgthinq$_loggerSuffix", 'warning', $_message, $_logicalId);
	}

	public static function error($_message, $_logicalId = '', $_loggerSuffix = ''){
		parent::add("lgthinq$_loggerSuffix", 'error', $_message, $_logicalId);
	}
	
}