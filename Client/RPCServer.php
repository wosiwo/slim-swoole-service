<?php
namespace Client;

/**
 * RPC服务器
 * @package Swoole\Network
 * @method beforeRequest
 * @method afterRequest
 */
class RPCServer
{
    /**
     * 版本号
     */
    const VERSION = 1005;

    protected $_buffer  = array(); //buffer区
    protected $_headers = array(); //保存头

    protected $errCode;
    protected $errMsg;

    /**
     * 客户端环境变量
     * @var array
     */
    static $clientEnv;
    static $stop = false;

    /**
     * 请求头
     * @var array
     */
    static $requestHeader;

    public $packet_maxlen       = 2465792; //2M默认最大长度
    protected $buffer_maxlen    = 10240; //最大待处理区排队长度, 超过后将丢弃最早入队数据
    protected $buffer_clear_num = 128; //超过最大长度后，清理100个数据

    const ERR_HEADER            = 9001;   //错误的包头
    const ERR_TOOBIG            = 9002;   //请求包体长度超过允许的范围
    const ERR_SERVER_BUSY       = 9003;   //服务器繁忙，超过处理能力
    const ERR_UNPACK            = 9204;   //解包失败
    const ERR_PARAMS            = 9205;   //参数错误
    const ERR_NOFUNC            = 9206;   //函数不存在
    const ERR_CALL              = 9207;   //执行错误
    const ERR_ACCESS_DENY       = 9208;   //访问被拒绝，客户端主机未被授权
    const ERR_USER              = 9209;   //用户名密码错误

    const HEADER_SIZE           = 16;
    const HEADER_STRUCT         = "Nlength/Ntype/Nuid/Nserid";
    const HEADER_PACK           = "NNNN";

    const DECODE_PHP            = 1;   //使用PHP的serialize打包
    const DECODE_JSON           = 2;   //使用json_encode打包
    const DECODE_MSGPACK        = 3;   //使用msgpack打包
    const DECODE_GZIP           = 128; //启用GZIP压缩

    const ALLOW_IP              = 1;
    const ALLOW_USER            = 2;

    protected $appNamespaces    = array(); //应用程序命名空间
    protected $ipWhiteList      = array(); //IP白名单
    protected $userList         = array(); //用户列表

    protected $verifyIp         = false;
    protected $verifyUser       = false;


    /**
     * 打包数据
     * @param $data
     * @param $type
     * @param $uid
     * @param $serid
     * @return string
     */
    static function encode($data, $type = self::DECODE_PHP, $uid = 0, $serid = 0)
    {
        //启用压缩
        if ($type & self::DECODE_GZIP)
        {
            $_type = $type & ~self::DECODE_GZIP;
            $gzip_compress = true;
        }
        else
        {
            $gzip_compress = false;
            $_type = $type;
        }
        switch($_type)
        {
            case self::DECODE_JSON:
                $body = json_encode($data);
                break;
            case self::DECODE_PHP:
            default:
                $body = serialize($data);
                break;
        }
        if ($gzip_compress)
        {
            $body = gzencode($body);
        }
        return pack(RPCServer::HEADER_PACK, strlen($body), $type, $uid, $serid) . $body;
    }

    /**
     * 解包数据
     * @param string $data
     * @param int $unseralize_type
     * @return string
     */
    static function decode($data, $unseralize_type = self::DECODE_PHP)
    {
        if ($unseralize_type & self::DECODE_GZIP)
        {
            $unseralize_type &= ~self::DECODE_GZIP;
            $data = gzdecode($data);
        }
        switch ($unseralize_type)
        {
            case self::DECODE_JSON:
                return json_decode($data, true);
            case self::DECODE_PHP;
            default:
                return unserialize($data);
        }
    }

    /**
     * @param $serv
     * @param int $fd
     * @param $from_id
     */
    function onClose($serv, $fd, $from_id)
    {
        unset($this->_buffer[$fd]);
    }

}