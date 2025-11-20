<?php
require_once 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $conversas;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->conversas = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nova conexão! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) return;

        switch ($data['type']) {
            case 'subscribe':
                // Cliente se inscreve em uma conversa
                $conversaId = $data['conversa_id'];
                $this->conversas[$conversaId][$from->resourceId] = $from;
                $from->conversa_id = $conversaId;
                echo "Cliente {$from->resourceId} inscrito na conversa {$conversaId}\n";
                break;

            case 'message':
                // Nova mensagem - enviar para todos na conversa
                $conversaId = $data['conversa_id'];
                $mensagem = $data['mensagem'];
                
                $this->broadcastToConversa($conversaId, [
                    'type' => 'new_message',
                    'mensagem' => $mensagem,
                    'user_id' => $data['user_id'],
                    'user_tipo' => $data['user_tipo'],
                    'timestamp' => date('H:i, d/m/Y')
                ]);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        if (isset($conn->conversa_id) && isset($this->conversas[$conn->conversa_id])) {
            unset($this->conversas[$conn->conversa_id][$conn->resourceId]);
        }
        $this->clients->detach($conn);
        echo "Conexão {$conn->resourceId} desconectada\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }

    private function broadcastToConversa($conversaId, $data) {
        if (!isset($this->conversas[$conversaId])) return;

        foreach ($this->conversas[$conversaId] as $client) {
            $client->send(json_encode($data));
        }
    }
}

// Iniciar servidor na porta 8080
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatWebSocket()
        )
    ),
    8080
);

echo "Servidor WebSocket rodando na porta 8080...\n";
$server->run();