<?php
// Inclui a conexão e a sessão
include_once 'conexao.php'; //
include_once 'session.php'; //

// Protege a página: Somente usuários logados podem acessar
requerer_login(); //

// --- LÊ MENSAGENS DA SESSÃO (PADRÃO PRG) ---
// Usamos a mesma lógica do autenticacao.php para mostrar mensagens
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';

unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);

// --- INICIALIZA VARIÁVEIS DO FORMULÁRIO ---
// Isso é para manter os dados no formulário caso a validação falhe
$nome = $_SESSION['form_data']['nome'] ?? '';
$especie = $_SESSION['form_data']['especie'] ?? '';
$idade = $_SESSION['form_data']['idade'] ?? '';
$porte = $_SESSION['form_data']['porte'] ?? '';
$sexo = $_SESSION['form_data']['sexo'] ?? ''; 
$raca = $_SESSION['form_data']['raca'] ?? '';
$cor = $_SESSION['form_data']['cor'] ?? '';
$status_vacinacao = $_SESSION['form_data']['status_vacinacao'] ?? '';
$status_castracao = $_SESSION['form_data']['status_castracao'] ?? '';
$comportamento = $_SESSION['form_data']['comportamento'] ?? '';
$caracteristicas = $_SESSION['form_data']['caracteristicas'] ?? [];

// Limpa os dados do formulário da sessão após usá-los
unset($_SESSION['form_data']);


// --- PROCESSAMENTO DO FORMULÁRIO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coleta de dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $especie = trim($_POST['especie'] ?? ''); //
    $idade = trim($_POST['idade'] ?? ''); //
    $porte = trim($_POST['porte'] ?? ''); //
    $sexo = trim($_POST['sexo'] ?? '');
    $raca = trim($_POST['raca'] ?? 'Não definida'); //
    $cor = trim($_POST['cor'] ?? ''); //
    $status_vacinacao = trim($_POST['status_vacinacao'] ?? ''); //
    $status_castracao = trim($_POST['status_castracao'] ?? ''); //
    $comportamento = trim($_POST['comportamento'] ?? ''); //
    $caracteristicas = $_POST['caracteristicas'] ?? [];
    
    // Salva os dados na sessão para o caso de falha
    $_SESSION['form_data'] = $_POST;

    // Coleta dados da sessão
    $id_usuario_logado = $_SESSION['user_id'];
    $tipo_usuario_logado = $_SESSION['user_tipo']; // 'adotante' ou 'protetor'

    $erros = [];
    $fotos_salvas_paths = []; // Caminho da foto para salvar no banco

    // 2. Validações (PHP)
    
    // Validações de campos obrigatórios
    if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
    if (empty($especie)) $erros[] = "O campo 'Espécie' é obrigatório.";
    if ($idade === '') $erros[] = "O campo 'Idade' é obrigatório."; // Idade pode ser 0
    if (empty($sexo)) $erros[] = "O campo 'Sexo' é obrigatório.";
    if (empty($porte)) $erros[] = "O campo 'Porte' é obrigatório.";
    if (empty($status_vacinacao)) $erros[] = "O campo 'Vacinado' é obrigatório.";
    if (empty($status_castracao)) $erros[] = "O campo 'Castrado' é obrigatório.";

    // Validações de formato
    if ($idade !== '' && !filter_var($idade, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
        $erros[] = "A idade deve ser um número válido (0 ou mais).";
    }
    
    // Validações dos ENUMs (baseado no adote_patas.sql)
    $portes_validos = ['pequeno', 'medio', 'grande'];
    $especies_validas = ['cachorro', 'gato', 'outro']; // Adicionado 'outro'
    $status_validos = ['sim', 'nao'];
    $sexos_validos = ['macho', 'femea'];

    if (!empty($porte) && !in_array($porte, $portes_validos)) $erros[] = "Porte inválido.";
    if (!empty($especie) && !in_array($especie, $especies_validas)) $erros[] = "Espécie inválida.";
    if (!empty($status_vacinacao) && !in_array($status_vacinacao, $status_validos)) $erros[] = "Status de vacinação inválido.";
    if (!empty($status_castracao) && !in_array($status_castracao, $status_validos)) $erros[] = "Status de castração inválido.";
    if (!empty($sexo) && !in_array($sexo, $sexos_validos)) $erros[] = "Sexo inválido.";


    if (count($caracteristicas) > 5) {
        $erros[] = "Você só pode selecionar até 5 características.";
    }

    // 3. Validação e Upload das Fotos (*** LÓGICA MÚLTIPLA - WEBP JÁ VEM DO JS ***)
    
    // Verifica se alguma foto foi enviada
    if (isset($_FILES['fotos_novas']) && !empty(array_filter($_FILES['fotos_novas']['name']))) {
        $total_files = count($_FILES['fotos_novas']['name']);
        
        if ($total_files > 5) {
            $erros[] = "Você só pode enviar no máximo 5 fotos.";
        } else {
            $upload_dir = 'uploads/pets/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); 
            }
            if (!is_writable($upload_dir)) {
                 $erros[] = "Erro de servidor: O diretório '$upload_dir' não tem permissão de escrita.";
            }

            // Agora só permitimos/esperamos .webp
            $extensoes_permitidas = ['webp'];

            for ($i = 0; $i < $total_files; $i++) {
                $file_name = $_FILES['fotos_novas']['name'][$i];
                $file_tmp = $_FILES['fotos_novas']['tmp_name'][$i];
                $file_size = $_FILES['fotos_novas']['size'][$i];
                $file_error = $_FILES['fotos_novas']['error'][$i];
                
                if ($file_error == UPLOAD_ERR_OK) {
                    $file_ext_check = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    if ($file_ext_check != 'webp') {
                        $erros[] = "Foto '$file_name': Formato inválido. (Esperado .webp)";
                    }
                    if ($file_size > 5 * 1024 * 1024) { // 5 MB
                        $erros[] = "Foto '$file_name': Imagem muito grande (Máx: 5MB).";
                    }

                    if (empty($erros)) {
                        // O nome do arquivo já vem como .webp, mas vamos gerar um uniqid
                        // para garantir que não haja conflitos
                        $novo_nome_arquivo = uniqid('', true) . '.webp';
                        $caminho_completo = $upload_dir . $novo_nome_arquivo;

                        // APENAS movemos o arquivo. Nenhuma conversão!
                        if (move_uploaded_file($file_tmp, $caminho_completo)) {
                            $fotos_salvas_paths[] = $caminho_completo; // Adiciona ao array
                        } else {
                            $erros[] = "Falha ao salvar a imagem '$file_name'.";
                        }
                    }
                }
            }
        }
    } else {
        $erros[] = "Pelo menos uma foto do pet é obrigatória.";
    }

    // 4. Decisão Final: Inserir no Banco ou Mostrar Erro

    if (!empty($erros)) {
        // Se houver erros, REDIRECIONA de volta com a mensagem
        $_SESSION['mensagem_status'] = $erros[0];
        $_SESSION['tipo_mensagem'] = 'danger';
        // Os dados do formulário já estão salvos em $_SESSION['form_data']
        
         // Limpa fotos que possam ter sido salvas antes do erro
        foreach ($fotos_salvas_paths as $path) {
            if (file_exists($path)) @unlink($path);
        }
        
        header("Location: cadastrar-pet.php");
        exit;

    } else {
        // Se NÃO houver erros, insere no banco
        
        // Define quem é o "dono" do pet (ONG ou Usuário)
        $id_usuario_fk = null;
        $id_ong_fk = null;

        if ($tipo_usuario_logado == 'ong') {
            $id_ong_fk = $id_usuario_logado; //
        } else {
            // Assumindo 'adotante' ou 'doador'
            $id_usuario_fk = $id_usuario_logado;
        }

        $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);

        $conn->beginTransaction();
        try {
           $sql = "INSERT INTO pet (nome, especie, sexo, idade, porte, raca, cor, status_vacinacao, status_castracao, comportamento, id_usuario_fk, id_ong_fk, status_disponibilidade, caracteristicas) 
        VALUES (:nome, :especie, :sexo, :idade, :porte, :raca, :cor, :status_vacinacao, :status_castracao, :comportamento, :id_usuario_fk, :id_ong_fk, 'Em Analise', :caracteristicas)";
        
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':especie' => $especie,
                ':sexo' => $sexo, 
                ':idade' => $idade,
                ':porte' => $porte,
                ':raca' => $raca,
                ':cor' => $cor,
                ':status_vacinacao' => $status_vacinacao,
                ':status_castracao' => $status_castracao,
                ':comportamento' => $comportamento,
                ':id_usuario_fk' => $id_usuario_fk,
                ':id_ong_fk' => $id_ong_fk,
                ':caracteristicas' => $caracteristicas_json
            ]);
            // Pega o ID do pet que acabou de ser inserido
            $id_pet_inserido = $conn->lastInsertId();

            // Passo 2: Insere as Fotos na tabela 'pet_fotos'
            $sql_foto = "INSERT INTO pet_fotos (id_pet_fk, caminho_foto) VALUES (:id_pet_fk, :caminho_foto)";
            $stmt_foto = $conn->prepare($sql_foto);

            foreach ($fotos_salvas_paths as $caminho) {
                $stmt_foto->execute([
                    ':id_pet_fk' => $id_pet_inserido,
                    ':caminho_foto' => $caminho
                ]);
            }

// Se tudo deu certo, confirma a transação
$conn->commit();

// --- LÓGICA PRG CORRIGIDA ---
unset($_SESSION['form_data']); // Limpa os dados do formulário

// Salva a mensagem na sessão para exibir no perfil
$_SESSION['toast_message'] = "Pet cadastrado com sucesso!";
$_SESSION['toast_type'] = 'success';

// Redireciona para a página meus-pets
header("Location: perfil?page=meus-pets");
exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();

            foreach ($fotos_salvas_paths as $path) {
                if (file_exists($path)) @unlink($path);
            }
            
            $_SESSION['mensagem_status'] = "Ocorreu uma falha no banco de dados. Tente novamente.";
            $_SESSION['tipo_mensagem'] = 'danger';
            
            // Log do erro (idealmente)
            // error_log("Erro no cadastro de pet: " . $e->getMessage());

            header("Location: cadastrar-pet.php");
            exit;
        }
    }
}
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
        /* Estilo para o Input File */
        .file-input-label { cursor: pointer; display: flex; align-items: center; gap: 10px; border: 2px dashed var(--cor-vermelho-claro); background-color: #fff8f8; transition: all 0.3s ease; }
        .file-input-label:hover { background-color: #fff0f0; border-color: var(--cor-vermelho); }
        .file-input-label i { color: var(--cor-vermelho); }
        .file-input-label span { color: #555; font-size: 0.95rem; }
        
        /* Preview de novas fotos */
        #fotos-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .foto-preview {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Proporção 1:1 */
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

        /* Oculta os spinners (setinhas) em navegadores WebKit */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Oculta os spinners no Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Estilos para o Select Customizado */
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
        
        /* Ajuste de cor para placeholder */
        .custom-select-value.placeholder {
            color: var(--cor-branca);
            background-color: transparent;
            opacity: 0.8; /* Um pouco mais claro para indicar que é placeholder */
        }

        .custom-select-arrow {
            width: 10px;
            height: 10px;
            border-right: 2px solid var(--cor-vermelho); /* Cor da seta */
            border-bottom: 2px solid var(--cor-vermelho); /* Cor da seta */
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

        /* Estilos para as tags de características no input */
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
            color: var(--cor-texto);
            border: 1px solid #eee;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Ajuste para o botão de características */
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

        /* Estilos para Drag and Drop */
        #drop-area {
            border: 2px dashed var(--cor-vermelho-claro);
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #fff8f8;
            cursor: pointer;
            position: relative;
        }

        #drop-area.highlight {
            background-color: #fff0f0;
            border-color: var(--cor-vermelho);
            transform: scale(1.02);
        }

        #drop-area.highlight i {
            color: var(--cor-vermelho);
            transform: scale(1.1);
        }

        .drop-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        #drop-area i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--cor-vermelho-claro);
            transition: all 0.3s ease;
        }

        #file-name-span {
            font-weight: 600;
            color: #555;
            font-size: 1rem;
        }

        #drop-area small {
            color: #888;
            font-size: 0.85rem;
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
            <div id="php-data" 
                 data-message="<?php echo htmlspecialchars($mensagem_status); ?>" 
                 data-type="<?php echo htmlspecialchars($tipo_mensagem); ?>" 
                 style="display: none;">
            </div>
        <?php endif; ?>

       <form action="cadastrar-pet.php" method="post" enctype="multipart/form-data" id="form-cadastro-pet" class="space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nome">Nome do Pet</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Pet" required class="input-style w-full"
                           value="<?php echo htmlspecialchars($nome); ?>">
                </div>
                <div>
                    <label for="idade">Idade (anos)</label>
                    <input type="number" name="idade" id="idade" placeholder="Idade (anos)" required min="0" class="input-style w-full"
                           value="<?php echo htmlspecialchars($idade); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                    else echo 'Espécie'; // Placeholder
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
                                    else echo 'Gênero'; // Placeholder
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
                                    else echo 'Porte'; // Placeholder
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
                                    else echo 'Vacinado?'; // Placeholder
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                    else echo 'Castrado?'; // Placeholder
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
                
                <div>
                    <label for="openModalBtn">Características</label>
                    <button type="button" id="openModalBtn" class="input-style w-full text-left">
                        <span id="tagsPlaceholder" class="tags-placeholder" style="<?php echo !empty($caracteristicas) ? 'display: none;' : 'display: block;'; ?>">Selecionar Características...</span>
                        <span class="tags-preview" id="tagsPreview"></span>
                    </button>
                </div>

                <div id="hidden-tags-container"></div>
            </div>
    
            <div>
                <label class="font-semibold text-gray-700">Adicionar Fotos</label>
                
                <!-- Área de Drag and Drop -->
                <div id="drop-area" class="input-style w-full file-input-label mt-4">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="drop-content">
                        <span id="file-name-span">Arraste e solte fotos aqui ou clique para selecionar</span>
                        <small class="block mt-1">Máximo: 5 fotos | Formatos: PNG, JPG, JPEG, WEBP</small>
                    </div>
                    <input type="file" id="fotos_novas_input" class="hidden" multiple accept="image/png, image/jpeg, image/jpg, image/webp">
                </div>
                
                <input type="file" name="fotos_novas[]" id="fotos_novas_final" class="hidden" multiple>

                <div id="fotos-preview-container" class="mt-4"></div>
                <small id="limite-fotos-helper" class="text-sm text-gray-600 mt-1"></small>
            </div>

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

// Implementação do Drag and Drop
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
        dropArea.classList.add('highlight');
    }

    function unhighlight() {
        dropArea.classList.remove('highlight');
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
        // Esta é uma implementação simplificada - em produção você precisaria
        // manter um array com os arquivos convertidos
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

// Custom Select Functionality
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
            trigger.click();
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