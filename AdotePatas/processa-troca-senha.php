<?php
include_once 'conexao.php'; // Sua conexão PDO

// CRÍTICO: Define o cabeçalho para JSON
header('Content-Type: application/json');

// Resposta padrão de erro (será alterada em caso de sucesso)
$response = ['success' => false, 'message' => 'Erro interno do servidor.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Método não permitido.';
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------
// 1. Recebe e Valida Dados
// ----------------------------------------------------
$token = $_POST['token'] ?? null;
$nova_senha = $_POST['nova_senha'] ?? null;
$confirma_senha = $_POST['confirma_senha'] ?? null;

if (!$token || !$nova_senha || !$confirma_senha) {
    http_response_code(400);
    $response['message'] = 'Dados incompletos fornecidos. Por favor, preencha todos os campos.';
    echo json_encode($response);
    exit;
}

if ($nova_senha !== $confirma_senha) {
    http_response_code(400);
    $response['message'] = 'As senhas não coincidem. Por favor, verifique.';
    echo json_encode($response);
    exit;
}

// Em um ambiente real, adicione aqui a função validarForcaSenha($nova_senha)

// Hash da nova senha para armazenamento seguro
$senha_hashed = password_hash($nova_senha, PASSWORD_DEFAULT);

try {
    // Inicia a transação para garantir que a atualização e a exclusão do token sejam atômicas
    $conn->beginTransaction();
    
    $now = date("Y-m-d H:i:s");
    
    // ----------------------------------------------------
    // 2. Valida o Token no Banco de Dados
    // ----------------------------------------------------
    $sql_check = "SELECT email FROM recuperar_senha_tolken WHERE token = :token AND expires_at > :now LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':token' => $token, ':now' => $now]);
    $reset_request = $stmt_check->fetch();

    if (!$reset_request) {
        $conn->rollBack(); // Token é inválido ou expirado. Nada mais a fazer.
        http_response_code(401);
        $response['message'] = 'O link de redefinição de senha é inválido ou expirou. Solicite um novo link.';
        echo json_encode($response);
        exit;
    }

    $user_email = $reset_request['email'];

    // ----------------------------------------------------
    // 3. Atualiza a Senha do Usuário/ONG
    // ----------------------------------------------------

    // Tenta atualizar a tabela 'usuario' (Adotante)
    $sql_update_user = "UPDATE usuario SET senha = :senha WHERE email = :email";
    $stmt_user = $conn->prepare($sql_update_user);
    $stmt_user->execute([':senha' => $senha_hashed, ':email' => $user_email]);
    $rows_updated = $stmt_user->rowCount();

    // Se nenhuma linha foi atualizada (não era usuário), tenta atualizar a tabela 'ong'
    if ($rows_updated === 0) {
        $sql_update_ong = "UPDATE ong SET senha = :senha WHERE email = :email";
        $stmt_ong = $conn->prepare($sql_update_ong);
        $stmt_ong->execute([':senha' => $senha_hashed, ':email' => $user_email]);
        $rows_updated = $stmt_ong->rowCount();
    }
    
    // ----------------------------------------------------
    // 4. Deleta o Token (Uso Único)
    // ----------------------------------------------------
    if ($rows_updated > 0) {
        $sql_delete = "DELETE FROM recuperar_senha_tolken WHERE token = :token";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->execute([':token' => $token]);

        $conn->commit(); // Confirma a atualização da senha e a exclusão do token!
        
        $response['success'] = true;
        $response['message'] = 'Sua senha foi redefinida com sucesso! Você pode entrar agora.';
        
    } else {
        $conn->rollBack(); // Nenhuma conta encontrada com este e-mail
        http_response_code(404);
        $response['message'] = 'Conta de usuário ou ONG não encontrada para este e-mail.';
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro na transação de troca de senha: " . $e->getMessage());
    http_response_code(500);
    $response['message'] = 'Falha no banco de dados. Por favor, tente novamente.';
}

echo json_encode($response);
exit;
?>
