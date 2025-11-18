<?php
session_start();

include_once 'conexao.php';

$logado = isset($_SESSION['user_id']);
$usuario = null;
$user_id = null;
$user_tipo = null;
$primeiro_nome = '';
$pagina = "chats";

if ($logado) {
    $user_id = $_SESSION['user_id'];
    $user_tipo = $_SESSION['user_tipo'] ?? null;

    try {
        if ($user_tipo == 'usuario') {
            $sql = "SELECT nome, email, cpf FROM usuario WHERE id_usuario = :id LIMIT 1";
        } elseif ($user_tipo == 'ong') {
            $sql = "SELECT nome, email, cnpj FROM ong WHERE id_ong = :id LIMIT 1";
        } else {
            $sql = null;
        }

        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // silencioso — manter UX
    }

    if ($logado && isset($_SESSION['nome'])) {
        $partes = explode(' ', $_SESSION['nome']);
        $primeiro_nome = $partes[0] ?? '';
    }
}

$conversa_id = $_GET['id'] ?? null;

$lista_conversas = [
    [
        "id" => 1,
        "nome" => "Adote Patas",
        "preview" => "Olá! Vimos que você se interessou...",
        "data" => "02/04",
        "avatar" => "images/global/Logo-AdotePatas.png"
    ],
    [
        "id" => 2,
        "nome" => "Marcella",
        "preview" => "Texto texto texto...",
        "data" => "04/08",
        "avatar" => "https://via.placeholder.com/50/BF6964/FFFFFF?text=M"
    ],
    [
        "id" => 3,
        "nome" => "Nome",
        "preview" => "Olá! Vimos que você se interess...",
        "data" => "04/08",
        "avatar" => "https://via.placeholder.com/50/DEA796/FFFFFF?text=N"
    ]
];

$conversa_ativa = null;
if ($conversa_id) {
    foreach ($lista_conversas as $conversa) {
        if ($conversa['id'] == $conversa_id) {
            $conversa_ativa = $conversa;
            break;
        }
    }
}

date_default_timezone_set('America/Sao_Paulo');
$hora_atual = date('H');
$minuto_atual = date('i');
$horario_completo = date('H:i');

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversas - Adote Patas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="assets/css/pages/chat/chat.css">

    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
</head>
<body class="chat-page-body">

<header>
  <nav class="navbar navbar-expand">
    <div class="container">
      <a class="navbar-brand" href="./">
        <img src="./images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
      </a>

      <?php if ($logado): ?>
        <div class="d-flex align-items-center gap-4">
          <div class="d-none d-xl-block">
            <ul class="navbar-nav d-flex flex-row gap-4 mb-0">
              <li class="nav-item">
                <a class="nav-link navlink" href="sobre-nos">Sobre Nós</a>
              </li>
              <li class="nav-item">
                <a class="nav-link navlink" href="ajuda.php">Ajuda</a>
              </li>
            </ul>
          </div>

          <a href="perfil?page=perfil" class="profile-info-link d-flex align-items-center gap-3 text-decoration-none" title="Ver meu perfil">
            <div class="d-flex align-items-center flex-row-reverse gap-2">
              <i class="fa-regular fa-circle-user profile-icon"></i>
              <span class="profile-name fs-5" style="color: var(--cor-vermelho);"><?php echo htmlspecialchars($primeiro_nome); ?></span>
            </div>
          </a>

          <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
            <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
          </button>
        </div>
      <?php endif; ?>
    </div>
  </nav>
</header>
      
<main class="chat-container container">
  <div class="row g-0 chat-main-card">
    
    <aside class="col-lg-4 col-md-5 col-12 chat-sidebar">
      
      <div class="chat-sidebar-header">
        <h1 class="chat-title">Conversas</h1>
        <div class="chat-search-wrapper">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input type="text" class="form-control chat-search-input" placeholder="Pesquisar conversas...">
        </div>
      </div>

      <div class="chat-list">
        
        <?php foreach ($lista_conversas as $conversa): ?>
          <?php
            $is_active = ($conversa_id == $conversa['id']);
          ?>
          <a href="chat.php?id=<?php echo $conversa['id']; ?>" 
             class="chat-list-item <?php echo $is_active ? 'active' : ''; ?>"
             <?php echo $is_active ? 'aria-current="true"' : ''; ?>
             data-conversation-id="<?php echo $conversa['id']; ?>">
            
            <img src="<?php echo htmlspecialchars($conversa['avatar']); ?>" alt="Foto de perfil de <?php echo htmlspecialchars($conversa['nome']); ?>" class="chat-avatar">
            
            <div class="chat-item-details">
              <div class="chat-item-header">
                <span class="chat-name"><?php echo htmlspecialchars($conversa['nome']); ?></span>
                <span class="chat-date"><?php echo htmlspecialchars($conversa['data']); ?></span>
              </div>
              <p class="chat-preview">
                <?php echo htmlspecialchars($conversa['preview']); ?>
              </p>
            </div>
          </a>
        <?php endforeach; ?>
        
      </div>
    </aside>

    <section class="col-lg-8 col-md-7 d-none d-md-flex chat-conversation-area">

      <?php if ($conversa_ativa): ?>
        <div class="chat-active-header">
            <button class="btn btn-back-mobile d-md-none">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            
            <img src="<?php echo htmlspecialchars($conversa_ativa['avatar']); ?>" alt="Foto de perfil de <?php echo htmlspecialchars($conversa_ativa['nome']); ?>" class="chat-avatar">
            <span class="chat-active-name"><?php echo htmlspecialchars($conversa_ativa['nome']); ?></span>
        </div>

        <div class="chat-messages">

            <div class="message received">
                <p>Olá! Vimos que você se interessou pelo Caramelo</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>
            <div class="message sent">
                <p>Sim! Gostaria de saber mais sobre ele.</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>
            <div class="message received">
                <p>Claro! Qual sua Dúvida?</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>

            <div class="message sent">
                <p>Moro em um Apartamento, posso deixar ele sozinho enquanto trabalho?</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>

            <div class="message received">
                <p>Ele é muito manhoso, e não gosta de ficar sozinho</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>

            <div class="message received">
                <p>Aconselho deixar com alguém ou adotar um amiguinho para o  Caramelo!</p>
                <div class="date message-timestamp">
                  <?php echo date('d/m/Y' . "   " . $horario_completo); ?>
                </div>
            </div>

        </div>

        <div class="chat-input-area">
          <button class="files chat-send-files" type="button">
            <i class="fa-solid fa-plus"></i>
          </button>
            <input type="text" class="form-control chat-message-input" placeholder="Digite sua mensagem...">
            <button class="btn chat-send-btn" type="button" aria-label="Enviar mensagem">
                <i class="fa-solid fa-paper-plane me-1"></i>
            </button>
        </div>

        <div class="modal fade" id="fileModal" tabindex="-1" aria-labelledby="fileModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileModalLabel">Enviar Arquivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-lg" id="documentBtn">
                                <i class="fa-solid fa-file-lines me-2"></i>Documento
                            </button>
                            <button type="button" class="btn btn-outline-success btn-lg" id="mediaBtn">
                                <i class="fa-solid fa-image me-2"></i>Fotos/Vídeos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <input type="file" id="documentInput" accept=".pdf,.doc,.docx,.txt,.rtf" hidden>
        <input type="file" id="mediaInput" accept="image/*,video/*" hidden>

      <?php else: ?>
        <div class="chat-placeholder">
            <img src="images/global/Logo-AdotePatas.png" alt="" class="chat-placeholder-logo">
            <h2 class="adote-patas">Adote Patas</h2>
            <p>Selecione uma conversa ao lado para começar.</p>
        </div>
      <?php endif; ?>

    </section>

  </div>
</main>

<div id="toast-notification" class="adp-toast p-0" style="display: none;">
    <div id="toast-icon" class="adp-toast-icon" style="font-size: 1.6rem"></div>
    <div class="adp-toast-content">
        <p id="toast-message" class="adp-toast-message text-center"></p>
    </div>
    <div class="adp-toast-progress-bar"></div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
  <div class="offcanvas-header border-bottom">
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <div class="offcanvas-body p-0">
      <aside class="profile-sidebar p-3">
        <div class="sidebar-header text-center mb-4">
          <i class="fa-regular fa-circle-user sidebar-profile-icon logged-in"></i>
          <h5 class="mt-2 mb-0">
            <?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?>
          </h5>
          <small class="text-muted fs-6">
            <?php echo htmlspecialchars(ucfirst($user_tipo ?? '')); ?>
          </small>
        </div>
        
        <nav class="nav nav-pills flex-column profile-nav">
          <div class="d-xl-none">
            <a class="nav-link" href="sobre-nos">
              <i class="fa-solid fa-info-circle fa-fw me-2"></i> Sobre Nós
            </a>
            <a class="nav-link" href="#">
              <i class="fa-solid fa-question-circle fa-fw me-2"></i> Ajuda
            </a>
            <hr class="my-2">
          </div>
          
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

          <a class="nav-link <?php echo ($pagina == 'chats') ? 'active' : ''; ?>" 
             href="chat.php"
             <?php echo ($pagina == 'chats') ? 'aria-current="page"' : ''; ?>>
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

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/pages/chat/file-size-upload.js" ></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botão voltar no mobile
    const backButton = document.querySelector('.btn-back-mobile');
    if (backButton) {
        backButton.addEventListener('click', function() {
            window.location.href = 'chat.php';
        });
    }

    // Verifica se estamos em mobile e há conversa ativa para aplicar a classe
    function checkMobileLayout() {
        if (window.innerWidth < 768 && <?php echo ($conversa_ativa && $conversa_id) ? 'true' : 'false'; ?>) {
            document.body.classList.add('chat-active-mobile');
        } else {
            document.body.classList.remove('chat-active-mobile');
        }
    }

    // Verifica layout ao carregar
    checkMobileLayout();

    // Verifica layout ao redimensionar a janela
    window.addEventListener('resize', checkMobileLayout);

    // Ao clicar em uma conversa no mobile
    const chatLinks = document.querySelectorAll('.chat-list-item');
    chatLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Apenas em telas menores que 768px
            if (window.innerWidth < 768) {
                e.preventDefault();
                const conversationId = this.getAttribute('data-conversation-id');
                window.location.href = `chat.php?id=${conversationId}`;
            }
        });
    });
});
</script>
</body>
</html>