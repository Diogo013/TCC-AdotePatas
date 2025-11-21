<?php
// Carrega o autoload do Composer para usar o PHPMailer
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// 1. Verifica se é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// 2. Recebe e valida dados de uma vez
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$nome = trim($input['nome'] ?? '');
$email = trim($input['email'] ?? '');
$assunto = trim($input['assunto'] ?? '');
$mensagem = trim($input['mensagem'] ?? '');

// 3. Validação mais eficiente
$errors = [];
if (empty($nome)) $errors[] = 'nome';
if (empty($email)) $errors[] = 'email';
if (empty($assunto)) $errors[] = 'assunto';
if (empty($mensagem)) $errors[] = 'mensagem';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios: ' . implode(', ', $errors)]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}

// 4. Template HTML pré-definido (evita concatenação complexa)
$emailTemplate = function($nome, $email, $assunto, $mensagem) {
    return "
    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
        <div style='background-color: #bf6964; color: white; padding: 20px; text-align: center;'>
            <h2 style='margin: 0;'>Nova Mensagem de Contato</h2>
        </div>
        <div style='padding: 20px;'>
            <p><strong>De:</strong> {$nome}</p>
            <p><strong>E-mail:</strong> {$email}</p>
            <p><strong>Assunto:</strong> {$assunto}</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <h3 style='color: #bf6964;'>Mensagem:</h3>
            <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #bf6964;'>
                {$mensagem}
            </div>
        </div>
        <div style='background-color: #f4f4f4; padding: 10px; text-align: center; font-size: 0.8em; color: #777;'>
            Enviado através do formulário de contato do site Adote Patas.
        </div>
    </div>
    ";
};

// 5. Configuração otimizada do PHPMailer
$mail = new PHPMailer(true);

try {
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'adotepatastcc@gmail.com';
    $mail->Password   = 'ynzgbyiqaislwgme';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    
    // Timeout reduzido para evitar espera longa
    $mail->Timeout    = 10; // 10 segundos
    $mail->SMTPDebug  = 0; // Garantir que debug está desligado

    // Destinatários
    $mail->setFrom('adotepatastcc@gmail.com', 'Adote Patas - Contato Site', false);
    $mail->addAddress('adotepatastcc@gmail.com', 'Administração Adote Patas');
    $mail->addReplyTo($email, $nome);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = "Fale Conosco: " . $assunto;
    $mail->Body    = $emailTemplate($nome, $email, $assunto, $mensagem);
    $mail->AltBody = "Nova mensagem de contato:\n\nNome: {$nome}\nE-mail: {$email}\nAssunto: {$assunto}\n\nMensagem:\n{$mensagem}";

    // 6. Envio rápido com timeout
    $mail->send();
    
    echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail. Tente novamente mais tarde.']);
    // Log de erro opcional (manter comentado em produção)
    // error_log("Erro PHPMailer: " . $e->getMessage());
}
?>