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
// Shared library
defined('SHARED_LIBRARY_PATH') ||
	define('SHARED_LIBRARY_PATH', (getenv('SHARED_LIBRARY_PATH') ? getenv('SHARED_LIBRARY_PATH') : BASE_PATH . '/../slibrary'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Update include path
set_include_path(implode(PATH_SEPARATOR, array(
    LOCAL_LIBRARY_PATH,
    LOCAL_LIBRARY_PATH . '/PEAR',
    SHARED_LIBRARY_PATH,
    SHARED_LIBRARY_PATH . '/PEAR',
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

    //TODO: replace with application logging
}

function getmicrotime($t)
{
	list($usec, $sec) = explode(" ", $t);
	return ((float)$usec + (float)$sec*1000);
}