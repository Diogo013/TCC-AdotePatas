<?php
session_start();

// 1. Verificação de Login
$logado = isset($_SESSION['user_id']) && isset($_SESSION['user_tipo']);
$usuario = null;
$user_id = null;
$user_tipo = null;
$primeiro_nome = '';

// 2. Se estiver logado, busca os dados (para exibir o nome no menu)
if ($logado) {
    // Inclui conexão SÓ SE precisar
    // Certifique-se que o caminho para conexao.php está correto
    if (file_exists('conexao.php')) {
        include_once 'conexao.php'; 
    }
    
    $user_id = $_SESSION['user_id'];
    $user_tipo = $_SESSION['user_tipo'];

    // Define o primeiro nome
    if (isset($_SESSION['nome'])) {
        $partes = explode(' ', $_SESSION['nome']);
        $primeiro_nome = $partes[0] ?? '';
    } elseif (isset($conn)) { // Tenta buscar no DB se a sessão 'nome' não existir
        try {
            if ($user_tipo == 'adotante') {
                $sql = "SELECT nome FROM usuario WHERE id_usuario = :id LIMIT 1";
            } elseif ($user_tipo == 'protetor') {
                $sql = "SELECT nome FROM ong WHERE id_ong = :id LIMIT 1";
            } else {
                $sql = null;
            }

            if ($sql) {
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($usuario && isset($usuario['nome'])) {
                    $partes = explode(' ', $usuario['nome']);
                    $primeiro_nome = $partes[0];
                }
            }
        } catch (PDOException $e) {
            // Silencioso, não quebra a página se o DB falhar
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Como Adotar - Adote Patas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="assets/css/pages/como-adotar/adote.css">
</head>
<body>

<header>
  <nav class="navbar navbar-expand-lg navbar-static-white">
    <div class="container">
      
      <a class="navbar-brand" href="./"> <img src="./images/global/logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
      </a>

      <?php if ($logado): ?>
        <div class="d-flex align-items-center gap-2">
            <div class="d-none d-lg-block">
                <ul class="navbar-nav d-flex flex-row gap-3">
                    <li class="nav-item">
                        <a class="nav-link navlink" href="sobre-nos.php">Sobre Nós</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link navlink" href="#">Ajuda</a>
                    </li>
                </ul>
            </div>
            
            <a href="perfil?page=perfil" class="profile-info-link d-none d-lg-flex align-items-center gap-2 text-decoration-none" title="Ver meu perfil">
              <span class="profile-name" style="color: var(--cor-vermelho);"><?php echo htmlspecialchars($primeiro_nome); ?></span>
                <i class="fa-regular fa-circle-user profile-icon logged-in mr-4"></i>
            </a>

            <button class="border-0 bg-transparent p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
              <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
            </button>
        </div>

      <?php else: ?>
<button class="navbar-toggler hamburger d-lg-none" style="border: none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
  <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
</button>

        <div class="d-flex align-items-center gap-2">
            <div class="d-none d-lg-block">
                <ul class="navbar-nav d-flex flex-row gap-3">
                    <li class="nav-item">
                        <a class="nav-link navlink" href="sobre-nos.php">Sobre Nós</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link navlink" href="#">Ajuda</a>
                    </li>
                    <li class="nav-item position-relative">
                      <a class="nav-link loginlink" href="login">Entrar</a>
                    </li>
                </ul>
            </div>
        

      <?php endif; ?>

    </div>
  </nav>
</header>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
  <div class="offcanvas-header border-bottom">
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <div class="offcanvas-body p-0">
    
    <?php if ($logado): ?>
      <aside class="profile-sidebar p-3">
        <div class="sidebar-header text-center mb-4">
          <i class="fa-regular fa-circle-user sidebar-profile-icon logged-in"></i>
          <h5 class="mt-2 mb-0">
            <?php echo htmlspecialchars($primeiro_nome); ?>
          </h5>
          <small class="text-muted fs-6">
            <?php echo htmlspecialchars(ucfirst($user_tipo ?? '')); ?>
          </small>
        </div>
        
        <nav class="nav nav-pills flex-column profile-nav">
          <a class="nav-link" href="sobre-nos.php"><i class="fa-solid fa-info-circle fa-fw me-2"></i> Sobre Nós</a>
          <a class="nav-link" href="#"><i class="fa-solid fa-question-circle fa-fw me-2"></i> Ajuda</a>
          <hr class="my-2">
          <a class="nav-link" href="perfil?page=perfil"><i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil</a>
          <a class="nav-link" href="perfil?page=meus-pets"><i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets</a>
          <a class="nav-link" href="perfil?page=pets-curtidos"><i class="fa-regular fa-heart fa-fw me-2"></i> Pets Curtidos</a>
          <a class="nav-link" href="chat.php"><i class="fa-regular fa-comments fa-fw me-2"></i> Chats</a>
          <hr class="my-2">
          <a class="nav-link logout-link-sidebar" href="sair.php"><i class="fa-solid fa-right-from-bracket fa-fw me-2"></i> Sair</a>
        </nav>
      </aside>

    <?php else: ?>
      <aside class="profile-sidebar p-3">
        <div class="sidebar-header text-center mb-4">
          <i class="fa-regular fa-circle-user sidebar-profile-icon logged-out"></i>
          <h5 class="mt-2 mb-0">Visitante</h5>
          <small class="text-muted fs-6">Faça login para ver seu perfil</small>
        </div>

        <nav class="nav nav-pills flex-column profile-nav">
          <a class="nav-link" href="sobre-nos.php">
            <i class="fa-solid fa-info-circle fa-fw me-2"></i> Sobre Nós
          </a>
          <a class="nav-link" href="#">
            <i class="fa-solid fa-question-circle fa-fw me-2"></i> Ajuda
          </a>
          <hr class="my-2">
          <a class="nav-link loginlink-sidebar" href="login">
            <i class="fa-solid fa-right-to-bracket fa-fw me-2"></i> Entrar
          </a>
        </nav>
      </aside>
    <?php endif; ?>

  </div>
</div>
<main class="como-adotar-section"id="como-adotar">
  <div class="container">
    
    <div class="row">
      <div class="col-12 text-center">
        <h1 class="section-title-kawaii" style="font-size: 2.7rem">O Caminho para o seu Novo Amigo</h1>
        <p class="section-subtitle-poppins" style="font-size: 1.2rem">Adotar é um ato de amor! Veja como é fácil:</p>
      </div>
    </div>

    <div class="row align-items-center">
      
      <div class="col-lg-6 order-lg-1 order-2">
        <ul class="adoption-steps">
          
          <li class="step-item">
            <div class="step-icon-wrapper">
              <i class="bi bi-person-fill"></i> 
            </div>
            <div class="step-content">
              <h3>1. Crie Sua Conta</h3>
              <p>Rápido e seguro.</p>
            </div>
          </li>

          <li class="step-item">
            <div class="step-icon-wrapper">
              <i class="bi bi-search"></i> </div>
            <div class="step-content">
              <h3>2. Procure pelo seu Amiguinho</h3>
              <p>Encontre o pet que mais combina com você.</p>
            </div>
          </li>

          <li class="step-item">
            <div class="step-icon-wrapper">
              <i class="bi bi-clipboard2-check-fill"></i>
            </div>
            <div class="step-content">
              <h3>3. Preencha o Formulário</h3>
              <p>Conte sobre você e seu lar.</p>
            </div>
          </li>

          <li class="step-item">
            <div class="step-icon-wrapper">
              <i class="bi bi-chat-dots-fill"></i>
            </div>
            <div class="step-content">
              <h3>4. Converse com a ONG</h3>
              <p>Tire dúvidas e combine.</p>
            </div>
          </li>

          <li class="step-item">
            <div class="step-icon-wrapper">
              <i class="bi bi-house-heart-fill"></i> 
            </div>
            <div class="step-content">
              <h3>5. Combine a Busca</h3>
              <p>Leve seu amiguinho para casa!</p>
            </div>
          </li>

        </ul>
      </div>

      <div class="col-lg-6 order-lg-2 order-1 mb-5 mb-lg-0">
        <div class="adoption-illustration text-center">
          <img src="./images/como-adotar/como-adotar-image.png" 
               alt="Ilustração de uma pessoa feliz abraçando dois cachorrinhos." 
               class="img-fluid">
        </div>
      </div>

    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
</body>
</html>