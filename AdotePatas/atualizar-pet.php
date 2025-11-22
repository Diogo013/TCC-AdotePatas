<?php
// 1. Iniciar a sessão e carregar a conexão
include_once 'conexao.php';
include_once 'session.php';

// 2. Garantir que o usuário está logado
requerer_login();

// Função para verificar se houve mudanças nos dados
// Função para verificar se houve mudanças nos dados (exceto status)
function houveMudancas($pet_atual, $novos_dados) {
    // Comparar campos básicos (excluindo status)
    $campos_para_comparar = [
        'nome', 'especie', 'sexo', 'idade', 'porte', 'raca', 'cor',
        'status_vacinacao', 'status_castracao', 'comportamento'
    ];
    
    foreach ($campos_para_comparar as $campo) {
        if ($pet_atual[$campo] != $novos_dados[$campo]) {
            return true;
        }
    }
    
    // Comparar características (JSON)
    $caracteristicas_atuais = json_decode($pet_atual['caracteristicas'] ?? '[]', true);
    $caracteristicas_novas = $novos_dados['caracteristicas'] ?? [];
    sort($caracteristicas_atuais);
    sort($caracteristicas_novas);
    
    if ($caracteristicas_atuais != $caracteristicas_novas) {
        return true;
    }
    
    // Verificar se há fotos novas
    if (!empty($_FILES['fotos_novas']['name'][0])) {
        return true;
    }
    
    // Verificar se há fotos marcadas para exclusão
    if (!empty($_POST['fotos_para_excluir'])) {
        return true;
    }
    
    return false;
}

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
$idade_valor = $_POST['idade_valor'] ?? '';
$idade_unidade = $_POST['idade_unidade'] ?? 'anos';
$idade_final = "$idade_valor $idade_unidade";
$porte = trim($_POST['porte'] ?? '');
$raca = trim($_POST['raca'] ?? 'Não definida');
$cor = trim($_POST['cor'] ?? '');
$status_vacinacao = trim($_POST['status_vacinacao'] ?? '');
$status_castracao = trim($_POST['status_castracao'] ?? '');
$status_disponibilidade_form = trim($_POST['status_disponibilidade'] ?? 'disponivel');
$comportamento = trim($_POST['comportamento'] ?? '');
$caracteristicas = $_POST['caracteristicas'] ?? [];

// Dados das fotos
$fotos_para_excluir = $_POST['fotos_para_excluir'] ?? [];
$fotos_novas = $_FILES['fotos_novas'] ?? null;

$erros = [];
$MAX_FOTOS_GLOBAL = 5;

// 5. Validações Básicas
if (empty($id_pet)) $erros[] = "ID do pet perdido.";
if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
if (count($caracteristicas) > 5) $erros[] = "Você só pode selecionar até 5 características.";

// Buscar dados atuais do pet para comparação
$sql_check = "SELECT * FROM pet WHERE id_pet = :id_pet";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->execute([':id_pet' => $id_pet]);
$pet_atual = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$pet_atual) {
    $_SESSION['toast_message'] = "Pet não encontrado.";
    $_SESSION['toast_type'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// VERIFICAÇÃO DE PROPRIEDADE
$tem_permissao = false;
if ($user_tipo == 'usuario' && $pet_atual['id_usuario_fk'] == $user_id) $tem_permissao = true;
if ($user_tipo == 'ong' && $pet_atual['id_ong_fk'] == $user_id) $tem_permissao = true;
if ($user_tipo == 'admin') $tem_permissao = true;

if (!$tem_permissao) {
    $_SESSION['toast_message'] = "Você não tem permissão para atualizar este pet.";
    $_SESSION['toast_type'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}
// *** VERIFICAR SE HOUVE MUDANÇAS E DEFINIR STATUS ***
$mudancas_detectadas = houveMudancas($pet_atual, [
    'nome' => $nome,
    'especie' => $especie,
    'sexo' => $sexo,
    'idade' => $idade,
    'porte' => $porte,
    'raca' => $raca,
    'cor' => $cor,
    'status_vacinacao' => $status_vacinacao,
    'status_castracao' => $status_castracao,
    'comportamento' => $comportamento,
    'caracteristicas' => $caracteristicas
]);

// Lógica de status - CORRIGIDA
if ($user_tipo == 'admin') {
    // Admin sempre usa o status do formulário
    $status_disponibilidade = $status_disponibilidade_form;
} else {
    // Verifica se a única mudança foi no status
    $status_mudou = ($pet_atual['status_disponibilidade'] != $status_disponibilidade_form);
    $outras_mudancas = $mudancas_detectadas;
    
    // Se apenas o status mudou e não há outras alterações, permite a mudança
    if ($status_mudou && !$outras_mudancas) {
        $status_disponibilidade = $status_disponibilidade_form;
    } 
    // Se houve outras mudanças além do status, vai para análise
    elseif ($outras_mudancas) {
        $status_disponibilidade = 'Em Analise';
    }
    // Se não houve mudanças, mantém o status atual
    else {
        $status_disponibilidade = $pet_atual['status_disponibilidade'];
    }
}

if (!empty($erros)) {
    $_SESSION['toast_message'] = $erros[0];
    $_SESSION['toast_type'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet);
    exit;
}

// Arrays para gerenciar arquivos
$novos_caminhos_salvos = [];
$caminhos_para_excluir_fisico = [];

// Upload de nova carteirinha (se houver)
$novo_caminho_vacina = null;
if (isset($_FILES['carteira_vacinacao']) && $_FILES['carteira_vacinacao']['error'] == UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['carteira_vacinacao']['name'], PATHINFO_EXTENSION));
    $nome_vacina = uniqid('vacina_update_') . '.' . $ext;
    $destino = 'uploads/documentos/' . $nome_vacina;
    
    if (move_uploaded_file($_FILES['carteira_vacinacao']['tmp_name'], $destino)) {
        $novo_caminho_vacina = $destino;
    }
}

// 6. Iniciar Transação
$conn->beginTransaction();

try {
    // 7. PROCESSAR EXCLUSÕES DE FOTOS
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

    // 8. CONTAR FOTOS ATUAIS (APÓS EXCLUSÕES)
    $sql_count = "SELECT COUNT(*) FROM pet_fotos WHERE id_pet_fk = :id_pet_fk";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->execute([':id_pet_fk' => $id_pet]);
    $total_fotos_atuais = $stmt_count->fetchColumn();

    // 9. PROCESSAR NOVAS FOTOS
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
        
        $extensoes_permitidas = ['webp'];

        for ($i = 0; $i < $total_novas_fotos; $i++) {
            if ($fotos_novas['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = $fotos_novas['name'][$i];
                $file_tmp = $fotos_novas['tmp_name'][$i];
                $file_size = $fotos_novas['size'][$i];
                
                $file_ext_check = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext_check, $extensoes_permitidas)) {
                    throw new Exception("Foto '$file_name': Formato inválido (apenas .webp permitido).");
                }
                if ($file_size > 5 * 1024 * 1024) {
                    throw new Exception("Foto '$file_name': Imagem muito grande (Máx: 5MB).");
                }

                $novo_nome_arquivo = uniqid('', true) . '.webp';
                $caminho_completo = $upload_dir . $novo_nome_arquivo;

                if (move_uploaded_file($file_tmp, $caminho_completo)) {
                    $novos_caminhos_salvos[] = $caminho_completo;
                } else {
                    throw new Exception("Falha ao salvar a imagem '$file_name'.");
                }
            }
        }
    }
    
    // 9.1 Verificação final: não pode ficar sem fotos
    if ($total_fotos_atuais == 0 && empty($novos_caminhos_salvos)) {
        throw new Exception("O pet deve ter pelo menos 1 foto. Você excluiu todas e não adicionou nenhuma nova.");
    }

    // Se tiver nova vacina, adiciona na query
    

    // 10. ATUALIZAR DADOS DO PET
    $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);
    
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

    if ($novo_caminho_vacina) {
        $sql_update = str_replace("WHERE", ", carteira_vacinacao = :vacina WHERE", $sql_update);
    }
    
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->execute([
        ':nome' => $nome,
        ':especie' => $especie,
        ':sexo' => $sexo,
        ':idade' => $idade_final,
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

    // 11. INSERIR NOVAS FOTOS NO BANCO
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
    // Adiciona o parâmetro da vacina se necessário
    if ($novo_caminho_vacina) {
        $params[':vacina'] = $novo_caminho_vacina;
    }

    // 12. COMMIT!
    $conn->commit();

    // 13. Apagar arquivos físicos marcados para exclusão
    foreach ($caminhos_para_excluir_fisico as $caminho) {
        if (file_exists($caminho)) {
            @unlink($caminho);
        }
    }

    // 14. Redireciona de volta com sucesso
    $_SESSION['toast_message'] = "Pet atualizado com sucesso!";
    $_SESSION['toast_type'] = 'success';
    
    if ($user_tipo == 'admin') {
        header('Location: perfil?page=painel-admin');
    } else {
        header('Location: perfil?page=meus-pets');
    }
    exit;

} catch (Exception $e) {
    // 15. Se deu erro, ROLLBACK
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