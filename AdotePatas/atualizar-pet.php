<?php
// 1. Iniciar a sessão e carregar a conexão
include_once 'conexao.php';
include_once 'session.php';

// 2. Garantir que o usuário está logado
requerer_login();

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
    
    // Comparar alergias
    $alergias_atuais = json_decode($pet_atual['alergias'] ?? '[]', true);
    $alergias_novas = $novos_dados['alergias'] ?? [];
    sort($alergias_atuais);
    sort($alergias_novas);
    
    if ($alergias_atuais != $alergias_novas) {
        return true;
    }
    
    // Comparar medicação
    if ($pet_atual['medicacao'] != $novos_dados['medicacao']) {
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
    
    // Verificar se há nova carteirinha de vacinação
    if (isset($_FILES['carteira_vacinacao']) && $_FILES['carteira_vacinacao']['error'] == UPLOAD_ERR_OK) {
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
$alergias = $_POST['alergias'] ?? [];
$medicacao = trim($_POST['medicacao'] ?? '');

// Dados das fotos
$fotos_para_excluir = $_POST['fotos_para_excluir'] ?? [];
$fotos_novas = $_FILES['fotos_novas'] ?? null;

$erros = [];
$MAX_FOTOS_GLOBAL = 5;

// 5. Validações Básicas
if (empty($id_pet)) $erros[] = "ID do pet perdido.";
if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
if (count($caracteristicas) > 5) $erros[] = "Você só pode selecionar até 5 características.";

// Validação da idade - apenas números
if (!empty($idade_valor) && !is_numeric($idade_valor)) {
    $erros[] = "A idade deve conter apenas números.";
}

// Validação de idade máxima em meses
if ($idade_unidade == 'meses' && $idade_valor > 11) {
    $erros[] = "A idade em meses não pode ser maior que 11. Para idades maiores, selecione 'Anos'.";
}

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
    'idade' => $idade_final,
    'porte' => $porte,
    'raca' => $raca,
    'cor' => $cor,
    'status_vacinacao' => $status_vacinacao,
    'status_castracao' => $status_castracao,
    'comportamento' => $comportamento,
    'caracteristicas' => $caracteristicas,
    'alergias' => $alergias,
    'medicacao' => $medicacao
]);

// Lógica de status
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
    $vacina_file = $_FILES['carteira_vacinacao'];
    $ext = strtolower(pathinfo($vacina_file['name'], PATHINFO_EXTENSION));
    $allowed_vacina = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
    
    if (!in_array($ext, $allowed_vacina)) {
        $erros[] = "Formato da carteirinha inválido. Use JPG, PNG, WEBP ou PDF.";
    } else {
        $upload_dir_vacina = 'uploads/documentos/';
        if (!is_dir($upload_dir_vacina)) mkdir($upload_dir_vacina, 0755, true);
        
        $nome_vacina = uniqid('vacina_update_') . '.' . $ext;
        $caminho_vacina = $upload_dir_vacina . $nome_vacina;
        
        if (!move_uploaded_file($vacina_file['tmp_name'], $caminho_vacina)) {
            $erros[] = "Erro ao salvar carteirinha de vacinação.";
        } else {
            $novo_caminho_vacina = $caminho_vacina;
        }
    }
}

if (!empty($erros)) {
    $_SESSION['toast_message'] = $erros[0];
    $_SESSION['toast_type'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet);
    exit;
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

    // 10. PROCESSAR ALERGIAS E MEDICAÇÃO
    $alergias_limpas = array_filter($alergias, function($alergia) {
        return !empty(trim($alergia));
    });
    $alergias_json = !empty($alergias_limpas) ? json_encode($alergias_limpas, JSON_UNESCAPED_UNICODE) : null;
    $medicacao_limpa = !empty(trim($medicacao)) ? trim($medicacao) : null;
    $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);
    
    // 11. ATUALIZAR DADOS DO PET
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
                        caracteristicas = :caracteristicas,
                        alergias = :alergias,
                        medicacao = :medicacao";

    // Adiciona o campo da carteira de vacinação se houver nova
    if ($novo_caminho_vacina) {
        $sql_update .= ", carteira_vacinacao = :carteira_vacinacao";
    }

    $sql_update .= " WHERE id_pet = :id_pet";

    $params = [
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
        ':alergias' => $alergias_json,
        ':medicacao' => $medicacao_limpa,
        ':id_pet' => $id_pet
    ];

    // Adiciona o parâmetro da vacina se necessário
    if ($novo_caminho_vacina) {
        $params[':carteira_vacinacao'] = $novo_caminho_vacina;
    }

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->execute($params);

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

    // 14. Apagar arquivos físicos marcados para exclusão
    foreach ($caminhos_para_excluir_fisico as $caminho) {
        if (file_exists($caminho)) {
            @unlink($caminho);
        }
    }

    // Se houve upload de nova carteirinha, apaga a antiga
    if ($novo_caminho_vacina && !empty($pet_atual['carteira_vacinacao']) && file_exists($pet_atual['carteira_vacinacao'])) {
        @unlink($pet_atual['carteira_vacinacao']);
    }

    // 15. Redireciona de volta com sucesso
    $_SESSION['toast_message'] = "Pet atualizado com sucesso!";
    $_SESSION['toast_type'] = 'success';
    
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
    
    // Apaga a nova carteirinha se foi salva
    if ($novo_caminho_vacina && file_exists($novo_caminho_vacina)) {
        @unlink($novo_caminho_vacina);
    }

    // Redireciona de volta para a edição com o erro
    $_SESSION['toast_message'] = $e->getMessage();
    $_SESSION['toast_type'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet);
    exit;
}