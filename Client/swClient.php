<?php



class swClient extends Client\RPC
{
    protected $service_name;
    protected $namespace;
    protected $config;

    /**
     * 是否重新加载配置
     */
    protected $reloadConfig = false;


    const ERR_NO_CONF = 7001;
    const ERR_NO_IP   = 7002;
    /**
     * 构造函数
     * @param $service
     * @throws ServiceException
     */
    function __construct($name,$config=[])
    {
        if (empty($name))
        {
            $name = 'demo';
        }
        $this->service_name = strtolower($name);

        if (PHP_SAPI == 'cli')
        {
            $this->reloadConfig = true;
        }
        $servers = $config['servers'];
        $this->setServers($servers);
        parent::__construct($name);
    }


    function call()
    {
        $args = func_get_args();
        return $this->task($args[0], array_slice($args, 1));
    }

    function wait($timeout = 0.5)
    {
        parent::wait($timeout);
    }
}

class ServiceException extends Exception
{

}