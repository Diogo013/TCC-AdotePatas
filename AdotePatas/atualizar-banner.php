<?php
session_start();
include_once 'conexao.php';

// Debug: log para verificar se o script está sendo chamado
error_log("atualizar-banner.php foi chamado");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    error_log("Usuário não autorizado");
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$banner = $_POST['banner'] ?? '';

error_log("Dados recebidos - User ID: $user_id, Tipo: $user_tipo, Banner: $banner");

if (empty($banner)) {
    error_log("Banner vazio");
    echo json_encode(['success' => false, 'message' => 'Banner não especificado.']);
    exit;
}

// Valida se o banner existe na lista permitida
$bannersPermitidos = ['banner1.jpg', 'banner2.jpg', 'banner3.jpg', 'banner4.jpg', 'banner5.jpg'];
if (!in_array($banner, $bannersPermitidos)) {
    error_log("Banner inválido: $banner");
    echo json_encode(['success' => false, 'message' => 'Banner inválido.']);
    exit;
}

try {
    if ($user_tipo == 'adotante') {
        $sql = "UPDATE usuario SET banner_fixo = :banner WHERE id_usuario = :id";
    } elseif ($user_tipo == 'protetor') {
        $sql = "UPDATE ong SET banner_fixo = :banner WHERE id_ong = :id";
    } else {
        error_log("Tipo de usuário inválido: $user_tipo");
        echo json_encode(['success' => false, 'message' => 'Tipo de usuário inválido.']);
        exit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':banner', $banner);
    $stmt->bindParam(':id', $user_id);
    
    if ($stmt->execute()) {
        error_log("Banner atualizado com sucesso para: $banner");
        echo json_encode(['success' => true, 'message' => 'Banner atualizado com sucesso!']);
    } else {
        error_log("Erro ao executar query");
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar banner no banco de dados.']);
    }
} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor: ' . $e->getMessage()]);
}