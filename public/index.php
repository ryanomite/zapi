<?php

date_default_timezone_set('America/Chicago');
ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

// Define base directory path
defined('BASE_PATH')
	|| define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
// Define path to application directory
defined('APPLICATION_PATH')
	|| define('APPLICATION_PATH', BASE_PATH . '/application');
// Local library
defined('LOCAL_LIBRARY_PATH') ||
	define('LOCAL_LIBRARY_PATH', (getenv('LOCAL_LIBRARY_PATH') ? getenv('LOCAL_LIBRARY_PATH') : BASE_PATH . '/library'));
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Update include path
set_include_path(implode(PATH_SEPARATOR, array(
    LOCAL_LIBRARY_PATH,
    get_include_path(),
)));

try {
    require_once 'Zend/Application.php';

    // Create application, bootstrap, and run
    $application = new Zend_Application(
        APPLICATION_ENV,
        APPLICATION_PATH . '/configs/application.ini'
    );
    $application->bootstrap()
                ->run();
} catch (Exception $e) {

    $json = array(
        'status' => 0,
        'data' => array(),
        'messages' => array(
            array('type'=>0,'message'=>'An error has occurred.')
        )
    );

    if (preg_match('/^development/i',getenv('APPLICATION_ENV'))) {
        $json['messages']['message'] = $e->getMessage();
        $json['file'] = $e->getFile();
        $json['line'] = $e->getLine();
        $json['stack'] = $e->getTrace();
    }

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');

    echo json_encode($json);
}

function getmicrotime($t)
{
	list($usec, $sec) = explode(" ", $t);
	return ((float)$usec + (float)$sec*1000);
}