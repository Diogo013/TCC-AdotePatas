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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .reset-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 420px; width: 95%; }
        /* Adicionamos a classe .hidden para controlar a visibilidade de elementos com JavaScript */
        .hidden { display: none; }
    </style>
</head>
<body>

<div class="reset-card">
    <h3 class="text-center mb-4" style="color: #bf6964;">Redefinir Senha</h3>
    
    <?php if ($error_message): ?>
        <!-- Se o token for inválido, exibe a mensagem de erro e um link para voltar. -->
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <div class="text-center mt-4">
            <a href="autenticacao.php?active_tab=recuperar" class="btn btn-secondary">Solicitar Novo Link</a>
        </div>
    
    <?php else: ?>
        <!-- Se o token for válido, exibe o formulário de troca de senha. -->
        <div id="reset-message" class="alert hidden" role="alert"></div>

        <form id="reset-password-form">
            <!-- O token é enviado de forma oculta para ser processado pelo backend. -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label for="nova_senha" class="form-label fw-bold">Nova Senha</label>
                <div class="input-group">
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres" required class="form-control">
                    <span class="input-group-text toggle-senha" style="cursor: pointer;" data-target="nova_senha"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirma_senha" class="form-label fw-bold">Confirmar Nova Senha</label>
                <div class="input-group">
                    <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a nova senha" required class="form-control">
                    <span class="input-group-text toggle-senha" style="cursor: pointer;" data-target="confirma_senha"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" id="reset-submit-btn" class="btn btn-lg text-white" style="background-color: #bf6964;">
                    <span class="spinner-border spinner-border-sm hidden me-2" role="status" aria-hidden="true"></span>
                    <span class="button-text">Trocar Senha</span>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Lógica para alternar a visibilidade da senha
    document.querySelectorAll(".toggle-senha").forEach((icon) => {
        icon.addEventListener("click", function () {
            const targetId = this.dataset.target;
            const targetInput = document.getElementById(targetId);
            const iconElement = this.querySelector('i');

            if (targetInput.type === "password") {
                targetInput.type = "text";
                iconElement.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                targetInput.type = "password";
                iconElement.classList.replace("fa-eye-slash", "fa-eye");
            }
        });
    });

    const resetForm = document.getElementById("reset-password-form");
    if (resetForm) {
        const submitBtn = document.getElementById("reset-submit-btn");
        const messageDiv = document.getElementById("reset-message");
        const spinner = submitBtn.querySelector('.spinner-border');
        const buttonText = submitBtn.querySelector('.button-text');

        resetForm.addEventListener("submit", async function(e) {
            e.preventDefault();

            const novaSenha = document.getElementById('nova_senha').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;

            // 1. Validação no lado do cliente (front-end)
            messageDiv.classList.add("hidden"); // Esconde mensagens antigas
            if (novaSenha !== confirmaSenha) {
                messageDiv.className = "alert alert-warning";
                messageDiv.textContent = "As senhas não coincidem. Por favor, verifique.";
                return;
            }

            // Sugestão: Adicionar validação de força da senha aqui também.
            // Ex: if (novaSenha.length < 8) { ... }

            // 2. Feedback visual de carregamento
            submitBtn.disabled = true;
            spinner.classList.remove("hidden");
            buttonText.textContent = 'Aguarde...';

            // 3. Submissão via AJAX para 'processa_troca_senha.php'
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
                        window.location.href = 'autenticacao.php?active_tab=login';
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
