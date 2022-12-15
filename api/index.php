<?php
error_reporting(0);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->get('/hello/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Hello World!");
    return $response;
});

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Index!");
    return $response;
});

$app->get('/testbot/{message}', function (Request $request, Response $response, array $args) {
    
    $mensageInformada = $args['message'];
    
    if (isset($mensageInformada)) {
     $mensageInformada = " Mensagem : " . $mensageInformada;
    }
    require_once ("controllers/ControllerApiTelegram.php");
    
    ControllerApiTelegram::sendMessage("Testando chat bot" . $mensageInformada);
    
    $response->getBody()->write("Enviando mensagem para o chatbot!");

    return $response;
});


$app->run();
