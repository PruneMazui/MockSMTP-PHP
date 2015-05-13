<?php

class Config
{
    public static function getDbConfig()
    {
        return array(
            'host' => 'localhost',
            'port' => '3306',
            'dbname' => 'mock_smtp',
            'username' => 'mock_smtp',
            'password' => '',
            'charset' => 'utf8mb4'
        );
    }

    public static function getAppConfig()
    {
        return array(
            'phpSettings' => array(
                'display_startup_errors' => '1',
                'display_errors' => '1'
            ),
            'bootstrap' => array(
                'path' => APPLICATION_PATH . '/Bootstrap.php',
                'class' => 'Bootstrap'
            ),
            'appnamespace' => 'Application',
            'resources' => array(
                'frontController' => array(
                    'controllerDirectory' => APPLICATION_PATH . '/controllers',
                    'params' => array(
                        'displayExceptions' => '1'
                    )
                )
            )
        );
    }
}