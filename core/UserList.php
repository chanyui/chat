<?php
/**
 * Created by PhpStorm.
 * User: yc
 * Date: 2017/11/17
 * Time: 上午10:16
 */

class UserList
{
    protected $server, $fd, $uname;

    public function __construct($server, $fd, $uname = '')
    {
        $this->server = $server;
        $this->fd = $fd;
        $this->uname = $uname;
    }

    /**
     * 添加用户
     * +-----------------------------------------------------------
     * @functionName : addUser
     * +-----------------------------------------------------------
     * @param $oldUserArr
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    protected function addUser($oldUserArr)
    {
        //当前在线的client fd
        $connectFds = [];
        foreach ($this->server->connections as $fd) {
            $connectFds[] = $fd;
        }
        if ($oldUserArr) {
            foreach ($oldUserArr as $key => $value) {
                if (!in_array($value[0], $connectFds)) {
                    unset($oldUserArr[$key]);
                }
            }
        }
        $key = $this->fd . '_user';
        $user_arr = [
            $key => [
                $this->fd,
                $this->uname
            ]
        ];
        $newUserArr = array_merge($oldUserArr, $user_arr);
        File::write('user_list.txt', json_encode($newUserArr), 'w+');
    }

    /**
     * 保存上线的用户到文件中
     * +-----------------------------------------------------------
     * @functionName : saveUser
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    public function saveUser()
    {
        $json_user = File::readFile('user_list.txt');

        if (!empty($json_user)) {
            $oldUser = json_decode($json_user, true);
            $this->addUser($oldUser);
        } else {
            $oldUser = [];
            $this->addUser($oldUser);
        }
    }

    /**
     * 获取全部用户列表
     * +-----------------------------------------------------------
     * @functionName : getUser
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     * @return array|mixed
     */
    public function getUser()
    {
        $json_user = File::readFile('user_list.txt');

        if (!empty($json_user)) {
            $user_arr = json_decode($json_user, true);
        } else {
            $user_arr = [];
        }
        return $user_arr;
    }

    /**
     * 获取当前用户
     * +-----------------------------------------------------------
     * @functionName : getUserByFd
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     * @return array
     */
    public function getUserByFd()
    {
        $json_user = File::readFile('user_list.txt');

        if (!empty($json_user)) {
            $user_arr = json_decode($json_user, true);
            $key = $this->fd . '_user';
            $userInfo = $user_arr[$key];
        } else {
            $userInfo = [];
        }
        return $userInfo;
    }

    /**
     * 删除下线的用户；根据fd客户端传过来的id
     * +-----------------------------------------------------------
     * @functionName : delUser
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     * @return mixed|string
     */
    public function delUser()
    {
        $json_user = File::readFile('user_list.txt');
        $key = $this->fd . '_user';
        if (!empty($json_user)) {
            $user_arr = json_decode($json_user, true);
            unset($user_arr[$key]);
            File::write('user_list.txt', json_encode($user_arr), 'w+');
        } else {
            $user_arr = json_encode([]);
        }
        return $user_arr;
    }

    /**
     * 通知全部用户在线列表更新
     * +-----------------------------------------------------------
     * @functionName : noticeUser
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    public function noticeUser()
    {
        $userList = $this->getUser();
        foreach ($userList as $key => $val) {
            $this->server->push($val[0], json_encode([
                    'code' => 'user_list',
                    'userArr' => $userList,
                    'pushFd' => $val[0]
                ])
            );
        }
    }
}