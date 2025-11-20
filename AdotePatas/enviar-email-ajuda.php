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

// 2. Recebe os dados JSON do JavaScript
$input = json_decode(file_get_contents('php://input'), true);

$nome = trim($input['nome'] ?? '');
$email = trim($input['email'] ?? '');
$assunto = trim($input['assunto'] ?? '');
$mensagem = trim($input['mensagem'] ?? '');

// 3. Validação Básica
if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}

// 4. Configuração do PHPMailer
$mail = new PHPMailer(true);

try {
    // --- Configurações do Servidor (Iguais ao recuperar-senha.php) ---
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'adotepatastcc@gmail.com'; // Seu e-mail do projeto
    $mail->Password   = 'ynzgbyiqaislwgme';        // Sua senha de App
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8'; // Garante acentos corretos

    // --- Destinatários ---
    // Quem envia: O sistema (autenticado)
    $mail->setFrom('adotepatastcc@gmail.com', 'Adote Patas - Contato Site');
    
    // Quem recebe: A administração do site (você mesmo)
    $mail->addAddress('adotepatastcc@gmail.com', 'Administração Adote Patas');
    
    // Responder para: O e-mail do usuário que preencheu o formulário
    // Assim, quando você clicar em "Responder" no Gmail, vai direto para o usuário.
    $mail->addReplyTo($email, $nome);

    // --- Conteúdo ---
    $mail->isHTML(true);
    $mail->Subject = "Fale Conosco: " . $assunto;
    
    // Corpo do e-mail bonito em HTML
    $mail->Body    = "
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
    
    // Texto puro para clientes de e-mail antigos
    $mail->AltBody = "Nova mensagem de contato:\n\nNome: {$nome}\nE-mail: {$email}\nAssunto: {$assunto}\n\nMensagem:\n{$mensagem}";

    // 5. Envia
    $mail->send();
    
    echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso! Em breve entraremos em contato.']);

} catch (Exception $e) {
    // Loga o erro no servidor para debug (opcional)
    // error_log("Erro PHPMailer: {$mail->ErrorInfo}");
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar e-mail. Tente novamente mais tarde.']);
}
?>