<?php
call_user_func(function() {
    error_reporting(E_ALL);
    ini_set('display_error', 'On');

    mb_internal_encoding('UTF-8');
    mb_regex_encoding('UTF-8');

    // モジュール類のrequire
    set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/class');

    define('C_MAIL_RECIEVE_TYPE_TO',  1);
    define('C_MAIL_RECIEVE_TYPE_CC',  2);
    define('C_MAIL_RECIEVE_TYPE_BCC', 3);

    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/../vendor/autoload.php';

    Zend_Loader_Autoloader::getInstance()->setFallbackAutoloader(true);
});

if(!function_exists('h'))
{
    function h($str)
    {
        return htmlspecialchars($str);
    }
}