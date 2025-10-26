<?php
// 1. Iniciar a sessão e carregar a conexão
include_once 'conexao.php';
include_once 'session.php';

// 2. Garantir que o usuário está logado
requerer_login();

// 3. Pegar IDs da sessão e da URL
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$id_pet_para_excluir = $_GET['id'] ?? null;

// Se não for passado um ID, redireciona com erro
if (empty($id_pet_para_excluir)) {
    $_SESSION['mensagem_status'] = "ID do pet não fornecido.";
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

try {
    // 4. VERIFICAÇÃO DE PROPRIEDADE (A PARTE MAIS IMPORTANTE)
    // Primeiro, buscamos o pet e quem é o dono dele
    $sql_check = "SELECT foto, id_usuario_fk, id_ong_fk FROM pet WHERE id_pet = :id_pet";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':id_pet' => $id_pet_para_excluir]);
    $pet = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        // Pet não existe
        throw new Exception("Pet não encontrado.");
    }

    // Agora, verificamos se o dono do pet é o usuário logado
    $tem_permissao = false;
    if ($user_tipo == 'adotante' && $pet['id_usuario_fk'] == $user_id) {
        $tem_permissao = true;
    } elseif ($user_tipo == 'protetor' && $pet['id_ong_fk'] == $user_id) {
        $tem_permissao = true;
    }

    if (!$tem_permissao) {
        // Se não for o dono, é uma tentativa de acesso indevido
        throw new Exception("Você não tem permissão para excluir este pet.");
    }

    // 5. SE TEM PERMISSÃO, PROSSEGUE COM A EXCLUSÃO
    
    // Deleta o pet do banco de dados
    $sql_delete = "DELETE FROM pet WHERE id_pet = :id_pet";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->execute([':id_pet' => $id_pet_para_excluir]);

    // 6. Tenta apagar a foto do servidor
    if (!empty($pet['foto']) && file_exists($pet['foto'])) {
        @unlink($pet['foto']); // O '@' suprime erros caso não consiga apagar
    }

    // 7. Redireciona de volta com sucesso (Padrão PRG)
    $_SESSION['mensagem_status'] = "Pet excluído com sucesso!";
    $_SESSION['tipo_mensagem'] = 'success';
    header('Location: perfil.php?page=meus-pets');
    exit;

} catch (Exception $e) {
    // 8. Se der qualquer erro, redireciona com a mensagem de falha
    $_SESSION['mensagem_status'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}
?>
