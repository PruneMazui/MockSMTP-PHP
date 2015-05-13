<?php
require_once  __DIR__ . '/../app/initialize.php';

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(__DIR__ . '/../app'));

set_include_path(get_include_path() . PATH_SEPARATOR . APPLICATION_PATH . '/controllers');

// Create application, bootstrap, and run
$application = new Zend_Application(
    'dummy',
    Config::getAppConfig()
);
$application->bootstrap()->run();