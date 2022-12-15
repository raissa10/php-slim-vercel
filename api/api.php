<?php

date_default_timezone_set('America/Maceio');

use \Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require_once "lib/slim/autoload.php";
require_once("core/Utils.php");

require_once("controllers/ControllerApiBase.php");
require_once("controllers/ControllerApiTelegram.php");

require_once("controllers/ControllerEtapaTelegram.php");

class Routes {

    public function __construct() {
        $this->runApp();
    }

    /**
     * Executa o app para realizar a chamada de rotas
     *
     * @throws Throwable
     */
    protected function runApp() {
        $app = new \Slim\App($this->getConfigurationContainer());
        
        $app->add(function ($req, $res, $next) {
            $response = $next($req, $res);
            
            return $response
                // Aceita todas as origens
                ->withHeader('Access-Control-Allow-Origin', '*')
                // Aceita somente os atributos headers desta lista abaixo
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, apikey, x-api-key-system')
                // Aceita apenas os metodos abaixo
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE');
        });
        
        // Agrupando rotas para adicionar o middleware em todas as rotas de uma só vez
        $app->group('', function () use ($app) {
            // Pagina inicial da api
            $app->get('/', ControllerApiBase::class . ':home');

            // Ping
            $app->get('/ping', ControllerApiBase::class . ':callPing');

            // Lista todos os usuarios
            $app->get('/users/all', ControllerApiUsuario::class . ':getUsuario');

            // Gravar um usuario
            $app->post('/users/store', ControllerApiUsuario::class . ':gravaUsuario');

            // Login do usuario
            $app->post('/users/login', ControllerApiUsuario::class . ':loginUsuario');

            // Login via cpf
            $app->post('/users/logincpf', ControllerApiUsuario::class . ':loginUsuarioPorCpf');

            // Validacao usuario telegram
            $app->post('/users/valida_id_telegram', ControllerApiUsuario::class . ':validaIdTelegram');

            // Valida cpf usuario
            $app->post('/users/valida_cpf_telegram', ControllerApiUsuario::class . ':validaCpfTelegram');
            
            // update bot
            $app->post('/users/updatebot', ControllerEtapaTelegram::class . ':updatechatbot');
            $app->get('/users/updates', ControllerEtapaTelegram::class . ':getUpdates');
            
            // Webhook
            $app->post('/webhook', ControllerEtapaTelegram::class . ':getWebhook');
            $app->post('/setwebhook', ControllerEtapaTelegram::class . ':setWebhook');
            $app->post('/removewebhook', ControllerEtapaTelegram::class . ':removeWebhook');
            $app->post('/getwebhook', ControllerEtapaTelegram::class . ':getWebhookInfo');
    
            // Atendimentos
            $app->get('/atendimentos', ControllerEtapaTelegram::class . ':getAtendimentos');

        })->add($this->getMiddlewares());

        $app->run();
    }

    /**
     * Retorna a configuração das rotas
     *
     * @return \Slim\Container
     */
    private function getConfigurationContainer()
    {
        // Configuração de erros
        $configuration = [
            'settings' => [
                'displayErrorDetails' => true,
                'determineRouteBeforeAppMiddleware' => true,
            ],
        ];
        $configurationContainer = new \Slim\Container($configuration);

        return $configurationContainer;
    }

    /**
     * Retorna os midlewares de validação de rotas
     *
     * @return Closure
     */
    private function getMiddlewares()
    {
        // Middlewares
        $Middlware = function (Request $request, Response $response, $next) {
            
            $headers = $request->getHeaders();
            
            // if(isset($headers["HTTP_APIKEY"]) && is_array($headers["HTTP_APIKEY"])){
            //     $token = $headers["HTTP_APIKEY"][0];
            //     if (trim($token) == "") {
            //         $data = array("message" => "Acesso inválido - TOKEN - Envio:" . $token);
            //         return $response->withJson($data, 401);
            //     }
            //
            //     // Verifica se esse token de usuario existe
            //     if (!Routes::isValidTokenUsuario($token)) {
            //         $data = array("message" => "Token inválido", "token informado:" => $token);
            //         return $response->withJson($data, 401);
            //     }
            //
            // } else {
            //     $data = array("message" => "Token inválido!");
            //     return $response->withJson($data, 401);
            // }

            $response = $next($request, $response);

            return $response;
        };

        return $Middlware;
    }
  
    public static function isValidTokenUsuario($token) {
        require_once("core/Query.php");
        $oQuery = new Query();
        
        $aDados = $oQuery->select("select usutoken as token
                                     from tbusuario
                                    where tbusuario.usutoken = '$token'
                                      and coalesce(tbusuario.usuativo, 0) = 1");
        
        if (!$aDados) {
            return false;
        }
        
        return true;
    }
}

$routes = new Routes();
