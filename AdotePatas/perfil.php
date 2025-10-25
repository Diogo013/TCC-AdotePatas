<?php
session_start();

include_once 'conexao.php'; // 1. Inclui a conexão com o banco



// 2. Segurança: Verifica se o usuário está logado

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {

    // Se não estiver logado, redireciona para a página de login
    header("Location: login");
    exit;
}
// 3. Pega os dados básicos da sessão

$user_id = $_SESSION['user_id'];

$user_tipo = $_SESSION['user_tipo'];

$usuario = null;

$erro = ''; 



// 4. Busca os dados completos do usuário no banco

try {

    if ($user_tipo == 'adotante') {

        // Se for adotante, busca na tabela 'usuario'

        $sql = "SELECT nome, email, cpf FROM usuario WHERE id_usuario = :id LIMIT 1";

    } elseif ($user_tipo == 'protetor') {

        // Se for protetor/ONG, busca na tabela 'ong'

        $sql = "SELECT nome, email, cnpj FROM ong WHERE id_ong = :id LIMIT 1";

    } else {

        // Tipo de usuário desconhecido

        $erro = "Tipo de usuário inválido.";

    }



    // Executa a consulta se não houver erro no tipo

    if (empty($erro)) {

        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);



        if (!$usuario) {

            $erro = "Usuário não encontrado no banco de dados.";

        }

    }



} catch (PDOException $e) {

    $erro = "Ocorreu um erro ao buscar seus dados. Tente novamente.";

    // Para debug: error_log("Erro no perfil.php: " . $e->getMessage());

}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Adote Patas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    
    <link rel="stylesheet" href="assets/css/pages/perfil/perfil.css">
    <link rel="stylesheet" href="assets/css/global/toast.css"> </head>
<body class="profile-body">

    <a href="./" class="btn-voltar" title="Voltar para a página inicial">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Voltar</span>
    </a>

    <div id="toast-notification" class="toast" style="display: none;">
        <div id="toast-icon" class="toast-icon">
            </div>
        <div class="toast-content">
            <p id="toast-message" class="toast-message">Mensagem...</p>
        </div>
        <div class="toast-progress-bar"></div>
    </div>
    <div class="container-fluid mt-5 pt-4">
        <div class="row full-height-row">

            <div class="col-lg-9">
                <main class="profile-card">
                    <h1>Meu Perfil</h1>

                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($erro); ?>
                        </div>

                   <?php elseif ($usuario): ?>
                        <form id="profileForm" novalidate>
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <input type="hidden" name="user_tipo" value="<?php echo $user_tipo; ?>">

                            <div class="mb-3">
                                <label for="inputNome" class="form-label"><strong>Nome:</strong></label>
                                <input type="text" class="form-control" id="inputNome" name="nome"
                                       value="<?php echo htmlspecialchars($usuario['nome']); ?>"  data-profile-field>
                                <div id="feedbackNome" class="feedback-message"></div>
                            </div>

                            <div class="mb-3">
                                <label for="inputEmail" class="form-label"><strong>E-mail:</strong></label>
                                <input type="email" class="form-control" id="inputEmail" name="email"
                                       value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled data-profile-field>
                                <div id="feedbackEmail" class="feedback-message"></div>
                            </div>

                            <?php if ($user_tipo == 'adotante'): ?>
                                <div class="mb-3">
                                    <label for="inputDocumento" class="form-label"><strong>CPF:</strong></label>
                                    <input type="text" class="form-control" id="inputDocumento" name="documento"
                                           value="<?php echo htmlspecialchars($usuario['cpf']); ?>" disabled data-profile-field>
                                    <div id="feedbackDocumento" class="feedback-message"></div>
                                </div>
                            <?php elseif ($user_tipo == 'protetor'): ?>
                                <div class="mb-3">
                                    <label for="inputDocumento" class="form-label"><strong>CNPJ:</strong></label>
                                    <input type="text" class="form-control" id="inputDocumento" name="documento"
                                           value="<?php echo htmlspecialchars($usuario['cnpj']); ?>" disabled data-profile-field>
                                    <div id="feedbackDocumento" class="feedback-message"></div>
                                </div>
                            <?php endif; ?>

                            <hr class="my-4">

                            <button type="button" id="btnEditar" class="btn btn-danger">
                                <i class="fa-solid fa-pencil me-1"></i> Editar Perfil
                            </button>

                            <button type="submit" id="btnSalvar" class="btn btn-success d-none">
                                <i class="fa-solid fa-check me-1"></i> Salvar Alterações
                            </button>
                        </form>
                    <?php endif; ?>
                    
                </main>
            </div>

            <div class="col-lg-3 sidebar-wrapper-col">
                <div class="sidebar-sticky-wrapper">
                    <aside class="profile-sidebar p-3">
                        <div class="sidebar-header text-center mb-4">
                            <i class="fa-regular fa-circle-user sidebar-profile-icon"></i>
                            <h5 class="mt-2 mb-0">
                                <?php echo htmlspecialchars($usuario['nome'] ?? 'Usuário'); ?>
                            </h5>
                            <small class="text-muted fs-6">
                                <?php echo htmlspecialchars(ucfirst($user_tipo)); ?>
                            </small>
                        </div>
                        <nav class="nav nav-pills flex-column profile-nav">
                            <a class="nav-link active" href="perfil.php" aria-current="page">
                                <i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil
                            </a>
                            <a class="nav-link" href="meus-pets.php">
                                <i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets
                            </a>
                            <a class="nav-link" href="pets-curtidos.php">
                                <i class="fa-regular fa-heart fa-fw me-2"></i> Pets Curtidos
                            </a>
                            <hr class="my-2">
                            <a class="nav-link logout-link-sidebar" href="sair.php">
                                <i class="fa-solid fa-right-from-bracket fa-fw me-2"></i> Sair
                            </a>
                        </nav>
                    </aside>
                </div>
            </div>

        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js"></script>
    
  <script src="assets/js/pages/perfil/editar.js"  type="module"></script>

</body>
</html>