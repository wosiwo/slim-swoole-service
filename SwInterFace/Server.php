<?php
namespace SwInterFace;

/**
 * Class Server
 * $callBack SwInterFace\CallBace
 * @package SwInterFace
 */
class Server{
    protected static $beforeStopCallback;
    protected static $beforeReloadCallback;
    static $defaultOptions = array(
        'd|daemon' => '启用守护进程模式',
        'h|host?' => '指定监听地址',
        'p|port?' => '指定监听端口',
        'help' => '显示帮助界面',
        'b|base' => '使用BASE模式启动',
        'w|worker?' => '设置Worker进程的数量',
        'r|thread?' => '设置Reactor线程的数量',
        't|tasker?' => '设置Task进程的数量',
    );

    static $options = array();
    public $runtimeSetting = array();
    static $swooleMode;
    static $optionKit;
    static $pidFile;

    /**
     * @var \SwInterFace\CallBack
     */
    public $callBack;

    function __construct($host, $port, $ssl = false){
        $flag = $ssl ? (SWOOLE_SOCK_TCP | SWOOLE_SSL) : SWOOLE_SOCK_TCP;
        if (!empty(self::$options['base']))
        {
            self::$swooleMode = SWOOLE_BASE;
        }
        elseif (extension_loaded('swoole'))
        {
            self::$swooleMode = SWOOLE_PROCESS;
        }

        $this->sw = new \swoole_server($host, $port, self::$swooleMode, $flag);
        $this->host = $host;
        $this->port = $port;

    }

    /**
     * 绑定事件回调
     * @param $protocol
     * @throws \Exception
     */
    function setCallBack($callBack)
    {
        $this->callBack = $callBack;
        $this->sw->on('Connect', array($callBack, 'onConnect'));
        $this->sw->on('Receive', array($callBack, 'onReceive'));
        $this->sw->on('Close', array($callBack, 'onClose'));
        $this->sw->on('WorkerStop', array($callBack, 'onShutdown'));
    }


    /**
     * 自动推断扩展支持
     * 默认使用swoole扩展,其次是libevent,最后是select(支持windows)
     * @param      $host
     * @param      $port
     * @param bool $ssl
     * @return Server
     */
    static function autoCreate($host, $port, $ssl = false)
    {
        if (class_exists('\\swoole_server', false))
        {
            return new self($host, $port, $ssl);
        }
        elseif (function_exists('event_base_new'))
        {
//            return new EventTCP($host, $port, $ssl);
        }
        else
        {
//            return new SelectTCP($host, $port, $ssl);
        }
    }

    /**
     * 设置PID文件
     * @param $pidFile
     */
    static function setPidFile($pidFile)
    {
        self::$pidFile = $pidFile;
    }

    function run(){
        $version = explode('.', SWOOLE_VERSION);

        $this->sw->on('Start', array($this, 'onMasterStart'));
        $this->sw->on('Shutdown', array($this, 'onMasterStop'));
        $this->sw->on('ManagerStop', array($this, 'onManagerStop'));
        $this->sw->on('WorkerStart', array($this, 'onWorkerStart'));
        print_r('start');
        $this->sw->start();
    }




    function onMasterStart($serv)
    {
        if (!empty($this->runtimeSetting['pid_file']))
        {
            file_put_contents(self::$pidFile, $serv->master_pid);
        }
        if (method_exists($this->callBack, 'onMasterStart'))
        {
            $this->callBack->onMasterStart($serv);
        }
    }

    function onMasterStop($serv)
    {
        if (!empty($this->runtimeSetting['pid_file']))
        {
            unlink(self::$pidFile);
        }
        if (method_exists($this->callBack, 'onMasterStop'))
        {
            $this->callBack->onMasterStop($serv);
        }
    }

    function onManagerStop(){

    }

    function onWorkerStart($serv, $worker_id)
    {
        if (method_exists($this->callBack, 'onStart'))
        {
            $this->callBack->onStart($serv, $worker_id);
        }
        if (method_exists($this->callBack, 'onWorkerStart'))
        {
            $this->callBack->onWorkerStart($serv, $worker_id);
        }
    }








    /**
     * 设置进程名称
     * @param $name
     */
    function setProcessName($name)
    {
        $this->processName = $name;
    }

    /**
     * 获取进程名称
     * @return string
     */
    function getProcessName()
    {
        if (empty($this->processName))
        {
            global $argv;
            return "php {$argv[0]}";
        }
        else
        {
            return $this->processName;
        }
    }

    /**
     * 显示命令行指令
     */
    static function start($startFunction)
    {
        $startFunction();
    }


}