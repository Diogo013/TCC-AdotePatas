<?php
// Inclui a conexão com o banco de dados
include_once 'conexao.php';

// --- CONFIGURAÇÃO INICIAL ---
$mensagem_status = '';
$tipo_mensagem = ''; // success, danger, warning
$active_tab = 'login'; // Aba padrão a ser exibida

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

        // --- CASO 2: CADASTRO DE USUÁRIO ---
        case 'cadastro_usuario':
            $active_tab = 'cadastro_usuario';
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email_cadastro'] ?? '';
            $senha = $_POST['senha_cadastro'] ?? '';
            $confirma_senha = $_POST['confirma_senha_cadastro'] ?? '';
            $cpf = $_POST['cpf'] ?? '';

            if (empty($nome) || empty($email) || empty($senha) || empty($cpf)) {
                 $mensagem_status = "Todos os campos são obrigatórios.";
                 $tipo_mensagem = 'danger';
            } elseif ($senha !== $confirma_senha) {
                $mensagem_status = "As senhas não coincidem.";
                $tipo_mensagem = 'warning';
            } else {
                $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
                try {
                    $sql = "INSERT INTO usuario (nome, email, senha, cpf) VALUES (:nome, :email, :senha, :cpf)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':nome' => $nome, ':email' => $email, ':senha' => $senha_hashed, ':cpf' => $cpf]);
                    $mensagem_status = "Cadastro realizado com sucesso! Você já pode fazer login.";
                    $tipo_mensagem = 'success';
                    $active_tab = 'login'; // Muda para a aba de login após sucesso
                } catch (PDOException $e) {
                    $mensagem_status = ($e->getCode() == '23000') ? "Este e-mail ou CPF já está cadastrado." : "Falha no banco de dados: " . $e->getMessage();
                    $tipo_mensagem = 'danger';
                }
            }
            break;

        // --- CASO 3: CADASTRO DE ONG ---
        case 'cadastro_ong':
            $active_tab = 'cadastro_ong';
            $nome_ong = $_POST['nome_ong'] ?? '';
            $cnpj = $_POST['cnpj'] ?? '';
            $email_ong = $_POST['email_ong'] ?? '';
            $senha_ong = $_POST['senha_ong'] ?? '';
            $confirma_senha_ong = $_POST['confirma_senha_ong'] ?? '';

            if (empty($nome_ong) || empty($cnpj) || empty($email_ong) || empty($senha_ong)) {
                $mensagem_status = "Todos os campos são obrigatórios.";
                $tipo_mensagem = 'danger';
            } elseif ($senha_ong !== $confirma_senha_ong) {
                $mensagem_status = "As senhas não coincidem.";
                $tipo_mensagem = 'warning';
            } else {
                $senha_hashed = password_hash($senha_ong, PASSWORD_DEFAULT);
                try {
                    $sql = "INSERT INTO ong (nome, cnpj, email, senha) VALUES (:nome, :cnpj, :email, :senha)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([':nome' => $nome_ong, ':cnpj' => $cnpj, ':email' => $email_ong, ':senha' => $senha_hashed]);
                    $mensagem_status = "Cadastro da ONG realizado com sucesso! Você já pode fazer login.";
                    $tipo_mensagem = 'success';
                    $active_tab = 'login'; // Muda para a aba de login após sucesso
                } catch (PDOException $e) {
                    $mensagem_status = ($e->getCode() == '23000') ? "Este e-mail ou CNPJ já está cadastrado." : "Falha no banco de dados: " . $e->getMessage();
                    $tipo_mensagem = 'danger';
                }
            }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
     <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<div id="toast-notification" class="toast" style="display: none;">
    <div id="toast-icon" class="toast-icon">
        </div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>


<a href="index.html" class="btn-voltar" title="Voltar para a página inicial">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Voltar</span>
</a>

<img src="images/cadastro-login/pata.png" alt="Desenho de Pata" class="pata-fundo">


<div class="w-full max-w-lg mx-auto">
    <div class="w-full flex items-center justify-between mb-6 relative">
        <div>
            <a href="index.html" title="Voltar para a página inicial">
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
                    <input type="email" name="email" placeholder="E-mail" required class="input-style w-full email-input">
                    <div class="relative">
                        <input type="password" id="senha_login" name="senha" placeholder="Senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="senha_login"></i>
                    </div>
                    <div class="flex justify-end pt-2">
                        <a href="#" id="forgot-password-link" class="link-style">Esqueci a senha</a>
                    </div>
                    <div class="flex justify-center w-40 mx-auto">
                        <button type="submit" class="adopt-btn">
                            <div class="heart-background">❤</div><span>Entrar</span>
                        </button>
                    </div>
                </form>
            </div>

            <div id="cadastro_usuario" class="form-container">
                <form action="autenticacao.php" method="post" class="space-y-6">
                    <input type="hidden" name="form_type" value="cadastro_usuario">
                    <input type="text" name="nome" placeholder="Nome Completo" required class="input-style w-full">
                    <input type="text" name="cpf" placeholder="CPF" required class="input-style w-full">
                    <input type="email" name="email_cadastro" placeholder="E-mail" required class="input-style w-full">
                    <div class="relative">
                        <input type="password" id="senha_cadastro" name="senha_cadastro" placeholder="Senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="senha_cadastro"></i>
                    </div>
                    <div class="relative">
                        <input type="password" id="confirma_senha_cadastro" name="confirma_senha_cadastro" placeholder="Confirmar a Senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="confirma_senha_cadastro"></i>
                    </div>
                    <div class="flex justify-center w-40 mx-auto">
                        <button type="submit" class="adopt-btn">
                            <div class="heart-background">❤</div><span>Cadastrar</span>
                        </button>
                    </div>
                </form>
            </div>

            <div id="cadastro_ong" class="form-container">
                <form action="autenticacao.php" method="post" class="space-y-6">
                    <input type="hidden" name="form_type" value="cadastro_ong">
                    <input type="text" name="nome_ong" placeholder="Nome Oficial da ONG" required class="input-style w-full">
                    <input type="text" name="cnpj" placeholder="CNPJ" required class="input-style w-full">
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
</div>

<div id="modal-esqueci-senha" class="modal-overlay">
    <div class="modal-content">
        <button id="close-modal-btn" class="modal-close-btn">&times;</button>
        
        <div id="recovery-form-state">
            <h2 class="text-3xl font-bold text-[#666662] text-center mb-2">Esqueci a senha</h2>
            <div class="w-12 h-1 bg-[#666662] mx-auto mb-6 rounded-full"></div>

            <p class="text-center text-gray-600 mb-6">
                Digite o <strong>E-mail</strong> cadastrado para receber o link de redefinição de senha:
            </p>

            <form id="form-recuperar-senha" class="space-y-4">
                <input type="email" id="email_recuperar" name="email_recuperar" placeholder="E-mail" required class="input-style w-full email-input">
                <p id="modal-error-msg" class="text-red-700 font-semibold text-center hidden"></p>
                <div class="flex justify-center w-full pt-4">
                    <button type="submit" class="adopt-btn">
                        <div class="heart-background">❤</div><span>Enviar</span>
                    </button>
                </div>
            </form>
        </div>

        <div id="recovery-success-state" class="hidden text-center">
            <i class="fas fa-envelope-open-text text-5xl text-pink-500 mb-4"></i>
            <h3 class="text-2xl font-bold text-gray-800">Verifique seu e-mail</h3>
            <p class="text-gray-600 mt-2">
                Enviamos um link de redefinição para <br>
                <strong id="sent-email-address" class="text-gray-900"></strong>
            </p>
            <div class="mt-8">
                <button id="resend-button" class="text-gray-600 font-semibold link-style" disabled>
                    Reenviar em (<span id="resend-timer">30</span>s)
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Passa a aba ativa do PHP para o JavaScript
    const activeTabOnLoad = '<?php echo $active_tab; ?>';
</script>
<script src="assets/js/pages/autenticacao/autenticacao.js"></script>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>