<?php
require_once(APPPATH . 'libraries/KLogger.php');
require_once(APPPATH . 'libraries/error_handler.php');

const EXCEPTION_SEVERITY = 7;

if (!defined('log_helper')) {
    define('log_helper', true);

    function info($file, $message)
    {
        $CI =& get_instance();
        $logger = KLogger::instance($CI->orion_config['LOG_DIRECTORY'], 1);
        $file_segments = explode('/', $file);
        $logger->logInfo(end($file_segments) . ' - ' . $message);
    }

    function debug($file, $message)
    {
        $CI =& get_instance();
        $logger = KLogger::instance($CI->orion_config['LOG_DIRECTORY'], 1);
        $file_segments = explode('/', $file);
        $logger->logDebug(end($file_segments) . ' - ' . $message);
    }

    function warn($file, $message)
    {
        $CI =& get_instance();
        $logger = KLogger::instance($CI->orion_config['LOG_DIRECTORY'], 1);
        $file_segments = explode('/', $file);
        $logger->logWarn(end($file_segments) . ' - ' . $message);
    }

    function error($file, $message)
    {

        $CI =& get_instance();
        $logger = KLogger::instance($CI->orion_config['LOG_DIRECTORY'], 1);
        $file_segments = explode('/', $file);

        try {
            throw new Exception();
        } catch (Exception $e) {
            $error_message = (end($file_segments) . ' - ' . $message);
            $stack_trace = format_stack_trace($error_message, $e);
            $logger->logError($stack_trace);
        }
    }

    function format_stack_trace($error_message, $e)
    {
        return $error_message . "\n\t" . str_replace("\n", "\n\t", $e->getTraceAsString());
    }

    function fatal($file, $message)
    {
        $CI =& get_instance();
        $logger = KLogger::instance($CI->orion_config['LOG_DIRECTORY'], 1);
        $file_segments = explode('/', $file);
        $logger->logFatal(end($file_segments) . ' - ' . $message);
    }

    function exception($file, $e) {
        handle_error(EXCEPTION_SEVERITY, $e->getMessage(), $e->getFile(), $e->getLine());
        error($file, $e->getTraceAsString());
    }

}
