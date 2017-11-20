<?php
/**
 * Created by PhpStorm.
 * User: yc
 * Date: 2017/11/15
 * Time: 上午10:52
 * Desc: 面向对象代码
 */

require_once './File.php';
require_once './UserList.php';

class Websocket
{
    protected $server;
    protected $host;
    protected $port;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;

        //创建websocket服务器对象，监听192.168.13.203:9502端口
        $this->server = new swoole_websocket_server($this->host, $this->port);

        //$this->connect_info(0, 'clear');
        File::write('jsonFds.txt', json_encode([]), 'w+');
        File::write('user_list.txt', json_encode([]), 'w+');

        //监听WebSocket连接打开事件
        $this->server->on('open', function (swoole_websocket_server $server, $req) {

            $this->onOpen($server, $req);
        });

        //监听WebSocket消息事件
        $this->server->on('message', function (swoole_websocket_server $server, $frame) {

            $this->onMessage($server, $frame);
        });

        //监听WebSocket连接关闭事件
        $this->server->on('close', function (swoole_websocket_server $server, $fd) {
            //关闭连接 连接减少
            /*global $max;
            $max--;
            echo "client {$fd} closed\n";*/

            $this->onClose($server, $fd);
        });

        $this->server->start();
    }

    /**
     * 处理监听WebSocket连接打开事件时的任务
     * +-----------------------------------------------------------
     * @functionName : onOpen
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param object $req 请求信息
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function onOpen($server, $req)
    {
        //把客户端传过来的id保存到文件中
        //$this->connect_info($req->fd, 'insert');
        $oldFds = File::readFile('jsonFds.txt');
        if (!empty($oldFds)) {
            $oldFdArr = json_decode($oldFds, true);
            $fdArr = array_merge($oldFdArr, array($req->fd));
        } else {
            $fdArr = [$req->fd];
        }
        File::write('jsonFds.txt', json_encode($fdArr), 'w+');

    }

    /**
     * 处理监听WebSocket消息事件时的任务
     * +-----------------------------------------------------------
     * @functionName : onMessage
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param object $frame 接收到的信息
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function onMessage($server, $frame)
    {
        $data_arr = explode('-', $frame->data);

        $this->switchMode($server, $frame, $data_arr);
    }

    /**
     * 处理接收客户端过来的信息(是登录还是发送消息)
     * +-----------------------------------------------------------
     * @functionName : switchMode
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param object $frame 接收来自客户端的数据
     * @param array $data_arr 处理过的数据
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function switchMode($server, $frame, $data_arr)
    {
        $code = $data_arr[0];
        switch ($code) {
            case 'init':
                $this->_init($server, $frame, $data_arr);
                break;
            case 'all':
                $this->_toAll($server, $frame, $data_arr);
                break;
        }
    }

    /**
     * 有新用户进入系统初始化操作
     * +-----------------------------------------------------------
     * @functionName : _init
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param object $frame 接收来自客户端的数据
     * @param array $data_arr 处理过的数据
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function _init($server, $frame, $data_arr)
    {
        $userDb = new UserList($server, $frame->fd, $data_arr[1]);
        $userDb->saveUser();
        $userDb->noticeUser();
        $dataArr = [
            'code' => $data_arr[0],
            'currentUserFd' => $frame->fd,
            'user' => $data_arr[1],
            'msg' => $data_arr[2]
        ];
        echo PHP_EOL . date('Y-m-d H:i:s', time()) . ': ' . $dataArr['currentUserFd'] . ' : ' . $dataArr['user'] . ' ' . $dataArr['msg'];

        //向在线的所有人广播上线信息
        foreach ($this->server->connections as $fd) {
            $this->server->push($fd, json_encode($dataArr));
        }
    }

    /**
     * 当有用户发送消息给其他在线所有人
     * +-----------------------------------------------------------
     * @functionName : _toAll
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param object $frame 接收来自客户端的数据
     * @param array $dataArr 处理过的数据
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function _toAll($server, $frame, $data_arr)
    {
        $userDb = new UserList($server, $frame->fd);
        $userInfo = $userDb->getUserByFd();

        $dataArr = [
            'code' => $data_arr[0],
            'currentUserFd' => $frame->fd,
            'time' => date('Y-m-d H:i:s', time()),
            'user' => $userInfo[1],
            'msg' => $data_arr[1]
        ];

        //向在线的所有人广播发送消息
        foreach ($this->server->connections as $fd) {
            $this->server->push($fd, json_encode(array_merge($dataArr, ['pushUserFd' => $fd])));
        }

    }

    /**
     * 处理监听WebSocket连接关闭事件时的任务
     * +-----------------------------------------------------------
     * @functionName : onClose
     * +-----------------------------------------------------------
     * @param object $server swoole_websocket服务对象
     * @param int $fd 客户端id
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function onClose($server, $fd)
    {
        //刷新页面后去掉断开连接的fd 并在文件中保持新的fd
        //$this->connect_info($fd, 'close');
        $oldFds = File::readFile('jsonFds.txt');
        if (!empty($oldFds)) {
            $oldFdArr = json_decode($oldFds, true);
            if (in_array($fd, $oldFdArr)) {
                $fd_arr = array_filter($oldFdArr, function ($value) use ($fd) {
                    if ($value != $fd) {
                        return true;
                    }
                });
                File::write('jsonFds.txt', json_encode($fd_arr), 'w+');
            }
        }

        $userDb = new UserList($server, $fd);
        $userDb->delUser();

    }

    /**
     * 处理客户端传递过来的id（fd）文件
     * +-----------------------------------------------------------
     * @functionName : connect_info
     * +-----------------------------------------------------------
     * @param int $fd 客户端id
     * @param string $opt 操作方法
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    private function connect_info($fd = 0, $opt = 'insert')
    {
        $json_info = file_get_contents(__DIR__ . '/jsonFd.txt');
        $fd_arr = json_decode($json_info, true);
        if ($opt == 'clear') {
            file_put_contents(__DIR__ . '/jsonFd.txt', json_encode([]));
        } elseif ($opt == 'read') {
            if (!$fd_arr) {
                return;
            }
            return $fd_arr;
        } elseif ($opt == 'close') {
            if (!$fd_arr) {
                return;
            }
            if (in_array($fd, $fd_arr)) {
                $fd_arr = array_filter($fd_arr, function ($value) use ($fd) {
                    if ($value != $fd) {
                        return true;
                    }
                });
                file_put_contents(__DIR__ . '/jsonFd.txt', json_encode($fd_arr));
            }
        } else {
            if (!$fd_arr) {
                $fd_arr = [];
            }
            $arr = array_merge($fd_arr, array($fd));
            file_put_contents(__DIR__ . '/jsonFd.txt', json_encode($arr));
        }
    }


}
