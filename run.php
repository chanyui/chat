#! /usr/local/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: yc
 * Date: 2017/11/15
 * Time: 上午11:03
 */

date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', realpath(dirname(__FILE__)));

require_once ROOT_PATH . '/websocket/Websocket.php';
require_once ROOT_PATH . '/config/config.php';

$server = new Websocket($config['websocket']['host'], $config['websocket']['port']);