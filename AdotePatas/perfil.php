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
    if ($user_tipo == 'adotante') {
        $sql = "SELECT nome, email, cpf FROM usuario WHERE id_usuario = :id LIMIT 1";
    } elseif ($user_tipo == 'protetor') {
        $sql = "SELECT nome, email, cnpj FROM ong WHERE id_ong = :id LIMIT 1";
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
    // Para debug: error_log("Erro no perfil.php: " . $e->getMessage());
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
    $listaDeBanners = [
        'banner1.jpg',
        'banner2.jpg',
        'banner3.jpg',
        'banner4.jpg',
        'banner5.jpg'
    ];
    $nomeBannerSorteado = $listaDeBanners[array_rand($listaDeBanners)];
    $caminhoBanner = 'images/perfil/' . $nomeBannerSorteado;
}
// ==========================================================
// INÍCIO DO BLOCO ADICIONADO
// Lógica para buscar os pets do usuário (APENAS se a página for 'meus-pets')
// ==========================================================
$pets = [];
$erro_pets = '';
if ($pagina == 'meus-pets') {
    try {
        $sql_pets = "SELECT id_pet, nome, foto, sexo, status_disponibilidade FROM pet WHERE ";
        
        if ($user_tipo == 'adotante') {
            // [Cite: adote_patas.sql, Tabela pet, Coluna id_usuario_fk]
            $sql_pets .= "id_usuario_fk = :id_usuario"; 
        } elseif ($user_tipo == 'protetor') {
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
            <p id="toast-message" class="toast-message">Mensagem...</p>
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
                        <main class="profile-card">

                            <div class="banner">
                                <img src="<?php echo htmlspecialchars($caminhoBanner); ?>" alt="Banner do Usuário">
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
                        <main class="profile-card pets-grid-container">
    
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
        <div class="no-pets-section d-flex flex-column align-items-center justify-content-center text-center p-4 p-md-5" style="margin-top: -30px;">
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
                        <main class="profile-card">
                            <h1>Pets Curtidos</h1>
                            <p>Aqui você verá a lista de todos os pets que você curtiu.</p>
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
                            
                            <hr class="my-2">
                            
                            <a class="nav-link logout-link-sidebar" href="sair.php">
                                <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i> Sair
                            </a>
                        </nav>
                    </aside>
                </div>
            </div>


            <!--MODAL DE DELETAR PET-->
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
                    <!-- Este botão 'Confirmar' receberá o link de exclusão via JS -->
                     <a href="#" id="confirmDeleteBtn"><button type="button" class="btn btn-danger">Excluir</button></a>
                    
                </div>
            </div>
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

</body>
</html>