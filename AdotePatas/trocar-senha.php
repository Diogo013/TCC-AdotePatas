<?php
include_once 'conexao.php'; // Sua conexão PDO
include_once 'session.php'; // Inclui session_start()

// Remove qualquer erro anterior
$error_message = null;
$token = $_GET['token'] ?? null;
$token_valid = false;

// ----------------------------------------------------
// 1. Validação do Token
// ----------------------------------------------------
if (!$token) {
    $error_message = "Token de recuperação não fornecido. Por favor, use o link completo enviado por e-mail.";
} else {
    try {
        $now = date("Y-m-d H:i:s");
        
        // Busca o token ativo e não expirado
        $sql = "SELECT email FROM recuperar_senha_tolken WHERE token = :token AND expires_at > :now LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':token' => $token, ':now' => $now]);
        $reset_request = $stmt->fetch();

        if ($reset_request) {
            $token_valid = true;
        } else {
            $error_message = "O link de redefinição de senha é inválido ou expirou. Solicite um novo link.";
        }

    } catch (PDOException $e) {
        error_log("Erro na validação do token: " . $e->getMessage());
        $error_message = "Erro de conexão com o banco de dados. Tente novamente mais tarde.";
    }
}

// O restante do HTML da página será exibido.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar Senha - Adote Patas</title>
    <!-- Inclua seu CSS aqui (Tailwind/Bootstrap/Seu estilo) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background-color: #f7f7f7; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .reset-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 400px; width: 90%; }
        .input-group-append { cursor: pointer; }
        .input-style { border: 1px solid #ccc; padding: 10px; border-radius: 8px; width: 100%; box-sizing: border-box; }
    </style>
</head>
<body>

<div class="reset-card">
    <h3 class="text-center mb-4" style="color: #bf6964;">Redefinir Senha</h3>
    
    <?php if ($error_message): ?>
        <!-- Exibe mensagem de erro se o token for inválido/expirado -->
        <div class="alert alert-danger text-center" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <p class="text-center mt-4"><a href="autenticacao.php">Voltar para Login</a></p>
    
    <?php else: ?>
        <!-- Exibe o formulário de troca de senha -->
        <div id="reset-message" class="alert hidden" role="alert"></div>

        <form id="reset-password-form" action="processa_troca_senha.php" method="POST">
            <!-- Token escondido que será enviado para processa_troca_senha.php -->
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha</label>
                <div class="input-group">
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Mínimo 8 caracteres, maiúscula, número" required class="form-control senha-input">
                    <span class="input-group-text toggle-senha" data-target="nova_senha"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirma_senha" class="form-label">Confirmar Nova Senha</label>
                <div class="input-group">
                    <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Confirmar a Nova Senha" required class="form-control senha-input">
                    <span class="input-group-text toggle-senha" data-target="confirma_senha"><i class="fas fa-eye"></i></span>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" id="reset-submit-btn" class="btn btn-lg text-white" style="background-color: #bf6964;">
                    <span class="spinner-border spinner-border-sm hidden me-2" role="status" aria-hidden="true"></span>
                    Trocar Senha
                </button>
            </div>
        </form>
    
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Lógica para alternar a visibilidade da senha (como no autenticacao.js)
    document.querySelectorAll(".toggle-senha").forEach((icon) => {
        icon.addEventListener("click", function () {
            const targetId = this.dataset.target;
            const targetInput = document.getElementById(targetId);
            const iconElement = this.querySelector('i');

            if (targetInput.type === "password") {
                targetInput.type = "text";
                iconElement.classList.remove("fa-eye");
                iconElement.classList.add("fa-eye-slash");
            } else {
                targetInput.type = "password";
                iconElement.classList.remove("fa-eye-slash");
                iconElement.classList.add("fa-eye");
            }
        });
    });

    // Lógica AJAX para submissão do formulário
    const resetForm = document.getElementById("reset-password-form");
    const submitBtn = document.getElementById("reset-submit-btn");
    const messageDiv = document.getElementById("reset-message");

    if (resetForm) {
        resetForm.addEventListener("submit", async function(e) {
            e.preventDefault();

            const novaSenha = document.getElementById('nova_senha').value;
            const confirmaSenha = document.getElementById('confirma_senha').value;

            // 1. Validação em JavaScript
            if (novaSenha !== confirmaSenha) {
                messageDiv.className = "alert alert-warning";
                messageDiv.textContent = "As senhas não coincidem. Por favor, verifique.";
                messageDiv.classList.remove("hidden");
                return;
            }

            // Você pode adicionar aqui uma validação de força de senha, se quiser!
            
            // 2. Estado de Loading
            submitBtn.disabled = true;
            submitBtn.querySelector('span').classList.remove("hidden");
            submitBtn.textContent = 'Aguarde...'; // O texto vai ser sobrescrito pelo spinner se não usarmos innerHTML

            // 3. Submissão AJAX
            try {
                const formData = new FormData(resetForm);

                const response = await fetch("processa_troca_senha.php", {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    messageDiv.className = "alert alert-success";
                    messageDiv.textContent = result.message + " Redirecionando para o login...";
                    messageDiv.classList.remove("hidden");
                    setTimeout(() => {
                        window.location.href = 'autenticacao.php?active_tab=login';
                    }, 3000); // Redireciona após 3 segundos
                } else {
                    messageDiv.className = "alert alert-danger";
                    messageDiv.textContent = result.message || "Erro desconhecido ao trocar a senha.";
                    messageDiv.classList.remove("hidden");
                }

            } catch (error) {
                console.error("Erro na requisição AJAX:", error);
                messageDiv.className = "alert alert-danger";
                messageDiv.textContent = "Houve um problema de rede ou no servidor. Tente novamente.";
                messageDiv.classList.remove("hidden");
            } finally {
                // Remove o estado de loading em caso de erro
                if (!messageDiv.classList.contains("alert-success")) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Trocar Senha'; 
                }
            }
        });
    }
</script>

</body>
</html>
