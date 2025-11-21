<?php
// Inclui a conexão e a sessão
include_once 'conexao.php';
include_once 'session.php';

// Verifica se há mensagens toast para exibir (para erros)
if (isset($_SESSION['toast_message'])) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('" . addslashes($_SESSION['toast_message']) . "', '" . ($_SESSION['toast_type'] ?? 'danger') . "');
        });
    </script>";
    // Limpa a mensagem da sessão após usar
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
}

// Protege a página: Somente usuários logados podem acessar
requerer_login();

// Pega IDs da sessão
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];

// Pega o ID do pet da URL
$id_pet_para_editar = $_GET['id'] ?? null;
$pet = null;
$erro = '';
$pet_fotos = [];

if (empty($id_pet_para_editar)) {
    // Se não tiver ID, volta para 'meus-pets' com erro
    $_SESSION['toast_message'] = "ID do pet não fornecido.";
    $_SESSION['toast_type'] = 'danger';
    header('Location: perfil?page=meus-pets');
    exit;
}

// --- Busca os dados do Pet e VERIFICA A PERMISSÃO ---
try {
    $sql = "SELECT * FROM pet WHERE id_pet = :id_pet";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_pet' => $id_pet_para_editar]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        throw new Exception("Pet não encontrado.");
    }

    // VERIFICAÇÃO DE PROPRIEDADE
    $tem_permissao = false;
    if ($user_tipo == 'usuario' && $pet['id_usuario_fk'] == $user_id) {
        $tem_permissao = true;
    } elseif ($user_tipo == 'ong' && $pet['id_ong_fk'] == $user_id) {
        $tem_permissao = true;
    } elseif ($user_tipo == 'admin') {
        $tem_permissao = true;
    }

    if (!$tem_permissao) {
        throw new Exception("Você não tem permissão para editar este pet.");
    }

    $sql_fotos = "SELECT id_foto, caminho_foto FROM pet_fotos WHERE id_pet_fk = :id_pet ORDER BY id_foto ASC";
    $stmt_fotos = $conn->prepare($sql_fotos);
    $stmt_fotos->execute([':id_pet' => $id_pet_para_editar]);
    $pet_fotos = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);

    $pet_caracteristicas = json_decode($pet['caracteristicas'] ?? '[]', true);

}  catch (Exception $e) {
    $_SESSION['toast_message'] = $e->getMessage();
    $_SESSION['toast_type'] = 'danger';
    header('Location: perfil?page=meus-pets');
    exit;
}

// Lógica para controle de status baseado no status atual - CORRIGIDA
$status_atual = $pet['status_disponibilidade'];
$status_options = [];

// Debug: Verificar qual é o status atual
error_log("Status atual do pet: " . $status_atual);

if ($status_atual == 'Em Analise') {
    // Se estiver em análise, só pode permanecer em análise
    $status_options = ['Em Analise' => 'Em Análise'];
} elseif ($status_atual == 'Disponivel') {
    // Se estiver disponível, pode ser adotado ou ficar indisponível
    $status_options = [
        'Disponivel' => 'Disponível',
        'Adotado' => 'Adotado', 
        'Indisponivel' => 'Indisponível'
    ];
} else {
    // Se estiver adotado ou indisponível, só pode voltar para disponível
    $status_options = [
        'Disponivel' => 'Disponível',
        'Adotado' => 'Adotado',
        'Indisponivel' => 'Indisponível'
    ];
}

// Se for admin, permite todas as opções
if ($user_tipo == 'admin') {
    $status_options = [
        'Em Analise' => 'Em Análise',
        'Disponivel' => 'Disponível',
        'Adotado' => 'Adotado',
        'Indisponivel' => 'Indisponível'
    ];
}

// Debug: Verificar opções disponíveis
error_log("Opções de status disponíveis: " . print_r($status_options, true));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pet - Adote Patas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
    <link rel="stylesheet" href="assets/css/pages/cadastro-pet/caracteristica.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Estilo para o Input File */
        .file-input-label { cursor: pointer; display: flex; align-items: center; gap: 10px; border: 2px dashed var(--cor-vermelho-claro); background-color: #fff8f8; transition: all 0.3s ease; }
        .file-input-label:hover { background-color: #fff0f0; border-color: var(--cor-vermelho); }
        .file-input-label i { color: var(--cor-vermelho); }
        .file-input-label span { color: #555; font-size: 0.95rem; }
        
        /* Galeria de fotos atuais */
        .fotos-atuais-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        .foto-atual-item {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Mantém a proporção 1:1 */
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .foto-atual-item img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        /* Checkbox de exclusão (escondido) */
        .foto-atual-item input[type="checkbox"] {
            display: none;
        }
        /* Botão de exclusão (Label estilizado) */
        .delete-foto-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 28px;
            height: 28px;
            background-color: rgba(255, 255, 255, 0.9);
            color: var(--cor-vermelho);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            line-height: 1;
        }
        .delete-foto-btn:hover {
            background-color: var(--cor-vermelho);
            color: white;
            transform: scale(1.1);
        }
        /* Estilo quando a foto está marcada para exclusão */
        .foto-atual-item input[type="checkbox"]:checked + img {
            opacity: 0.4;
            filter: grayscale(1);
        }
        .foto-atual-item input[type="checkbox"]:checked ~ .delete-foto-btn {
            background-color: var(--cor-vermelho);
            color: white;
            transform: rotate(45deg);
        }
        .delete-foto-btn::before {
            content: '\00D7'; /* Símbolo de multiplicação (X) */
        }
        
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

        /* Classe base para modais com ícone flutuante */
        .custom-icon-modal .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            overflow: visible;
            margin-top: 30px;
            margin-left: 5%;
            padding: 0;
        }

        .custom-icon-modal .modal-header {
            position: relative;
        }

        /* Container do Ícone */
        .custom-icon-modal .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 2rem;
            color: var(--cor-branca);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            margin-top: -50px;
            margin-bottom: 10px;
            background-color: var(--cor-vermelho);
        }

        /* --- Variação: Tema de Aviso (Para o modal de Análise) --- */
        .custom-icon-modal .modal-header.warning-theme .icon-box {
            background: linear-gradient(135deg, var(--cor-vermelho-aviso), var(--cor-vermelho));
        }

        /* Tipografia e Botões */
        .custom-icon-modal .modal-title {
            color: var(--cor-cinza-texto);
            font-weight: 700;
        }

        /* Ajuste específico para texto de destaque amarelo escuro */
        .text-warning-dark {
            color:  var(--cor-vermelho);
        }

        #confirmSaveChanges{
            background: linear-gradient(135deg, var(--cor-vermelho-aviso), var(--cor-vermelho)) !important;
            border: none !important;
            outline: none !important;
        }

        /* Melhoria na usabilidade dos botões */
        .custom-icon-modal .btn {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .custom-icon-modal .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            padding: 2rem;
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
            gap: 8px;
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

<div class="modal fade custom-icon-modal" id="analysisModal" tabindex="-1" aria-labelledby="analysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 d-flex flex-column align-items-center pb-0 warning-theme">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="icon-box">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>

            <div class="modal-body text-center">
                <h1 class="mb-2">
                    Você realizou alterações no cadastro do seu pet.
                </h1>
                <p class="mb-0 text-muted small">
                    Para garantir a segurança da adoção, seu pet irá para o status de <strong class="text-warning-dark">Em Análise</strong> após salvar.
                </p>
            </div>

            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning text-white px-4 rounded-pill fw-bold" id="confirmSaveChanges">
                    Confirmar e Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="toast-notification" class="toast p-0" style="display: none;">
    <div id="toast-icon" class="toast-icon"></div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>

<a href="perfil.php?page=meus-pets" class="btn-voltar" title="Voltar para Meus Pets">
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
            <h1 class="text-xl md:text-4xl font-bold text-[#666662]">Editar Pet</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">
        <form action="atualizar-pet.php" method="post" enctype="multipart/form-data" id="form-edit-pet" class="space-y-6">
            
            <input type="hidden" name="id_pet" value="<?php echo $pet['id_pet']; ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nome">Nome do Pet</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Pet" required class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['nome']); ?>">
                </div>
                <div>
                    <label for="idade">Idade (anos)</label>
                    <input type="number" name="idade" id="idade" placeholder="Idade (anos)" required min="0" class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['idade']); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label id="select-label-especie">Espécie</label>
                    <select name="especie" id="especie-real" class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="cachorro" <?php echo ($pet['especie'] == 'cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                        <option value="gato" <?php echo ($pet['especie'] == 'gato') ? 'selected' : ''; ?>>Gato</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="especie-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-especie">
                            <span class="custom-select-value">
                                <?php 
                                    echo htmlspecialchars(ucfirst($pet['especie'])); 
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
                    <select name="sexo" id="sexo-real" class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="macho" <?php echo ($pet['sexo'] == 'macho') ? 'selected' : ''; ?>>Macho</option>
                        <option value="femea" <?php echo ($pet['sexo'] == 'femea') ? 'selected' : ''; ?>>Fêmea</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="sexo-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-sexo">
                            <span class="custom-select-value">
                                <?php echo htmlspecialchars(ucfirst($pet['sexo'])); ?>
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
                    <select name="porte" id="porte-real" class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="pequeno" <?php echo ($pet['porte'] == 'pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                        <option value="medio" <?php echo ($pet['porte'] == 'medio') ? 'selected' : ''; ?>>Médio</option>
                        <option value="grande" <?php echo ($pet['porte'] == 'grande') ? 'selected' : ''; ?>>Grande</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="porte-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-porte">
                            <span class="custom-select-value">
                                <?php echo htmlspecialchars(ucfirst($pet['porte'])); ?>
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
                           value="<?php echo htmlspecialchars($pet['raca']); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cor">Cor</label>
                    <input type="text" name="cor" id="cor" placeholder="Cor (Ex: Caramelo)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['cor']); ?>">
                </div>
                <div>
                    <label id="select-label-vacinacao">Vacinado?</label>
                    <select name="status_vacinacao" id="status_vacinacao-real" class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="sim" <?php echo ($pet['status_vacinacao'] == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($pet['status_vacinacao'] == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="status_vacinacao-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-vacinacao">
                            <span class="custom-select-value">
                                <?php echo htmlspecialchars(ucfirst($pet['status_vacinacao'])); ?>
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
                    <select name="status_castracao" id="status_castracao-real" class="select-hidden" aria-hidden="true" tabindex="-1">
                        <option value="sim" <?php echo ($pet['status_castracao'] == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($pet['status_castracao'] == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="status_castracao-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-castracao">
                            <span class="custom-select-value">
                                <?php echo htmlspecialchars(ucfirst($pet['status_castracao'])); ?>
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
                    <label id="select-label-disponibilidade">Status</label>
<select name="status_disponibilidade" id="status_disponibilidade-real" class="select-hidden" aria-hidden="true" tabindex="-1">
    <?php foreach ($status_options as $value => $label): ?>
        <option value="<?php echo $value; ?>" <?php echo ($pet['status_disponibilidade'] == $value) ? 'selected' : ''; ?>>
            <?php echo $label; ?>
        </option>
    <?php endforeach; ?>
</select>

                    <div class="custom-select-wrapper" data-target-select="status_disponibilidade-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-disponibilidade">
<span class="custom-select-value">
    <?php 
    $status_display = $pet['status_disponibilidade'];
    if ($status_display == 'Disponivel') {
        echo 'Disponível';
    } else if ($status_display == 'Indisponivel') {
        echo 'Indisponível';
    } else if ($status_display == 'Adotado') {
        echo 'Adotado';
    } else if ($status_display == 'Em Analise') {
        echo 'Em Análise';
    } else {
        echo htmlspecialchars(ucfirst($status_display));
    }
    ?>
</span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-disponibilidade">
                            <?php foreach ($status_options as $value => $label): ?>
                                <li class="custom-option" data-value="<?php echo $value; ?>" role="option" tabindex="0">
                                    <?php echo $label; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="font-semibold text-gray-700">Gerenciar Fotos</label>
                <?php if (empty($pet_fotos)): ?>
                    <p class="text-sm text-gray-500">Nenhuma foto cadastrada. Adicione fotos abaixo.</p>
                <?php else: ?>
                    <p class="text-sm text-gray-500">Marque as fotos que deseja <span class="font-bold text-red-600">excluir</span>:</p>
                    <div class="fotos-atuais-grid">
                        <?php foreach ($pet_fotos as $foto): ?>
                            <div class="foto-atual-item">
                                <input type="checkbox" name="fotos_para_excluir[]" 
                                        id="foto_<?php echo $foto['id_foto']; ?>" 
                                        value="<?php echo $foto['id_foto']; ?>"
                                        onchange="validarLimiteFotos()">
                                <img src="<?php echo htmlspecialchars($foto['caminho_foto']); ?>" alt="Foto atual do pet"
                                            onerror="this.src='images/perfil/teste.jpg';">
                                <label class="delete-foto-btn" 
                                        for="foto_<?php echo $foto['id_foto']; ?>" 
                                        title="Marcar para excluir"></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Área de Drag and Drop -->
                <div id="drop-area" class="input-style w-full file-input-label mt-4">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <div class="drop-content">
                        <span id="file-name-span">Arraste e solte fotos aqui ou clique para selecionar</span>
                        <small class="block mt-1">Máximo: 5 fotos no total | Formatos: PNG, JPG, JPEG, WEBP</small>
                    </div>
                    <input type="file" id="fotos_novas_input" class="hidden" multiple accept="image/png, image/jpeg, image/jpg, image/webp">
                </div>
                
                <input type="file" name="fotos_novas[]" id="fotos_novas_final" class="hidden" multiple>

                <div id="fotos-preview-container" class="mt-4"></div>
                <small id="limite-fotos-helper" class="text-sm text-gray-600 mt-1"></small>
            </div>

            <div>
                <button type="button" id="openModalBtn" class="input-style w-full text-left">
                    <span id="tagsPlaceholder" class="tags-placeholder">Selecionar Características...</span>
                    <span class="tags-preview" id="tagsPreview"></span>
                </button>
                <div id="hidden-tags-container"></div>
            </div>

            <div>
                <label class="sr-only" for="comportamento">Comportamento (Ex: Dócil, adora crianças...)</label>
                <input type="text" name="comportamento" value="<?php echo htmlspecialchars($pet['comportamento']); ?>" id="comportamento" rows="4" placeholder="Conte um pouco sobre o pet..." class="input-style w-full"></input>
            </div>

            <div class="flex justify-center w-55 mx-auto">
                <button type="submit" class="adopt-btn" id="submit-btn"> 
                    <div class="heart-background" aria-hidden="true">
                        <i class="bi bi-heart-fill"></i>
                    </div>
                    <span>Salvar Alterações</span>
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
const totalFotosAtuais = <?php echo count($pet_fotos); ?>;
const MAX_FOTOS_GLOBAL = 5;

// Verificar se houve mudanças no formulário
function checkFormChanges() {
    const form = document.getElementById('form-edit-pet');
    const submitBtn = document.getElementById('submit-btn');
    
    if (!form || !submitBtn) return;
    
    // Flag para controlar se deve mostrar alerta
    window.formHasChanges = false;
    let formSubmitted = false;

    // Monitorar mudanças em todos os campos
    form.addEventListener('change', function() {
        if (!formSubmitted) {
            window.formHasChanges = true;
        }
    });
    
    // Monitorar input em campos de texto
    const textInputs = form.querySelectorAll('input[type="text"], input[type="number"], textarea');
    textInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (!formSubmitted) {
                window.formHasChanges = true;
            }
        });
    });
    
    // Interceptar o envio do formulário
    form.addEventListener('submit', function(e) {
        // Se for admin, envia diretamente sem modal
        const isAdmin = <?php echo $user_tipo === 'admin' ? 'true' : 'false'; ?>;
        
        if (isAdmin) {
            return; // Admin pode enviar diretamente
        }
        
        // Se não há mudanças, envia diretamente
        if (!window.formHasChanges) {
            return;
        }
        
        // Se há mudanças e não é admin, mostra o modal
        e.preventDefault();
        
        const analysisModal = new bootstrap.Modal(document.getElementById('analysisModal'));
        analysisModal.show();
    });
    
    // Configurar o botão de confirmação do modal
    document.getElementById('confirmSaveChanges').addEventListener('click', function() {
        formSubmitted = true;
        document.getElementById('form-edit-pet').submit();
    });
}

// Inicializar a verificação de mudanças
document.addEventListener('DOMContentLoaded', checkFormChanges);

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
        const fotosMarcadasParaExcluir = document.querySelectorAll('input[name="fotos_para_excluir[]"]:checked').length;
        const fotosAtuaisRestantes = totalFotosAtuais - fotosMarcadasParaExcluir;
        const totalPreliminar = fotosAtuaisRestantes + files.length;

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

// Função de validação de limite de fotos
function validarLimiteFotos() {
    const fotosMarcadasParaExcluir = document.querySelectorAll('input[name="fotos_para_excluir[]"]:checked').length;
    const fotosNovas = document.getElementById('fotos_novas_final').files.length;
    
    const fotosAtuaisRestantes = totalFotosAtuais - fotosMarcadasParaExcluir;
    const totalFinal = fotosAtuaisRestantes + fotosNovas;

    const limiteHelper = document.getElementById('limite-fotos-helper');
    const submitBtn = document.getElementById('submit-btn');

    if (totalFinal > MAX_FOTOS_GLOBAL) {
        limiteHelper.textContent = `Erro: Limite de ${MAX_FOTOS_GLOBAL} fotos excedido! (Total: ${totalFinal})`;
        limiteHelper.style.color = 'var(--cor-vermelho)';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        return false;
    } else if (totalFinal === 0) {
        limiteHelper.textContent = `Erro: O pet deve ter pelo menos 1 foto.`;
        limiteHelper.style.color = 'var(--cor-vermelho)';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.7';
        return false;
    } else {
        const espacoRestante = MAX_FOTOS_GLOBAL - fotosAtuaisRestantes;
        limiteHelper.textContent = `Você pode adicionar mais ${espacoRestante} foto(s). (Total será ${totalFinal}/${MAX_FOTOS_GLOBAL})`;
        limiteHelper.style.color = '#555';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        return true;
    }
}

// Inicializar a validação de fotos
document.addEventListener('DOMContentLoaded', validarLimiteFotos);

// Adiciona listeners para os checkboxes existentes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="fotos_para_excluir[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', validarLimiteFotos);
    });
});

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
    const existingCharacteristics = <?php echo json_encode($pet_caracteristicas); ?>;
    
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