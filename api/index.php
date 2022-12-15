<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require_once("controllers/ControllerApiTelegram.php");
require_once("controllers/ControllerEtapaTelegram.php");

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

// update bot
$app->post('/users/updatebot', ControllerEtapaTelegram::class . ':updatechatbot');
$app->get('/users/updates', ControllerEtapaTelegram::class . ':getUpdates');

// Webhook
$app->post('/webhook', ControllerEtapaTelegram::class . ':getWebhook');
$app->post('/setwebhook', ControllerEtapaTelegram::class . ':setWebhook');
$app->post('/removewebhook', ControllerEtapaTelegram::class . ':removeWebhook');
$app->post('/getwebhook', ControllerEtapaTelegram::class . ':getWebhookInfo');


$app->run();
