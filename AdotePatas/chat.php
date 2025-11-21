<?php
session_start();
include_once 'conexao.php';
include_once 'session.php'; 

// 1. Configuração e Segurança
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $base_path = '/TCC-AdotePatas/AdotePatas/';
} else {
    $base_path = '/';
}
$pagina = "chats";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    header("Location: " . $base_path . "login");
    exit;
}

$user_id_logado = $_SESSION['user_id'];
$user_tipo_logado = $_SESSION['user_tipo'];
$primeiro_nome = '';
if (isset($_SESSION['nome'])) {
    $partes_nome = explode(' ', $_SESSION['nome']);
    $primeiro_nome = $partes_nome[0];
}

$conversa_id_ativa = $_GET['id'] ?? null;
if ($conversa_id_ativa) {
    $conversa_id_ativa = filter_var($conversa_id_ativa, FILTER_SANITIZE_NUMBER_INT);
}

// 4. Busca a LISTA DE CONVERSAS (Sidebar)
$lista_conversas = [];
try {
    $sql_lista = "
        SELECT 
            c.id_conversa, 
            s.id_pet,
            p.nome AS pet_nome,
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
            END AS foto_outra_pessoa,
            (SELECT m.conteudo FROM mensagem m WHERE m.id_conversa_fk = c.id_conversa ORDER BY m.data_envio DESC LIMIT 1) AS preview_ultima_msg,
            (SELECT m.data_envio FROM mensagem m WHERE m.id_conversa_fk = c.id_conversa ORDER BY m.data_envio DESC LIMIT 1) AS data_ultima_msg
        FROM conversa c
        LEFT JOIN solicitacao s ON c.id_solicitacao_fk = s.id_solicitacao
        LEFT JOIN pet p ON s.id_pet = p.id_pet
        LEFT JOIN usuario u_adot ON c.id_adotante_fk = u_adot.id_usuario
        LEFT JOIN usuario u_prot ON c.id_protetor_fk = u_prot.id_usuario AND c.tipo_protetor = 'usuario'
        LEFT JOIN ong o_prot ON c.id_protetor_fk = o_prot.id_ong AND c.tipo_protetor = 'ong'
        WHERE
            (c.id_adotante_fk = :user_id AND :user_tipo = 'usuario')
        OR 
            (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo)
        ORDER BY data_ultima_msg DESC
    ";
    $stmt_lista = $conn->prepare($sql_lista);
    $stmt_lista->execute([':user_id' => $user_id_logado, ':user_tipo' => $user_tipo_logado]);
    $lista_conversas = $stmt_lista->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar lista: " . $e->getMessage());
}

// 5. Busca a CONVERSA ATIVA e MENSAGENS
$conversa_ativa = null;
$lista_mensagens = [];
$eu_sou_protetor = false; 
$ultimo_id_msg = 0; // *** NOVO: Variável para rastrear o último ID ***

if ($conversa_id_ativa) {
    try {
        $sql_ativa = "
            SELECT 
                c.id_conversa, 
                c.id_solicitacao_fk,
                p.nome AS pet_nome,
                (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo) AS sou_protetor,
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
                (c.id_adotante_fk = :user_id OR (c.id_protetor_fk = :user_id AND c.tipo_protetor = :user_tipo))
            LIMIT 1
        ";
        $stmt_ativa = $conn->prepare($sql_ativa);
        $stmt_ativa->execute([':conversa_id' => $conversa_id_ativa, ':user_id' => $user_id_logado, ':user_tipo' => $user_tipo_logado]);
        $conversa_ativa = $stmt_ativa->fetch(PDO::FETCH_ASSOC);

        if ($conversa_ativa) {
            $eu_sou_protetor = (bool)$conversa_ativa['sou_protetor'];
            $sql_msgs = "SELECT * FROM mensagem WHERE id_conversa_fk = :conversa_id ORDER BY data_envio ASC";
            $stmt_msgs = $conn->prepare($sql_msgs);
            $stmt_msgs->execute([':conversa_id' => $conversa_id_ativa]);
            $lista_mensagens = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
            
            // *** NOVO: Captura o ID da última mensagem carregada pelo PHP ***
            if (!empty($lista_mensagens)) {
                $ultimo_msg = end($lista_mensagens);
                $ultimo_id_msg = $ultimo_msg['id_mensagem'];
            }
        } else {
            $conversa_id_ativa = null;
        }
    } catch (PDOException $e) {
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
            <li class="nav-item"><a class="nav-link navlink" href="<?php echo $base_path; ?>sobre-nos">Sobre Nós</a></li>
            <li class="nav-item"><a class="nav-link navlink" href="<?php echo $base_path; ?>ajuda.php">Ajuda</a></li>
          </ul>
        </div>
        <a href="<?php echo $base_path; ?>perfil?page=perfil" class="profile-info-link d-flex align-items-center gap-3 text-decoration-none">
          <div class="d-flex align-items-center flex-row-reverse gap-2">
            <i class="fa-regular fa-circle-user profile-icon logged-in"></i>
            <span class="profile-name fs-5" style="color: var(--cor-vermelho);"><?php echo htmlspecialchars($primeiro_nome); ?></span>
          </div>
        </a>
        <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
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
                $is_active = ($conversa_id_ativa == $conversa['id_conversa']);
                $avatar_path = $base_path . 'images/perfil/teste.jpg';
                if (!empty($conversa['foto_outra_pessoa'])) {
                    $avatar_path = $base_path . htmlspecialchars($conversa['foto_outra_pessoa']);
                }
                $data_formatada = 'Sem data';
                if (!empty($conversa['data_ultima_msg'])) {
                    $data_formatada = date('d/m/Y', strtotime($conversa['data_ultima_msg']));
                }
              ?>
              <a href="<?php echo $base_path; ?>chat/<?php echo $conversa['id_conversa']; ?>" 
                 class="chat-list-item <?php echo $is_active ? 'active' : ''; ?>">
                <img src="<?php echo $avatar_path; ?>" class="chat-avatar" onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
                <div class="chat-item-details">
                  <div class="chat-item-header">
                    <span class="chat-name"><?php echo htmlspecialchars($conversa['nome_outra_pessoa']); ?></span>
                    <span class="chat-date"><?php echo $data_formatada; ?></span>
                  </div>
                  <p class="chat-preview-title">Interesse em: <strong><?php echo htmlspecialchars($conversa['pet_nome']); ?></strong></p>
                  <p class="chat-preview"><?php echo htmlspecialchars($conversa['preview_ultima_msg'] ?? 'Inicie a conversa...'); ?></p>
                </div>
              </a>
            <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <section class="col-lg-8 col-md-7 d-none d-md-flex chat-conversation-area">
      <?php if ($conversa_ativa): ?>
        <?php
            $avatar_ativo_path = $base_path . 'images/perfil/teste.jpg';
            if (!empty($conversa_ativa['foto_outra_pessoa'])) {
                $avatar_ativo_path = $base_path . htmlspecialchars($conversa_ativa['foto_outra_pessoa']);
            }
        ?>
        <div class="chat-active-header">
            <img src="<?php echo $avatar_ativo_path; ?>" class="chat-avatar" onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
            <div class="chat-active-info">
                <span class="chat-active-name"><?php echo htmlspecialchars($conversa_ativa['nome_outra_pessoa']); ?></span>
                <span class="chat-active-pet">Interessado(a) em: <strong><?php echo htmlspecialchars($conversa_ativa['pet_nome']); ?></strong></span>
            </div>
            <?php if ($eu_sou_protetor): ?>
                <a href="<?php echo $base_path; ?>gerar-pdf.php?solicitacao_id=<?php echo $conversa_ativa['id_solicitacao_fk']; ?>" 
                   target="_blank" class="btn btn-outline-danger btn-sm ms-auto pdf-button" style="color: var(--cor-vermelho); border: solid 1px var(--cor-vermelho)" title="Ver formulário">
                   <i class="fa-solid fa-file-pdf me-1"></i> Ver Formulário
                </a>
            <?php endif; ?>
        </div>

        <div class="chat-messages" id="chat-messages-container">
            <?php if (empty($lista_mensagens)): ?>
                <p class="text-center text-muted mt-4" id="no-messages-text">Nenhuma mensagem ainda.</p>
            <?php else: ?>
                <?php foreach ($lista_mensagens as $msg): ?>
                    <?php
                        $classe_msg = 'received'; 
                        if ($msg['id_remetente_fk'] == $user_id_logado && $msg['tipo_remetente'] == $user_tipo_logado) {
                            $classe_msg = 'sent';
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
          <button class="files chat-send-files" type="button" data-bs-toggle="modal" data-bs-target="#fileModal">
            <i class="fa-solid fa-plus"></i>
          </button>
            <input type="text" class="form-control chat-message-input" id="chat-message-input" placeholder="Digite sua mensagem...">
            <button class="btn chat-send-btn" type="button" id="chat-send-btn"><i class="fa-solid fa-paper-plane me-1"></i></button>
        </div>

        <div class="modal fade" id="fileModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enviar Arquivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
        <div class="chat-placeholder">
            <img src="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png" class="chat-placeholder-logo">
            <h2 class="adote-patas">Adote Patas</h2>
            <p>Selecione uma conversa ao lado para começar.</p>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<div id="toast-notification" class="adp-toast p-0" style="display: none;">
    <div id="toast-icon" class="adp-toast-icon" style="font-size: 1.6rem"></div>
    <div class="adp-toast-content"><p id="toast-message" class="adp-toast-message text-center"></p></div>
    <div class="adp-toast-progress-bar"></div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar">
  <div class="offcanvas-header border-bottom">
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
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

<?php if ($conversa_ativa): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatMessages = document.getElementById('chat-messages-container');
        const messageInput = document.getElementById('chat-message-input');
        const sendBtn = document.getElementById('chat-send-btn');
        
        // Variáveis do PHP
        const conversaId = <?php echo json_encode($conversa_id_ativa); ?>;
        const basePath = <?php echo json_encode($base_path); ?>;
        const userId = <?php echo json_encode($user_id_logado); ?>;
        const userTipo = <?php echo json_encode($user_tipo_logado); ?>;
        const postURL = basePath + 'mensagem.php';
        
        // Configuração do WebSocket
        const wsScheme = window.location.protocol === 'https:' ? 'wss' : 'ws';
        const wsHost = '<?php echo $_SERVER['SERVER_NAME']; ?>';
        const wsPort = '8080';
        const wsUrl = `${wsScheme}://${wsHost}:${wsPort}`;
        
        let ws = null;

        function connectWebSocket() {
            try {
                ws = new WebSocket(wsUrl);
                
                ws.onopen = function() {
                    console.log('Conectado ao WebSocket');
                    // Inscrever na conversa atual
                    ws.send(JSON.stringify({
                        type: 'subscribe',
                        conversa_id: conversaId
                    }));
                };

                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    
                    if (data.type === 'new_message') {
                        // Adiciona mensagem apenas se não for do usuário atual
                        if (data.user_id != userId || data.user_tipo != userTipo) {
                            addMessageToUI(data.mensagem, 'received', data.timestamp);
                        }
                    }
                };

                ws.onclose = function() {
                    console.log('Conexão WebSocket fechada. Tentando reconectar em 5 segundos.');
                    setTimeout(connectWebSocket, 5000);
                };

                ws.onerror = function(error) {
                    console.error('Erro no WebSocket:', error);
                };
            } catch (error) {
                console.error('Erro ao conectar WebSocket:', error);
                // Fallback para polling se WebSocket falhar
                startPolling();
            }
        }

        function startPolling() {
            console.log('Usando polling como fallback');
            setInterval(pollMessages, 3000);
        }

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function addMessageToUI(text, side, timestamp = 'enviando...') {
            const noMsgText = document.getElementById('no-messages-text');
            if(noMsgText) noMsgText.remove();

            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', side);
            
            const p = document.createElement('p');
            p.textContent = text;
            
            const timestampDiv = document.createElement('div');
            timestampDiv.classList.add('date', 'message-timestamp');
            timestampDiv.textContent = timestamp;

            messageDiv.appendChild(p);
            messageDiv.appendChild(timestampDiv);
            chatMessages.appendChild(messageDiv);

            scrollToBottom();
            return timestampDiv;
        }

        async function sendMessage() {
            const conteudo = messageInput.value.trim();
            if (conteudo === '' || !conversaId) return;

            messageInput.value = '';
            messageInput.focus();

            // Feedback visual imediato
            const timestampElement = addMessageToUI(conteudo, 'sent');

            try {
                // Envia via AJAX para salvar no banco
                const response = await fetch(postURL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({ conversa_id: conversaId, conteudo: conteudo })
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    timestampElement.textContent = result.timestamp || 'enviado';
                    
                    // Envia via WebSocket para outros usuários
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            type: 'message',
                            conversa_id: conversaId,
                            mensagem: conteudo,
                            user_id: userId,
                            user_tipo: userTipo
                        }));
                    }
                } else {
                    timestampElement.textContent = 'Falha ao enviar';
                    timestampElement.style.color = 'red';
                }
            } catch (error) {
                timestampElement.textContent = 'Erro de rede';
                timestampElement.style.color = 'red';
            }
        }

        // Event listeners
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });

        // Polling como fallback (mantém sua lógica original)
        let lastMessageId = <?php echo json_encode($ultimo_id_msg); ?>;

        async function pollMessages() {
            try {
                const response = await fetch(`${basePath}buscar-mensagens.php?conversa_id=${conversaId}&ultimo_id=${lastMessageId}`);
                const result = await response.json();

                if (response.ok && result.success && result.messages.length > 0) {
                    result.messages.forEach(msg => {
                        if (!msg.sou_eu) {
                            addMessageToUI(msg.conteudo, 'received', msg.data_formatada);
                        }
                        if (msg.id_mensagem > lastMessageId) {
                            lastMessageId = msg.id_mensagem;
                        }
                    });
                }
            } catch (error) {
                console.error("Erro no polling:", error);
            }
        }

        // Inicia WebSocket (e polling como fallback)
        connectWebSocket();
        
        // Scroll inicial
        scrollToBottom();
    });
</script>
<?php endif; ?>
</body>
</html>