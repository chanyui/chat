# swoole_chat

swoole 聊天室，一对多聊天

#安装swoole扩展
====
需要安装swoole扩展，1.8以上

Linux 用户

#!/bin/bash
pecl install swoole

Mac 用户

#!/bin/bash
brew install swoole

#注
====
需要修改index.html 的websocket里的ip地址

需要修改config.php 的websocket里的ip地址

#后台主要代码
====
<code>  
    
    <?php
    $server = new swoole_websocket_server("127.0.0.1", 9502);

    $server->on('open', function($server, $req) {
        echo "connection open: {$req->fd}\n";
    });

    $server->on('message', function($server, $frame) {
        echo "received message: {$frame->data}\n";
        $server->push($frame->fd, json_encode(["hello", "world"]));
    });

    $server->on('close', function($server, $fd) {
        echo "connection close: {$fd}\n";
    });

    $server->start();
</code>




