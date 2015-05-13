<?php
/**
 * メール受信のパイプスクリプト
 */
require_once  __DIR__ . '/../app/initialize.php';

$data = file_get_contents("php://stdin");

$dao = new Dao(Config::getDbConfig());
$dao->register($data);

