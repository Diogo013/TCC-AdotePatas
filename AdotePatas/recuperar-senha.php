<?php
// Carrega o autoloader do Composer
require 'vendor/autoload.php';
include_once 'conexao.php'; // Sua conexão com o banco

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido.']);
    exit;
}

$email = $_POST['email_recuperar'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'E-mail inválido ou não fornecido.']);
    exit;
}

try {
    $user = null;
    
    // 1. Verifica em ambas as tabelas
    $sql_usuario = "SELECT nome FROM usuario WHERE email = :email LIMIT 1";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bindParam(':email', $email);
    $stmt_usuario->execute();
    $user = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $sql_ong = "SELECT nome FROM ong WHERE email = :email LIMIT 1";
        $stmt_ong = $conn->prepare($sql_ong);
        $stmt_ong->bindParam(':email', $email);
        $stmt_ong->execute();
        $user = $stmt_ong->fetch(PDO::FETCH_ASSOC);
    }
    
    // Se o e-mail não foi encontrado, retornamos sucesso para não revelar informações
    if (!$user) {
        echo json_encode(['status' => 'success', 'message' => 'Se uma conta com este e-mail existir, um link de recuperação foi enviado.']);
        exit;
    }

    // 2. (REMOVIDO) Não geramos nem salvamos token.

    // 3. Envia o e-mail com o PHPMailer
    $mail = new PHPMailer(true);

    // Configurações do Servidor
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'adotepatastcc@gmail.com'; // SEU E-MAIL
    $mail->Password   = 'adotepatastcc2025';    // SUA SENHA DE APP
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    // Remetente e Destinatário
    $mail->setFrom('nao-responda@adotepatas.com', 'Adote Patas');
    $mail->addAddress($email, $user['nome']);

    // Conteúdo do E-mail
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Redefinição de Senha - Adote Patas';
    
    // Link de redefinição simplificado (com e-mail codificado em base64)
    $user_identifier = base64_encode($email);
    $reset_link = "http://localhost/seu-projeto/redefinir_senha.php?user=" . $user_identifier;

    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Olá, {$user['nome']}!</h2>
            <p>Recebemos uma solicitação para redefinir sua senha na plataforma Adote Patas.</p>
            <p>Clique no link abaixo para criar uma nova senha:</p>
            <p style='margin: 20px 0;'>
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
    echo json_encode(['status' => 'success', 'message' => 'Link de recuperação enviado.']);

} catch (Exception $e) {
    error_log("PHPMailer/PDO Error: " . ($mail->ErrorInfo ?? $e->getMessage()));
    echo json_encode(['status' => 'error', 'message' => 'Não foi possível enviar o e-mail. Tente novamente mais tarde.']);
}
?>