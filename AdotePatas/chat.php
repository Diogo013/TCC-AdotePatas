<?php
session_start();
include_once 'conexao.php';
include_once 'session.php'; // Inclui nosso session.php

// 1. Configuração e Segurança
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $base_path = '/TCC-AdotePatas/AdotePatas/';
} else {
    $base_path = '/';
}
$pagina = "chats";

// Se não está logado, manda pro login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    header("Location: " . $base_path . "login");
    exit;
}

// 2. Pega dados do usuário logado (da sessão)
$user_id_logado = $_SESSION['user_id'];
$user_tipo_logado = $_SESSION['user_tipo'];
$primeiro_nome = '';
if (isset($_SESSION['nome'])) {
    $partes_nome = explode(' ', $_SESSION['nome']);
    $primeiro_nome = $partes_nome[0];
}

// 3. Pega o ID da conversa ativa pela URL (graças ao .htaccess)
$conversa_id_ativa = $_GET['id'] ?? null;
if ($conversa_id_ativa) {
    $conversa_id_ativa = filter_var($conversa_id_ativa, FILTER_SANITIZE_NUMBER_INT);
}

// 4. Busca a LISTA DE CONVERSAS (Sidebar)
$lista_conversas = [];
try {
    // Essa query é complexa! Ela busca todas as conversas onde o usuário logado
    // é o adotante OU o protetor, e já pega o nome e foto da OUTRA pessoa.
    $sql_lista = "
        SELECT 
            c.id_conversa, 
            s.id_pet,
            p.nome AS pet_nome,
            
            -- Pega o nome e foto da OUTRA pessoa da conversa
            CASE 
                WHEN c.id_adotante_fk = :user_id THEN -- Eu sou o adotante
                    CASE 
                        WHEN c.tipo_protetor = 'usuario' THEN u_prot.nome
                        WHEN c.tipo_protetor = 'ong' THEN o_prot.nome
                    END
                ELSE -- Eu sou o protetor
                    u_adot.nome
            END AS nome_outra_pessoa,
            
            CASE 
                WHEN c.id_adotante_fk = :user_id THEN
                    CASE 
                        WHEN c.tipo_protetor = 'usuario' THEN u_prot.foto_perfil
                        WHEN c.tipo_protetor = 'ong' THEN o_prot.foto_perfil
                    END
                ELSE
                    u_adot.foto_perfil
            END AS foto_outra_pessoa,
            
            -- Pega a última mensagem (subquery)
            (SELECT m.conteudo FROM mensagem m WHERE m.id_conversa_fk = c.id_conversa ORDER BY m.data_envio DESC LIMIT 1) AS preview_ultima_msg,
            (SELECT m.data_envio FROM mensagem m WHERE m.id_conversa_fk = c.id_conversa ORDER BY m.data_envio DESC LIMIT 1) AS data_ultima_msg

        FROM conversa c
        
        -- *** AQUI ESTÁ A MUDANÇA ***
        -- Trocamos para LEFT JOIN para garantir que a conversa apareça
        -- mesmo se o pet ou a solicitação forem removidos.
        LEFT JOIN solicitacao s ON c.id_solicitacao_fk = s.id_solicitacao
        LEFT JOIN pet p ON s.id_pet = p.id_pet
        -- *** FIM DA MUDANÇA ***
        
        -- Join para pegar o Adotante
        LEFT JOIN usuario u_adot ON c.id_adotante_fk = u_adot.id_usuario
        
        -- Joins para pegar o Protetor (pode ser usuario ou ong)
        LEFT JOIN usuario u_prot ON c.id_protetor_fk = u_prot.id_usuario AND c.tipo_protetor = 'usuario'
        LEFT JOIN ong o_prot ON c.id_protetor_fk = o_prot.id_ong AND c.tipo_protetor = 'ong'
        
        WHERE
            -- Onde eu sou o adotante
            (c.id_adotante_fk = :user_id AND :user_tipo = 'usuario')
        OR 
            -- Onde eu sou o protetor
            (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo)
            
        ORDER BY data_ultima_msg DESC
    ";

    $stmt_lista = $conn->prepare($sql_lista);
    $stmt_lista->execute([':user_id' => $user_id_logado, ':user_tipo' => $user_tipo_logado]);
    $lista_conversas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar lista de conversas: " . $e->getMessage());
}


// 5. Busca a CONVERSA ATIVA e MENSAGENS (Janela Principal)
$conversa_ativa = null;
$lista_mensagens = [];
$eu_sou_protetor = false; // Flag para mostrar o botão de PDF

if ($conversa_id_ativa) {
    try {
        // Query para pegar os detalhes da conversa ATIVA (cabeçalho)
        // (Similar à de cima, mas para um ID específico)
        $sql_ativa = "
            SELECT 
                c.id_conversa, 
                c.id_solicitacao_fk,
                p.nome AS pet_nome,
                
                -- Verifica se o usuário logado é o protetor
                (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo) AS sou_protetor,
                
                -- Pega o nome e foto da OUTRA pessoa
                CASE 
                    WHEN c.id_adotante_fk = :user_id THEN
                        CASE 
                            WHEN c.tipo_protetor = 'usuario' THEN u_prot.nome
                            WHEN c.tipo_protetor = 'ong' THEN o_prot.nome
                        END
                    ELSE
                        u_adot.nome
                END AS nome_outra_pessoa,
                
                CASE 
                    WHEN c.id_adotante_fk = :user_id THEN
                        CASE 
                            WHEN c.tipo_protetor = 'usuario' THEN u_prot.foto_perfil
                            WHEN c.tipo_protetor = 'ong' THEN o_prot.foto_perfil
                        END
                    ELSE
                        u_adot.foto_perfil
                END AS foto_outra_pessoa
                
            FROM conversa c
            LEFT JOIN solicitacao s ON c.id_solicitacao_fk = s.id_solicitacao
            LEFT JOIN pet p ON s.id_pet = p.id_pet
            LEFT JOIN usuario u_adot ON c.id_adotante_fk = u_adot.id_usuario
            LEFT JOIN usuario u_prot ON c.id_protetor_fk = u_prot.id_usuario AND c.tipo_protetor = 'usuario'
            LEFT JOIN ong o_prot ON c.id_protetor_fk = o_prot.id_ong AND c.tipo_protetor = 'ong'
            
            WHERE 
                c.id_conversa = :conversa_id
            AND 
                -- Cláusula de segurança: garante que o usuário logado pertence a esta conversa
                (c.id_adotante_fk = :user_id OR (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo))
            LIMIT 1
        ";
        
        $stmt_ativa = $conn->prepare($sql_ativa);
        $stmt_ativa->execute([
            ':conversa_id' => $conversa_id_ativa,
            ':user_id' => $user_id_logado,
            ':user_tipo' => $user_tipo_logado
        ]);
        $conversa_ativa = $stmt_ativa->fetch(PDO::FETCH_ASSOC);

        // Se a conversa for válida, busca as mensagens
        if ($conversa_ativa) {
            $eu_sou_protetor = (bool)$conversa_ativa['sou_protetor'];

            $sql_msgs = "SELECT * FROM mensagem 
                         WHERE id_conversa_fk = :conversa_id
                         ORDER BY data_envio ASC";
            $stmt_msgs = $conn->prepare($sql_msgs);
            $stmt_msgs->execute([':conversa_id' => $conversa_id_ativa]);
            $lista_mensagens = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Se o ID for inválido ou não pertencer ao usuário, limpa
            $conversa_id_ativa = null;
        }

    } catch (PDOException $e) {
        // silencioso — manter UX
    }

    if ($logado && isset($_SESSION['nome'])) {
        $partes = explode(' ', $_SESSION['nome']);
        $primeiro_nome = $partes[0] ?? '';
    }
}

// 2. Lógica da Página (similar ao perfil.php)
$conversa_id = $_GET['id'] ?? null; // Pega o ID da conversa ativa pela URL

// 3. Dados Fictícios (Substituir por sua busca no BD)
// Em um app real, você faria um SELECT para buscar as conversas do usuário
$lista_conversas = [
    [
        "id" => 1,
        "nome" => "Adote Patas",
        "preview" => "Olá! Vimos que você se interessou...",
        "data" => "02/04",
        "avatar" => "images/global/Logo-AdotePatas.png" // Usando o logo como exemplo
    ],
    [
        "id" => 2,
        "nome" => "Marcella",
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

// Para pegar tudo de uma vez formatado (ex: 14:05)
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
    
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/pages/chat/chat.css">
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png"/>
</head>
<body class="chat-page-body">

<header>
  <nav class="navbar navbar-expand">
    <div class="container">
      <a class="navbar-brand" href="<?php echo $base_path; ?>./">
        <img src="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
      </a>

      <div class="d-flex align-items-center gap-4">
        <div class="d-none d-xl-block">
          <ul class="navbar-nav d-flex flex-row gap-4 mb-0">
            <li class="nav-item">
              <a class="nav-link navlink" href="<?php echo $base_path; ?>sobre-nos">Sobre Nós</a>
            </li>
            <li class="nav-item">
              <a class="nav-link navlink" href="<?php echo $base_path; ?>ajuda.php">Ajuda</a>
            </li>
          </ul>
        </div>
        <a href="<?php echo $base_path; ?>perfil?page=perfil" class="profile-info-link d-flex align-items-center gap-3 text-decoration-none" title="Ver meu perfil">
          <div class="d-flex align-items-center flex-row-reverse gap-2">
            <i class="fa-regular fa-circle-user profile-icon logged-in"></i>
            <span class="profile-name fs-5" style="color: var(--cor-vermelho);"><?php echo htmlspecialchars($primeiro_nome); ?></span>
          </div>
        </a>
        <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
          <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
        </button>
      </div>
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
        <?php
            // Define o avatar do cabeçalho da conversa ativa
            $avatar_ativo_path = $base_path . 'images/perfil/teste.jpg'; // Padrão
            if (!empty($conversa_ativa['foto_outra_pessoa'])) {
                $avatar_ativo_path = $base_path . htmlspecialchars($conversa_ativa['foto_outra_pessoa']);
            }
        ?>
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
          <button class="files chat-send-files" type="button" data-bs-toggle="modal" data-bs-target="#fileModal" aria-label="Anexar arquivo">
            <i class="fa-solid fa-plus"></i>
          </button>
            <input type="text" class="form-control chat-message-input" id="chat-message-input" placeholder="Digite sua mensagem...">
            <button class="btn chat-send-btn" type="button" id="chat-send-btn" aria-label="Enviar mensagem">
                <i class="fa-solid fa-paper-plane me-1"></i>
            </button>
        </div>

        <!-- Adicione este código dentro da section chat-conversation-area, após o chat-input-area -->
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
  
  <!-- Inputs de arquivo ocultos -->
  <input type="file" id="documentInput" accept=".pdf,.doc,.docx,.txt,.rtf" hidden>
  <input type="file" id="mediaInput" accept="image/*,video/*" hidden>


      <?php else: ?>
        <!-- Placeholder (nenhuma conversa selecionada) -->
        <div class="chat-placeholder">
            <img src="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png" alt="" class="chat-placeholder-logo">
            <h2 class="adote-patas">Adote Patas</h2>
            <p>Selecione uma conversa ao lado para começar.</p>
        </div>
      <?php endif; ?>


      
    </section>
  </div>
</main>

<!-- Toast Personalizado -->
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
          <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($_SESSION['nome']); ?></h5>
          <small class="text-muted fs-6"><?php echo htmlspecialchars(ucfirst($user_tipo_logado)); ?></small>
        </div>
        <nav class="nav nav-pills flex-column profile-nav">
          <div class="d-xl-none">
            <a class="nav-link" href="<?php echo $base_path; ?>sobre-nos"><i class="fa-solid fa-info-circle fa-fw me-2"></i> Sobre Nós</a>
            <a class="nav-link" href="#"><i class="fa-solid fa-question-circle fa-fw me-2"></i> Ajuda</a>
            <hr class="my-2">
          </div>
          <a class="nav-link <?php echo ($pagina == 'perfil') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>perfil?page=perfil"><i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil</a>
          <a class="nav-link <?php echo ($pagina == 'meus-pets') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>perfil?page=meus-pets"><i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets</a>
          <a class="nav-link <?php echo ($pagina == 'pets-curtidos') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>perfil?page=pets-curtidos"><i class="fa-regular fa-heart fa-fw me-2"></i> Pets Curtidos</a>
          <a class="nav-link <?php echo ($pagina == 'chats') ? 'active' : ''; ?>" href="<?php echo $base_path; ?>chat"><i class="fa-regular fa-comments fa-fw me-2"></i> Chats</a>
          <hr class="my-2">
          <a class="nav-link logout-link-sidebar" href="<?php echo $base_path; ?>sair.php"><i class="fa-solid fa-right-from-bracket fa-fw me-2"></i> Sair</a>
        </nav>
      </aside>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/pages/chat/file-size-upload.js" ></script>
</body>
</html>