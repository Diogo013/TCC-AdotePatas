<?php
// AdotePatas/php/verificar-email.php

// Inclui sua conexão já existente com o banco de dados
include_once 'conexao.php';

// Define o cabeçalho da resposta como JSON, que é o formato que o JavaScript entende facilmente.
header('Content-Type: application/json');

// Responde apenas a requisições do tipo POST para segurança.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit();
}

// Pega os dados enviados pelo JavaScript (que virão em formato JSON).
$input = json_decode(file_get_contents('php://input'), true);

// Valida e sanitiza o e-mail recebido para garantir que é um formato válido.
$email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400); // Bad Request
    echo json_encode(['exists' => false, 'message' => 'Formato de e-mail inválido.']);
    exit();
}

try {
    // --- CONSULTA SEGURA AO BANCO DE DADOS ---
    // Usamos prepared statements para prevenir SQL Injection.

    // 1. Verifica se o e-mail existe na tabela 'usuario'
    $stmtUser = $conn->prepare("SELECT 1 FROM usuario WHERE email = :email LIMIT 1");
    $stmtUser->bindParam(':email', $email, PDO::PARAM_STR);
    $stmtUser->execute();
    $userExists = $stmtUser->fetchColumn();

    // 2. Verifica se o e-mail existe na tabela 'ong'
    $stmtOng = $conn->prepare("SELECT 1 FROM ong WHERE email = :email LIMIT 1");
    $stmtOng->bindParam(':email', $email, PDO::PARAM_STR);
    $stmtOng->execute();
    $ongExists = $stmtOng->fetchColumn();

    // Se o e-mail for encontrado em qualquer uma das tabelas, consideramos que ele existe.
    $emailExists = $userExists || $ongExists;

    // Retorna a resposta final para o JavaScript.
    echo json_encode(['exists' => (bool)$emailExists]);

} catch (PDOException $e) {
    // Em caso de erro de banco de dados, retorna uma mensagem genérica.
    http_response_code(500); // Internal Server Error
    error_log("Erro ao verificar e-mail: " . $e->getMessage()); // Loga o erro real para o desenvolvedor
    echo json_encode(['success' => false, 'message' => 'Erro interno no servidor.']);
}
?>