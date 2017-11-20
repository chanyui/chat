#! /usr/local/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: yc
 * Date: 2017/11/15
 * Time: 上午11:03
 */

date_default_timezone_set('Asia/Shanghai');

require_once './Websocket.php';
require_once './config.php';

$server = new Websocket($config['websocket']['host'], $config['websocket']['port']);