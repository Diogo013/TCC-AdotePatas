<?php
// 1. Iniciar a sessão e carregar a conexão
include_once 'conexao.php';
include_once 'session.php';

// 2. Garantir que o usuário está logado
requerer_login();

// 3. Garantir que é um método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['toast_message'] = "Método inválido.";
    $_SESSION['toast_type'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// 4. Coletar IDs e dados
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$id_pet = $_POST['id_pet'] ?? null;

// Dados do formulário
$nome = trim($_POST['nome'] ?? '');
$especie = trim($_POST['especie'] ?? '');
$sexo = trim($_POST['sexo'] ?? '');
$idade = trim($_POST['idade'] ?? '');
$porte = trim($_POST['porte'] ?? '');
$raca = trim($_POST['raca'] ?? 'Não definida');
$cor = trim($_POST['cor'] ?? '');
$status_vacinacao = trim($_POST['status_vacinacao'] ?? '');
$status_castracao = trim($_POST['status_castracao'] ?? '');
$status_disponibilidade = trim($_POST['status_disponibilidade'] ?? 'disponivel');
$comportamento = trim($_POST['comportamento'] ?? '');
$caracteristicas = $_POST['caracteristicas'] ?? [];

// Dados das fotos
$fotos_para_excluir = $_POST['fotos_para_excluir'] ?? []; // Array de IDs de fotos
$fotos_novas = $_FILES['fotos_novas'] ?? null; // Array de novos arquivos

$erros = [];
$MAX_FOTOS_GLOBAL = 5;

// 5. Validações Básicas
if (empty($id_pet)) $erros[] = "ID do pet perdido.";
if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
if (count($caracteristicas) > 5) $erros[] = "Você só pode selecionar até 5 características.";
// ... (Adicione outras validações de ENUMs aqui se necessário) ...

if (!empty($erros)) {
    $_SESSION['mensagem_status'] = $erros[0];
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet); // Volta para o form
    exit;
}

// Arrays para gerenciar arquivos
$novos_caminhos_salvos = [];
$caminhos_para_excluir_fisico = [];

// 6. Iniciar Transação
$conn->beginTransaction();

try {
    // 7. VERIFICAÇÃO DE PROPRIEDADE
    $sql_check = "SELECT id_usuario_fk, id_ong_fk FROM pet WHERE id_pet = :id_pet";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':id_pet' => $id_pet]);
    $pet = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$pet) throw new Exception("Pet não encontrado.");

    $tem_permissao = false;
    if ($user_tipo == 'usuario' && $pet['id_usuario_fk'] == $user_id) $tem_permissao = true;
    if ($user_tipo == 'ong' && $pet['id_ong_fk'] == $user_id) $tem_permissao = true;
    // 1. Admin pode tudo
    if ($user_tipo == 'admin') {
        $tem_permissao = true;
    } 

    if (!$tem_permissao) throw new Exception("Você não tem permissão para atualizar este pet.");

    // 8. PROCESSAR EXCLUSÕES DE FOTOS
    if (!empty($fotos_para_excluir)) {
        $sql_get_path = "SELECT caminho_foto FROM pet_fotos WHERE id_foto = :id_foto AND id_pet_fk = :id_pet_fk";
        $stmt_get_path = $conn->prepare($sql_get_path);
        
        $sql_delete = "DELETE FROM pet_fotos WHERE id_foto = :id_foto AND id_pet_fk = :id_pet_fk";
        $stmt_delete = $conn->prepare($sql_delete);

        foreach ($fotos_para_excluir as $id_foto) {
            // Pega o caminho para apagar o arquivo físico
            $stmt_get_path->execute([':id_foto' => $id_foto, ':id_pet_fk' => $id_pet]);
            $caminho = $stmt_get_path->fetchColumn();
            if ($caminho) {
                $caminhos_para_excluir_fisico[] = $caminho;
            }
            
            // Deleta o registro do banco
            $stmt_delete->execute([':id_foto' => $id_foto, ':id_pet_fk' => $id_pet]);
        }
    }

    // 9. CONTAR FOTOS ATUAIS (APÓS EXCLUSÕES)
    $sql_count = "SELECT COUNT(*) FROM pet_fotos WHERE id_pet_fk = :id_pet_fk";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->execute([':id_pet_fk' => $id_pet]);
    $total_fotos_atuais = $stmt_count->fetchColumn();

    // 10. PROCESSAR NOVAS FOTOS
    if ($fotos_novas && !empty(array_filter($fotos_novas['name']))) {
        $total_novas_fotos = count($fotos_novas['name']);
        
        if (($total_fotos_atuais + $total_novas_fotos) > $MAX_FOTOS_GLOBAL) {
            throw new Exception("Limite de $MAX_FOTOS_GLOBAL fotos excedido. Você tem $total_fotos_atuais e tentou adicionar $total_novas_fotos.");
        }
        
        if ($total_fotos_atuais == 0 && $total_novas_fotos == 0) {
             throw new Exception("O pet deve ter pelo menos 1 foto.");
        }

        $upload_dir = 'uploads/pets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        // Agora SÓ permitimos/esperamos .webp!
        $extensoes_permitidas = ['webp']; 

        for ($i = 0; $i < $total_novas_fotos; $i++) {
            if ($fotos_novas['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = $fotos_novas['name'][$i];
                $file_tmp = $fotos_novas['tmp_name'][$i];
                $file_size = $fotos_novas['size'][$i];
                
                // O JS já nos manda o nome com .webp, mas checamos por segurança
                $file_ext_check = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext_check, $extensoes_permitidas)) {
                    throw new Exception("Foto '$file_name': Formato inválido (apenas .webp permitido).");
                }
                if ($file_size > 5 * 1024 * 1024) {
                    throw new Exception("Foto '$file_name': Imagem muito grande (Máx: 5MB).");
                }

                // Forçamos um novo nome único com a extensão .webp
                $novo_nome_arquivo = uniqid('', true) . '.webp';
                $caminho_completo = $upload_dir . $novo_nome_arquivo;

                // Apenas movemos o arquivo que já veio convertido
                if (move_uploaded_file($file_tmp, $caminho_completo)) {
                    $novos_caminhos_salvos[] = $caminho_completo; // Adiciona para Inserir no DB
                } else {
                    throw new Exception("Falha ao salvar a imagem '$file_name'.");
                }
            }
        }
    }
    
    // 10.1 Verificação final: não pode ficar sem fotos
    if ($total_fotos_atuais == 0 && empty($novos_caminhos_salvos)) {
        throw new Exception("O pet deve ter pelo menos 1 foto. Você excluiu todas e não adicionou nenhuma nova.");
    }

    // 11. ATUALIZAR DADOS DO PET (Texto)
    $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);
    
    // *** SQL SEM A COLUNA 'foto' ***
    $sql_update = "UPDATE pet SET 
                        nome = :nome, 
                        especie = :especie, 
                        sexo = :sexo, 
                        idade = :idade, 
                        porte = :porte, 
                        raca = :raca, 
                        cor = :cor, 
                        status_vacinacao = :status_vacinacao, 
                        status_castracao = :status_castracao, 
                        status_disponibilidade = :status_disponibilidade, 
                        comportamento = :comportamento, 
                        caracteristicas = :caracteristicas
                    WHERE id_pet = :id_pet";
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->execute([
        ':nome' => $nome,
        ':especie' => $especie,
        ':sexo' => $sexo,
        ':idade' => $idade,
        ':porte' => $porte,
        ':raca' => $raca,
        ':cor' => $cor,
        ':status_vacinacao' => $status_vacinacao,
        ':status_castracao' => $status_castracao,
        ':status_disponibilidade' => $status_disponibilidade,
        ':comportamento' => $comportamento,
        ':caracteristicas' => $caracteristicas_json,
        ':id_pet' => $id_pet
    ]);

    // 12. INSERIR NOVAS FOTOS NO BANCO
    if (!empty($novos_caminhos_salvos)) {
        $sql_foto_insert = "INSERT INTO pet_fotos (id_pet_fk, caminho_foto) VALUES (:id_pet_fk, :caminho_foto)";
        $stmt_foto_insert = $conn->prepare($sql_foto_insert);
        
        foreach ($novos_caminhos_salvos as $caminho) {
            $stmt_foto_insert->execute([
                ':id_pet_fk' => $id_pet,
                ':caminho_foto' => $caminho
            ]);
        }
    }

    // 13. COMMIT!
    $conn->commit();

    // 14. Se deu tudo certo (commit), apaga os arquivos físicos marcados
    foreach ($caminhos_para_excluir_fisico as $caminho) {
        if (file_exists($caminho)) {
            @unlink($caminho);
        }
    }

// 15. Redireciona de volta com sucesso
$_SESSION['toast_message'] = "Pet atualizado com sucesso!";
$_SESSION['toast_type'] = 'success';
// --- REDIRECIONAMENTO INTELIGENTE ---
if ($user_tipo == 'admin') {
    header('Location: perfil?page=painel-admin');
} else {
    header('Location: perfil?page=meus-pets');
}
exit;

} catch (Exception $e) {
    // 16. Se deu erro, ROLLBACK
    $conn->rollBack();
    
    // Apaga qualquer arquivo novo que tenha sido salvo no servidor
    foreach ($novos_caminhos_salvos as $caminho) {
        if (file_exists($caminho)) {
            @unlink($caminho);
        }
    }

// Redireciona de volta para a edição com o erro
$_SESSION['toast_message'] = $e->getMessage();
$_SESSION['toast_type'] = 'danger';
header('Location: editar-pet.php?id=' . $id_pet);
exit;
}
?>