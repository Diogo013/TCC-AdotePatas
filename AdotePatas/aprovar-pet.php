<?php
session_start();
include_once 'conexao.php';

// Verifica se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Verifica se o ID do pet foi enviado
if (!isset($_POST['id_pet']) || empty($_POST['id_pet'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'ID do pet não fornecido.']);
    exit;
}

$pet_id = intval($_POST['id_pet']);

try {
    // Verifica se o pet existe e está em análise
    $sql_verifica = "SELECT id_pet, status_disponibilidade FROM pet WHERE id_pet = :id_pet";
    $stmt_verifica = $conn->prepare($sql_verifica);
    $stmt_verifica->bindParam(':id_pet', $pet_id, PDO::PARAM_INT);
    $stmt_verifica->execute();
    $pet = $stmt_verifica->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        echo json_encode(['success' => false, 'message' => 'Pet não encontrado.']);
        exit;
    }
    
    if ($pet['status_disponibilidade'] !== 'Em Analise') {
        echo json_encode(['success' => false, 'message' => 'Este pet não está em análise.']);
        exit;
    }
    
    // Atualiza o status para 'disponivel'
    $sql_atualiza = "UPDATE pet SET status_disponibilidade = 'disponivel' WHERE id_pet = :id_pet";
    $stmt_atualiza = $conn->prepare($sql_atualiza);
    $stmt_atualiza->bindParam(':id_pet', $pet_id, PDO::PARAM_INT);
    
    if ($stmt_atualiza->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pet aprovado com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status do pet.']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao aprovar pet: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor. Tente novamente.']);
}