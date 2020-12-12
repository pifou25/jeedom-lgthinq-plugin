<?php

// Mock classes

class LgLog  {

    public static function debug($_message, $_logicalId = '', $_loggerSuffix = '') {
		print "[DEBUG] $_loggerSuffix $_logicalId $_message";
    }

    public static function info($_message, $_logicalId = '', $_loggerSuffix = '') {
		print "[INFO] $_loggerSuffix $_logicalId $_message";
    }

    public static function warning($_message, $_logicalId = '', $_loggerSuffix = '') {
		print "[WARN] $_loggerSuffix $_logicalId $_message";
    }

    public static function error($_message, $_logicalId = '', $_loggerSuffix = '') {
		print "[ERROR] $_loggerSuffix $_logicalId $_message";
    }

}
