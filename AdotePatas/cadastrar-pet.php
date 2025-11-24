<?php
// Inclui a conexão e a sessão
include_once 'conexao.php';
include_once 'session.php';

// Protege a página
requerer_login();

// --- LÊ MENSAGENS DA SESSÃO ---
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';

unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);

// --- INICIALIZA VARIÁVEIS ---
$nome = $_SESSION['form_data']['nome'] ?? '';
$especie = $_SESSION['form_data']['especie'] ?? '';
$idade_valor = $_SESSION['form_data']['idade_valor'] ?? '';
$idade_unidade = $_SESSION['form_data']['idade_unidade'] ?? 'anos';
$porte = $_SESSION['form_data']['porte'] ?? '';
$sexo = $_SESSION['form_data']['sexo'] ?? ''; 
$raca = $_SESSION['form_data']['raca'] ?? '';
$cor = $_SESSION['form_data']['cor'] ?? '';
$status_vacinacao = $_SESSION['form_data']['status_vacinacao'] ?? '';
$status_castracao = $_SESSION['form_data']['status_castracao'] ?? '';
$comportamento = $_SESSION['form_data']['comportamento'] ?? '';
$caracteristicas = $_SESSION['form_data']['caracteristicas'] ?? [];
$alergias = $_SESSION['form_data']['alergias'] ?? [''];
$medicacao = $_SESSION['form_data']['medicacao'] ?? '';

unset($_SESSION['form_data']);

// --- PROCESSAMENTO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Coleta de dados
    $nome = trim($_POST['nome'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    
    // Tratamento da Idade Composta
    $idade_valor = trim($_POST['idade_valor'] ?? '');
    $idade_unidade = trim($_POST['idade_unidade'] ?? 'anos');
    $idade_final = "$idade_valor $idade_unidade";

    $porte = trim($_POST['porte'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $raca = trim($_POST['raca'] ?? 'Não definida');
    $cor = trim($_POST['cor'] ?? '');
    $status_vacinacao = trim($_POST['status_vacinacao'] ?? '');
    $status_castracao = trim($_POST['status_castracao'] ?? '');
    $comportamento = trim($_POST['comportamento'] ?? '');
    $caracteristicas = $_POST['caracteristicas'] ?? [];
    $alergias = $_POST['alergias'] ?? [];
    $medicacao = trim($_POST['medicacao'] ?? '');
    
    // Novas validações
    $amamentacao_completa = isset($_POST['amamentacao_completa']);
    
    $_SESSION['form_data'] = $_POST;
    $id_usuario_logado = $_SESSION['user_id'];
    $tipo_usuario_logado = $_SESSION['user_tipo'];

    $erros = [];
    $fotos_salvas_paths = [];
    $caminho_vacina = null;

    // 2. Validações
    if (empty($nome)) $erros['nome'] = "O campo 'Nome' é obrigatório.";
    if (empty($idade_valor)) $erros['idade'] = "Informe o valor da idade.";
    
    // Validação da idade - apenas números
    if (!empty($idade_valor) && !is_numeric($idade_valor)) {
        $erros['idade'] = "A idade deve conter apenas números.";
    }
    
    // Validação de idade máxima em meses
    if ($idade_unidade == 'meses' && $idade_valor > 11) {
        $erros['idade'] = "A idade em meses não pode ser maior que 11. Para idades maiores, selecione 'Anos'.";
    }
    
    // Validação de Amamentação (Regra de Negócio)
    if (!$amamentacao_completa) {
        $erros['amamentacao'] = "O pet não pode ser cadastrado se não tiver concluído a amamentação.";
    }

// Validação de Upload da Carteirinha (Obrigatório apenas se vacinado)
if ($status_vacinacao === 'sim') {
    if (!isset($_FILES['carteira_vacinacao']) || $_FILES['carteira_vacinacao']['error'] != UPLOAD_ERR_OK) {
        $erros['carteira_vacinacao'] = "É obrigatório enviar a foto ou PDF da carteirinha de vacinação para comprovação, pois o pet foi marcado como vacinado.";
    } else {
        // Processar Carteirinha
        $vacina_file = $_FILES['carteira_vacinacao'];
        $ext = strtolower(pathinfo($vacina_file['name'], PATHINFO_EXTENSION));
        $allowed_vacina = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        
        if (!in_array($ext, $allowed_vacina)) {
            $erros['carteira_vacinacao'] = "Formato da carteirinha inválido. Use JPG, PNG, WEBP ou PDF.";
        } else {
            $upload_dir_vacina = 'uploads/documentos/';
            if (!is_dir($upload_dir_vacina)) mkdir($upload_dir_vacina, 0755, true);
            
            $nome_vacina = uniqid('vacina_') . '.' . $ext;
            $caminho_vacina = $upload_dir_vacina . $nome_vacina;
            
            if (!move_uploaded_file($vacina_file['tmp_name'], $caminho_vacina)) {
                $erros['carteira_vacinacao'] = "Erro ao salvar carteirinha de vacinação.";
            }
        }
    }
} else {
    // Se não for vacinado, não é obrigatório enviar carteirinha
    // Mas se enviou, processa normalmente (opcional)
    if (isset($_FILES['carteira_vacinacao']) && $_FILES['carteira_vacinacao']['error'] == UPLOAD_ERR_OK) {
        $vacina_file = $_FILES['carteira_vacinacao'];
        $ext = strtolower(pathinfo($vacina_file['name'], PATHINFO_EXTENSION));
        $allowed_vacina = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        
        if (!in_array($ext, $allowed_vacina)) {
            $erros['carteira_vacinacao'] = "Formato da carteirinha inválido. Use JPG, PNG, WEBP ou PDF.";
        } else {
            $upload_dir_vacina = 'uploads/documentos/';
            if (!is_dir($upload_dir_vacina)) mkdir($upload_dir_vacina, 0755, true);
            
            $nome_vacina = uniqid('vacina_') . '.' . $ext;
            $caminho_vacina = $upload_dir_vacina . $nome_vacina;
            
            if (!move_uploaded_file($vacina_file['tmp_name'], $caminho_vacina)) {
                $erros['carteira_vacinacao'] = "Erro ao salvar carteirinha de vacinação.";
            }
        }
    } else {
        $caminho_vacina = null; // Não é vacinado e não enviou arquivo
    }
}
    // Validação das Fotos do Pet
    if (isset($_FILES['fotos_novas']) && !empty(array_filter($_FILES['fotos_novas']['name']))) {
        $total_files = count($_FILES['fotos_novas']['name']);
        if ($total_files > 5) $erros['fotos'] = "Máximo 5 fotos.";
        
        $upload_dir = 'uploads/pets/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['fotos_novas']['error'][$i] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['fotos_novas']['tmp_name'][$i];
                $name = uniqid() . '.webp';
                if (move_uploaded_file($tmp_name, $upload_dir . $name)) {
                    $fotos_salvas_paths[] = $upload_dir . $name;
                }
            }
        }
    } else {
        $erros['fotos'] = "Pelo menos uma foto do pet é obrigatória.";
    }

    // 3. Inserção
    if (!empty($erros)) {
        $_SESSION['erros_form'] = $erros;
        $_SESSION['mensagem_status'] = "Por favor, corrija os erros abaixo.";
        $_SESSION['tipo_mensagem'] = 'danger';
        header("Location: cadastrar-pet.php");
        exit;
    } else {
        try {
            $conn->beginTransaction();
            
            $id_usuario_fk = ($tipo_usuario_logado == 'usuario') ? $id_usuario_logado : null;
            $id_ong_fk = ($tipo_usuario_logado == 'ong') ? $id_usuario_logado : null;
            
            // Processamento das características
            $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);
            
            // Processamento de alergias e medicação
            $alergias_limpas = array_filter($alergias, function($alergia) {
                return !empty(trim($alergia));
            });
            $alergias_json = !empty($alergias_limpas) ? json_encode($alergias_limpas, JSON_UNESCAPED_UNICODE) : null;
            $medicacao_limpa = !empty(trim($medicacao)) ? trim($medicacao) : null;

$sql = "INSERT INTO pet (nome, especie, sexo, idade, porte, raca, cor, status_vacinacao, status_castracao, comportamento, id_usuario_fk, id_ong_fk, status_disponibilidade, caracteristicas, carteira_vacinacao, alergias, medicacao) 
        VALUES (:nome, :especie, :sexo, :idade, :porte, :raca, :cor, :status_vacinacao, :status_castracao, :comportamento, :id_usuario_fk, :id_ong_fk, 'Em Analise', :caracteristicas, :carteira_vacinacao, :alergias, :medicacao)";

$stmt = $conn->prepare($sql);
$stmt->execute([
    ':nome' => $nome,
    ':especie' => $especie,
    ':sexo' => $sexo,
    ':idade' => $idade_final,
    ':porte' => $porte,
    ':raca' => $raca,
    ':cor' => $cor,
    ':status_vacinacao' => $status_vacinacao,
    ':status_castracao' => $status_castracao,
    ':comportamento' => $comportamento,
    ':id_usuario_fk' => $id_usuario_fk,
    ':id_ong_fk' => $id_ong_fk,
    ':caracteristicas' => $caracteristicas_json,
    ':carteira_vacinacao' => $caminho_vacina, 
    ':alergias' => $alergias_json,
    ':medicacao' => $medicacao_limpa
]);
            
            $id_pet = $conn->lastInsertId();

            // Salva fotos
            $stmt_foto = $conn->prepare("INSERT INTO pet_fotos (id_pet_fk, caminho_foto) VALUES (?, ?)");
            foreach ($fotos_salvas_paths as $path) {
                $stmt_foto->execute([$id_pet, $path]);
            }

            $conn->commit();
            unset($_SESSION['form_data']);
            unset($_SESSION['erros_form']);
            $_SESSION['toast_message'] = "Pet cadastrado! Aguardando análise da carteirinha de vacinação.";
            $_SESSION['toast_type'] = 'success';
            header("Location: perfil?page=meus-pets");
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            // Limpeza de arquivos em caso de erro
            if ($caminho_vacina && file_exists($caminho_vacina)) @unlink($caminho_vacina);
            foreach ($fotos_salvas_paths as $p) if (file_exists($p)) @unlink($p);
            
            $_SESSION['mensagem_status'] = "Erro no banco: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = 'danger';
            header("Location: cadastrar-pet.php");
            exit;
        }
    }
}

// Carrega erros da sessão para exibição
$erros_form = $_SESSION['erros_form'] ?? [];
unset($_SESSION['erros_form']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Pet - Adote Patas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/cadastro-pet/caracteristica.css">
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --cor-vermelho: #B46459;
            --cor-vermelho-claro: #d68a80;
            --cor-rosa-claro: #f8f0ef;
            --cor-rosa-escuro: #e8c4c0;
            --cor-branca: #ffffff;
            --cor-texto: #333333;
        }

        /* Estilos para os campos alinhados */
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            align-items: end;
        }

        @media (max-width: 768px) {
            .form-row-3 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .age-input-group {
            display: grid;
            grid-template-columns: 1fr 120px;
            gap: 8px;
            align-items: center;
        }

        @media (max-width: 768px) {
            .age-input-group {
                grid-template-columns: 1fr;
            }
        }

        /* Estilo para mensagens de erro */
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }

        .input-error {
            border-color: #dc2626 !important;
            background-color: #fef2f2 !important;
        }

        /* Estilo para o campo de arquivos com drag & drop */
        .file-drop-area {
            border: 2px dashed var(--cor-vermelho-claro);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            background: var(--cor-rosa-claro);
            cursor: pointer;
            position: relative;
        }

        .file-drop-area:hover, .file-drop-area.dragover {
            background: #fff0f0;
            border-color: var(--cor-vermelho);
            transform: translateY(-2px);
        }

        .file-drop-area i {
            font-size: 3rem;
            color: var(--cor-vermelho-claro);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .file-drop-area.dragover i {
            color: var(--cor-vermelho);
            transform: scale(1.1);
        }

        .file-info {
            font-weight: 600;
            color: var(--cor-texto);
            margin-bottom: 0.5rem;
        }

        .file-hint {
            color: #666;
            font-size: 0.9rem;
        }

        /* Estilo checkbox amamentação melhorado */
        .amamentacao-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fff8f8, #ffecec);
            border-radius: 16px;
            border: 2px solid #ffe0e0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .amamentacao-checkbox:hover {
            border-color: var(--cor-vermelho-claro);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(180, 100, 89, 0.1);
        }

        .amamentacao-checkbox input[type="checkbox"] {
            width: 24px;
            height: 24px;
            margin-top: 0.25rem;
            accent-color: var(--cor-vermelho);
            flex-shrink: 0;
        }

        .amamentacao-checkbox label {
            font-weight: 600;
            color: var(--cor-texto);
            line-height: 1.5;
            cursor: pointer;
        }

        .amamentacao-checkbox .required {
            color: var(--cor-vermelho);
            font-weight: 700;
        }

        /* Campos dinâmicos para alergias e medicação */
        .dynamic-field {
            margin-top: 1rem;
            padding: 1.5rem;
            background: var(--cor-rosa-claro);
            border-radius: 12px;
        }

        .dynamic-field h4 {
            color: var(--cor-vermelho);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alergia-input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            align-items: center;
        }

        .alergia-input {
            flex: 1;
        }

        .btn-add-alergia {
            background: var(--cor-vermelho);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-add-alergia:hover {
            background: var(--cor-vermelho-claro);
            transform: translateY(-1px);
        }

        .btn-remove-alergia {
            background: var(--cor-vermelho);
            color: white;
            border: none;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .btn-remove-alergia:hover {
            background: #b91c1c;
            transform: scale(1.1);
        }

        .medicacao-input {
            width: 100%;
        }

        /* Oculta os spinners nos inputs number */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Estilos para selects customizados */
        .select-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .custom-select-wrapper {
            position: relative;
            width: 100%;
            font-family: 'Poppins', sans-serif; 
        }

        .custom-select-trigger {
            width: 100%;
            padding: 1.15rem;
            background-color: rgba(180, 100, 89, 0.55);
            border: 1px solid transparent;
            border-radius: 12px;
            color: var(--cor-branca);
            font-size: 1rem;
            font-weight: 500;
            text-align: left;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            -webkit-appearance: none; 
            -moz-appearance: none; 
            appearance: none; 
        }

        .custom-select-trigger:focus,
        .custom-select-trigger:hover {
            border-color:  rgba(255, 255, 255, 0.6);
            outline: none; 
        }
        
        .custom-select-value.placeholder {
            color: var(--cor-branca);
            background-color: transparent;
            opacity: 0.8;
        }

        .custom-select-arrow {
            width: 10px;
            height: 10px;
            border-right: 2px solid var(--cor-vermelho);
            border-bottom: 2px solid var(--cor-vermelho);
            transform: rotate(45deg);
            transition: transform 0.3s ease;
            pointer-events: none; 
        }

        .custom-select-trigger[aria-expanded="true"] .custom-select-arrow {
            transform: rotate(225deg);
            margin-top: 5px; 
        }

        .custom-select-options {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 10;
            background-color: var(--cor-rosa-claro);
            border-radius: 12px;
            border: 0.5px solid var(--cor-rosa-escuro); 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 8px;
            list-style: none;
            margin: 0;
            overflow-y: auto;
            max-height: 200px;
            display: none;
            -webkit-overflow-scrolling: touch;
        }

        .custom-option {
            padding: 10px 12px;
            color: var(--cor-vermelho); 
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .custom-option:hover,
        .custom-option:focus {
            background-color: var(--cor-rosa-escuro);
            opacity: 0.4;
            color: var(--cor-branca);
            outline: none;
        }

        .custom-option.selected {
            background-color: var(--cor-rosa-escuro); 
            color: var(--cor-branca);
            font-weight: 700;
        }

        /* Estilo customizado para o select de idade */
        .age-unit-custom {
            width: 100%;
        }

        .age-unit-custom .custom-select-trigger {
            height: 100%;
            min-height: 54px;
        }

        .tags-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .char-tag-input {
            background-color: #ffffff;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--cor-vermelho);
            border: 1px solid #eee;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        #openModalBtn {
            min-height: 60px;
            display: flex;
            align-items: flex-start;
            padding: 1.15rem;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tags-placeholder {
            color: var(--cor-branca) !important;
        }

        #openModalBtn:has(.char-tag-input) {
            color: inherit;
        }

        /* Estilos para preview de fotos */
        #fotos-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .foto-preview {
            position: relative;
            width: 100%;
            padding-top: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 2px solid #eee;
        }

        .foto-preview img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            color: var(--cor-vermelho);
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .remove-preview:hover {
            background: var(--cor-vermelho);
            color: white;
            transform: scale(1.1);
        }

        /* Estilos para a seção de vacinação */
#vacinacao-section .file-drop-area {
    border: 2px dashed var(--cor-vermelho);
    background: var(--cor-rosa-claro);
}

#vacinacao-section .file-drop-area:hover {
    border-color: var(--cor-vermelho-claro);
    background: #fff0f0;
}
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<div id="toast-notification" class="toast p-0" style="display: none;">
    <div id="toast-icon" class="toast-icon"></div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>

<a href="perfil?page=meus-pets" class="btn-voltar" title="Voltar para a página inicial">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Voltar</span>
</a>

<img src="images/cadastro-login/pata.png" alt="Desenho de Pata" class="pata-fundo">

<div class="w-full max-w-2xl mx-auto"> 
    <div class="w-full flex items-center justify-between mb-6 relative">
        <div>
            <a href="./" title="Voltar para a página inicial">
                <img src="images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" width="70" height="70">
            </a>
        </div>
        <div class="absolute inset-x-0 text-center">
            <h1 class="text-xl md:text-4xl font-bold text-[#666662]">Cadastrar Pet</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">
        
        <?php if (!empty($mensagem_status)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> mb-4">
                <?php echo htmlspecialchars($mensagem_status); ?>
            </div>
        <?php endif; ?>

       <form action="cadastrar-pet.php" method="post" enctype="multipart/form-data" id="form-cadastro-pet" class="space-y-6">
            
            <div class="grid gap-6">
                <div>
                    <label for="nome">Nome do Pet</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Pet" required 
                           class="input-style w-full <?php echo isset($erros_form['nome']) ? 'input-error' : ''; ?>"
                           value="<?php echo htmlspecialchars($nome); ?>">
                    <?php if (isset($erros_form['nome'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($erros_form['nome']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- NOVO LAYOUT: Idade, Espécie e Sexo na mesma linha -->
            <div class="form-row-3">
                <!-- Idade Aproximada -->
                <div>
                    <label>Idade Aproximada</label>
                    <div class="age-input-group">
                        <input 
                            type="number" 
                            name="idade_valor" 
                            placeholder="Ex: 2" 
                            required 
                            min="1" 
                            max="11"
                            class="input-style age-value <?php echo isset($erros_form['idade']) ? 'input-error' : ''; ?>" 
                            value="<?php echo htmlspecialchars($idade_valor); ?>"
                        >
                        <select name="idade_unidade" id="idade_unidade-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                            <option value="anos" <?= $idade_unidade == 'anos' ? 'selected' : '' ?>>Anos</option>
                            <option value="meses" <?= $idade_unidade == 'meses' ? 'selected' : '' ?>>Meses</option>
                        </select>

                        <div class="custom-select-wrapper age-unit-custom" data-target-select="idade_unidade-real">
                            <button type="button" class="custom-select-trigger input-style w-full" 
                                    aria-haspopup="listbox" 
                                    aria-expanded="false">
                                <span class="custom-select-value <?php echo empty($idade_unidade) ? 'placeholder' : ''; ?>">
                                    <?php 
                                        if ($idade_unidade == 'anos') echo 'Anos';
                                        elseif ($idade_unidade == 'meses') echo 'Meses';
                                        else echo 'Unidade';
                                    ?>
                                </span>
                                <span class="custom-select-arrow"></span>
                            </button>
                            <ul class="custom-select-options" role="listbox">
                                <li class="custom-option" data-value="anos" role="option" tabindex="0">Anos</li>
                                <li class="custom-option" data-value="meses" role="option" tabindex="0">Meses</li>
                            </ul>
                        </div>
                    </div>
                    <?php if (isset($erros_form['idade'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($erros_form['idade']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Espécie -->
                <div>
                    <label id="select-label-especie">Espécie</label>
                    <select name="especie" id="especie-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="cachorro" <?php echo ($especie == 'cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                        <option value="gato" <?php echo ($especie == 'gato') ? 'selected' : ''; ?>>Gato</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="especie-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-especie">
                            <span class="custom-select-value <?php echo empty($especie) ? 'placeholder' : ''; ?>">
                                <?php 
                                    if ($especie == 'cachorro') echo 'Cachorro';
                                    elseif ($especie == 'gato') echo 'Gato';
                                    else echo 'Espécie';
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-especie">
                            <li class="custom-option" data-value="cachorro" role="option" tabindex="0">Cachorro</li>
                            <li class="custom-option" data-value="gato" role="option" tabindex="0">Gato</li>
                        </ul>
                    </div>
                </div>

                <!-- Sexo -->
                <div>
                    <label id="select-label-sexo">Sexo</label>
                    <select name="sexo" id="sexo-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="macho" <?php echo ($sexo == 'macho') ? 'selected' : ''; ?>>Macho</option>
                        <option value="femea" <?php echo ($sexo == 'femea') ? 'selected' : ''; ?>>Fêmea</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="sexo-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-sexo">
                            <span class="custom-select-value <?php echo empty($sexo) ? 'placeholder' : ''; ?>">
                                <?php 
                                    if ($sexo == 'macho') echo 'Macho';
                                    elseif ($sexo == 'femea') echo 'Fêmea';
                                    else echo 'Gênero';
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-sexo">
                            <li class="custom-option" data-value="macho" role="option" tabindex="0">Macho</li>
                            <li class="custom-option" data-value="femea" role="option" tabindex="0">Fêmea</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Resto dos campos mantidos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label id="select-label-porte">Porte</label>
                    <select name="porte" id="porte-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="pequeno" <?php echo ($porte == 'pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                        <option value="medio" <?php echo ($porte == 'medio') ? 'selected' : ''; ?>>Médio</option>
                        <option value="grande" <?php echo ($porte == 'grande') ? 'selected' : ''; ?>>Grande</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="porte-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-porte">
                            <span class="custom-select-value <?php echo empty($porte) ? 'placeholder' : ''; ?>">
                                <?php 
                                    if ($porte == 'pequeno') echo 'Pequeno';
                                    elseif ($porte == 'medio') echo 'Médio';
                                    elseif ($porte == 'grande') echo 'Grande';
                                    else echo 'Porte';
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-porte">
                            <li class="custom-option" data-value="pequeno" role="option" tabindex="0">Pequeno</li>
                            <li class="custom-option" data-value="medio" role="option" tabindex="0">Médio</li>
                            <li class="custom-option" data-value="grande" role="option" tabindex="0">Grande</li>
                        </ul>
                    </div>
                </div>
                <div>
                    <label for="raca">Raça (Ex: SRD)</label>
                    <input type="text" name="raca" id="raca" placeholder="Raça (Ex: SRD)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($raca); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cor">Cor</label>
                    <input type="text" name="cor" id="cor" placeholder="Cor (Ex: Caramelo)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($cor); ?>">
                </div>
                <div>
                    <label id="select-label-vacinacao">Vacinado?</label>
                    <select name="status_vacinacao" id="status_vacinacao-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="sim" <?php echo ($status_vacinacao == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($status_vacinacao == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="status_vacinacao-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-vacinacao">
                            <span class="custom-select-value <?php echo empty($status_vacinacao) ? 'placeholder' : ''; ?>">
                                 <?php 
                                    if ($status_vacinacao == 'sim') echo 'Sim';
                                    elseif ($status_vacinacao == 'nao') echo 'Não';
                                    else echo 'Vacinado?';
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-vacinacao">
                            <li class="custom-option" data-value="sim" role="option" tabindex="0">Sim</li>
                            <li class="custom-option" data-value="nao" role="option" tabindex="0">Não</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="grid gap-6"> 
                <div>
                    <label id="select-label-castracao">Castrado?</label>
                    <select name="status_castracao" id="status_castracao-real" required class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="sim" <?php echo ($status_castracao == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($status_castracao == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>
                    
                    <div class="custom-select-wrapper" data-target-select="status_castracao-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-castracao">
                            <span class="custom-select-value <?php echo empty($status_castracao) ? 'placeholder' : ''; ?>">
                                <?php 
                                    if ($status_castracao == 'sim') echo 'Sim';
                                    elseif ($status_castracao == 'nao') echo 'Não';
                                    else echo 'Castrado?';
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-castracao">
                            <li class="custom-option" data-value="sim" role="option" tabindex="0">Sim</li>
                            <li class="custom-option" data-value="nao" role="option" tabindex="0">Não</li>
                        </ul>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 my-4">
            
<!-- REQUISITOS DE SAÚDE - MELHORADO -->
<div class="grid gap-4 mt-2 grid-cols-1 md:grid-cols-1">
    <h3 class="text-lg font-bold text-gray-700">Requisitos de Saúde</h3>
    
    <!-- 1. Amamentação Completa - ESTILIZADO -->
    <div class="amamentacao-checkbox <?php echo isset($erros_form['amamentacao']) ? 'input-error' : ''; ?>">
        <input type="checkbox" name="amamentacao_completa" id="check_amamentacao" required
               <?php echo isset($_POST['amamentacao_completa']) ? 'checked' : ''; ?>>
        <label for="check_amamentacao">
            Declaro que o pet já completou o período de amamentação (desmame) e já come ração sólida/pastosa. 
            <span class="required">* Obrigatório</span>
        </label>
    </div>
    <?php if (isset($erros_form['amamentacao'])): ?>
        <span class="error-message"><?php echo htmlspecialchars($erros_form['amamentacao']); ?></span>
    <?php endif; ?>

    <!-- 2. Carteirinha de Vacinação - COMPORTAMENTO DINÂMICO -->
    <div id="vacinacao-section" class="mt-4">
        <h4 class="font-semibold text-gray-700 mb-3">Documentação de Vacinação</h4>
        
        <!-- Área que aparece apenas quando o pet É VACINADO -->
        <div id="vacinado-content" style="display: <?php echo ($status_vacinacao == 'sim') ? 'block' : 'none'; ?>;">
            <div class="mb-4 p-4 rounded-lg" style="background-color: rgba(98, 142, 109, 0.8);">
                <div class="flex items-center gap-3" style="color: var(--cor-branca); !important">
                    <i class="fa-solid fa-syringe fs-4"></i>
                    <div>
                        <p class="text-sm font-semibold">Pet vacinado</p>
                        <p class="text-sm text-informacao">É obrigatório enviar a carteirinha de vacinação para comprovação.</p>
                    </div>
                </div>
            </div>

            <!-- Campo de upload da carteirinha (obrigatório quando vacinado) -->
            <div class="mt-4">
                <label class="font-semibold text-gray-700 mb-1 block">Carteirinha de Vacinação <span class="text-red-600">*</span></label>
                <div id="drop-area-vacina" class="file-drop-area <?php echo isset($erros_form['carteira_vacinacao']) ? 'input-error' : ''; ?>">
                    <i class="fas fa-file-medical"></i>
                    <div class="file-info" id="file-name-vacina">Arraste e solte o arquivo aqui ou clique para selecionar</div>
                    <div class="file-hint">Formatos aceitos: JPG, PNG, PDF, WEBP</div>
                    <input type="file" name="carteira_vacinacao" id="file_vacina" class="hidden" accept="image/*,.pdf">
                </div>
                <div id="preview-vacina-text" class="text-md text-gray-800 mt-1 ml-2" style="color: #b91c1c;">Nenhum arquivo selecionado.</div>
                <?php if (isset($erros_form['carteira_vacinacao'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($erros_form['carteira_vacinacao']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mensagem quando NÃO É VACINADO -->
        <div id="nao-vacinado-content" style="display: <?php echo ($status_vacinacao == 'nao') ? 'block' : 'none'; ?>;">
            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                                    <i class="fa-solid fa-syringe fs-4"></i>
                <p class="text-sm text-gray-700">O pet <strong style="color: var(--cor-vermelho);">NÃO</strong> é vacinado. As opções de carteirinha ficarão disponíveis quando marcar como vacinado.</p>
            </div>
        </div>
    </div>
</div>
    
            <div>
                <label class="font-semibold text-gray-700">Adicionar Fotos</label>
                
                <!-- Área de Drag and Drop para Fotos -->
                <div id="drop-area" class="file-drop-area <?php echo isset($erros_form['fotos']) ? 'input-error' : ''; ?>">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="file-info" id="file-name-span">Arraste e solte fotos aqui ou clique para selecionar</div>
                    <div class="file-hint">Máximo: 5 fotos | Formatos: PNG, JPG, JPEG, WEBP</div>
                    <input type="file" id="fotos_novas_input" class="hidden" multiple accept="image/png, image/jpeg, image/jpg, image/webp">
                </div>
                
                <input type="file" name="fotos_novas[]" id="fotos_novas_final" class="hidden" multiple>

                <div id="fotos-preview-container" class="mt-4"></div>
                <small id="limite-fotos-helper" class="text-sm text-gray-600 mt-1"></small>
                <?php if (isset($erros_form['fotos'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($erros_form['fotos']); ?></span>
                <?php endif; ?>
            </div>

            <!-- CAMPOS DINÂMICOS PARA CARACTERÍSTICAS ESPECIAIS -->
            <div id="dynamic-fields-container">
                <!-- Alergias (aparece quando selecionar característica "Alergia") -->
                <div id="alergia-field" class="dynamic-field" style="display: none;">
                    <h4><i class="fas fa-allergies"></i> Alergias do Pet</h4>
                    <div id="alergias-container">
                        <!-- Inputs de alergia serão adicionados aqui dinamicamente -->
                    </div>
                    <button type="button" id="add-alergia-btn" class="btn-add-alergia">
                        <i class="fas fa-plus"></i> Adicionar Alergia
                    </button>
                </div>

                <!-- Medicação (aparece quando selecionar característica "Medicação") -->
                <div id="medicacao-field" class="dynamic-field" style="display: none;">
                    <h4><i class="fas fa-pills"></i> Medicação do Pet</h4>
                    <input type="text" name="medicacao" id="medicacao_input" placeholder="Nome da medicação que o pet toma" 
                           class="input-style medicacao-input" value="<?php echo htmlspecialchars($medicacao); ?>">
                </div>
            </div>

            <hr class="border-gray-200 my-4">
                
            <div>
                <label for="openModalBtn" class="sr-only">Características</label>
                <button type="button" id="openModalBtn" class="input-style w-full text-left">
                    <span id="tagsPlaceholder" class="tags-placeholder" style="<?php echo !empty($caracteristicas) ? 'display: none;' : 'display: block;'; ?>">Selecionar Características...</span>
                    <span class="tags-preview" id="tagsPreview"></span>
                </button>
            </div>

            <div id="hidden-tags-container"></div>

            <div>
                <label class="sr-only" for="comportamento">Comportamento (Ex: Dócil, adora crianças...)</label>
                <input type="text" name="comportamento" id="comportamento" placeholder="Conte um pouco sobre o pet..." class="input-style w-full" value="<?php echo htmlspecialchars($comportamento); ?>">
            </div>

            <div class="flex justify-center w-55 mx-auto">
                <button type="submit" class="adopt-btn" id="submit-btn"> 
                    <div class="heart-background" aria-hidden="true">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <span>Cadastrar Pet</span>
                </button>
            </div>

       </form>
    </div>
</div>

<!-- Modal de Características -->
<div id="charModal" class="char-modal">
    <div class="char-modal-content">
        <div class="char-modal-header">
            <div>
                <h2>Selecionar Características</h2>
                <p>Escolha até 5 características para o seu pet.</p>
            </div>
            <button type="button" class="char-modal-close" id="closeModalBtn">&times;</button>
        </div>

        <div class="char-modal-body">
            <!-- Conteúdo do modal mantido igual -->
            <h3>Temperamento</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="laranja" data-value="Dócil"><i class="fa-solid fa-face-smile"></i> Dócil <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Brincalhão"><i class="fa-solid fa-puzzle-piece"></i> Brincalhão <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Calmo"><i class="fa-solid fa-leaf"></i> Calmo <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Carinhoso"><i class="fa-solid fa-heart"></i> Carinhoso <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Tímido"><i class="fa-solid fa-user-secret"></i> Tímido <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Sociável"><i class="fa-solid fa-users"></i> Sociável <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Protetor"><i class="fa-solid fa-shield-halved"></i> Protetor <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Curioso"><i class="fa-solid fa-magnifying-glass"></i> Curioso <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Medroso"><i class="fa-solid fa-ghost"></i> Medroso <i class="fas fa-check"></i></span>
            </div>
            
            <h3>Nível de Energia</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="verde" data-value="Baixa Energia"><i class="fa-solid fa-battery-quarter"></i> Baixa Energia <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Média Energia"><i class="fa-solid fa-battery-half"></i> Média Energia <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Alta Energia"><i class="fa-solid fa-battery-full"></i> Alta Energia <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Hiperativo"><i class="fa-solid fa-bolt"></i> Hiperativo <i class="fas fa-check"></i></span>
            </div>
            
            <h3>Sociabilidade</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="rosa" data-value="Com Crianças"><i class="fa-solid fa-child"></i> Com Crianças <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Com Cães"><i class="fa-solid fa-dog"></i> Com Cães <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Com Gatos"><i class="fa-solid fa-cat"></i> Com Gatos <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Com Estranhos"><i class="fa-solid fa-user-group"></i> Com Estranhos <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Pet Único"><i class="fa-solid fa-user"></i> Pet Único <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Com Idosos"><i class="fa-solid fa-person-cane"></i> Com Idosos <i class="fas fa-check"></i></span>
            </div>
            
            <h3>Cuidados Especiais</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="roxo" data-value="Medicação"><i class="fa-solid fa-pills"></i> Medicação <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Dieta Especial"><i class="fa-solid fa-bowl-food"></i> Dieta Especial <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Alergia"><i class="fa-solid fa-allergies"></i> Alergia <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Deficiência Física"><i class="fa-solid fa-wheelchair"></i> Def. Física <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Deficiência Visual"><i class="fa-solid fa-eye-slash"></i> Def. Visual <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Deficiência Auditiva"><i class="fa-solid fa-ear-listen-slash"></i> Def. Auditiva <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Pós-operatório"><i class="fa-solid fa-kit-medical"></i> Pós-operatório <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Traumático"><i class="fa-solid fa-heart-crack"></i> Traumático <i class="fas fa-check"></i></span>
            </div>

            <h3>Treinamento e Hábitos</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="verde" data-value="Adestrado"><i class="fa-solid fa-graduation-cap"></i> Adestrado <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Em Treinamento"><i class="fa-solid fa-person-chalkboard"></i> Em Treinamento <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Não Adestrado"><i class="fa-solid fa-xmark"></i> Não Adestrado <i class="fas fa-check"></i></span>
            </div>

            <h3>Ambiente Ideal</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="roxo" data-value="Apartamento"><i class="fa-solid fa-building"></i> Apartamento <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Precisa de Quintal"><i class="fa-solid fa-tree"></i> Precisa de Quintal <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Casa"><i class="fa-solid fa-house"></i> Casa <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="1ª Adoção"><i class="fa-solid fa-star"></i> 1ª Adoção <i class="fas fa-check"></i></span>
            </div>
        </div>
        
        <div class="char-modal-footer">
            <button type="button" class="btn btn-cancelar" id="cancelModalBtn">Cancelar</button>
            <button type="button" class="btn btn-salvar" id="saveModalBtn">Salvar Seleção (0/5)</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>

<script src="assets/js/pages/autenticacao/autenticacao.js" type="module"></script>

<script>
// Variáveis globais para controle de fotos
const MAX_FOTOS_GLOBAL = 5;

// Função de validação de limite de fotos
function validarLimiteFotos() {
    const fotosNovas = document.getElementById('fotos_novas_final').files.length;
    const totalFinal = fotosNovas;

    const limiteHelper = document.getElementById('limite-fotos-helper');
    const submitBtn = document.getElementById('submit-btn');

    if (totalFinal > MAX_FOTOS_GLOBAL) {
        limiteHelper.textContent = `Erro: Limite de ${MAX_FOTOS_GLOBAL} fotos excedido! (Total: ${totalFinal})`;
        limiteHelper.style.color = 'var(--cor-vermelho)';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        return false;
    } else if (totalFinal === 0) {
        limiteHelper.textContent = `O pet deve ter pelo menos 1 foto.`;
        limiteHelper.style.color = 'var(--cor-vermelho)';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        return false;
    } else {
        const espacoRestante = MAX_FOTOS_GLOBAL - totalFinal;
        limiteHelper.textContent = `Você pode adicionar mais ${espacoRestante} foto(s). (Total será ${totalFinal}/${MAX_FOTOS_GLOBAL})`;
        limiteHelper.style.color = '#555';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        return true;
    }
}

// VALIDAÇÃO DA IDADE
document.addEventListener('DOMContentLoaded', function() {
    const idadeValorInput = document.querySelector('input[name="idade_valor"]');
    const idadeUnidadeSelect = document.getElementById('idade_unidade-real');
    
    // Validação para aceitar apenas números
    idadeValorInput.addEventListener('input', function() {
        // Remove qualquer caractere não numérico
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Validação específica para meses
        if (idadeUnidadeSelect.value === 'meses' && this.value > 11) {
            this.value = 11;
            if (typeof showToast === 'function') {
                showToast('A idade em meses não pode ser maior que 11.', 'warning');
            }
        }
    });
});

// DRAG & DROP PARA CARTEIRINHA DE VACINAÇÃO
document.addEventListener('DOMContentLoaded', function() {
    const dropAreaVacina = document.getElementById('drop-area-vacina');
    const fileInputVacina = document.getElementById('file_vacina');
    const fileNameVacina = document.getElementById('file-name-vacina');
    const previewVacina = document.getElementById('preview-vacina');

    // Prevenir comportamentos padrão
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Efeitos visuais
    ['dragenter', 'dragover'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropAreaVacina.classList.add('dragover');
    }

    function unhighlight() {
        dropAreaVacina.classList.remove('dragover');
    }

    // Manipular arquivos dropados
    dropAreaVacina.addEventListener('drop', handleDropVacina, false);

    function handleDropVacina(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFilesVacina(files);
    }

    // Manipular seleção via clique
    dropAreaVacina.addEventListener('click', () => {
        fileInputVacina.click();
    });

    fileInputVacina.addEventListener('change', function() {
        handleFilesVacina(this.files);
    });

    function handleFilesVacina(files) {
        if (files.length) {
            const file = files[0];
            // Validação do tipo de arquivo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                if (typeof showToast === 'function') {
                    showToast('Formato de arquivo não permitido. Use JPG, PNG, WEBP ou PDF.', 'danger');
                }
                return;
            }
            
            // Atualiza a interface
            fileNameVacina.textContent = file.name;
            previewVacina.textContent = 'Arquivo selecionado: ' + file.name;
            previewVacina.style.color = 'green';
            
            // Atualiza o input file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInputVacina.files = dataTransfer.files;
        }
    }
});

// CAMPOS DINÂMICOS PARA ALERGIAS E MEDICAÇÃO
document.addEventListener('DOMContentLoaded', function() {
    const alergiaField = document.getElementById('alergia-field');
    const medicacaoField = document.getElementById('medicacao-field');
    const alergiasContainer = document.getElementById('alergias-container');
    const addAlergiaBtn = document.getElementById('add-alergia-btn');
    
    // Preencher alergias existentes do PHP
    const alergiasExistentes = <?php echo json_encode($alergias); ?>;
    if (alergiasExistentes.length > 0 && alergiasExistentes[0] !== '') {
        alergiasExistentes.forEach((alergia, index) => {
            if (alergia.trim() !== '') {
                addAlergiaInput(alergia);
            }
        });
    } else {
        // Adiciona um input vazio por padrão
        addAlergiaInput('');
    }
    
    // Função para adicionar input de alergia
    function addAlergiaInput(value = '') {
        const inputGroup = document.createElement('div');
        inputGroup.className = 'alergia-input-group';
        
        const inputId = `alergia_${Date.now()}`;
        inputGroup.innerHTML = `
            <input type="text" name="alergias[]" value="${value}" 
                   placeholder="Nome da alergia" class="input-style alergia-input" id="${inputId}">
            <button type="button" class="btn-remove-alergia" ${alergiasContainer.children.length === 0 ? 'disabled' : ''}>
                <i class="fas fa-times"></i>
            </button>
        `;
        
        alergiasContainer.appendChild(inputGroup);
        
        // Atualiza estado dos botões de remover
        updateRemoveButtons();
        
        // Foca no novo input
        document.getElementById(inputId).focus();
    }
    
    // Atualizar estado dos botões de remover
    function updateRemoveButtons() {
        const removeButtons = alergiasContainer.querySelectorAll('.btn-remove-alergia');
        removeButtons.forEach((btn, index) => {
            btn.disabled = (index === 0 && removeButtons.length === 1);
        });
    }
    
    // Event listener para adicionar alergia
    addAlergiaBtn.addEventListener('click', () => {
        addAlergiaInput();
    });
    
    // Event listener para remover alergia (delegação de evento)
    alergiasContainer.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-alergia')) {
            const inputGroup = e.target.closest('.alergia-input-group');
            if (inputGroup && alergiasContainer.children.length > 1) {
                inputGroup.remove();
                updateRemoveButtons();
            }
        }
    });
    
    // Mostrar/ocultar campos baseado nas características selecionadas
    function toggleDynamicFields(selectedTags) {
        const hasAlergia = selectedTags.some(tag => tag.value === 'Alergia');
        const hasMedicacao = selectedTags.some(tag => tag.value === 'Medicação');
        
        // Alergia
        if (hasAlergia) {
            alergiaField.style.display = 'block';
        } else {
            alergiaField.style.display = 'none';
            // Limpa os inputs de alergia, mas mantém um vazio
            alergiasContainer.innerHTML = '';
            addAlergiaInput('');
        }
        
        // Medicação
        if (hasMedicacao) {
            medicacaoField.style.display = 'block';
        } else {
            medicacaoField.style.display = 'none';
            document.getElementById('medicacao_input').value = '';
        }
    }
    
    // Expor a função globalmente para o modal de características
    window.toggleDynamicFields = toggleDynamicFields;
});

// Implementação do Drag and Drop para Fotos (mantida igual)
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('fotos_novas_input');
    const finalInput = document.getElementById('fotos_novas_final');
    const fileNameSpan = document.getElementById('file-name-span');
    const previewContainer = document.getElementById('fotos-preview-container');

    // Prevenir comportamentos padrão
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Efeitos visuais
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropArea.classList.add('dragover');
    }

    function unhighlight() {
        dropArea.classList.remove('dragover');
    }

    // Manipular arquivos dropados
    dropArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    // Manipular seleção via clique
    dropArea.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    // Função principal para processar arquivos
    async function handleFiles(files) {
        if (!files.length) return;

        // Validação preliminar
        const totalPreliminar = files.length;

        if (totalPreliminar > MAX_FOTOS_GLOBAL) {
            if (typeof showToast === 'function') {
                showToast(`Limite de ${MAX_FOTOS_GLOBAL} fotos excedido!`, 'danger');
            }
            return;
        }

        fileNameSpan.textContent = 'Processando...';

        try {
            const conversionPromises = Array.from(files).map(file => {
                // Mostra preview imediato
                const reader = new FileReader();
                reader.onload = (e) => {
                    addImagePreview(e.target.result, file.name);
                };
                reader.readAsDataURL(file);

                // Converte para WebP
                return convertToWebP(file);
            });

            const convertedFiles = await Promise.all(conversionPromises);
            updateFinalInput(convertedFiles, finalInput);
            
            fileNameSpan.textContent = `${convertedFiles.length} foto(s) adicionada(s)`;
            validarLimiteFotos();

        } catch (error) {
            console.error("Erro ao processar imagens:", error);
            fileNameSpan.textContent = 'Erro no processamento. Tente novamente.';
            if (typeof showToast === 'function') {
                showToast('Erro ao processar imagens.', 'danger');
            }
        }
    }

    // Adicionar preview da imagem
    function addImagePreview(src, filename) {
        const previewDiv = document.createElement('div');
        previewDiv.className = 'foto-preview';
        
        const img = document.createElement('img');
        img.src = src;
        img.alt = `Preview: ${filename}`;
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-preview';
        removeBtn.innerHTML = '×';
        removeBtn.title = 'Remover foto';
        
        removeBtn.addEventListener('click', function() {
            previewDiv.remove();
            // Recria a lista de arquivos no input final
            updateFinalInputFromPreviews();
            validarLimiteFotos();
        });
        
        previewDiv.appendChild(img);
        previewDiv.appendChild(removeBtn);
        previewContainer.appendChild(previewDiv);
    }

    // Atualizar input final baseado nos previews
    function updateFinalInputFromPreviews() {
        // Limpa o input final
        const dataTransfer = new DataTransfer();
        finalInput.files = dataTransfer.files;
        
        // Re-adiciona os arquivos que ainda estão no preview
        fileNameSpan.textContent = 'Arraste e solte fotos aqui ou clique para selecionar';
        validarLimiteFotos();
    }
});

// Função para converter para WebP
async function convertToWebP(file) {
    return new Promise((resolve, reject) => {
        if (file.type === 'image/webp') {
            resolve(file);
            return;
        }
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                canvas.toBlob(function(blob) {
                    const webpFileName = file.name.split('.').slice(0, -1).join('.') + '.webp';
                    const webpFile = new File([blob], webpFileName, { type: 'image/webp' });
                    resolve(webpFile);
                }, 'image/webp', 0.8);
            };
            img.onerror = reject;
            img.src = event.target.result;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// Função para atualizar input final
function updateFinalInput(files, finalInput) {
    const dataTransfer = new DataTransfer();
    files.forEach(file => {
        dataTransfer.items.add(file);
    });
    finalInput.files = dataTransfer.files;
}

// Inicializar a validação de fotos
document.addEventListener('DOMContentLoaded', validarLimiteFotos);

// CONTROLE DINÂMICO DA SEÇÃO DE VACINAÇÃO NO CADASTRO
document.addEventListener('DOMContentLoaded', function() {
    const statusVacinacaoReal = document.getElementById('status_vacinacao-real');
    const vacinadoContent = document.getElementById('vacinado-content');
    const naoVacinadoContent = document.getElementById('nao-vacinado-content');
    const fileVacina = document.getElementById('file_vacina');

    // Função para toggle da seção de vacinação
    function toggleVacinacaoSection() {
        const isVacinado = statusVacinacaoReal.value === 'sim';
        
        // Mostra/oculta conteúdos
        vacinadoContent.style.display = isVacinado ? 'block' : 'none';
        naoVacinadoContent.style.display = isVacinado ? 'none' : 'block';
        
        // Atualiza a obrigatoriedade do campo
        if (isVacinado) {
            fileVacina.required = true;
            // Mostra mensagem de obrigatoriedade
            document.querySelector('#vacinado-content .text-informacao').textContent = 
                'É obrigatório enviar a carteirinha de vacinação para comprovação.';
        } else {
            fileVacina.required = false;
            // Limpa o preview e o input file
            if (typeof clearVacinaPreview === 'function') {
                clearVacinaPreview();
            }
        }
    }

    // Inicializar estado
    toggleVacinacaoSection();

    // Observar mudanças no select de vacinação
    if (statusVacinacaoReal) {
        statusVacinacaoReal.addEventListener('change', function() {
            toggleVacinacaoSection();
        });
    }
});

// PREVIEW DA CARTEIRINHA DE VACINAÇÃO NO CADASTRO
function setupVacinaPreviewCadastro() {
    const dropAreaVacina = document.getElementById('drop-area-vacina');
    const fileInputVacina = document.getElementById('file_vacina');
    const fileNameVacina = document.getElementById('file-name-vacina');
    const previewVacinaContainer = document.getElementById('preview-vacina-container');
    const previewVacina = document.getElementById('preview-vacina');
    const previewVacinaText = document.getElementById('preview-vacina-text');

    if (!dropAreaVacina) return;

    // Prevenir comportamentos padrão
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Efeitos visuais
    ['dragenter', 'dragover'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropAreaVacina.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropAreaVacina.classList.add('dragover');
    }

    function unhighlight() {
        dropAreaVacina.classList.remove('dragover');
    }

    // Manipular arquivos dropados
    dropAreaVacina.addEventListener('drop', handleDropVacina, false);

    function handleDropVacina(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFilesVacina(files);
    }

    // Manipular seleção via clique
    dropAreaVacina.addEventListener('click', () => {
        fileInputVacina.click();
    });

    fileInputVacina.addEventListener('change', function() {
        handleFilesVacina(this.files);
    });

    function handleFilesVacina(files) {
        if (files.length) {
            const file = files[0];
            // Validação do tipo de arquivo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                if (typeof showToast === 'function') {
                    showToast('Formato de arquivo não permitido. Use JPG, PNG, WEBP ou PDF.', 'danger');
                }
                return;
            }
            
            // Atualiza a interface
            fileNameVacina.textContent = file.name;
            previewVacinaText.textContent = 'Arquivo selecionado: ' + file.name;
            previewVacinaText.style.color = 'green';
            showVacinaPreview(file);
            
            // Atualiza o input file
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            fileInputVacina.files = dataTransfer.files;
        }
    }

    function showVacinaPreview(file) {
        previewVacina.innerHTML = '';
        previewVacinaContainer.classList.remove('hidden');

        if (file.type === 'application/pdf') {
            // Preview para PDF
            const pdfPreview = document.createElement('div');
            pdfPreview.className = 'vacina-preview pdf';
            pdfPreview.innerHTML = `
                <i class="fas fa-file-pdf"></i>
                <span class="text-sm font-medium">${file.name}</span>
                <span class="text-xs text-gray-500">PDF Document</span>
            `;
            previewVacina.appendChild(pdfPreview);
        } else {
            // Preview para imagens
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgPreview = document.createElement('div');
                imgPreview.className = 'flex flex-col items-center';
                imgPreview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview carteirinha" class="vacina-preview">
                    <span class="text-sm mt-2">${file.name}</span>
                `;
                previewVacina.appendChild(imgPreview);
            };
            reader.readAsDataURL(file);
        }

        // Botão para remover preview
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn-remove-preview';
        removeBtn.innerHTML = '<i class="fas fa-times"></i> Remover arquivo';
        removeBtn.addEventListener('click', function() {
            clearVacinaPreview();
        });
        previewVacina.appendChild(removeBtn);
    }

    window.clearVacinaPreview = function() {
        const previewVacinaContainer = document.getElementById('preview-vacina-container');
        const previewVacina = document.getElementById('preview-vacina');
        const fileInputVacina = document.getElementById('file_vacina');
        const fileNameVacina = document.getElementById('file-name-vacina');
        const previewVacinaText = document.getElementById('preview-vacina-text');
        
        if (previewVacinaContainer) previewVacinaContainer.classList.add('hidden');
        if (previewVacina) previewVacina.innerHTML = '';
        if (fileInputVacina) fileInputVacina.value = '';
        if (fileNameVacina) fileNameVacina.textContent = 'Arraste e solte o arquivo aqui ou clique para selecionar';
        if (previewVacinaText) {
            previewVacinaText.textContent = 'Nenhum arquivo selecionado.';
            previewVacinaText.style.color = '#b91c1c';
        }
    }
}

// Inicializar o preview da carteirinha no cadastro
document.addEventListener('DOMContentLoaded', setupVacinaPreviewCadastro);

// Custom Select Functionality (incluindo o novo select de idade)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.custom-select-wrapper').forEach(setupCustomSelect);
});

function setupCustomSelect(wrapper) {
    const trigger = wrapper.querySelector('.custom-select-trigger');
    const optionsList = wrapper.querySelector('.custom-select-options');
    const options = wrapper.querySelectorAll('.custom-option');
    const valueSpan = trigger.querySelector('.custom-select-value');
    
    // Pega o <select> real escondido usando o data-target-select
    const targetSelectId = wrapper.dataset.targetSelect;
    const realSelect = document.getElementById(targetSelectId);

    // Encontra a opção selecionada no select real e a marca no customizado
    if (realSelect) {
        const initialSelectedValue = realSelect.value;
        options.forEach(option => {
            if (option.dataset.value === initialSelectedValue) {
                option.classList.add('selected');
                valueSpan.textContent = option.textContent;
            }
        });
    }

    // 1. Abrir/Fechar o menu
    trigger.addEventListener('click', () => {
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
        // Fecha outros selects abertos
        document.querySelectorAll('.custom-select-trigger[aria-expanded="true"]').forEach(otherTrigger => {
            if (otherTrigger !== trigger) {
                otherTrigger.setAttribute('aria-expanded', 'false');
                otherTrigger.nextElementSibling.style.display = 'none';
            }
        });

        trigger.setAttribute('aria-expanded', !isExpanded);
        optionsList.style.display = isExpanded ? 'none' : 'block';
    });

    // 2. Selecionar uma opção
    options.forEach(option => {
        option.addEventListener('click', () => {
            // Remove a classe 'selected' de todos
            options.forEach(o => o.classList.remove('selected'));
            
            // Adiciona 'selected' na clicada
            option.classList.add('selected');
            
            // Atualiza o valor visual no "trigger"
            valueSpan.textContent = option.textContent;
            valueSpan.classList.remove('placeholder');
            
            // ATUALIZA O VALOR NO <select> ESCONDIDO
            if (realSelect) {
                realSelect.value = option.dataset.value;
                // Dispara um evento 'change' no select real
                const event = new Event('change');
                realSelect.dispatchEvent(event);
            }
            
            // Fecha o menu
            trigger.setAttribute('aria-expanded', 'false');
            optionsList.style.display = 'none';
        });
    });

    // 3. Fechar ao clicar fora
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            trigger.setAttribute('aria-expanded', 'false');
            optionsList.style.display = 'none';
        }
    });
}
</script>

<script type="module">
    // Pega os elementos do DOM
    const modal = document.getElementById('charModal');
    const openBtn = document.getElementById('openModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const saveBtn = document.getElementById('saveModalBtn');
    const allTagsInModal = document.querySelectorAll('.char-tag');
    const hiddenTagsContainer = document.getElementById('hidden-tags-container');
    const tagsPreview = document.getElementById('tagsPreview');
    const tagsPlaceholder = document.getElementById('tagsPlaceholder');
    
    // Pega características existentes do PHP
    const existingCharacteristics = <?php echo json_encode($caracteristicas); ?>;
    
    const MAX_SELECTIONS = 5;
    let selectedTags = [];

    // --- Funções do Modal ---
    function openModal() {
        if (modal) modal.style.display = 'flex';
        syncModalStateFromForm();
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }
    
    function updateSelectionCount() {
        const count = selectedTags.length;
        saveBtn.textContent = `Salvar Seleção (${count}/${MAX_SELECTIONS})`;
        saveBtn.disabled = (count === 0);
    }
    
    // --- Sincronização ---
    function syncModalStateFromForm() {
        selectedTags = [];
        const hiddenInputs = hiddenTagsContainer.querySelectorAll('input[name="caracteristicas[]"]');
        
        hiddenInputs.forEach(input => {
            const value = input.value;
            const matchingTag = document.querySelector(`.char-tag[data-value="${value}"]`);
            if (matchingTag) {
                const iconHTML = matchingTag.querySelector('i:first-child').outerHTML;
                selectedTags.push({ value: value, iconHTML: iconHTML });
            }
        });
        
        // Atualiza a aparência das tags no modal
        allTagsInModal.forEach(tag => {
            if (selectedTags.some(t => t.value === tag.dataset.value)) {
                tag.classList.add('active');
            } else {
                tag.classList.remove('active');
            }
        });
        updateSelectionCount();
    }
    
    // Função para salvar e atualizar a UI
    function saveAndApplyTags() {
        // 1. Limpa os inputs escondidos e o preview de tags
        hiddenTagsContainer.innerHTML = '';
        tagsPreview.innerHTML = '';
        
        let hasSelection = selectedTags.length > 0;

        selectedTags.forEach(tag => {
            // 2a. Cria os novos inputs escondidos
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'caracteristicas[]';
            input.value = tag.value;
            hiddenTagsContainer.appendChild(input);
            
            // 2b. Adiciona a TAG ESTILIZADA ao preview no botão
            const tagElement = document.createElement('span');
            tagElement.className = 'char-tag-input';
            
            // Adiciona o ícone e o texto
            tagElement.innerHTML = tag.iconHTML + ' ' + tag.value;
            
            tagsPreview.appendChild(tagElement);
        });
        
        // 3. Mostra/Esconde o placeholder
        if (tagsPlaceholder) {
            tagsPlaceholder.style.display = hasSelection ? 'none' : 'block';
            if (hasSelection) {
                tagsPlaceholder.classList.remove('tags-placeholder');
            } else {
                tagsPlaceholder.classList.add('tags-placeholder');
            }
        }
        
        // 4. Atualiza campos dinâmicos (alergia e medicação)
        if (typeof toggleDynamicFields === 'function') {
            toggleDynamicFields(selectedTags);
        }
    }
    
    // Função para pré-popular no load da página
    function prefillCharacteristics() {
        existingCharacteristics.forEach(value => {
            const matchingTag = document.querySelector(`.char-tag[data-value="${value}"]`);
            if (matchingTag) {
                const iconHTML = matchingTag.querySelector('i:first-child').outerHTML;
                if (selectedTags.length < MAX_SELECTIONS) {
                    selectedTags.push({ value: value, iconHTML: iconHTML });
                }
            }
        });
        // Aplica as tags carregadas
        saveAndApplyTags();
    }

    // --- Event Handlers ---
    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (event) => (event.target === modal) && closeModal());
    }

    // Clicar numa Tag
    allTagsInModal.forEach(tag => {
        tag.addEventListener('click', () => {
            const value = tag.dataset.value;
            const iconHTML = tag.querySelector('i:first-child').outerHTML; 
            const isActive = tag.classList.contains('active');

            if (isActive) {
                tag.classList.remove('active');
                selectedTags = selectedTags.filter(t => t.value !== value);
            } else {
                if (selectedTags.length < MAX_SELECTIONS) {
                    tag.classList.add('active');
                    selectedTags.push({ value: value, iconHTML: iconHTML });
                } else {
                    console.warn(`Limite de ${MAX_SELECTIONS} características atingido.`);
                }
            }
            updateSelectionCount();
        });
    });
    
    // Salvar Seleção
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            saveAndApplyTags();
            closeModal();
        });
    }
    
    // Executa o pré-preenchimento
    prefillCharacteristics();
</script>

</body>
</html>