<?php
session_start();

include_once 'conexao.php'; // 1. Inclui a conexão com o banco

// 2. Segurança: Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login");
    exit;
}
// 3. Pega os dados básicos da sessão
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$usuario = null;
$erro = '';

// 4. Busca os dados completos do usuário no banco (Isso só executa UMA VEZ)
try {
    if ($user_tipo == 'usuario') {
        $sql = "SELECT nome, email, cpf, banner_fixo FROM usuario WHERE id_usuario = :id LIMIT 1";
    } elseif ($user_tipo == 'ong') {
        $sql = "SELECT nome, email, cnpj, banner_fixo FROM ong WHERE id_ong = :id LIMIT 1";
    } else {
        $erro = "Tipo de usuário inválido.";
    }

    if (empty($erro)) {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $erro = "Usuário não encontrado no banco de dados.";
        }
    }
} catch (PDOException $e) {
    $erro = "Ocorreu um erro ao buscar seus dados. Tente novamente.";
    echo "Erro no perfil.php: " . $e->getMessage();
}

/* ==========================================================================
   LÓGICA DE NAVEGAÇÃO (SWITCH CASE) E BANNER
   ========================================================================== */

// 1. Determina a página atual. O padrão é 'perfil'.
// Usamos um parâmetro URL 'page' para controlar o switch
$pagina = $_GET['page'] ?? 'perfil';

// 2. Prepara a lógica do banner, MAS só se a página for 'perfil'
$caminhoBanner = ''; // Inicializa a variável
if ($pagina == 'perfil') {
    // Usa o banner fixo do banco, se existir, senão usa o banner1.jpg
    $bannerFixo = $usuario['banner_fixo'] ?? 'banner1.jpg';
    $caminhoBanner = 'images/perfil/' . $bannerFixo;
}

// ==========================================================
// INÍCIO DO BLOCO ADICIONADO
// Lógica para buscar os pets do usuário (APENAS se a página for 'meus-pets')
// ==========================================================
$pets = [];
$erro_pets = '';
if ($pagina == 'meus-pets') {
    try {
        $sql_pets = "SELECT id_pet, nome, foto, sexo, status_disponibilidade, raca, idade FROM pet WHERE ";
        
        if ($user_tipo == 'usuario') {
            // [Cite: adote_patas.sql, Tabela pet, Coluna id_usuario_fk]
            $sql_pets .= "id_usuario_fk = :id_usuario"; 
        } elseif ($user_tipo == 'ong') {
            // [Cite: adote_patas.sql, Tabela pet, Coluna id_ong_fk]
            $sql_pets .= "id_ong_fk = :id_usuario";
        } else {
            throw new Exception("Tipo de usuário inválido para buscar pets.");
        }
        $stmt_pets = $conn->prepare($sql_pets);
        $stmt_pets->bindParam(':id_usuario', $user_id, PDO::PARAM_INT);
        $stmt_pets->execute();
        $pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $erro_pets = "Erro ao buscar seus pets: " . $e->getMessage();
        // Para debug: error_log("Erro ao buscar pets: " . $e->getMessage());
    }
}

// 4. --- NOVA LÓGICA 'Pets Curtidos' ---
$pets_curtidos = [];
$erro_pets_curtidos = '';
if ($pagina == 'pets-curtidos') {
    try {
        // SQL com JOIN para buscar os pets favoritados pelo usuário
        $sql_curtidos = "SELECT p.id_pet, p.nome, p.foto, p.sexo
                         FROM favorito AS f
                         JOIN pet AS p ON f.id_pet = p.id_pet
                         WHERE f.id_usuario = :id_usuario";
                         // Opcional: AND p.status_disponibilidade = 'disponivel'
                         
        $stmt_curtidos = $conn->prepare($sql_curtidos);
        $stmt_curtidos->bindParam(':id_usuario', $user_id, PDO::PARAM_INT);
        $stmt_curtidos->execute();
        $pets_curtidos = $stmt_curtidos->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $erro_pets_curtidos = "Erro ao buscar seus pets curtidos: " . $e->getMessage();
        error_log("Erro ao buscar pets curtidos: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>
        <?php
        switch ($pagina) {
            case 'meus-pets':
                echo "Meus Pets";
                break;
            case 'pets-curtidos':
                echo "Pets Curtidos";
                break;
            case 'perfil':
            default:
                echo "Meu Perfil";
                break;
        }
        ?> - Adote Patas
    </title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
      <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link rel="stylesheet" href="assets/css/pages/perfil/perfil.css">
    <link rel="stylesheet" href="assets/css/global/toast.css">
</head>
<body class="profile-body">

    <a href="./" class="btn-voltar" title="Voltar para a página inicial">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Voltar</span>
    </a>

    <div id="toast-notification" class="toast" style="display: none;">
        <div id="toast-icon" class="toast-icon"></div>
        <div class="toast-content">
            <p id="toast-message" class="toast-message text-center">Mensagem...</p>
        </div>
        <div class="toast-progress-bar"></div>
    </div>

    <div class="container-fluid mt-5 pt-4">
        <div class="row full-height-row">

            <div class="col-lg-9">
                <?php
                // Inicia o switch case para carregar o conteúdo principal
                switch ($pagina) {
                    
                    // ==========================================================
                    // CASO 1: PÁGINA 'MEU PERFIL' (Padrão)
                    // ==========================================================
                    case 'perfil':
                    default:
                ?>
                        <main class="profile-card" style=" animation: fadeIn 0.8s ease-out;">

                            <div class="banner">
    <img src="<?php echo htmlspecialchars($caminhoBanner); ?>" alt="Banner do Usuário">
    <!-- Botão para trocar banner - AGORA NO CANTO INFERIOR DIREITO -->
    <div class="banner-actions">
        <button type="button" class="btn btn-sm btn-light btn-change-banner" data-bs-toggle="modal" data-bs-target="#bannerModal">
            <i class="fa-solid fa-image me-1"></i> Trocar Banner
        </button>
    </div>
</div>

                            <h1>Meu Perfil</h1>

                            <?php if (!empty($erro)): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($erro); ?>
                                </div>

                            <?php elseif ($usuario): ?>
                                <form id="profileForm" novalidate>
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    <input type="hidden" name="user_tipo" value="<?php echo $user_tipo; ?>">

                                    <div class="mb-3">
                                        <label for="inputNome" class="form-label"><strong>Nome:</strong></label>
                                        <input type="text" class="form-control" id="inputNome" name="nome"
                                               value="<?php echo htmlspecialchars($usuario['nome']); ?>" disabled data-profile-field>
                                        <div id="feedbackNome" class="feedback-message"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="inputEmail" class="form-label"><strong>E-mail:</strong></label>
                                        <input type="email" class="form-control" id="inputEmail" name="email"
                                               value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled data-profile-field>
                                        <div id="feedbackEmail" class="feedback-message"></div>
                                    </div>

                                    <?php if ($user_tipo == 'adotante'): ?>
                                        <div class="mb-3">
                                            <label for="inputDocumento" class="form-label"><strong>CPF:</strong></label>
                                            <input type="text" class="form-control" id="inputDocumento" name="documento"
                                                   value="<?php echo htmlspecialchars($usuario['cpf']); ?>" disabled data-profile-field>
                                            <div id="feedbackDocumento" class="feedback-message"></div>
                                        </div>
                                    <?php elseif ($user_tipo == 'protetor'): ?>
                                        <div class="mb-3">
                                            <label for="inputDocumento" class="form-label"><strong>CNPJ:</strong></label>
                                            <input type="text" class="form-control" id="inputDocumento" name="documento"
                                                   value="<?php echo htmlspecialchars($usuario['cnpj']); ?>" disabled data-profile-field>
                                            <div id="feedbackDocumento" class="feedback-message"></div>
                                        </div>
                                    <?php endif; ?>

                                    <hr class="my-4">

                                    <button type="button" id="btnEditar" class="btn btn-danger">
                                        <i class="fa-solid fa-pencil me-1"></i> Editar Perfil
                                    </button>

                                    <button type="submit" id="btnSalvar" class="btn btn-success d-none">
                                        <i class="fa-solid fa-check me-1"></i> Salvar Alterações
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                        </main>
                <?php
                    break; // Fim do 'case perfil'

                    // ==========================================================
                    // CASO 2: PÁGINA 'MEUS PETS'
                    // ==========================================================
                    case 'meus-pets':
                ?>
                        <main class="profile-card pets-grid-container"  style=" animation: fadeIn 0.8s ease-out;">
    
    <div class="d-flex justify-content-between align-items-center m-0 header-pets-section" style="border-bottom: 2px solid var(--cor-rosa-pastel); padding-bottom: 10px;">
        <h1 class="mb-0 border border-0 section-title-pets">Meus Pets</h1>
        <a href="cadastrar-pet.php" class="btn btn-danger btn-add-pet-header">
            <i class="fa-solid fa-plus me-1"></i> Cadastrar Pet
        </a>
    </div>

    <?php if (!empty($erro_pets)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($erro_pets); ?>
        </div>

    <?php elseif (empty($pets)): ?>
        <div class="no-pets-section d-flex flex-column align-items-center justify-content-center text-center p-4 p-md-5" style="margin-top: -50px;">
            <div class="no-pets-illustration mb-4">
                 <lottie-player src="animações/pets.json" background="transparent" speed="1" style="width: 250px; height: 250px;"
    loop autoplay>
</lottie-player>
            </div>
            <h2 class="section-title mb-3" style="color: var(--cor-cinza-texto)">Ainda não tem um pet registrado?</h2>
            <p class="section-description mb-4">
                Que tal adicionar seu Pet e começar a aproveitar todos os benefícios? É rápido e fácil!
            </p>
            <a href="cadastrar-pet.php" class="btn btn-success btn-lg custom-btn-add-pet">
                <i class="fas fa-plus-circle me-2"></i> Cadastrar Novo Pet
            </a>
            <p class="mt-3 text-muted">
                <em>Encontre a felicidade em cada patinha cadastrada!</em>
            </p>
        </div>

    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4" id="petsGrid">
            <?php foreach ($pets as $pet): ?>
            <div class="col">
                <div class="pet-card">
                    <div class="pet-card-img">
                        <img src="<?php echo htmlspecialchars($pet['foto'] ?? 'images/global/placeholder-pet.png'); ?>" 
                             alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                             onerror="this.src='images/perfil/teste.jpg';"> </div>

                    <div class="pet-card-body">
                        <div class="d-flex align-items-center mb-2">
                            <h2 class="pet-name me-2 mb-0"><?php echo htmlspecialchars($pet['nome']); ?></h2>
                            <?php if (!empty($pet['sexo'])): ?>
                                <?php if ($pet['sexo'] == 'femea'): ?>
                                    <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <?php else: // 'macho' ?>
                                    <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="pet-info text-muted small"><?php echo htmlspecialchars($pet['raca'] ?? 'Raça Desconhecida'); ?> - <?php echo htmlspecialchars($pet['idade'] ?? 'Idade Desconhecida'); ?></p>
                    </div>

                    <div class="pet-card-actions d-flex justify-content-between align-items-center p-3 border-top">
                        <span class="badge bg-<?php echo ($pet['status_disponibilidade'] == 'disponivel') ? 'success' : 'secondary'; ?> status-badge">
                            <?php echo ucfirst($pet['status_disponibilidade']); ?>
                        </span>
                        <div class="action-buttons">
                            <a href="editar-pet.php?id=<?php echo $pet['id_pet']; ?>" class="btn btn-sm btn-outline-primary me-2" title="Editar Pet">
                                <i class="fa-solid fa-pencil"></i>
                            </a>
                            <a href="excluir-pet.php?id=<?php echo $pet['id_pet']; ?>" class="btn btn-sm btn-outline-danger btn-excluir-pet" title="Excluir Pet">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>
                <?php
                    break; // Fim do 'case meus-pets'

                    // ==========================================================
                    // CASO 3: PÁGINA 'PETS CURTIDOS'
                    // ==========================================================
                    case 'pets-curtidos':
                ?>
                       <main class="profile-card pets-grid-container"  style=" animation: fadeIn 0.8s ease-out;">
                            <h1 class="mb-4">Pets Curtidos</h1>

                            <?php if (!empty($erro_pets_curtidos)): ?>
                                <div class="alert alert-danger"><?php echo htmlspecialchars($erro_pets_curtidos); ?></div>
                            
                            <?php elseif (empty($pets_curtidos)): ?>

                                <div class="alert text-center flex-column d-flex justify-content align-items-center text-center" style="margin-top: -1.5rem; animation: fadeIn 0.8s ease-out;">
                                         
                                <lottie-player src="animações/dog-beijos.json" background="transparent" speed="1" style="width: 200px; height: 200px;"
                                loop autoplay>
                            </lottie-player>
                                    <h5 class="mb-1">Você ainda não curtiu nenhum amiguinho.</h5>
                                    <p>Que tal curtir seu novo amigo?</p>
                                    <a href="pets" class="btn btn-danger btn-add-pet-header" style="box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;">Ver pets para adoção</a>
                                </div>
                            
                            <?php else: ?>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4" id="petsGrid">
                                
                                    <?php foreach ($pets_curtidos as $pet): ?>
                                    <div class="col">
                                        <div class="pet-card">
                                            <a href="pet-detalhe.php?id=<?php echo $pet['id_pet']; ?>" class="pet-card-link-img">
                                                <div class="pet-card-img">
                                                    <img src="<?php echo htmlspecialchars($pet['foto'] ?? 'images/global/placeholder-pet.png'); ?>" 
                                                         alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                                                         onerror="this.src='images/perfil/teste.jpg';">
                                                </div>
                                            </a>
                                            <div class="pet-card-body">
                                                <h2 class="pet-name">
                                                    <a href="pet-detalhe.php?id=<?php echo $pet['id_pet']; ?>" class="pet-card-link-nome">
                                                        <?php echo htmlspecialchars($pet['nome']); ?>
                                                    </a>
                                                </h2>
                                                
                                                <?php if (!empty($pet['sexo'])): ?>
                                                    <?php if ($pet['sexo'] == 'femea'): ?>
                                                        <i class="fa-solid fa-venus pet-gender-female" title="Fêmea"></i>
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-mars pet-gender-male" title="Macho"></i>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <i class="pet-like fa-solid fa-heart favorited" 
                                                   data-pet-id="<?php echo $pet['id_pet']; ?>" 
                                                   aria-label="Desfavoritar" 
                                                   role="button">
                                                </i>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>

                                </div>
                            <?php endif; ?>
                        </main>
                <?php
                    break; // Fim do 'case pets-curtidos'
                
                } // Fim do switch
                ?>
            </div>

                 <div class="col-lg-3 sidebar-wrapper-col">
                <div class="sidebar-sticky-wrapper">
                    <aside class="profile-sidebar p-3">
                        <div class="sidebar-header text-center mb-4">
                            <i class="fa-regular fa-circle-user sidebar-profile-icon"></i>
                            <h5 class="mt-2 mb-0">
                                <?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?>
                            </h5>
                            <small class="text-muted fs-6">
                                <?php echo htmlspecialchars(ucfirst($user_tipo)); ?>
                            </small>
                        </div>
                        
                        <nav class="nav nav-pills flex-column profile-nav">
                            
                            <a class="nav-link <?php echo ($pagina == 'perfil') ? 'active' : ''; ?>" 
                               href="perfil?page=perfil" 
                               <?php echo ($pagina == 'perfil') ? 'aria-current="page"' : ''; ?>>
                                <i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil
                            </a>
                            
                            <a class="nav-link <?php echo ($pagina == 'meus-pets') ? 'active' : ''; ?>" 
                               href="perfil?page=meus-pets"
                               <?php echo ($pagina == 'meus-pets') ? 'aria-current="page"' : ''; ?>>
                                <i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets
                            </a>
                            
                            <a class="nav-link <?php echo ($pagina == 'pets-curtidos') ? 'active' : ''; ?>" 
                               href="perfil?page=pets-curtidos"
                               <?php echo ($pagina == 'pets-curtidos') ? 'aria-current="page"' : ''; ?>>
                                <i class="fa-regular fa-heart fa-fw me-2"></i> Pets Curtidos
                            </a>

                            <a class="nav-link" href="chat.php">
                                <i class="fa-regular fa-comments fa-fw me-2"></i> Chats
                            </a>
                            <hr class="my-2">
                            
                            <a class="nav-link logout-link-sidebar" href="sair.php">
                                <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i> Sair
                            </a>
                        </nav>
                    </aside>
                </div>
            </div>


            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <i class="fa-solid fa-trash"></i>
                    <h4 class="modal-title mt-3" id="confirmDeleteModalLabel">Confirmar Exclusão</h4>
                    Você tem certeza que deseja excluir este pet?
                    <br>
                    <strong style="color: var(--cor-vermelho);">Esta ação não pode ser desfeita.</strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn"><button type="button" class="btn btn-danger">Excluir</button></a>
                    
                </div>
            </div>
        </div>
    </div>

            
        </div>
    </div>

<!-- Modal de Seleção de Banner -->
<div class="modal fade" id="bannerModal" tabindex="-1" aria-labelledby="bannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" id="modalBanner">
                <button type="button" class="btn-close" onclick="refresh()" data-bs-dismiss="modal" aria-label="Close"></button>
                <h3 class="modal-title" id="bannerModalLabel">Escolha seu Banner de Perfil</h3>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php
                    $banners = ['banner1.jpg', 'banner2.jpg', 'banner3.jpg', 'banner4.jpg', 'banner5.jpg'];
                    // Busca o banner atual do banco de dados
                    $bannerAtual = $usuario['banner_fixo'] ?? 'banner1.jpg';
                    
                    foreach ($banners as $banner) {
                        $isActive = ($bannerAtual == $banner) ? 'active' : '';
                        echo "
                        <div class='col-6 col-md-4 mb-3'>
                            <div class='banner-option $isActive' data-banner='$banner'>
                                <img src='images/perfil/$banner' alt='Banner $banner' class='img-fluid rounded'>
                                " . ($isActive ? "<div class='badge mt-2' style='  background-color: var(--cor-verde-pastel) !important;'><i class='fa-solid fa-check me-1'></i>Em uso</div>" : "") . "
                            </div>
                        </div>";
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary cancelar" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary salvar" id="saveBanner">Salvar Banner</button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    
    <script src="assets/js/pages/perfil/editar.js" type="module"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Só executa se estivermos na página 'meus-pets'
            <?php if ($pagina == 'meus-pets'): ?>
            
            const deleteModalElement = document.getElementById('confirmDeleteModal');
            
            // Verifica se o modal existe na página
            if (deleteModalElement) {
                const deleteModal = new bootstrap.Modal(deleteModalElement);
                const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                const petsGrid = document.getElementById('petsGrid');

                // Usa "event delegation" para ouvir cliques nos botões de excluir
                if (petsGrid) {
                    petsGrid.addEventListener('click', function(event) {
                        // Encontra o botão de excluir mais próximo que foi clicado
                        const deleteButton = event.target.closest('.btn-excluir-pet');

                        if (deleteButton) {
                            // Previne a ação padrão do link (ir para a página)
                            event.preventDefault(); 
                            
                            // Pega a URL de exclusão do link clicado
                            const deleteUrl = deleteButton.href;
                            
                            // Define a URL no botão "Confirmar" do modal
                            if (confirmDeleteBtn) {
                                confirmDeleteBtn.href = deleteUrl;
                            }
                            
                            // Abre o modal
                            deleteModal.show();
                        }
                    });
                }
            }
            
            <?php endif; ?>
        });
    </script>

     <script>
    document.addEventListener('DOMContentLoaded', function() {
        const petsGrid = document.getElementById('petsGrid');
        
        // Função Toast (precisa ser acessível por este script também)
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            const toastIcon = document.getElementById('toast-icon');
            const toastMessage = document.getElementById('toast-message');
            if (!toast || !toastIcon || !toastMessage) return;
            toastMessage.textContent = message;
            toast.classList.remove('success', 'danger', 'warning');
            toastIcon.className = 'toast-icon';
            toast.classList.add(type);
            if (type === 'success') toastIcon.classList.add('fas', 'fa-check');
            else if (type === 'danger') toastIcon.classList.add('fas', 'fa-times');
            else if (type === 'warning') toastIcon.classList.add('fas', 'fa-exclamation-triangle');
            toast.style.display = 'block';
            const progressBar = toast.querySelector('.toast-progress-bar');
            progressBar.style.animation = 'none';
            void progressBar.offsetWidth;
            progressBar.style.animation = 'progress 3s linear forwards';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }

        if (petsGrid) {
            petsGrid.addEventListener('click', function(event) {
                const heartIcon = event.target.closest('.pet-like');
                if (heartIcon) {
                    event.preventDefault();
                    event.stopPropagation();
                    const petId = heartIcon.dataset.petId;
                    toggleFavorite(petId, heartIcon);
                }
            });
        }

        async function toggleFavorite(petId, iconElement) {
            try {
                const response = await fetch('favoritar-pet.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({ id_pet: petId })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (result.action === 'favorited') {
                        iconElement.classList.remove('fa-regular');
                        iconElement.classList.add('fa-solid', 'favorited');
                        showToast(result.message, 'success');
                    } else if (result.action === 'unfavorited') {
                        iconElement.classList.remove('fa-solid', 'favorited');
                        iconElement.classList.add('fa-regular');
                        showToast(result.message, 'warning');
                        
                        // NOVO: Remove o card da tela após descurtir
                        // (Apenas se estivermos na página 'pets-curtidos')
                        <?php if ($pagina == 'pets-curtidos'): ?>
                        setTimeout(() => {
                            iconElement.closest('.col').remove();
                            // Verifica se a grid ficou vazia
                            if (petsGrid.querySelectorAll('.col').length === 0) {
                                document.querySelector('.pets-grid-container').innerHTML = `

                        <h1 class="mb-4">Pets Curtidos</h1>

                        <div class="alert text-center flex-column d-flex justify-content align-items-center text-center" style="margin-top: -1.5rem; animation: fadeIn 0.8s ease-out;">
                                <lottie-player src="animações/dog-beijos.json" background="transparent" speed="1" style="width: 200px; height: 200px;"
                                loop autoplay>
                            </lottie-player>
                                    <h5 class="mb-1">Você ainda não curtiu nenhum amiguinho.</h5>
                                    <p>Que tal curtir seu novo amigo?</p>
                                    <a href="pets" class="btn btn-danger btn-add-pet-header" style="box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.3) 0px 8px 16px -8px;">Ver pets para adoção</a>
                                </div>

                                `;
                            }
                        }, 1000); // Espera 1s antes de remover
                        <?php endif; ?>
                    }
                } else {
                    if (response.status === 403) {
                        showToast(result.message, 'danger');
                        setTimeout(() => { window.location.href = 'login'; }, 1500);
                    } else {
                        showToast(result.message || 'Erro ao favoritar.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Erro no fetch:', error);
                showToast('Erro de conexão. Tente novamente.', 'danger');
            }
        }
    });
    </script>

    <script>
        function refresh() {
             window.location.reload();
        }
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bannerModal = document.getElementById('bannerModal');
    const saveBannerBtn = document.getElementById('saveBanner');
    let selectedBanner = '';

    // Função Toast global
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        const toastIcon = document.getElementById('toast-icon');
        const toastMessage = document.getElementById('toast-message');
        if (!toast || !toastIcon || !toastMessage) return;
        toastMessage.textContent = message;
        toast.classList.remove('success', 'danger', 'warning');
        toastIcon.className = 'toast-icon';
        toast.classList.add(type);
        if (type === 'success') toastIcon.classList.add('fas', 'fa-check');
        else if (type === 'danger') toastIcon.classList.add('fas', 'fa-times');
        else if (type === 'warning') toastIcon.classList.add('fas', 'fa-exclamation-triangle');
        toast.style.display = 'block';
        const progressBar = toast.querySelector('.toast-progress-bar');
        progressBar.style.animation = 'none';
        void progressBar.offsetWidth;
        progressBar.style.animation = 'progress 3s linear forwards';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    // Quando o modal é aberto, configura os eventos
    bannerModal.addEventListener('show.bs.modal', function() {
        const activeBanner = document.querySelector('.banner-option.active');
        if (activeBanner) {
            selectedBanner = activeBanner.dataset.banner;
        }

        // Adiciona evento de clique nas opções de banner
        document.querySelectorAll('.banner-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove a classe active de todas as opções
                document.querySelectorAll('.banner-option').forEach(opt => {
                    opt.classList.remove('active');
                    // Remove o badge "Em uso" ou "Selecionado"
                    const badge = opt.querySelector('.badge');
                    if (badge) badge.remove();
                });

                // Adiciona a classe active na opção clicada
                this.classList.add('active');
                
                // Adiciona badge "Selecionado"
                const selectedBadge = document.createElement('div');
                selectedBadge.className = 'badge mt-2';
                selectedBadge.style = '  background-color: var(--cor-laranja-pastel) !important;';
                selectedBadge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Selecionado';
                this.appendChild(selectedBadge);

                selectedBanner = this.dataset.banner;
            });
        });
    });

    // Salvar banner selecionado
    saveBannerBtn.addEventListener('click', function() {
        if (!selectedBanner) {
            showToast('Por favor, selecione um banner.', 'warning');
            return;
        }

        // Desabilita o botão durante a requisição
        saveBannerBtn.disabled = true;
        saveBannerBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Salvando...';

        // Envia requisição para salvar o banner
        fetch('atualizar-banner.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `banner=${selectedBanner}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na rede: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showToast('Banner atualizado com sucesso! A página será recarregada.', 'success');
                
                // Fecha o modal
                const modal = bootstrap.Modal.getInstance(bannerModal);
                modal.hide();
                
                // Recarrega a página após 1.5 segundos para mostrar o novo banner
                setTimeout(() => {
                    window.location.reload();
                }, 400);
                
            } else {
                showToast(data.message || 'Erro ao atualizar banner.', 'danger');
                // Reabilita o botão em caso de erro
                saveBannerBtn.disabled = false;
                saveBannerBtn.innerHTML = 'Salvar Banner';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showToast('Erro de conexão. Tente novamente.', 'danger');
            // Reabilita o botão em caso de erro
            saveBannerBtn.disabled = false;
            saveBannerBtn.innerHTML = 'Salvar Banner';
        });
    });

    // Fecha o modal quando clica no X ou Cancelar
    bannerModal.addEventListener('hidden.bs.modal', function () {
        // Reseta o botão quando o modal é fechado
        saveBannerBtn.disabled = false;
        saveBannerBtn.innerHTML = 'Salvar Banner';
    });
});
</script>


</body>
</html>