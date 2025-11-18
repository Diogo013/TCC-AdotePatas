<?php
session_start();
include_once 'conexao.php';

// Define o tipo de resposta como JSON
header('Content-Type: application/json');

// --- 1. Validação de Segurança Básica ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    // 403 Forbidden: Usuário não logado
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

// Pega os dados do usuário logado
$user_id_logado = $_SESSION['user_id'];
$user_tipo_logado = $_SESSION['user_tipo'];

// --- 2. Validação da Requisição ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 405 Method Not Allowed: Apenas POST é permitido
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Pega o corpo da requisição (enviamos como JSON)
$data = json_decode(file_get_contents('php://input'), true);

$conversa_id = $data['conversa_id'] ?? null;
$conteudo = $data['conteudo'] ?? null;

// Valida os dados recebidos
if (empty($conversa_id) || !filter_var($conversa_id, FILTER_VALIDATE_INT) || empty(trim($conteudo))) {
    // 400 Bad Request: Dados inválidos
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados da conversa ou conteúdo inválidos.']);
    exit;
}

try {
    // --- 3. Validação de Segurança CRÍTICA ---
    // Verifica se o usuário logado REALMENTE pertence a esta conversa
    $sql_check = "SELECT id_conversa FROM conversa 
                  WHERE id_conversa = :conversa_id 
                  AND (
                      (id_adotante_fk = :user_id AND :user_tipo = 'usuario')
                      OR 
                      (id_protetor_fk = :user_id AND tipo_protetor = :user_tipo)
                  )
                  LIMIT 1";
                  
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([
        ':conversa_id' => $conversa_id,
        ':user_id' => $user_id_logado,
        ':user_tipo' => $user_tipo_logado
    ]);

    if ($stmt_check->rowCount() == 0) {
        // 403 Forbidden: Usuário tentando enviar msg para conversa alheia
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para enviar mensagens nesta conversa.']);
        exit;
    }

    // --- 4. Inserir a Mensagem no Banco ---
    $sql_insert = "INSERT INTO mensagem 
                        (id_conversa_fk, id_remetente_fk, tipo_remetente, conteudo, data_envio)
                   VALUES
                        (:id_conversa, :id_remetente, :tipo_remetente, :conteudo, NOW())";
                        
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([
        ':id_conversa' => $conversa_id,
        ':id_remetente' => $user_id_logado,
        ':tipo_remetente' => $user_tipo_logado,
        ':conteudo' => trim($conteudo) // Salva a msg limpa
    ]);

    if ($stmt_insert->rowCount() > 0) {
        // 200 OK: Sucesso
        // Define o fuso horário para formatar a data de volta
        date_default_timezone_set('America/Sao_Paulo');
        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada.',
            'timestamp' => date('H:i, d/m/Y') // Envia a data/hora formatada
        ]);
    } else {
        throw new Exception("Falha ao inserir a mensagem no banco de dados.");
    }

} catch (Exception $e) {
    // 500 Internal Server Error: Erro no servidor
    http_response_code(500);
    error_log("Erro em enviar_mensagem.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>