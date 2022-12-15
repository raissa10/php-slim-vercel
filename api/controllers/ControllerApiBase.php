<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

define("ETAPA_NAO_INICIALIZADA", 0);
define("ETAPA_VALIDACAO_CPF", 1);
define("ETAPA_OPCOES_CONSULTA", 2);
define("ETAPA_OPCOES_PENDENTE", 3);
define("ETAPA_TELEGRAM_FIM", 10);

define("ETAPA_LISTAR_CONSULTA", 10);
define("ETAPA_CANCELAR_CONSULTA", 20);
define("ETAPA_REAGENDAR_CONSULTA", 30);
define("ETAPA_ATENDIMENTO_HUMANO", 40);
define("ETAPA_FINALIZAR_ATENDIMENTO", 99);

define("STATUS_MENSAGEM_DESATUALIZADA", 1);
define("STATUS_MENSAGEM_ATUALIZADA", 2);

define("ID_TELEGRAM_TESTES", 5739605956);

/**
 * Contem os metodos base de chamada da api que são chamados em mais de uma rota.
 *
 * User: Gelvazio Camargo
 * Date: 10/12/2020
 * Time: 17:40
 */
class ControllerApiBase {
    
    /**
     * Token de acesso ao bot
     */
    // TOKEN GELVAZIO
    // const TELEGRAM_BOT_TOKEN = '5887064892:AAE4TKkPpM8t6DFdqokWkSqXB-iItLk9TKU';

    const TELEGRAM_BOT_TOKEN = 'COLOCAR SEU TOKEN_AQUI!!!';

    // TOKEN BOT ALEX
    // const TELEGRAM_BOT_TOKEN = '5362336257:AAHR6gjMXofy8kc8bjeO2NZzj3gcqsmtzjQ';
    
    public function home(Request $request, Response $response, array $args) {
        
        require_once("documentacao.html");
        
        //return $response->withBody();
    }
    
    public function callPing(Request $request, Response $response, array $args) {
        $data = array("data" => date("Y-m-d H:i:s"));
        
        return $response->withJson($data, 200);
    }

    public function test(Request $request, Response $response, array $args) {
        $data = array(
            "data" => date("Y-m-d H:i:s"),
            "body"=>$request->getParsedBody(),
            "headers"=>$request->getHeaders()
        );
        
        return $response->withJson($data, 200);
    }

    /**
     *
     * @var ModelPadrao
     */
    protected $Model;

    /**
     *
     * @var Query
     */
    private $Query;

    public function getQuery() {
        if (!isset($this->Query)) {
            require_once ("core/Query.php");
            $this->Query = new Query();
        }
        return $this->Query;
    }

    public function setQuery(Query $Query) {
        $this->Query = $Query;
    }

    public function getModel() {
        return $this->Model;
    }

    public function setModel(ModelPadrao $Model) {
        $this->Model = $Model;
    }
}
