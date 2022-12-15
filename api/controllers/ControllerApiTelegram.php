<?php
/**
 * Created by PhpStorm.
 * User: Alex Abreu
 * Date: 07/12/2022
 * Time: 19:47
 */
use \Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use TelegramBot\Api\BotApi;

require_once("ControllerApiBase.php");
require_once("ControllerApiTelegram.php");

require_once("./lib/bottelegram/vendor-bot-telegram/autoload.php");

//require __DIR__ . '/../../vendor/autoload.php';

class ControllerApiTelegram extends ControllerApiBase {

    /**
     * Método responsavel por enviar a mensagem de alerta
     *
     * @param $message
     * @param int $telegram_chat_id
     * @return \TelegramBot\Api\Types\Message
     * @throws \TelegramBot\Api\Exception
     * @throws \TelegramBot\Api\InvalidArgumentException
     */
    public static function sendMessage($message, $telegram_chat_id = 5455911022){
        // instancia do bot com o token gerado
        $oBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);
        
        // envia a mensagem para o telegram
        return $oBotApi->sendMessage($telegram_chat_id, $message);
    }
    
    public function getUpdatesTelegram(Request $request, Response $response, array $args) {
    
        $aDados = self::callApiTelegramUpdates();
        
        return $response->withJson($aDados, 200);
    }
    
    public static function callApiTelegramUpdates() {
        $json = new stdClass();
        $json->id = 1;
        $json->codigo = 1;
        $json->limit = 10000;
    
        $json = json_encode($json);
    
        // updates
        $endpoint = "https://api.telegram.org/bot" . self::TELEGRAM_BOT_TOKEN . "/getUpdates";
    
        // envia mensagem
        //$endpoint = 'http://localhost/bot-telegram-sistema-sus/api.php?m=' + message;
    
        $ch = curl_init($endpoint);
    
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'x-api-key: 123456',
            'Content-Type: application/json'
        ));
    
        $retornoApi = curl_exec($ch);
        $result = json_decode($retornoApi);
    
        curl_close($ch);
    
        return $result;
    }
    
    public static function setWebhook($url = ""){
        $oBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);
        
        return $oBotApi->setWebhook($url);
    }
    
    public static function removeWebhook(){
        $oBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);
        
        return $oBotApi->removeWebhook();
    }
    
    public static function getWebhookInfo(){
        $oBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);
        
        return $oBotApi->getWebhookInfo();
    }
    
    public static function getUpdates(){
        $oBotApi = new BotApi(self::TELEGRAM_BOT_TOKEN);
        
        return $oBotApi->getUpdates();
    }
}
