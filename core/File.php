<?php
/**
 * Created by PhpStorm.
 * User: yc
 * Date: 2017/11/16
 * Time: 下午2:49
 */

class File
{
    /**
     * 文件写入
     * +-----------------------------------------------------------
     * @functionName : write
     * +-----------------------------------------------------------
     * @param string $msg 写入信息
     * @param string $filename 文件名
     * @param string $_mode 文件访问类型
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    public static function write($filename = "", $msg, $_mode = "a+")
    {
        if (empty($filename)) {
            $hd_txt = self::createFile("jsonFds.txt", $_mode);
        } else {
            $hd_txt = self::createFile($filename, $_mode);
        }

        fwrite($hd_txt[0], $hd_txt[1] . $msg);
        self::close($hd_txt[0]);
    }

    /**
     * 创建/打开文件资源
     * +-----------------------------------------------------------
     * @functionName : createFile
     * +-----------------------------------------------------------
     * @param string $filename 文件名
     * @param string $_mode 文件访问类型
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     * @return array
     */
    protected static function createFile($filename, $_mode = "a+")
    {
        $hd = fopen(dirname(__DIR__) . '/log/' . $filename, $_mode);
        $_txt = fgets($hd);
        return [
            $hd,
            $_txt
        ];
    }

    /**
     * 读取文件内容
     * +-----------------------------------------------------------
     * @functionName : readFile
     * +-----------------------------------------------------------
     * @param string $filename 文件名
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     * @return bool|string
     */
    public static function readFile($filename)
    {
        if (file_exists(dirname(__DIR__) . '/log/' . $filename)) {
            $res = file_get_contents(dirname(__DIR__) . '/log/' . $filename);
        } else {
            $res = '';
        }

        return $res;
    }

    /**
     * 关闭一个已打开的文件指针
     * +-----------------------------------------------------------
     * @functionName : close
     * +-----------------------------------------------------------
     * @param resource $handle 文件指针
     * +-----------------------------------------------------------
     * @author yc
     * +-----------------------------------------------------------
     */
    public static function close($handle)
    {
        fclose($handle);
    }
}