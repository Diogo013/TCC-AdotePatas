<?php
session_start();

// 1. Segurança: Verifica se o usuário está logado (como no seu perfil.php)
 if (!isset($_SESSION['user_id'])) {
     header("Location: login");
     exit;
}

// 2. Lógica da Página (similar ao perfil.php)
$conversa_id = $_GET['id'] ?? null; // Pega o ID da conversa ativa pela URL

// 3. Dados Fictícios (Substituir por sua busca no BD)
// Em um app real, você faria um SELECT para buscar as conversas do usuário
$lista_conversas = [
    [
        "id" => 1,
        "nome" => "Abrigo Cão",
        "preview" => "Olá! Vimos que você se interessou...",
        "data" => "02/04",
        "avatar" => "images/global/Logo-AdotePatas.png" // Usando o logo como exemplo
    ],
    [
        "id" => 2,
        "nome" => "Maria Silva",
        "preview" => "Texto texto texto...",
        "data" => "04/08",
        "avatar" => "https://via.placeholder.com/50/BF6964/FFFFFF?text=M" // Placeholder
    ],
    [
        "id" => 3,
        "nome" => "Nome",
        "preview" => "Olá! Vimos que você se interess...",
        "data" => "04/08",
        "avatar" => "https://via.placeholder.com/50/DEA796/FFFFFF?text=N" // Placeholder
    ]
];

// Variável para guardar os dados da conversa ativa (se houver)
$conversa_ativa = null;
if ($conversa_id) {
    foreach ($lista_conversas as $conversa) {
        if ($conversa['id'] == $conversa_id) {
            $conversa_ativa = $conversa;
            break;
        }
    }
}

// Define o fuso horário padrão para Brasília
date_default_timezone_set('America/Sao_Paulo');

// Pega a hora, minuto e segundo atuais
$hora_atual = date('H'); // Formato 24h (ex: 14)
$minuto_atual = date('i'); // Minutos com zero à esquerda (ex: 05)

// Para pegar tudo de uma vez formatado (ex: 14:05:09)
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

<header class="app-header navbar navbar-expand-lg">
  <div class="container">
    
    <a class="navbar-brand" href="./">
      <img src="./images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
    </a>
    
    <button class="navbar-toggler hamburger" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <svg fill="none" viewBox="0 0 50 50" height="50" width="50">
        <path class="lineTop line" stroke-linecap="round" stroke-width="4" stroke="black" d="M6 11L44 11"></path>
        <path class="lineMid line" stroke-linecap="round" stroke-width="4" stroke="black" d="M6 24H43"></path>
        <path class="lineBottom line" stroke-linecap="round" stroke-width="4" stroke="black" d="M6 37H43"></path>
      </svg>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="#">Sobre Nós</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Ajuda</a>
        </li>
        <li class="nav-item nav-icon">
          <a class="nav-link" href="perfil.php" aria-label="Meu Perfil">
            <i class="fa-regular fa-circle-user"></i>
          </a>
        </li>
        <li class="nav-item nav-icon d-lg-none"> <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="fa-solid fa-bars"></i>
           </a>
        </li>
      </ul>
    </div>
  </div>
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
            // Verifica se este item é o ativo
            $is_active = ($conversa_id == $conversa['id']);
          ?>
          <a href="chat.php?id=<?php echo $conversa['id']; ?>" 
             class="chat-list-item <?php echo $is_active ? 'active' : ''; ?>"
             <?php echo $is_active ? 'aria-current="true"' : ''; ?>>
            
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

              </div>

          <div class="chat-input-area">
              <input type="text" class="form-control chat-message-input" placeholder="Digite sua mensagem...">
              <button class="btn chat-send-btn" type="button" aria-label="Enviar mensagem">
                  <i class="fa-solid fa-paper-plane me-1"></i>
              </button>
          </div>

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


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>