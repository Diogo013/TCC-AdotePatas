<?php
session_start();
include_once 'conexao.php';

// Segurança: Só admin pode acessar
if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'admin') {
    header("Location: login");
    exit;
}

$acao = $_GET['acao'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['toast_message'] = "ID inválido.";
    $_SESSION['toast_type'] = "danger";
    header("Location: perfil?page=painel-admin");
    exit;
}

try {
    if ($acao == 'excluir_usuario') {
        // Excluir Usuário (PF)
        // O CASCADE do banco deve apagar pets, conversas e solicitações vinculadas
        $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        
        $_SESSION['toast_message'] = "Usuário excluído com sucesso.";
        $_SESSION['toast_type'] = "success";

    } elseif ($acao == 'excluir_ong') {
        // Excluir ONG (PJ)
        $stmt = $conn->prepare("DELETE FROM ong WHERE id_ong = :id");
        $stmt->execute([':id' => $id]);
        
        $_SESSION['toast_message'] = "ONG excluída com sucesso.";
        $_SESSION['toast_type'] = "success";

    } elseif ($acao == 'excluir_pet') {
        // Excluir Pet
        $stmt = $conn->prepare("DELETE FROM pet WHERE id_pet = :id");
        $stmt->execute([':id' => $id]);
        
        $_SESSION['toast_message'] = "Pet excluído com sucesso.";
        $_SESSION['toast_type'] = "success";
    }

} catch (PDOException $e) {
    $_SESSION['toast_message'] = "Erro ao excluir: " . $e->getMessage();
    $_SESSION['toast_type'] = "danger";
}

// Volta para o painel
header("Location: perfil?page=painel-admin");
exit;
?>