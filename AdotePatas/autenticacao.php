<?php
// Inclui a conexão com o banco de dados
include_once 'conexao.php';
session_start();


// --- FUNÇÕES DE VALIDAÇÃO (PHP) ---

// Valida a estrutura do CPF
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// Valida a força da senha
function validarForcaSenha($senha) {
    $erros = [];
    if (strlen($senha) < 8) $erros[] = "A senha deve ter no mínimo 8 caracteres.";
    if (!preg_match('/[A-Z]/', $senha)) $erros[] = "A senha deve conter ao menos uma letra maiúscula.";
    if (!preg_match('/[0-9]/', $senha)) $erros[] = "A senha deve conter ao menos um número.";
    if (!preg_match('/[\W_]/', $senha)) $erros[] = "A senha deve conter ao menos um caractere especial.";
    return $erros;
}

// --- INÍCIO DA PARTE ALTERADA ---

// --- LÊ MENSAGENS E ABAS DA SESSÃO (PADRÃO PRG) ---
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
$active_tab_from_session = $_SESSION['active_tab'] ?? null;

// Limpa as mensagens da sessão para que não apareçam novamente se o usuário atualizar a página
unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);
unset($_SESSION['active_tab']);

// --- CONFIGURAÇÃO INICIAL ---
// --- Bloco Corrigido (Melhor Prática) ---
// Define a aba ativa, priorizando a URL e usando a sessão como fallback
if (isset($_GET['tab']) && in_array($_GET['tab'], ['login', 'cadastro_usuario', 'cadastro_ong'])) {
    $active_tab = $_GET['tab']; // Prioridade 1: URL
} elseif ($active_tab_from_session) {
    $active_tab = $active_tab_from_session; // Prioridade 2: Sessão (após um POST)
} else {
    $active_tab = 'login'; // Valor padrão
}

// --- PROCESSAMENTO DOS FORMULÁRIOS (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $form_type = $_POST['form_type'] ?? '';

    switch ($form_type) {
        // --- CASO 1: LOGIN ---
        case 'login':
            $active_tab = 'login';
            $email = $_POST['email'] ?? '';
            $senha = $_POST['senha'] ?? '';

            if (empty($email) || empty($senha)) {
                $mensagem_status = "Por favor, preencha o e-mail e a senha.";
                $tipo_mensagem = 'danger';
            } else {
                $logado = false;

                // Tenta logar como Adotante (Tabela: usuario)
                try {
                    $sql = "SELECT id_usuario, senha, nome FROM usuario WHERE email = :email LIMIT 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario && password_verify($senha, $usuario['senha'])) {
                        session_start();
                        $_SESSION['nome'] = $usuario['nome'];
                        $_SESSION['user_id'] = $usuario['id_usuario'];
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_tipo'] = 'adotante';
                        $logado = true;
                        header("Location: home.php");
                        exit;
                    }
                } catch (PDOException $e) {
                    error_log("Erro ao logar como adotante: " . $e->getMessage());
                }

                // Tenta logar como Protetor/ONG (Tabela: ong)
                if (!$logado) {
                    try {
                        $sql = "SELECT id_ong, senha, nome FROM ong WHERE email = :email LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':email', $email);
                        $stmt->execute();
                        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($usuario && password_verify($senha, $usuario['senha'])) {
                            session_start();
                            $_SESSION['nome'] = $usuario['nome'];
                            $_SESSION['user_id'] = $usuario['id_ong'];
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_tipo'] = 'protetor';
                            $logado = true;
                            header("Location: home.php");
                            exit;
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao logar como protetor: " . $e->getMessage());
                    }
                }
                
                if (!$logado) {
                    $mensagem_status = "E-mail ou senha incorretos.";
                    $tipo_mensagem = 'warning';
                }
            }
            break;

    // --- CASO 2: CADASTRO DE USUÁRIO COM VALIDAÇÃO ROBUSTA (VERSÃO CORRIGIDA) ---
case 'cadastro_usuario':
    $active_tab = 'cadastro_usuario';
    $nome = trim($_POST['nome-completo'] ?? '');
    $cpf = trim($_POST['cpf-cadastro'] ?? '');
    $email = trim($_POST['email-cadastro'] ?? '');
    $senha = $_POST['senha-cadastro'] ?? '';
    $confirma_senha = $_POST['confirma-senha-cadastro'] ?? '';
    
    $erros = [];

    // VALIDAÇÃO EM CASCATA: Se um erro fundamental é encontrado, não continua para o próximo.
    
    // 1. Validação de campos vazios
    if (empty($nome) || empty($cpf) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $erros[] = "Todos os campos são obrigatórios.";
    } else {
        // Se os campos não estão vazios, prossiga com validações de formato e regras
        
        // 2. Validação do Nome Completo
        if (strpos($nome, ' ') === false) {
            $erros[] = "Por favor, digite seu nome completo.";
        }

        // 3. Validação do E-mail (formato)
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail é inválido.";
        }

        // 4. Validação do CPF (formato)
        if (!validarCPF($cpf)) {
            $erros[] = "O CPF informado é inválido.";
        }
        
        // 5. Validação da Senha (força e confirmação)
        $erros_senha = validarForcaSenha($senha);
        if (!empty($erros_senha)) {
            $erros = array_merge($erros, $erros_senha);
        } elseif ($senha !== $confirma_senha) {
            $erros[] = "As senhas não coincidem.";
        }
    }

    // --- Verificação de Duplicidade no Banco (SÓ SE NÃO HOUVER ERROS DE FORMATO) ---
    if (empty($erros)) {
        // Checa E-mail
        $sql = "SELECT id_usuario FROM usuario WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $erros[] = "Este e-mail já está cadastrado.";
        }

        // Checa CPF
        $sql = "SELECT id_usuario FROM usuario WHERE cpf = :cpf LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':cpf' => preg_replace('/[^0-9]/', '', $cpf)]);
        if ($stmt->fetch()) {
            $erros[] = "Este CPF já está cadastrado.";
        }
    }
    
    // Em: autenticacao.php -> case 'cadastro_usuario'

// --- Decisão Final ---
if (!empty($erros)) {
    // Se houver erros, o comportamento atual está correto:
    // mostra a mensagem e mantém os dados na tela.
    $mensagem_status = $erros[0];
    $tipo_mensagem = 'danger';
    $active_tab = 'cadastro_usuario';
} else {
    // Se NÃO houver erros, insere no banco e usa o padrão PRG.
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
    try {
        $sql = "INSERT INTO usuario (nome, email, senha, cpf) VALUES (:nome, :email, :senha, :cpf)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nome' => $nome, 
            ':email' => $email, 
            ':senha' => $senha_hashed, 
            ':cpf' => preg_replace('/[^0-9]/', '', $cpf)
        ]);

        // --- INÍCIO DA LÓGICA PRG ---
        // 1. Salva a mensagem de sucesso e a aba de destino na sessão.
        $_SESSION['mensagem_status'] = "Cadastro realizado com sucesso! Você já pode fazer login.";
        $_SESSION['tipo_mensagem'] = 'success';
        $_SESSION['active_tab'] = 'login';

        // 2. Redireciona para a mesma página para limpar os dados do POST.
        header("Location: autenticacao.php");
        exit();
        // --- FIM DA LÓGICA PRG ---

    } catch (PDOException $e) {
        $mensagem_status = "Ocorreu uma falha no banco de dados. Tente novamente.";
        $tipo_mensagem = 'danger';
        $active_tab = 'cadastro_usuario';
        error_log("Erro no cadastro: " . $e->getMessage());
    }
}
break; // Fim do 'case cadastro_usuario'

        // --- CASO 3: CADASTRO DE ONG ---
        case 'cadastro_ong':
            $active_tab = 'cadastro_ong';
            
            

            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso - Adote Patas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<!-- Modal de Recuperação de Senha -->
    <div id="recovery-modal" class="fixed inset-0 z-50 bg-black bg-opacity-50 hidden items-center justify-center p-4" style="z-index: 1000;">
        <div class="bg-white shadow-2xl p-6 w-full max-w-md mx-auto" style="margin-top: 10%; border-radius: 16px">
            <!-- Título e Botão de Fechar -->
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h1 class="text-2xl font-bold text-gray-600">Recuperar Senha</h1>
                <button id="close-recovery-modal" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- 1. Estado do Formulário de Recuperação -->
            <div id="recovery-form-state">
                <p class="text-md text-gray-600 mb-4">
                    Insira o <strong style="color: var(--cor-vermelho);">e-mail</strong> associado à sua conta para receber um link de redefinição de senha.
                </p>
                <form id="recuperar-form" action="recuperar-senha.php" method="POST">
                    <div class="mb-4">
                        <!-- ID do input é 'email_recuperar' conforme esperado no recuperar-senha.php -->
                        <input type="email" id="email_recuperar" name="email_recuperar" placeholder="E-mail" required
                            class="input-style w-full">
                        <p id="recovery-error-message" class="text-sm text-red-500 mt-1 hidden text-left"></p>
                    </div>
                    <div class="flex justify-center">
                        <button type="submit" class="adopt-btn w-48 justify-center">
                            <div class="heart-background">❤</div><span>Enviar Link</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. Estado de Sucesso (Enviado com sucesso) -->
            <div id="success-state" class="hidden text-center">
            <i class="fas fa-envelope-open-text text-5xl text-pink-500 mb-4" ></i>
            <h3 class="text-2xl font-bold text-gray-800">Verifique seu e-mail</h3>
            <p class="text-gray-600 mt-2">
                Enviamos um link de redefinição para <br>
                <strong id="sent-email-address" class="text-gray-900"></strong>
            </p>
            <div class="mt-8">
                <button id="resend-btn" class="text-gray-600 font-semibold" disabled>
                    Reenviar em (<span id="resend-timer">30</span>s)
                </button>
            </div>
        </div>

             
        </div>
    </div>
    <!-- Fim Modal -->


<div id="toast-notification" class="toast p-0" style="display: none;">
    <div id="toast-icon" class="toast-icon"></div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>

<a href="./" class="btn-voltar" title="Voltar para a página inicial">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Voltar</span>
</a>

<img src="images/cadastro-login/pata.png" alt="Desenho de Pata" class="pata-fundo">

<div class="w-full max-w-lg mx-auto">
    <div class="w-full flex items-center justify-between mb-6 relative">
        <div>
            <a href="./" title="Voltar para a página inicial">
                <img src="images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" width="70" height="70">
            </a>
        </div>
        <div class="absolute inset-x-0 text-center">
            <h1 id="page-title" class="text-4xl sm:text-4xl font-bold text-[#666662]">Entrar</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">
        
        <div class="flex border-b-2 border-white/20 mb-6">
            <button data-tab="login" class="tab-btn flex-1 py-3 text-lg font-bold text-white/70 transition-all duration-300">Entrar</button>
            <button data-tab="cadastro_usuario" class="tab-btn flex-1 py-3 text-lg font-bold text-white/70 transition-all duration-300">Cadastro</button>
            <button data-tab="cadastro_ong" class="tab-btn flex-1 py-3 text-lg font-bold text-white/70 transition-all duration-300">Cadastro ONG</button>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <div id="php-data" 
                 data-message="<?php echo htmlspecialchars($mensagem_status); ?>" 
                 data-type="<?php echo htmlspecialchars($tipo_mensagem); ?>" 
                 style="display: none;">
            </div>
        <?php endif; ?>

        <div class="form-content">
            <div id="login" class="form-container">
                <form action="autenticacao.php" method="post" class="space-y-6">
                    <input type="hidden" name="form_type" value="login">
                    <input type="email" name="email" placeholder="E-mail" required class="input-style w-full email-input" 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                           <div class="relative">
                        <input type="password" id="senha_login" name="senha" placeholder="Senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="senha_login"></i>
                    </div>
                    <div class="flex justify-end pt-2">
                        <a href="#" id="open-recovery-modal" class="link-style">Esqueci a senha</a>
                    </div>
                    <div class="flex justify-center w-40 mx-auto">
                        <button type="submit" class="adopt-btn">
                            <div class="heart-background">❤</div><span>Entrar</span>
                        </button>
                    </div>
                </form>
            </div>

<div id="cadastro_usuario" class="form-container">
    <form action="autenticacao.php" method="post" id="form-cadastro" class="space-y-6">
        <input type="hidden" name="form_type" value="cadastro_usuario">
        <div>
            <input type="text" name="nome-completo" id="nome-completo" placeholder="Nome Completo" required class="input-style w-full" 
                   value="<?php echo htmlspecialchars($nome ?? ''); ?>">
            <div id="mensagem-nome-completo" class="mensagem-validacao"></div>
        </div>
        <div>
            <input type="text" name="cpf-cadastro" id="cpf-cadastro" placeholder="CPF" required class="input-style w-full" 
                   value="<?php echo htmlspecialchars($cpf ?? ''); ?>">
            <div id="mensagem-cpf-cadastro" class="mensagem-validacao"></div>
        </div>
        <div>
            <input type="email" name="email-cadastro" id="email-cadastro" placeholder="E-mail" required class="input-style w-full"
                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
            <div id="mensagem-email-cadastro" class="mensagem-validacao"></div>
        </div>
        <div>
            <div class="relative">
                <input type="password" id="senha-cadastro" name="senha-cadastro" placeholder="Senha" required class="input-style w-full pr-12 senha-input">
                <i class="fas fa-eye toggle-senha" data-target="senha-cadastro"></i>
            </div>
            <div id="mensagem-senha-cadastro" class="mensagem-validacao"></div>
        </div>
        <div>
            <div class="relative">
                <input type="password" id="confirma-senha-cadastro" name="confirma-senha-cadastro" placeholder="Confirmar a Senha" required class="input-style w-full pr-12 senha-input">
                <i class="fas fa-eye toggle-senha" data-target="confirma-senha-cadastro"></i>
            </div>
            <div id="mensagem-confirma-senha-cadastro" class="mensagem-validacao"></div>
        </div>
        <div class="flex justify-center w-40 mx-auto">
            <button type="submit" id="cadastro-cliente" class="adopt-btn">
                <div class="heart-background">❤</div><span>Cadastrar</span>
            </button>
        </div>
    </form>
</div>

            
        <div id="cadastro_ong" class="form-container">
            <form action="autenticacao.php" method="post" class="space-y-6">
                <input type="hidden" name="form_type" value="cadastro_ong">
                <input type="text" name="nome_ong" placeholder="Nome Oficial da ONG" required class="input-style w-full">
                <input type="text" name="cnpj" id="cnpj" placeholder="CNPJ" required class="input-style w-full">
                <input type="email" name="email_ong" placeholder="E-mail" required class="input-style w-full">
                <div class="relative">
                    <input type="password" id="senha_ong" name="senha_ong" placeholder="Senha" required class="input-style w-full pr-12 senha-input">
                    <i class="fas fa-eye toggle-senha" data-target="senha_ong"></i>
                </div>
                <div class="relative">
                    <input type="password" id="confirma_senha_ong" name="confirma_senha_ong" placeholder="Confirmar a Senha" required class="input-style w-full pr-12 senha-input">
                    <i class="fas fa-eye toggle-senha" data-target="confirma_senha_ong"></i>
                </div>
                <div class="flex justify-center w-60 mx-auto">
                    <button type="submit" class="adopt-btn">
                        <div class="heart-background">❤</div><span>Cadastrar ONG</span>
                    </button>
                </div>
            </form>
        </div>
        </div>
</div>

<script>
    <?php
    // Essa lógica garante que, ao redirecionar após um erro de cadastro de usuário, a aba correta é reaberta
    if (!empty($mensagem_status) && ($_POST['form_type'] ?? '') === 'cadastro_usuario') {
        echo "window.activeTabOnLoad = 'cadastro_usuario';";
    } else {
        // Se a aba 'recuperar' veio da URL, o JS deve tratar o modal, mas a aba principal deve ser 'login'
        $tab_to_load = ($active_tab === 'recuperar') ? 'login' : $active_tab;
        echo "window.activeTabOnLoad = '" . $tab_to_load . "';";
    }
    ?>
</script>



<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
<script src="assets/js/pages/autenticacao/autenticacao.js"></script>
</body>
</html>