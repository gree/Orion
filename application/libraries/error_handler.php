<?php

function handle_error($severity, $message, $filepath, $line)
{	
	 // We don't bother with "strict" notices since they will fill up
	 // the log file with information that isn't normally very
	 // helpful.  For example, if you are running PHP 5 and you
	 // use version 4 style class functions (without prefixes
	 // like "public", "private", etc.) you'll get notices telling
	 // you that these have been deprecated.
	
	/*if ($severity == E_STRICT)
	{
		return;
	}*/

	error($filepath, "severity: " . $severity . "  " . $message . ",  file = " . $filepath . ":" . $line);

	return true;
}

/**
 * log the file name and other error for fatal errors not handled by error handler
 */
function handle_shutdown_error()
{
	$error = error_get_last();
	if ($error !== null && isset($error['file'])) {
        error($error['file'], "SHUTDOWN_ERROR : " .  $error['message'] . ",  file = " . $error['file'] . ":" . $error['line']);
		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

		$data = $_REQUEST;
		debug(__FILE__, '[SHUTOWN_ERROR] request uri: ' . $request_uri . ' parameters: ' . print_r($data, true));
	}
}


/**
 * log relevant information for uncaught exception
 * @param $exception
 */
function handle_uncaught_exception($exception)
{
    error($exception->getFile(), "UNCAUGHT_EXCEPTION: " .   $exception->getMessage() . ",  file = " . $exception->getFile() . ":" . $exception->getLine());

	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$data = $_REQUEST;
	debug(__FILE__, 'uncaught exception: request uri: ' . $request_uri . ' parameters: ' . print_r($data, true));
}
