<?php
// Inclui a conexão e a sessão
include_once 'conexao.php';
include_once 'session.php';

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
    $_SESSION['mensagem_status'] = "ID do pet não fornecido.";
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
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
    }

    if (!$tem_permissao) {
        throw new Exception("Você não tem permissão para editar este pet.");
    }

    $sql_fotos = "SELECT id_foto, caminho_foto FROM pet_fotos WHERE id_pet_fk = :id_pet ORDER BY id_foto ASC";
    $stmt_fotos = $conn->prepare($sql_fotos);
    $stmt_fotos->execute([':id_pet' => $id_pet_para_editar]);
    $pet_fotos = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);

    $pet_caracteristicas = json_decode($pet['caracteristicas'] ?? '[]', true);

} catch (Exception $e) {
    $_SESSION['mensagem_status'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// --- LÊ MENSAGENS DE ERRO (Vindas do atualizar-pet.php) ---
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);
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
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .foto-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #eee;
        }

        /* Oculta os spinners (setinhas) em navegadores WebKit 
          (Chrome, Safari, Edge, Opera) 
        */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none; /* Remove o estilo padrão do navegador */
            margin: 0; /* Remove qualquer margem que possa ter ficado */
        }

        /* Oculta os spinners no Firefox 
        */
        input[type=number] {
            -moz-appearance: textfield; /* Faz o Firefox tratar o input como um campo de texto */
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
            color: var(--cor-branca); /* Cor do texto dos seus inputs */
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
            <h1 id="page-title" class="text-xl md:text-4xl font-bold text-[#666662]">Editar Pet</h1>
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
                                    // Exibe o texto da opção selecionada inicialmente
                                    echo htmlspecialchars(ucfirst($pet['especie'])); 
                                ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-especie">
                            <li class="custom-option" data-value="cachorro" role="option" tabindex="0">Cachorro</li>
                            <li class="custom-option" data-value="gato" role="option" tabindex="0">Gato</li>
                            <li class="custom-option" data-value="outro" role="option" tabindex="0">Outro</li>
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
                        <option value="disponivel" <?php echo ($pet['status_disponibilidade'] == 'disponivel') ? 'selected' : ''; ?>>Disponível</option>
                        <option value="adotado" <?php echo ($pet['status_disponibilidade'] == 'adotado') ? 'selected' : ''; ?>>Adotado</option>
                        <option value="indisponivel" <?php echo ($pet['status_disponibilidade'] == 'indisponivel') ? 'selected' : ''; ?>>Indisponível</option>
                    </select>

                    <div class="custom-select-wrapper" data-target-select="status_disponibilidade-real">
                        <button type="button" class="custom-select-trigger input-style w-full" 
                                aria-haspopup="listbox" 
                                aria-expanded="false" 
                                aria-labelledby="select-label-disponibilidade">
                            <span class="custom-select-value">
                                <?php echo htmlspecialchars(ucfirst($pet['status_disponibilidade'])); ?>
                            </span>
                            <span class="custom-select-arrow"></span>
                        </button>
                        <ul class="custom-select-options" role="listbox" aria-labelledby="select-label-disponibilidade">
                            <li class="custom-option" data-value="disponivel" role="option" tabindex="0">Disponível</li>
                            <li class="custom-option" data-value="adotado" role="option" tabindex="0">Adotado</li>
                            <li class="custom-option" data-value="indisponivel" role="option" tabindex="0">Indisponível</li>
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
            </div>

            <div>
                <label for="fotos_novas" class="input-style w-full file-input-label">
                    <i class="fas fa-plus"></i>
                    <span id="file-name-span">Adicionar novas fotos (Máx: 5 no total)</span>
                </label>
                <input type="file" name="fotos_novas[]" id="fotos_novas" class="hidden" multiple accept="image/png, image/jpeg">
                
                <div id="fotos-preview-container"></div>
                <small id="limite-fotos-helper" class="text-sm text-gray-600 mt-1"></small>
            </div>

            <div>
                <button type="button" id="openModalBtn" class="input-style w-full">
                    <span id="tagsPlaceholder">Selecionar Características...</span>
                    <span class="tags-preview" id="tagsPreview">
                        </span>
                </button>
                <div id="hidden-tags-container">
                    </div>
            </div>

            <div>
                <label class="sr-only" for="comportamento">Comportamento (Ex: Dócil, adora crianças...)</label>
                <input type="text" name="comportamento" value="<?php echo htmlspecialchars($pet['comportamento']); ?>" id="comportamento" rows="4" placeholder="Conte um pouco sobre o pet..." class="input-style w-full"></input>
            </div>

            <div class="flex justify-center w-55 mx-auto">
                <button type="submit" class="adopt-btn" id="submit-btn"> <div class="heart-background" aria-hidden="true">
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
    // Passa o total de fotos do PHP para o JS
    const totalFotosAtuais = <?php echo count($pet_fotos); ?>;
    const MAX_FOTOS_GLOBAL = 5;

    const fileInput = document.getElementById('fotos_novas');
    const fileNameSpan = document.getElementById('file-name-span');
    const previewContainer = document.getElementById('fotos-preview-container');
    const form = document.getElementById('form-edit-pet');
    const submitBtn = document.getElementById('submit-btn');
    const limiteHelper = document.getElementById('limite-fotos-helper');

    function validarLimiteFotos() {
        const fotosMarcadasParaExcluir = document.querySelectorAll('input[name="fotos_para_excluir[]"]:checked').length;
        const fotosNovas = fileInput.files.length;
        
        const fotosAtuaisRestantes = totalFotosAtuais - fotosMarcadasParaExcluir;
        const totalFinal = fotosAtuaisRestantes + fotosNovas;

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

    document.addEventListener("DOMContentLoaded", function () {
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                previewContainer.innerHTML = ''; // Limpa preview
                const files = fileInput.files;
                
                if (files.length > 0) {
                    fileNameSpan.textContent = `${files.length} nova(s) foto(s) selecionada(s)`;
                    
                    // Cria o preview
                    Array.from(files).forEach(file => {
                        if (['image/jpeg', 'image/png'].includes(file.type)) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.classList.add('foto-preview');
                                previewContainer.appendChild(img);
                            }
                            reader.readAsDataURL(file);
                        }
                    });
                } else {
                    fileNameSpan.textContent = 'Adicionar novas fotos (Máx: 5 no total)';
                }
                // Valida o limite
                validarLimiteFotos();
            });
        }
        
        if(form) {
            form.addEventListener('submit', function(e) {
                if (!validarLimiteFotos()) {
                    e.preventDefault(); // Impede o envio
                    if (typeof showToast === 'function') {
                        showToast('Corrija os erros no formulário (limite de fotos).', 'danger');
                    } else {
                        console.error('Erro no limite de fotos.');
                    }
                }
            });
        }
        
        // Valida uma vez ao carregar a página
        validarLimiteFotos();

        // Adiciona listeners para os checkboxes existentes
        document.querySelectorAll('input[name="fotos_para_excluir[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', validarLimiteFotos);
        });
    });
</script>

<script>
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
                valueSpan.textContent = option.textContent; // Garante que o texto inicial esteja correto
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
            
            // ATUALIZA O VALOR NO <select> ESCONDIDO (MUITO IMPORTANTE!)
            if (realSelect) {
                realSelect.value = option.dataset.value;
                // Dispara um evento 'change' no select real, útil para outros listeners
                const event = new Event('change');
                realSelect.dispatchEvent(event);
            }
            
            // Fecha o menu
            trigger.click(); // Simula um clique para fechar
        });

        // Adiciona funcionalidade de foco para acessibilidade
        option.addEventListener('focus', () => {
            options.forEach(o => o.classList.remove('focused'));
            option.classList.add('focused');
        });
        option.addEventListener('blur', () => {
            option.classList.remove('focused');
        });
    });

    // 3. Fechar ao clicar fora
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            trigger.setAttribute('aria-expanded', 'false');
            optionsList.style.display = 'none';
        }
    });

    // 4. Navegação por teclado (Acessibilidade)
    let focusedIndex = -1;

    trigger.addEventListener('keydown', (e) => {
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!isExpanded) {
                    trigger.click(); // Abre se estiver fechado
                    focusedIndex = 0;
                } else {
                    focusedIndex = (focusedIndex + 1) % options.length;
                }
                if (options[focusedIndex]) options[focusedIndex].focus();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (!isExpanded) {
                    trigger.click(); // Abre se estiver fechado
                    focusedIndex = options.length - 1;
                } else {
                    focusedIndex = (focusedIndex - 1 + options.length) % options.length;
                }
                if (options[focusedIndex]) options[focusedIndex].focus();
                break;
            case 'Enter':
            case ' ': // Tecla Espaço
                e.preventDefault();
                if (isExpanded && focusedIndex !== -1 && options[focusedIndex]) {
                    options[focusedIndex].click(); // Seleciona a opção focada
                } else if (!isExpanded) {
                    trigger.click(); // Abre se estiver fechado
                }
                break;
            case 'Escape':
                e.preventDefault();
                if (isExpanded) {
                    trigger.click(); // Fecha
                    trigger.focus(); // Retorna o foco ao trigger
                }
                break;
        }
    });

    options.forEach((option, index) => {
        option.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    focusedIndex = (index + 1) % options.length;
                    if (options[focusedIndex]) options[focusedIndex].focus();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    focusedIndex = (index - 1 + options.length) % options.length;
                    if (options[focusedIndex]) options[focusedIndex].focus();
                    break;
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    option.click();
                    break;
                case 'Escape':
                    e.preventDefault();
                    trigger.click(); // Fecha
                    trigger.focus(); // Retorna o foco ao trigger
                    break;
            }
        });
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
    
    // --- NOVO: Pega características existentes do PHP ---
    const existingCharacteristics = <?php echo json_encode($pet_caracteristicas); ?>;
    
    const MAX_SELECTIONS = 5;
    let selectedTags = []; // Armazena objetos {value, iconHTML}

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
    // (Lê os inputs hidden e atualiza o array 'selectedTags')
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
    
    // --- NOVO: Função para salvar e atualizar a UI ---
    // (Separada para ser chamada no 'save' e no 'load')
    function saveAndApplyTags() {
        // 1. Limpa os inputs escondidos e o preview de ícones
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
            
            // 2b. Adiciona o ícone ao preview no botão
            tagsPreview.innerHTML += tag.iconHTML;
        });
        
        // 3. Mostra/Esconde o placeholder
        if (tagsPlaceholder) {
            tagsPlaceholder.style.display = hasSelection ? 'none' : 'block';
        }
    }
    
    // --- NOVO: Função para pré-popular no load da página ---
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
    
    // --- NOVO: Executa o pré-preenchimento ---
    prefillCharacteristics();
    
</script>

</body>
</html>