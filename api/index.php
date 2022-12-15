<?php

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

$app->get('/testbot/', function (Request $request, Response $response, array $args) {
    
    require_once ("controllers/ControllerApiTelegram.php");
    
    ControllerApiTelegram::sendMessage("Informe seu CPF para iniciar a conversa! Senac - Testes");
    
    $response->getBody()->write("Enviando mensagem para o chatbot!");
});

$app->run();
