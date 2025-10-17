<?php
include_once 'conexao.php'; // Sua conexão PDO

// Define o cabeçalho para indicar que a resposta será em formato JSON.
header('Content-Type: application/json');

// Resposta padrão de erro. Será alterada em caso de sucesso.
$response = ['success' => false, 'message' => 'Erro interno do servidor.'];

// ----------------------------------------------------
// 1. Validação da Requisição e Dados
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    $response['message'] = 'Método não permitido.';
    echo json_encode($response);
    exit;
}

$token = $_POST['token'] ?? null;
$nova_senha = $_POST['nova_senha'] ?? null;
$confirma_senha = $_POST['confirma_senha'] ?? null;

if (!$token || !$nova_senha || !$confirma_senha) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Todos os campos são obrigatórios.';
    echo json_encode($response);
    exit;
}

if ($nova_senha !== $confirma_senha) {
    http_response_code(400);
    $response['message'] = 'As senhas não coincidem.';
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 2. Validação da Força da Senha (CRÍTICO)
// ----------------------------------------------------
// Adicione aqui regras de validação para a senha no backend.
// Esta é a validação mais importante, pois o JavaScript pode ser burlado.
if (strlen($nova_senha) < 8) {
    http_response_code(400);
    $response['message'] = 'A senha deve ter no mínimo 8 caracteres.';
    echo json_encode($response);
    exit;
}

// Cria o hash da nova senha para armazenamento seguro no banco.
$senha_hashed = password_hash($nova_senha, PASSWORD_DEFAULT);

try {
    // ----------------------------------------------------
    // 3. Validação do Token e Atualização (Transação)
    // ----------------------------------------------------
    $conn->beginTransaction();
    
    $now = date("Y-m-d H:i:s");
    
    // Verifica novamente se o token é válido e não expirou.
    // Esta é a segunda verificação, crucial para a segurança do processo.
    $sql_check = "SELECT email FROM recuperar_senha_tolken WHERE token = :token AND expires_at > :now LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':token' => $token, ':now' => $now]);
    $reset_request = $stmt_check->fetch();

    if (!$reset_request) {
        $conn->rollBack(); // Reverte a transação
        http_response_code(401); // Unauthorized
        $response['message'] = 'O link para redefinição de senha é inválido ou já expirou. Solicite um novo.';
        echo json_encode($response);
        exit;
    }

    $user_email = $reset_request['email'];

    // Tenta atualizar a senha na tabela 'usuario'.
    $sql_update_user = "UPDATE usuario SET senha = :senha WHERE email = :email";
    $stmt_user = $conn->prepare($sql_update_user);
    $stmt_user->execute([':senha' => $senha_hashed, ':email' => $user_email]);
    
    // Se não afetou nenhuma linha em 'usuario', tenta em 'ong'.
    if ($stmt_user->rowCount() === 0) {
        $sql_update_ong = "UPDATE ong SET senha = :senha WHERE email = :email";
        $stmt_ong = $conn->prepare($sql_update_ong);
        $stmt_ong->execute([':senha' => $senha_hashed, ':email' => $user_email]);
    }
    
    // ----------------------------------------------------
    // 4. Invalidação do Token (Uso Único)
    // ----------------------------------------------------
    // Após a senha ser alterada com sucesso, o token é deletado para não ser reutilizado.
    $sql_delete = "DELETE FROM recuperar_senha_tolken WHERE token = :token";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->execute([':token' => $token]);

    // Confirma todas as operações da transação (UPDATE e DELETE).
    $conn->commit(); 
    
    $response['success'] = true;
    $response['message'] = 'Sua senha foi redefinida com sucesso!';
    
} catch (PDOException $e) {
    // Se qualquer operação no banco de dados falhar, reverte a transação.
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro na transação de troca de senha: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Ocorreu uma falha no banco de dados. Por favor, tente novamente.';
}

// Envia a resposta final (seja de sucesso ou de erro do catch) em formato JSON.
echo json_encode($response);
exit;
