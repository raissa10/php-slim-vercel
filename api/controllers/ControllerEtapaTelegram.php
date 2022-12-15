<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ControllerEtapaTelegram extends ControllerApiBase {
    
    protected $idtelegram;
    
    protected $aListaValidacao = array();
    
    public function getUpdates(Request $request, Response $response, array $args) {
    
        // Deleta o webhook para poder pegar os updates
        $this->setWebhookApi();
    
        $aDadosUpdate = ControllerApiTelegram::callApiTelegramUpdates();
        
        $aListaUpdates = ControllerApiTelegram::getUpdates();
    
        // Adiciona de novo o webhook
        ControllerApiTelegram::setWebhook($url = "https://api-javalis-riodosul-38.herokuapp.com/api.php/webhook");
        
        return $response->withJson(array("status" => true, "data" => date("Y-m-d H:i:s"), "updates" => $aListaUpdates, "updates2" => $aDadosUpdate), 200);
    }
    
    protected function getEtapaPorIdTelegram($idtelegram, $message_id){
        $sql_usuario_telegram = "select * from public.tbetapatelegram where idtelegram = " . $idtelegram . " and idmensagem = $message_id";
        if($aDados = $this->getQuery()->select($sql_usuario_telegram)){
            return $aDados;
        }
        return false;
    }
    
    private function updateEtapasBot($aDadosUpdate){
        $oMensagem = "branco";
        foreach ($aDadosUpdate->result as $key => $aValues){
            $chatid = $aValues->message->chat->id;
            
            // Atualiza apenas as mensagens do IDTELEGRAM atual
            if($this->idtelegram == $chatid){
                // Pega a Mensagem da lista de updates
                $oMensagem = $this->getMensagem($aValues);
                
                // Se a mensagem atual ainda nao foi atualizada, chama o chat para atualizar
                if(!$this->isMensagemAtualizada($oMensagem)){
                    // Atualiza o chat do usuario
                    $this->sendMensagemUpdateUsuario($oMensagem);
                }
            }
        }
        
        return $oMensagem;
    }
    
    protected function isMensagemAtualizada($oMensagem){
        $data_atual = date("Y-m-d H:i:s");
        $message_id = isset($oMensagem->message_id) ? $oMensagem->message_id : 99999;
        
        // Se a mensagem ja existe, e por que ela ja esta atualizada
        if($this->getMensagemPorIdTelegram($this->idtelegram, $message_id)){
            return true;
        }
        
        $statusmensagem = STATUS_MENSAGEM_ATUALIZADA;
        
        $sql_insert = "INSERT INTO public.tbetapatelegram
                                (idtelegram, etapa, dataatualizacao,idmensagem,statusmensagem)
                                VALUES($this->idtelegram, 1, '$data_atual', $message_id, $statusmensagem);";
        
        // Atualiza a mensagem
        $this->getQuery()->executaQuery($sql_insert, true);
        
        // retorna true para chamar o chat bot
        return false;
    }
    
    protected function getMensagemPorIdTelegram($idtelegram, $message_id){
        $sql_usuario_telegram = "select * from public.tbetapatelegram where idtelegram = " . $idtelegram . " and idmensagem = $message_id";
        
        if($aDados = $this->getQuery()->select($sql_usuario_telegram)){
            return true;
        }
        return false;
    }
    
    private function getMensagem($aValues){
        $oObjeto2 = new stdClass();
        $oObjeto2->update_id = $aValues->update_id;
        
        $oObjeto2->message_id = $aValues->message->message_id;
        
        // OBJETO FROM
        $oObjeto2->message_from_id = $aValues->message->from->id;
        $oObjeto2->message_from_is_bot = $aValues->message->from->is_bot;
        $oObjeto2->message_from_first_name = $aValues->message->from->first_name;
        $oObjeto2->message_from_last_name = $aValues->message->from->last_name;
        
        // Texto da Mensagem
        // OBJETO CHAT
        $oObjeto2->message_chat = $aValues->message->chat;
        $oObjeto2->message_chat_id = $aValues->message->chat->id;
        $oObjeto2->message_chat_first_name = $aValues->message->chat->first_name;
        $oObjeto2->message_chat_last_name = $aValues->message->chat->last_name;
        
        // OBJETO DATE
        $oObjeto2->from_date = $aValues->message->date;
        
        // OBJETO TEXTO MENSAGEM
        $oObjeto2->mensagem = $aValues->message->text;
        
        return $oObjeto2;
    }
    
    private function sendMensagemUpdateUsuario($oMensagem){
        $message_id = isset($oMensagem->message_id) ? $oMensagem->message_id : 99999;
        $mensagem_informada = $oMensagem->mensagem;
        
        // ControllerApiTelegram::sendMessage("INICIANDO VALIDACAO MENSAGEM ID:" . $message_id . " ULTIMA MENSAGEM:" . $mensagem_informada, $this->idtelegram);
        
        if(!$this->validaCpfTelegram()){
            
            // Atualiza o status do cpf para cadastrado, mas nao validado, se houver
            $sql_update_cpf = "UPDATE public.tbetapatelegramcpf SET statusmensagem = 1 WHERE idtelegram = $this->idtelegram;";
            
            $this->getQuery()->executaQuery($sql_update_cpf);
            
            ControllerApiTelegram::sendMessage("Informe seu CPF para iniciar a conversa!", $this->idtelegram);
            
            return false;
        }
        
        // ETAPA DE VALIDACAO DO CPF
        if($this->validaCpfTelegramInformado($oMensagem)){
            $this->inicializaEtapasBot($oMensagem);
        }
    }
    
    protected function validaCpfTelegramInformado($oMensagem){
        $cpfinformado = $oMensagem->mensagem;
        
        $sql = "select * from public.tbetapatelegramcpf where idtelegram = $this->idtelegram and statusmensagem = 1";
        // Se o status ainda nao tiver sido validado, executa a validacao do ultimo valor informado pelo usuario
        if($aDados = $this->getQuery()->select($sql)) {
            $sql_usuario_sus = "select * from public.tbusuariosus where cpf = '$cpfinformado'";
            
            // Se o status ainda nao tiver sido validado, executa a validacao do ultimo valor informado pelo usuario
            $status_validacao = " CPF NAO ENCONTRADO!ENTRE EM CONTATO COM O POSTO DE SAUDE DA SUA REGIAO!";
            
            $status = false;
            
            if($aDadosSus = $this->getQuery()->select($sql_usuario_sus)) {
                // Se encntrou o usuario do SUS, retorna ok
                $status = true;
                
                $status_validacao = " CPF VALIDADO COM SUCESSO!Usuário:" . $aDadosSus["nome"];
                
                // Atualiza o status do cpf para nao cair mais nesta validacao
                $sql_update_cpf = "UPDATE public.tbetapatelegramcpf SET statusmensagem = 2, cpf = '$cpfinformado' WHERE idtelegram = $this->idtelegram;";
                
                $this->getQuery()->executaQuery($sql_update_cpf);
                
                // Remove a etapa inicial
                $sql_delete_etapa = "delete from public.tbatualizacaoetapastelegram where idtelegram = $this->idtelegram";
                
                $this->getQuery()->executaQuery($sql_delete_etapa);
                
            } else {
                // Reseta o status do cpf
                // Atualiza o status do cpf para nao cair mais nesta validacao
                $sql_update_cpf = "DELETE FROM public.tbetapatelegramcpf WHERE idtelegram = $this->idtelegram;";
                
                $this->getQuery()->executaQuery($sql_update_cpf);
            }
            
            ControllerApiTelegram::sendMessage("CPF sendo validado!CPF INFORMADO:" . $cpfinformado . $status_validacao, $this->idtelegram);
            
            return $status;
        }
        
        return true;
    }
    
    protected function validaCpfTelegram(){
        $dia_atual    = intval(date("d"));
        $mes_atual    = intval(date("m"));
        $ano_atual    = intval(date("Y"));
        
        $hora_atual   = intval(date("H"));
        $minuto_atual = intval(date("i"));
        
        $sql = "select * from public.tbetapatelegramcpf where idtelegram = $this->idtelegram";
        
        if($aDados = $this->getQuery()->select($sql)) {
            
            // CPF VALIDADO com o minuto atual
            $sql_update_cpf = "UPDATE public.tbetapatelegramcpf SET minuto=$minuto_atual, hora=$hora_atual, dia=$dia_atual, mes=$mes_atual, ano=$ano_atual WHERE idtelegram = $this->idtelegram;";
            
            $this->getQuery()->executaQuery($sql_update_cpf);
            
            return true;
        }
        
        // insere o cpf para validacao no banco de dados
        $sql = "INSERT INTO public.tbetapatelegramcpf (idtelegram, cpf, minuto, hora, dia, mes, ano, statusmensagem)
                VALUES($this->idtelegram, 'CPF_BRANCO', $minuto_atual, $hora_atual, $dia_atual, $mes_atual, $ano_atual, 1);";
        
        $this->getQuery()->executaQuery($sql);
        
        return false;
    }
    
    protected function atualizaStatusMensagem($oMensagem){
        $data_atual = date("Y-m-d H:i:s");
        
        $message_id = isset($oMensagem->message_id) ? $oMensagem->message_id : 99999;
        
        $idtelegram = $this->idtelegram;
        
        $sql_atualiza_etapa = "  UPDATE public.tbetapatelegram SET
                                        dataatualizacao = '$data_atual',
                                        statusmensagem = " . STATUS_MENSAGEM_ATUALIZADA . "
                                    WHERE idtelegram = $idtelegram
                                      and idmensagem = $message_id;";
        
        $this->getQuery()->executaQuery($sql_atualiza_etapa);
    }
    
    public function updatechatbot(Request $request, Response $response, array $args) {
        // Deleta o webhook para poder pegar os updates
        $this->setWebhookApi();
    
        $aDadosUpdate = $this->updatebot();
    
        // Adiciona de novo o webhook
        ControllerApiTelegram::setWebhook($url = "https://api-javalis-riodosul-38.herokuapp.com/api.php/webhook");
    
        $body = $request->getParsedBody();
        
        $body = isset($body) ? $body : "BODY VAZIO";
        
        return $response->withJson(array(
            "status" => true,
            "data" => date("Y-m-d H:i:s"),
            "listavalidacao" => $this->aListaValidacao,
            "updates" => $aDadosUpdate,
            "updateswebhook" => $body), 200);
    }
    
    public function getAtendimentos(Request $request, Response $response, array $args) {
        $sSql = "   select tbetapatelegram.idmensagem as codigomensagem,
                           tbusuariosus.nome,
                           tbetapatelegram.dataatualizacao
                      from tbetapatelegram
                inner join tbetapatelegramcpf on tbetapatelegramcpf.idtelegram = tbetapatelegram.idtelegram
                inner join tbusuariosus on (tbusuariosus.cpf = tbetapatelegramcpf.cpf)
                     where etapa = 3 and tbetapatelegram.statusmensagem = 2
                     limit 5";
        
        $aDados = $this->getQuery()->selectAll($sSql);
        
        return $response->withJson($aDados, 200);
    }
    
    public function getWebhook(Request $request, Response $response, array $args) {
        // Deleta o webhook para poder pegar os updates
        $this->setWebhookApi();
        
        $aDadosUpdate = $this->updatebot();
        
        // Adiciona de novo o webhook
        ControllerApiTelegram::setWebhook($url = "https://api-javalis-riodosul-38.herokuapp.com/api.php/webhook");
        
        return $response->withJson(array("status" => true, "data" => date("Y-m-d H:i:s"), "listavalidacao" => $this->aListaValidacao, "updates" => $aDadosUpdate), 200);
    }
    
    public function setWebhook(Request $request, Response $response, array $args) {
        $body = $request->getParsedBody();
        $url = isset($body["urlwebhook"]) ? $body["urlwebhook"] : false;
        if($url){
            $this->setWebhookApi($url);
            
            return $response->withJson(array("status" => true, "data" => date("Y-m-d H:i:s")), 200);
        }
        
        return $response->withJson(array("status" => false, "data" => date("Y-m-d H:i:s"), "mensagem" => "Parametro 'urlwebhook' nao informado!"), 200);
    }
    
    private function setWebhookApi($url = ""){
        ControllerApiTelegram::setWebhook($url);
    }
    
    public function removeWebhook(Request $request, Response $response, array $args) {
        $body = $request->getParsedBody();
        $url = isset($body["urlwebhook"]) ? $body["urlwebhook"] : false;
        if($url){
            ControllerApiTelegram::removeWebhook();
            
            return $response->withJson(array("status" => true, "data" => date("Y-m-d H:i:s")), 200);
        }
        
        return $response->withJson(array("status" => false, "data" => date("Y-m-d H:i:s"), "mensagem" => "Parametro 'urlwebhook' nao informado!"), 200);
    }
    
    public function getWebhookInfo(Request $request, Response $response, array $args) {
        
        $aDadosWebhook = ControllerApiTelegram::getWebhookInfo();
        
        return $response->withJson(array("status" => true, "data" => date("Y-m-d H:i:s"), "dadoswebhook" => $aDadosWebhook), 200);
    }
    
    private function updatebot(){
        $aDadosUpdate = ControllerApiTelegram::callApiTelegramUpdates();
        
        if(isset($aDadosUpdate->result)){
            $aListaUsuariosIdTelegram = array();
            foreach ($aDadosUpdate->result as $key => $aValues){
                $chatid = $aValues->message->chat->id;
                $aListaUsuariosIdTelegram[$chatid] = $chatid;
            }
            
            // Percorre os idtelegram de cada usuario
            foreach ($aListaUsuariosIdTelegram as $key => $aValues){
                // Seta o id do telegram do usuario que sera atualizado o bot
                
                $this->idtelegram = $key;
                
                // Atualiza as etapas do bot
                $this->updateEtapasBot($aDadosUpdate);
            }
        }
        
        return $aDadosUpdate;
    }
    
    protected function inicializaEtapasBot($oMensagem){
        $iEtapaAtual = $this->getEtapaAtual($oMensagem);
        
        $this->atualizaEtapasBot($oMensagem, $iEtapaAtual);
    }
    
    protected function getEtapaAtual($oMensagem){
        $sql = "select etapa from public.tbatualizacaoetapastelegram where idtelegram = $this->idtelegram";
        
        if($aDados = $this->getQuery()->select($sql)) {
            
            // valida a opcao escolhida da etapa atual
            $etapaescolhida = intval($oMensagem->mensagem);
            
            $this->validaEtapasBot($etapaescolhida);
            
            return $etapaescolhida;
        }
        
        // insere a etapa inicial
        $data_atual = date("Y-m-d H:i:s");
        
        $sql_insert = "INSERT INTO public.tbatualizacaoetapastelegram (idtelegram, etapa, dataatualizacao) VALUES($this->idtelegram, 2, '$data_atual');";
        
        $this->getQuery()->executaQuery($sql_insert);
        
        return ETAPA_OPCOES_CONSULTA;
    }
    
    protected function atualizaEtapasBot($oMensagem, $iEtapa = ETAPA_OPCOES_CONSULTA){
        switch ($iEtapa){
            case ETAPA_OPCOES_CONSULTA:
                // Se passou a etapa do CPF, vai para a proxima etapa
                $mensagemEtapas = "Escolha a opção: 10 - Listar Consultas 20 - Cancelar uma Consulta 30 - Reagendar uma Consulta 40 - Atendimento Humano - 99 - Finalizar Atendimento!";
                
                ControllerApiTelegram::sendMessage($mensagemEtapas, $this->idtelegram);
                break;
        }
    }
    
    protected function validaEtapasBot($iEtapa){
        switch ($iEtapa){
            case ETAPA_LISTAR_CONSULTA:
                ControllerApiTelegram::sendMessage("Listando Consultas - Medico:Juliano - Especialidade: Cardiologia - Data:15/12/2022 ás 15:00 horas", $this->idtelegram);
                break;
            case ETAPA_CANCELAR_CONSULTA:
                ControllerApiTelegram::sendMessage("Cancelando Consulta do Medico:Juliano - Especialidade: Cardiologia da Data:15/12/2022 ás 15:00 horas", $this->idtelegram);
                break;
            case ETAPA_REAGENDAR_CONSULTA:
                ControllerApiTelegram::sendMessage("Reagendando Consulta do Medico:Juliano - Especialidade: Cardiologia da Data:15/12/2022 ás 15:00 horas", $this->idtelegram);
                break;
            case ETAPA_ATENDIMENTO_HUMANO:
                ControllerApiTelegram::sendMessage("Atendimento Humano solicitado.Por favor aguarde, entraremos em contato em instantes...", $this->idtelegram);
                break;
            case ETAPA_FINALIZAR_ATENDIMENTO:
                
                // Remove a etapa inicial
                $sql_delete_etapa = "delete from public.tbatualizacaoetapastelegram where idtelegram = $this->idtelegram";
                $this->getQuery()->executaQuery($sql_delete_etapa);
                
                // Remove a validacao de CPF
                $sql_delete_cpf = "delete from public.tbetapatelegramcpf where idtelegram = $this->idtelegram";
                $this->getQuery()->executaQuery($sql_delete_cpf);
                
                ControllerApiTelegram::sendMessage("Atendimento Finalizado!Agradecemos pelo seu contato!Tenha um bom dia!", $this->idtelegram);
                break;
            default:
                ControllerApiTelegram::sendMessage("Opção invalida!", $this->idtelegram);
                
                // Se passou a etapa do CPF, vai para a proxima etapa
                $mensagemEtapas = "Escolha a opção: 10 - Listar Consultas 20 - Cancelar uma Consulta 30 - Reagendar uma Consulta 40 - Atendimento Humano - 99 - Finalizar Atendimento!";
                
                ControllerApiTelegram::sendMessage($mensagemEtapas, $this->idtelegram);
                break;
        }
    }
}
