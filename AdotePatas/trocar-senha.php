<?php
include_once 'conexao.php'; // Sua conexão PDO

$error_message = null;
$token = $_GET['token'] ?? null;

// ----------------------------------------------------
// 1. Validação do Token na Chegada
// ----------------------------------------------------
if (!$token) {
    $error_message = "Token de recuperação não fornecido. Por favor, use o link completo que enviamos para o seu e-mail.";
} else {
    try {
        $now = date("Y-m-d H:i:s");
        
        // Busca o token no banco para garantir que ele é válido e não expirou.
        $sql = "SELECT email FROM recuperar_senha_tolken WHERE token = :token AND expires_at > :now LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':token' => $token, ':now' => $now]);
        $reset_request = $stmt->fetch();

        // Se a busca não retornar nada, o token é inválido ou já expirou.
        if (!$reset_request) {
            $error_message = "O link para redefinição de senha é inválido ou já expirou. Por favor, solicite um novo.";
        }
    } catch (PDOException $e) {
        error_log("Erro na validação do token: " . $e->getMessage());
        $error_message = "Ocorreu um erro de conexão com o banco de dados. Tente novamente mais tarde.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Adote Patas</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<a href="autenticacao.php?tab=login" class="btn-voltar" title="Voltar para o Login">
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
            <h1 id="page-title" class="text-xl md:text-4xl font-bold text-[#666662]">Redefinir Senha</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">

        <div id="reset-message" class="alert hidden" role="alert"></div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <div class="text-center mt-4">
                <a href="autenticacao.php?tab=login" id="open-recovery-modal" class="link-style">Solicitar Novo Link</a>
            </div>
        
        <?php else: ?>
            <form id="reset-password-form" class="space-y-6">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div>
                    <div class="relative">
                        <label for="nova_senha" class="sr-only">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" placeholder="Nova Senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="nova_senha"></i>
                    </div>
                    <div id="mensagem-nova_senha" class="mensagem-validacao"></div>
                </div>

                <div>
                    <div class="relative">
                        <label for="confirma_senha" class="sr-only">Confirmar Nova Senha</label>
                        <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a nova senha" required class="input-style w-full pr-12 senha-input">
                        <i class="fas fa-eye toggle-senha" data-target="confirma_senha"></i>
                    </div>
                    <div id="mensagem-confirma_senha" class="mensagem-validacao"></div>
                </div>

                <div class="flex justify-center w-55 mx-auto pt-4">
                    <button type="submit" id="reset-submit-btn" class="adopt-btn">
                        <div class="heart-background">❤</div>
                        <span>
                            <span class="spinner-border spinner-border-sm hidden me-2" role="status" aria-hidden="true"></span>
                            <span class="button-text">Trocar Senha</span>
                        </span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>

<script type="module">
    // Importa as funções de validação do seu arquivo JS
    import { validarSenha, validarConfirmaSenha, initPasswordToggle } from './assets/js/pages/autenticacao/modules/forms/validation.js';

    // Inicializa os ícones de "mostrar/ocultar" senha
    initPasswordToggle();

    const resetForm = document.getElementById("reset-password-form");
    if (resetForm) {
        const submitBtn = document.getElementById("reset-submit-btn");
        const messageDiv = document.getElementById("reset-message");
        const spinner = submitBtn.querySelector('.spinner-border');
        const buttonText = submitBtn.querySelector('.button-text');

        // Campos de senha
        const novaSenhaInput = document.getElementById('nova_senha');
        const confirmaSenhaInput = document.getElementById('confirma_senha');

        // --- Adiciona validação em tempo real ---
        if (novaSenhaInput) {
            novaSenhaInput.addEventListener('input', () => {
                // Valida a força da senha e mostra na div "mensagem-nova_senha"
                validarSenha(novaSenhaInput, 'mensagem-nova_senha');
            });
        }
        if (confirmaSenhaInput) {
            confirmaSenhaInput.addEventListener('input', () => {
                // Valida a confirmação e mostra na div "mensagem-confirma_senha"
                validarConfirmaSenha('nova_senha', 'confirma_senha');
            });
        }
        // --- Fim da validação em tempo real ---


        resetForm.addEventListener("submit", async function(e) {
            e.preventDefault();

            // 1. Validação no lado do cliente (front-end) ANTES de enviar
            const isSenhaForte = validarSenha(novaSenhaInput, 'mensagem-nova_senha');
            const isSenhaConfirmada = validarConfirmaSenha('nova_senha', 'confirma_senha');
            
            // Esconde mensagens de erro globais antigas
            messageDiv.classList.add("hidden"); 
            messageDiv.className = "alert hidden";

            if (!isSenhaForte || !isSenhaConfirmada) {
                messageDiv.className = "alert alert-warning"; // Use a classe de alerta global
                messageDiv.textContent = "Por favor, corrija os erros no formulário.";
                return; // Impede o envio do formulário
            }

            // 2. Feedback visual de carregamento
            submitBtn.disabled = true;
            spinner.classList.remove("hidden");
            buttonText.textContent = 'Aguarde...';

            // 3. Submissão via AJAX para 'processa-troca-senha.php'
            try {
                const formData = new FormData(resetForm);
                const response = await fetch("processa-troca-senha.php", {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    messageDiv.className = "alert alert-success";
                    messageDiv.textContent = result.message + " Redirecionando para o login em 3 segundos...";
                    resetForm.classList.add('hidden'); // Oculta o formulário após o sucesso
                    
                    setTimeout(() => {
                        window.location.href = 'autenticacao.php?tab=login';
                    }, 3000);
                } else {
                    // Se o servidor retornar um erro, exibe a mensagem recebida.
                    messageDiv.className = "alert alert-danger";
                    messageDiv.textContent = result.message || "Ocorreu um erro desconhecido.";
                }

            } catch (error) {
                console.error("Erro na requisição AJAX:", error);
                messageDiv.className = "alert alert-danger";
                messageDiv.textContent = "Houve um problema de rede. Por favor, tente novamente.";
            } finally {
                // Só reativa o botão se a operação falhou.
                if (!messageDiv.classList.contains("alert-success")) {
                    submitBtn.disabled = false;
                    spinner.classList.add("hidden");
                    buttonText.textContent = 'Trocar Senha';
                }
            }
        });
    }
</script>

</body>
</html>