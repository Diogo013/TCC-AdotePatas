<?php
// Certifique-se de ter rodado `composer require phpmailer/phpmailer`
require 'vendor/autoload.php';
include_once 'conexao.php'; // Sua conexão PDO
include_once 'session.php'; // Inclui session_start()


if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $base_path = 'localhost/TCC-AdotePatas/AdotePatas/';
} else {
    $base_path = '/'; // Para a Hostinger (adotepatas.com)
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// CRÍTICO: Remove o cabeçalho JSON e usa redirecionamento
// header('Content-Type: application/json');

// Redireciona para a página de autenticação em caso de método não POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login');
    exit;
}

// ----------------------------------------------------
// 1. Processa o E-mail Recebido
// ----------------------------------------------------
$email = $_POST['email_recuperar'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Em caso de falha na validação, redireciona para a aba de recuperação com mensagem de erro
    header('Location: login?active_tab=recuperar&recovery_error=invalid_email');
    exit;
}

try {
    // Inicia a transação para garantir que a exclusão e inserção sejam atômicas
    $conn->beginTransaction();

    // ----------------------------------------------------
    // 2. Tenta Encontrar o Usuário (Adotante ou ONG)
    // ----------------------------------------------------
    $stmtUser = $conn->prepare("SELECT email FROM usuario WHERE email = :email LIMIT 1");
    $stmtUser->execute([':email' => $email]);
    $userFound = $stmtUser->fetchColumn();

    $stmtOng = $conn->prepare("SELECT email FROM ong WHERE email = :email LIMIT 1");
    $stmtOng->execute([':email' => $email]);
    $ongFound = $stmtOng->fetchColumn();

    // ----------------------------------------------------
    // 3. Gerencia o Token de Recuperação
    // ----------------------------------------------------

    if (!$userFound && !$ongFound) {
        // Se o e-mail não for encontrado em NENHUMA tabela, retorna sucesso por segurança,
        // mas não faz a operação de token/envio de email.
        $conn->commit();
        // Redirecionamento de Sucesso FALSO (segurança)
        header('Location: login?active_tab=recuperar&recovery_success=true&email=' . urlencode($email));
        exit;
    }

    // CRÍTICO: Exclui qualquer token de recuperação anterior para este e-mail
    $stmtDelete = $conn->prepare("DELETE FROM recuperar_senha_tolken WHERE email = :email");
    $stmtDelete->execute([':email' => $email]);

    // Gera um token criptograficamente seguro (64 caracteres hexadecimais)
    $token = bin2hex(random_bytes(32)); 
    $expires = date("Y-m-d H:i:s", time() + 3600); // Expira em 1 hora

    // Insere o novo token no banco de dados
    $sqlInsert = "INSERT INTO recuperar_senha_tolken(email, token, expires_at) VALUES (:email, :token, :expires_at)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->execute([':email' => $email, ':token' => $token, ':expires_at' => $expires]);

    // Se chegamos aqui, o token foi salvo. Confirma a transação.
    $conn->commit();

    // ----------------------------------------------------
    // 4. Envio do E-mail com PHPMailer
    // ----------------------------------------------------

    // 2. (Opcional, mas recomendado) Informa o PHP para usar UTF-8 internamente
    mb_internal_encoding('UTF-8');

    $reset_link = "https:$base_path/trocar-senha.php?token=" . $token;

    $mail = new PHPMailer(true);

    // Configurações do Servidor
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'adotepatastcc@gmail.com'; // O seu email
    $mail->Password = 'ynzgbyiqaislwgme'; // Senha de App gerada
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
    $mail->Port = 465;
    
    // Destinatário
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('adotepatastcc@gmail.com', 'Adote Patas - Suporte');
    $mail->addAddress($email);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Redefinir Senha - Adote Patas';
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <h2 style='color: #bf6964;'>Recuperação de Senha Solicitada</h2>
            <p>Olá,</p>
            <p>Recebemos uma solicitação de redefinição de senha para sua conta associada ao e-mail <strong>{$email}</strong>.</p>
            <p>Clique no link abaixo para criar uma nova senha. Este link expirará em <strong>1 Hora</strong></p>
            <p style='margin: 30px 0;'>
                <a href='{$reset_link}' style='background-color: #bf6964; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                    Redefinir Minha Senha
                </a>
            </p>
            <p>Se você não solicitou esta alteração, por favor, ignore este e-mail.</p>
            <hr>
            <p style='font-size: 0.9em; color: #777;'>Atenciosamente,<br>Equipe Adote Patas</p>
        </div>
    ";
    $mail->AltBody = "Para redefinir sua senha, copie e cole este link no seu navegador: {$reset_link}";

    $mail->send();
    
    // ----------------------------------------------------
    // 5. Redirecionamento de Sucesso
    // ----------------------------------------------------
    // Redireciona para o login com a aba de recuperação ativada e exibe o e-mail
    header('Location: login?active_tab=recuperar&recovery_success=true&email=' . urlencode($email));
    exit;
    
} catch (Exception $e) {
    // ----------------------------------------------------
    // 6. Redirecionamento de Erro
    // ----------------------------------------------------
    // Em caso de falha, faz o rollback da transação e redireciona com erro genérico
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Erro ao processar recuperação de senha ou enviar e-mail: " . $e->getMessage());
    // Redireciona com um erro genérico (para não expor detalhes)
    header('Location: login?active_tab=recuperar&recovery_error=internal_error');
    exit;
}

// O script deve sair antes de chegar aqui
?>
