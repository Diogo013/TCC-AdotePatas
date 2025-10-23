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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Adote Patas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    
    <link rel="stylesheet" href="assets/css/pages/perfil/perfil.css">

</head>
<body>

    <a href="./" class="btn-voltar" title="Voltar para a página inicial">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Voltar</span>
    </a>

 <div class="row">
  <div class="col-3">
    
    <nav class="navbar navbar-expand-lg mt-5 bg-primary">
      
      <div class="container-fluid">
        
        <a class="navbar-brand" href="./"> 
          <img src="./images/global/logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
        </a>

        <div class="foto-de-perfil">
            <i class="fa-regular fa-circle-user" style="font-size: 3.8rem; color: var(--cor-vermelho)"></i>
        </div>
        
      </div>
    </nav>
    
  </div>


    <div class="col-9">

    <main class="container">
        <div class="profile-card">
            <h1>Meu Perfil</h1>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erro); ?>
                </div>

            <?php elseif ($usuario): ?>
                <p>
                    <strong>Nome:</strong>
                    <?php echo htmlspecialchars($usuario['nome']); ?>
                </p>
                <p>
                    <strong>E-mail:</strong>
                    <?php echo htmlspecialchars($usuario['email']); ?>
                </p>

                <?php if ($user_tipo == 'adotante'): ?>
                    <p>
                        <strong>CPF:</strong>
                        <?php echo htmlspecialchars($usuario['cpf']); ?> 
                        </p>
                    <p>
                        <strong>Tipo:</strong>
                        <span class="user-type-tag">Adotante</span>
                    </p>

                <?php elseif ($user_tipo == 'protetor'): ?>
                    <p>
                        <strong>CNPJ:</strong>
                        <?php echo htmlspecialchars($usuario['cnpj']); ?>
                    </p>
                    <p>
                        <strong>Tipo:</strong>
                        <span class="user-type-tag protetor">Protetor/ONG</span>
                    </p>
                <?php endif; ?>

                <hr class="my-4">
                
                <a href="#" class="btn btn-primary">Editar Perfil</a>
            <?php endif; ?>

            <a class="nav-link logout-link" href="sair.php" style="font-size: 1rem; font-weight: bold;">Sair</a>
        </div>


    </main>
    
    </div>

  </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>