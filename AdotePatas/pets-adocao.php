<?php
// INICIE A SESSÃO NO TOPO DE TODA PÁGINA QUE PRECISA SABER SE O USUÁRIO ESTÁ LOGADO
session_start();

// Supondo que, ao fazer login, você define $_SESSION['usuario_id'] ou algo similar.
$usuario_logado = isset($_SESSION['usuario_id']); 

// Pega mensagens de erro do processa_cadastro.php, se houver
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';

// Limpa as mensagens da sessão para não aparecerem novamente
unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pets para Adoção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f8f9fa; }
        .pets-section { padding: 40px 0; }
        .pet-card {
            max-width: 18rem;
            width: 100%;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .pet-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        .pet-card img { object-fit: cover; height: 180px; }
        
        /* ======================================== */
        /* == NOVO CÓDIGO PARA O EFEITO DE BLUR == */
        /* ======================================== */
        .modal-backdrop.show {
            opacity: 1; /* Garante que o backdrop não fique mais claro que o definido abaixo */
        }
        .modal-backdrop {
            /* Aplica o desfoque ao conteúdo atrás do backdrop */
            -webkit-backdrop-filter: blur(5px);
            backdrop-filter: blur(5px);
            
            /* Define um fundo escuro e semi-transparente, necessário para o blur funcionar */
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        /* Estilos do Modal de Cadastro (adaptados do seu CSS original) */
        .modal-body {
            /* Tom levemente mais claro para contraste — não tão escuro */
            background-color: #a76b66;
        }
        .input-style {
            background-color: #980403;
            opacity: 0.5;
            color: white;
            font-weight: 500;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
        }
        .input-style::placeholder { color: rgba(255, 255, 255, 0.8); }
        .input-style:focus {
            background-color: #980403;
            color: white;
            box-shadow: none;
            outline: none;
        }
        .btn-cadastrar {
            background-color: #b92b2b;
            color: #f0e9e9;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-cadastrar:hover {
            transform: scale(1.02);
            color: white;
        }
    </style>
</head>
<body>

<main class="pets-section">
    <div class="container">
        <h1 class="text-center mb-4">Pets para Adoção</h1>
        <div class="row justify-content-center g-4">
            <?php
            $pets = [['img'=>'images/index/zeus.webp','nome'=>'Zeus','desc'=>'Cão carinhoso, 3 anos, vacinado.'], /* ... outros pets ... */];
            foreach ($pets as $pet) {
                $imgPath = file_exists(__DIR__ . '/' . $pet['img']) ? $pet['img'] : 'images/global/Logo-AdotePatas.png';
            ?>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card pet-card mx-auto" style="width: 18rem;">
                    <img src="<?= $imgPath ?>" class="card-img-top" alt="<?= htmlspecialchars($pet['nome']) ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($pet['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($pet['desc']) ?></p>
                        <a href="#" class="btn btn-primary">Ver mais</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card pet-card mx-auto" style="width: 18rem;">
                    <img src="<?= $imgPath ?>" class="card-img-top" alt="<?= htmlspecialchars($pet['nome']) ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($pet['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($pet['desc']) ?></p>
                        <a href="#" class="btn btn-primary">Ver mais</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card pet-card mx-auto" style="width: 18rem;">
                    <img src="<?= $imgPath ?>" class="card-img-top" alt="<?= htmlspecialchars($pet['nome']) ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($pet['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($pet['desc']) ?></p>
                        <a href="#" class="btn btn-primary">Ver mais</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex">
                <div class="card pet-card mx-auto" style="width: 18rem;">
                    <img src="<?= $imgPath ?>" class="card-img-top" alt="<?= htmlspecialchars($pet['nome']) ?>">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= htmlspecialchars($pet['nome']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($pet['desc']) ?></p>
                        <a href="#" class="btn btn-primary">Ver mais</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</main>

<div class="modal fade" id="cadastroModal" tabindex="-1" aria-labelledby="cadastroModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: transparent; border: none;">
      <div class="modal-body p-4 rounded-3">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem; z-index: 10;"></button>

        <div class="text-center text-white mb-4">
            <img src="images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" class="h-25 w-25 mx-auto mb-2">
            <h3 class="modal-title" id="cadastroModalLabel">Crie sua Conta</h3>
            <p class="text-white-50">É rápido e fácil para começar a adotar!</p>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <?php $bg_class = $tipo_mensagem == 'success' ? 'alert-success' : 'alert-danger'; ?>
            <div class="alert <?php echo $bg_class; ?>" role="alert">
                <?php echo htmlspecialchars($mensagem_status); ?>
            </div>
        <?php endif; ?>

        <form action="processa_cadastro.php" method="post" class="space-y-3">
            <div class="mb-3">
                <input type="text" name="nome" placeholder="Nome Completo" required class="form-control input-style">
            </div>
            <div class="mb-3">
                <input type="text" name="cpf" placeholder="CPF" required class="form-control input-style">
            </div>
            <div class="mb-3">
                <input type="email" name="email" placeholder="E-mail" required class="form-control input-style">
            </div>
            <div class="position-relative mb-3">
                <input type="password" id="senha" name="senha" placeholder="Senha" required class="form-control input-style pe-5">
                <i id="toggleSenha" class="fas fa-eye position-absolute top-50 end-0 translate-middle-y me-3 text-white-50" style="cursor: pointer;"></i>
            </div>
            <div class="position-relative mb-3">
                <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Confirme a Senha" required class="form-control input-style pe-5">
                <i id="toggleConfirmaSenha" class="fas fa-eye position-absolute top-50 end-0 translate-middle-y me-3 text-white-50" style="cursor: pointer;"></i>
            </div>
            <div class="d-grid pt-2">
                <button type="submit" class="btn btn-cadastrar fs-5 py-2 rounded-pill">Cadastrar</button>
            </div>
        </form>

        <div class="text-center mt-3">
            <a href="login.php" class="text-white-50">Já tem conta? Fazer Login</a>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica para ABRIR o modal
        <?php if (!$usuario_logado): ?>
            const cadastroModal = new bootstrap.Modal(document.getElementById('cadastroModal'), {
                keyboard: false, 
                backdrop: 'static'
            });
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('modal')) {
                cadastroModal.show();
            } else {
                setTimeout(() => {
                    cadastroModal.show();
                }, 400); 
            }
        <?php endif; ?>

        // Lógica para MOSTRAR/ESCONDER senha (reutilizada)
        function setupPasswordToggle(inputId, toggleId) {
            const toggleElement = document.getElementById(toggleId);
            if (toggleElement) {
                toggleElement.addEventListener('click', function (e) {
                    const senhaInput = document.getElementById(inputId);
                    const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    senhaInput.setAttribute('type', type);
                    this.classList.toggle('fa-eye-slash');
                    this.classList.toggle('fa-eye');
                });
            }
        }

        setupPasswordToggle('senha', 'toggleSenha');
        setupPasswordToggle('confirma_senha', 'toggleConfirmaSenha');
    });
</script>

</body>
</html>