<?php


require './SwInterFace/Loader.php';

spl_autoload_register('\\SwInterFace\\Loader::autoload');

SwInterFace\Loader::addNameSpace('Tiny', __DIR__ . '/Tiny');
SwInterFace\Loader::addNameSpace('SwInterFace', __DIR__ . '/SwInterFace');
SwInterFace\Loader::addNameSpace('Search', __DIR__ . '/Search');


SwInterFace\Server::setPidFile(__DIR__ . '/logs/server.pid');

require 'vendor/autoload.php';

$app = new Slim\App();

$app->get('/hello/{name}', function ($request, $response, $args) {
    print_r($_GET);
    return "Hello, " . $args['name'];
});
SwInterFace\Server::start(function ()
{
    global $app;
//    print_r($app);

    $AppSvr = new SwInterFace\CallBack($app);
    $setting = array(
        //TODO： 实际使用中必须调大进程数
        'worker_num' => 4,
        'max_request' => 1000,
        'dispatch_mode' => 3,
        'daemonize' => true,
        'log_file' => __DIR__ . '/logs/swoole.log',
        'open_length_check' => 1,
        'package_max_length' => $AppSvr->packet_maxlen,
        'package_length_type' => 'N',
        'package_body_offset' => 16,
        'package_length_offset' => 0,
        'watch_path' => __DIR__ . '/SwInterFace',
    );

    //设置为512M
    ini_set('memory_limit', '512M');

    $listenHost = '0.0.0.0';

    $server = SwInterFace\Server::autoCreate($listenHost, 7777);
    $AppSvr->server = $server->sw;
    //设置事件回调
    $server->setCallBack($AppSvr);
    $server->setProcessName("TinyServer");
    $server->run($setting);
});