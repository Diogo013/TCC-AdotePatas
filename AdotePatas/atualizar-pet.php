<?php
// 1. Iniciar a sessão e carregar a conexão
include_once 'conexao.php';
include_once 'session.php';

// 2. Garantir que o usuário está logado
requerer_login();

// 3. Garantir que é um método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['mensagem_status'] = "Método inválido.";
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// 4. Coletar IDs e dados da sessão
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$id_pet = $_POST['id_pet'] ?? null;
$foto_atual = $_POST['foto_atual'] ?? '';

// 5. Coletar e validar dados do formulário
// (Exatamente como em cadastrar-pet.php)
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

$erros = [];

// Validações
if (empty($id_pet)) $erros[] = "ID do pet perdido.";
if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
if (empty($especie)) $erros[] = "O campo 'Espécie' é obrigatório.";
if (empty($sexo)) $erros[] = "O campo 'Gênero' é obrigatório.";
if (count($caracteristicas) > 5) {
        $erros[] = "Você só pode selecionar até 5 características.";
}
// ... (Adicione todas as outras validações de ENUM, etc., do cadastrar-pet.php) ...

// Se houver erros de validação, redireciona de volta para a edição
if (!empty($erros)) {
    $_SESSION['mensagem_status'] = $erros[0];
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet); // Volta para o form
    exit;
}

try {
    // 6. VERIFICAÇÃO DE PROPRIEDADE (Dupla checagem)
    $sql_check = "SELECT id_usuario_fk, id_ong_fk, foto FROM pet WHERE id_pet = :id_pet";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':id_pet' => $id_pet]);
    $pet = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        throw new Exception("Pet não encontrado.");
    }

    $tem_permissao = false;
    if ($user_tipo == 'adotante' && $pet['id_usuario_fk'] == $user_id) $tem_permissao = true;
    if ($user_tipo == 'protetor' && $pet['id_ong_fk'] == $user_id) $tem_permissao = true;

    if (!$tem_permissao) {
        throw new Exception("Você não tem permissão para atualizar este pet.");
    }

    // 7. LÓGICA DE UPLOAD DE NOVA FOTO
    $caminho_foto_db = $foto_atual; // Começa com a foto antiga

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
        // Se uma NOVA foto foi enviada, processa ela
        $upload_dir = 'uploads/pets/';
        $file_info = $_FILES['foto'];
        $file_ext_check = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $extensoes_permitidas = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext_check, $extensoes_permitidas) && $file_info['size'] <= 5 * 1024 * 1024) {
            
            // Apaga a foto antiga (se ela existir)
            if (!empty($foto_atual) && file_exists($foto_atual)) {
                @unlink($foto_atual);
            }

            // Salva a nova foto
            $novo_nome_arquivo = uniqid('', true) . '.' . $file_ext_check;
            $caminho_completo = $upload_dir . $novo_nome_arquivo;
            
            if (move_uploaded_file($file_info['tmp_name'], $caminho_completo)) {
                $caminho_foto_db = $caminho_completo; // Atualiza o caminho para o DB
            } else {
                throw new Exception("Falha ao mover o novo arquivo de foto.");
            }
        } else {
            throw new Exception("Formato de imagem inválido ou foto muito grande.");
        }
    }
    $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);

    // 8. ATUALIZAR O BANCO DE DADOS
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
                        foto = :foto,
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
        ':foto' => $caminho_foto_db,
        ':caracteristicas' => $caracteristicas_json,
        ':id_pet' => $id_pet
    ]);

    // 9. Redireciona de volta com sucesso
    $_SESSION['mensagem_status'] = "Pet atualizado com sucesso!";
    $_SESSION['tipo_mensagem'] = 'success';
    header('Location: perfil.php?page=meus-pets');
    exit;

} catch (Exception $e) {
    // 10. Se der qualquer erro, redireciona de volta para a edição
    $_SESSION['mensagem_status'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: editar-pet.php?id=' . $id_pet);
    exit;
}
?>
