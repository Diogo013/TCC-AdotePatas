<?php
session_start();
include_once 'conexao.php';
include_once 'session.php';

header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];

// 2. Validação dos Parâmetros (GET)
// O JS vai mandar: ?conversa_id=1&ultimo_id=50
$conversa_id = filter_input(INPUT_GET, 'conversa_id', FILTER_VALIDATE_INT);
$ultimo_id = filter_input(INPUT_GET, 'ultimo_id', FILTER_VALIDATE_INT);

if (!$conversa_id || $ultimo_id === null) {
    echo json_encode(['success' => false, 'messages' => []]); // Retorna vazio se dados inválidos
    exit;
}

try {
    // 3. Verificação de Segurança (O usuário pertence à conversa?)
    $sql_perm = "SELECT id_conversa FROM conversa 
                 WHERE id_conversa = :id_conversa 
                 AND (
                     (id_adotante_fk = :uid AND :utipo = 'usuario') 
                     OR 
                     (id_protetor_fk = :uid AND tipo_protetor = :utipo)
                 ) LIMIT 1";
    $stmt_perm = $conn->prepare($sql_perm);
    $stmt_perm->execute([':id_conversa' => $conversa_id, ':uid' => $user_id, ':utipo' => $user_tipo]);

    if ($stmt_perm->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }

    // 4. Busca APENAS as mensagens novas (ID maior que o último que temos)
    $sql = "SELECT id_mensagem, conteudo, data_envio, id_remetente_fk, tipo_remetente 
            FROM mensagem 
            WHERE id_conversa_fk = :id_conversa 
            AND id_mensagem > :ultimo_id 
            ORDER BY id_mensagem ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_conversa' => $conversa_id, ':ultimo_id' => $ultimo_id]);
    
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formata a data para o padrão brasileiro antes de enviar
    foreach ($mensagens as &$msg) {
        $msg['data_formatada'] = date('H:i, d/m/Y', strtotime($msg['data_envio']));
        // Flag para o front saber se a mensagem é "minha" ou "do outro"
        $msg['sou_eu'] = ($msg['id_remetente_fk'] == $user_id && $msg['tipo_remetente'] == $user_tipo);
    }

    echo json_encode(['success' => true, 'messages' => $mensagens]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro no servidor']);
}
?>