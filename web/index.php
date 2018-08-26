<?php

require '../vendor/autoload.php';

$app = new Slim\App();

//print_r($_SERVER);
//$_SERVER['REQUEST_URI'] = '/hello/xxx';
$app->get('/hello/{name}', function ($request, $response, $args) {
//    print_r($args);
//    trigger_error('test');
    return $response->getBody()->write("Hello, " . $args['name']);
});

$response = $app->run(1);
echo $response->getBody();
