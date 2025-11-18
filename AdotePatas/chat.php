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
        error_log("Erro ao buscar conversa ativa: " . $e->getMessage());
        $conversa_id_ativa = null;
    }
}
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
        
        <?php if (empty($lista_conversas)): ?>
            <p class="text-center p-3 text-muted">Nenhuma conversa encontrada.</p>
        <?php else: ?>
            <?php foreach ($lista_conversas as $conversa): ?>
              <?php
                // Verifica se este item é o ativo
                $is_active = ($conversa_id_ativa == $conversa['id_conversa']);
                
                // Foto de perfil (avatar)
                $avatar_path = $base_path . 'images/perfil/teste.jpg'; // Padrão
                if (!empty($conversa['foto_outra_pessoa'])) {
                    $avatar_path = $base_path . htmlspecialchars($conversa['foto_outra_pessoa']);
                }
                
                // Data formatada
                $data_formatada = 'Sem data';
                if (!empty($conversa['data_ultima_msg'])) {
                    $data_formatada = date('d/m/Y', strtotime($conversa['data_ultima_msg']));
                }
              ?>
              <a href="<?php echo $base_path; ?>chat/<?php echo $conversa['id_conversa']; ?>" 
                 class="chat-list-item <?php echo $is_active ? 'active' : ''; ?>"
                 <?php echo $is_active ? 'aria-current="true"' : ''; ?>>
                
                <img src="<?php echo $avatar_path; ?>" alt="Foto de perfil de <?php echo htmlspecialchars($conversa['nome_outra_pessoa']); ?>" class="chat-avatar" onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
                
                <div class="chat-item-details">
                  <div class="chat-item-header">
                    <span class="chat-name"><?php echo htmlspecialchars($conversa['nome_outra_pessoa']); ?></span>
                    <span class="chat-date"><?php echo $data_formatada; ?></span>
                  </div>
                  <p class="chat-preview-title">Interesse em: <strong><?php echo htmlspecialchars($conversa['pet_nome']); ?></strong></p>
                  <p class="chat-preview">
                    <?php echo htmlspecialchars($conversa['preview_ultima_msg'] ?? 'Inicie a conversa...'); ?>
                  </p>
                </div>
              </a>
            <?php endforeach; ?>
        <?php endif; ?>
        
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
            <img src="<?php echo $avatar_ativo_path; ?>" alt="Foto de perfil de <?php echo htmlspecialchars($conversa_ativa['nome_outra_pessoa']); ?>" class="chat-avatar" onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
            <div class="chat-active-info">
                <span class="chat-active-name"><?php echo htmlspecialchars($conversa_ativa['nome_outra_pessoa']); ?></span>
                <span class="chat-active-pet">Interessado(a) em: <strong><?php echo htmlspecialchars($conversa_ativa['pet_nome']); ?></strong></span>
            </div>
            
            <?php if ($eu_sou_protetor): ?>
                <!-- *** NOSSO BOTÃO PARA O PDF (PRÓXIMO PASSO) *** -->
                <a href="<?php echo $base_path; ?>gerar_pdf.php?solicitacao_id=<?php echo $conversa_ativa['id_solicitacao_fk']; ?>" 
                   target="_blank" 
                   class="btn btn-outline-danger btn-sm ms-auto" 
                   title="Ver formulário de adoção">
                   <i class="fa-solid fa-file-pdf me-1"></i> Ver Formulário
                </a>
            <?php endif; ?>
        </div>

        <div class="chat-messages" id="chat-messages-container">
            
            <?php if (empty($lista_mensagens)): ?>
                <p class="text-center text-muted mt-4">Nenhuma mensagem ainda.</p>
            <?php else: ?>
                <?php foreach ($lista_mensagens as $msg): ?>
                    <?php
                        // Verifica se a mensagem é "enviada" (sent) ou "recebida" (received)
                        $classe_msg = 'received'; // Padrão é recebida
                        if ($msg['id_remetente_fk'] == $user_id_logado && $msg['tipo_remetente'] == $user_tipo_logado) {
                            $classe_msg = 'sent'; // É minha, então é enviada
                        }
                        
                        $data_msg = date('H:i, d/m/Y', strtotime($msg['data_envio']));
                    ?>
                    <div class="message <?php echo $classe_msg; ?>">
                        <p><?php echo nl2br(htmlspecialchars($msg['conteudo'])); ?></p>
                        <div class="date message-timestamp"><?php echo $data_msg; ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

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

        <!-- Modal de Anexos (do seu template) -->
        <div class="modal fade" id="fileModal" tabindex="-1" aria-labelledby="fileModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileModalLabel">Enviar Arquivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary btn-lg" id="documentBtn"><i class="fa-solid fa-file-lines me-2"></i>Documento</button>
                            <button type="button" class="btn btn-outline-success btn-lg" id="mediaBtn"><i class="fa-solid fa-image me-2"></i>Fotos/Vídeos</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

<!-- Toast (do seu template) -->
<div id="toast-notification" class="adp-toast p-0" style="display: none;">
    <div id="toast-icon" class="adp-toast-icon" style="font-size: 1.6rem"></div>
    <div class="adp-toast-content">
        <p id="toast-message" class="adp-toast-message text-center"></p>
    </div>
    <div class="adp-toast-progress-bar"></div>
</div>

<!-- Offcanvas (do seu template, agora com a variável $pagina correta) -->
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
<script src="<?php echo $base_path; ?>assets/js/pages/chat/file-size-upload.js" ></script>
<!-- *** SCRIPT DE CHAT *** -->
<?php if ($conversa_ativa): // Só executa o JS se uma conversa estiver aberta ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. Seleciona os elementos do DOM ---
        const chatMessages = document.getElementById('chat-messages-container');
        const messageInput = document.getElementById('chat-message-input');
        const sendBtn = document.getElementById('chat-send-btn');

        // Se os elementos não existirem, não faz nada
        if (!chatMessages || !messageInput || !sendBtn) {
            return;
        }

        // --- 2. Pega as variáveis do PHP ---
        const conversaId = <?php echo json_encode($conversa_id_ativa); ?>;
        const basePath = <?php echo json_encode($base_path); ?>;
        const postURL = basePath + 'mensagem.php';

        // --- 3. Função para rolar o chat para baixo ---
        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Rola para o final assim que a página carrega
        scrollToBottom();

        // --- 4. Função para adicionar a mensagem na UI ---
        // (Isso faz o chat parecer instantâneo)
        function addMessageToUI(text, side, timestamp = 'enviando...') {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', side);
            
            // Sanitiza o texto (forma simples de evitar XSS)
            const p = document.createElement('p');
            p.textContent = text;
            
            const timestampDiv = document.createElement('div');
            timestampDiv.classList.add('date', 'message-timestamp');
            timestampDiv.textContent = timestamp;

            messageDiv.appendChild(p);
            messageDiv.appendChild(timestampDiv);
            chatMessages.appendChild(messageDiv);

            scrollToBottom();
            
            // Retorna o elemento da data para podermos atualizar depois
            return timestampDiv; 
        }

        // --- 5. Função Principal para Enviar a Mensagem ---
        async function sendMessage() {
            const conteudo = messageInput.value.trim();

            if (conteudo === '' || !conversaId) {
                return; // Não envia mensagem vazia
            }

            // Limpa o input imediatamente
            messageInput.value = '';
            messageInput.focus();

            // Adiciona a mensagem na tela (Otimista)
            const timestampElement = addMessageToUI(conteudo, 'sent');

            try {
                const response = await fetch(postURL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        conversa_id: conversaId,
                        conteudo: conteudo
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Sucesso! Atualiza o "enviando..." para a hora certa
                    timestampElement.textContent = result.timestamp || 'enviado';
                } else {
                    // Falhou! Mostra um erro
                    timestampElement.textContent = 'Falha ao enviar';
                    timestampElement.style.color = 'red';
                    console.error('Erro do servidor:', result.message);
                }

            } catch (error) {
                // Falha de rede!
                timestampElement.textContent = 'Erro de rede';
                timestampElement.style.color = 'red';
                console.error('Erro de fetch:', error);
            }
        }

        // --- 6. Event Listeners ---
        
        // Clique no botão Enviar
        sendBtn.addEventListener('click', sendMessage);

        // Apertar "Enter" no campo de texto
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault(); // Impede de pular linha
                sendMessage();
            }
        });

    });
</script>
<?php endif; ?>
</body>
</html>