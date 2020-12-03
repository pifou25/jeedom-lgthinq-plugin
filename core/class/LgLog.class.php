<?php

/* This file is part of Plugin LgThinq for jeedom.
 *
 */

/**
 * this class extends jeedom core/class/log.class.php
 * and add convenient methods for this plugin
 */
class LgLog extends log {

    public static function debug($_message, $_logicalId = '', $_loggerSuffix = '') {
        parent::add("lgthinq$_loggerSuffix", 'debug', $_message, $_logicalId);
    }

    public static function info($_message, $_logicalId = '', $_loggerSuffix = '') {
        parent::add("lgthinq$_loggerSuffix", 'info', $_message, $_logicalId);
    }

    public static function warning($_message, $_logicalId = '', $_loggerSuffix = '') {
        parent::add("lgthinq$_loggerSuffix", 'warning', $_message, $_logicalId);
    }

    public static function error($_message, $_logicalId = '', $_loggerSuffix = '') {
        parent::add("lgthinq$_loggerSuffix", 'error', $_message, $_logicalId);
    }

}

class LgSystem extends system {
    
    /**
     * if command is provided, use sudo sh with -c param surround it
     * @param string $cmd
     * @return string the sudo'ed command
     */
    public static function getCmdSudo( $cmd = '') {
            if (!jeedom::isCapable('sudo')) {
                    return $cmd;
            }
            return empty($cmd) ? 'sudo ' : sprintf("sudo sh -c '%s'", addslashes($cmd));
    }

}