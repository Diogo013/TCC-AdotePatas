<?php
session_start();

include_once 'conexao.php'; // 1. Inclui a conexão com o banco

// Verifica se o usuário está logado ANTES de tentar acessar as variáveis de sessão
$logado = isset($_SESSION['user_id']);
$usuario = null;
$erro = '';
$user_id = null;
$user_tipo = null;

// SÓ busca dados do usuário se estiver logado
if ($logado) {
    // 3. Pega os dados básicos da sessão (agora com segurança)
    $user_id = $_SESSION['user_id'];
    $user_tipo = $_SESSION['user_tipo'];

    // 4. Busca os dados completos do usuário no banco
    try {
        if ($user_tipo == 'adotante') {
            $sql = "SELECT nome, email, cpf FROM usuario WHERE id_usuario = :id LIMIT 1";
        } elseif ($user_tipo == 'protetor') {
            $sql = "SELECT nome, email, cnpj FROM ong WHERE id_ong = :id LIMIT 1";
        } else {
            $erro = "Tipo de usuário inválido.";
        }

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
}

// Define o link do botão principal com base no status de login
if ($logado) {
  $acesso = "pets-adocao.php";
} else {
  $acesso = "login";// Se não estiver logado, o botão "Quero Adotar" leva para a tela de login
}

// Pega o primeiro nome do usuário se estiver logado
$primeiro_nome = '';
if ($logado && isset($_SESSION['nome'])) {
    $partes_nome = explode(' ', $_SESSION['nome']);
    $primeiro_nome = $partes_nome[0];
}

$pagina = "";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Adote Patas</title>
  <link rel="stylesheet" href="assets/css/pages/index/index.css">
  <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
</head>

<body>

  <!-- LOADING SCREEN DESATIVADO PARA MAIOR PRODUTIVIDADE
  <div class="loading-screen" id="loading">
    <lottie-player src="animações/loading.json" background="transparent" speed="1" style="width: 250px; height: 250px;"
    loop autoplay>
  </lottie-player>
</div>
!-->

  <header>
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand" href="#">
          <img src="./images/global/logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
        </a>

        <?php if ($logado): ?>
          <!-- Navbar para usuário LOGADO -->
          <div class="profile-container">
            
        <ul class="navbar-nav d-flex">
          <li class="nav-item">
            <a class="nav-link navlink" href="#">Sobre Nós</a>
          </li>
          <li class="nav-item">
            <a class="nav-link navlink" href="#">Ajuda</a>
          </li>
        </ul>

            <a href="perfil?page=perfil" class="profile-info-link" title="Ver meu perfil">
              <div class="profile-info">
                <i class="fa-regular fa-circle-user profile-icon"></i>
                <!--<span class="profile-name"><?php //echo htmlspecialchars($primeiro_nome); ?></span>!-->
              </div>
            </a>

            <button class="border-0 bg-transparent" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
              <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
            </button>

          </div>

        <?php else: ?>
        <ul class="navbar-nav d-flex">
          <li class="nav-item">
            <a class="nav-link navlink" href="#">Sobre Nós</a>
          </li>
          <li class="nav-item">
            <a class="nav-link navlink" href="#">Ajuda</a>
          </li>
          <li class="nav-item">
            <a class="nav-link loginlink" href="login">Entrar</a>
          </li>
        </ul>
        <?php endif; ?>


      </div>
    </nav>
  </header>

 <main class="hero">
    <div class="container" style="margin-top: 5rem;">
      <div class="row align-items-center">

        <div class="col-lg-6 order-1"> <div class="hero-text-content">
            <h1 class="adote-patas">Adote Patas</h1>
            <h2 class="adote-vidas">Adote Vidas</h2>
            <p>
              Adotar é um gesto de amor e responsabilidade, capaz de transformar vidas e criar laços de confiança e
              felicidade. Mais do que uma escolha, a adoção é amor e compromisso.
            </p>
          </div>

          <div class="btn-container">
            <a href="<?php
                  echo  $acesso
                  ?>">
              <button class="adopt-btn mt-4" id="adoptBtn">
                <div class="heart-background" style="user-select: none;">❤</div>
                <span>Quero adotar</span>
              </button>
            </a>
            <div class="paw-prints" id="pawPrints"></div>
          </div>

        </div> </div>
    </div>
  </main>



  <section class="image-background">


    <section class="cards-section">
      <div class="container text-center">
        <div class="row justify-content-center">

          <div class="col-4">
            <a href="pets" style="text-decoration: none;">
              <div class="card-item">
                <div class="card-icon">
                  <i class="fa-solid fa-paw"></i>
                </div>
                <h3>Adote</h3>
              </div>
            </a>
          </div>

          <div class="col-4">
            <div class="card-item">
              <div class="card-icon">
                <img src="./images/index/icone-ong.png" alt="Ícone de ONGs">
              </div>
              <h3>ONGs</h3>
            </div>
          </div>

          <div class="col-4">
            <div class="card-item">
              <div class="card-icon">
                <i class="fa-solid fa-heart"></i>
              </div>
              <h3>Doações</h3>
            </div>
          </div>

        </div>
      </div>
    </section>


    <section class="pets-section">
      <div class="container">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="titulo-adocao">Animais para Adoção</h1>
          </div>
        </div>
        <div class="row justify-content-between">

          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="pet-card">
              <div class="pet-card-img">
                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
              </div>
              <div class="pet-card-body">
                <h2 class="pet-name">Baunilha</h2>
                <i class="fa-solid fa-venus pet-gender-female"></i>
                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha"></i>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="pet-card">
              <div class="pet-card-img">
                <img src="images/index/caramelo.webp" alt="Foto do cachorro caramelo">
              </div>
              <div class="pet-card-body">
                <h2 class="pet-name">Caramelo</h2>
                <i class="fa-solid fa-mars pet-gender-male"></i>
                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo"></i>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="pet-card">
              <div class="pet-card-img">
                <img src="images/index/cookie.webp" alt="Foto da gata cookie">
              </div>
              <div class="pet-card-body">
                <h2 class="pet-name">Cookie</h2>
                <i class="fa-solid fa-venus pet-gender-female"></i>
                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie"></i>
              </div>
            </div>
          </div>

        </div>

        <center>
          <div class="btn-container">
            <button class="adopt-btn" id="adoptBtn">
              <div class="heart-background" style="user-select: none;">❤</div>
              <span>Ver mais patinhas</span>
            </button>
          </div>
        </center>

      </div>
    </section>

    <div class="cachorro">
      <lottie-player class="dog-walking" src="animações/animacao.json" background="transparent" speed="1"
        style="width: 200px; height: 200px;" loop autoplay>
      </lottie-player>
    </div>

  </section>

  <section class="about container mt-5 gap-3" style="margin-bottom: 3rem;">
    <h1 class="titulo-about" style="margin-bottom: 1rem">Como nós surgimos?</h1>

    <div class="row align-items-center" style="margin-bottom: 10rem;">
      <div class="col-lg-8 col-md-12 mb-2">
        <div class="about-content">
          <p class="about-text">
            Nosso projeto surgiu do amor pelos animais e do desejo de ajudá-los de forma concreta. Ao percebermos a
            quantidade de
            cães e gatos abandonados e ignorados pela sociedade, sentimos a necessidade de agir. Muitos desses animais
            não são
            adotados por falta de informação ou visibilidade. Assim, criamos este espaço com o objetivo de promover a
            adoção
            responsável, conectando pessoas dispostas a amar com animais que só precisam de uma chance.
          </p>

          <h3 class="conclusao-about">
            Aqui, cada adoção é celebrada como uma nova história de
            <span class="destaque-amor">AMOR</span>,
            <span class="destaque-confianca">CONFIANÇA</span>
            e
            <span class="destaque-recomeco">RECOMEÇO</span>
          </h3>
        </div>
      </div>

      <div class="col-lg-4 col-md-12">

        <div class="card-carousel-container">
          <div class="card-deck ms-5 mb-5">

            <div class="card-item-deck">
              <div class="card-content" style="background-image: url(./images/index/cacau.webp);"></div>
            </div>

            <div class="card-item-deck">
              <div class="card-content" style="background-image: url(./images/index/zeus.webp);"></div>
            </div>

            <div class="card-item-deck">
              <div class="card-content" style="background-image: url(./images/index/kitty.jpg);"></div>
            </div>

          </div>

        </div>




      </div>
    </div>
  </section>


  <svg width="0" height="0" style="position: absolute;">
    <defs>
      <clipPath id="footerConcavity" clipPathUnits="objectBoundingBox">
        <path d="M0,0 Q 0.5,0.35 1,0 L1,1 L0,1 Z" />
      </clipPath>
      <clipPath id="footerConcavityShallow" clipPathUnits="objectBoundingBox">
        <path d="M0,0 Q 0.5,0.15 1,0 L1,1 L0,1 Z" />
      </clipPath>
      <clipPath id="footerConcavityDeep" clipPathUnits="objectBoundingBox">
        <path d="M0,0 Q 0.5,0.25 1,0 L1,1 L0,1 Z" />
      </clipPath>
      <clipPath id="footerConcavityExtraDeep" clipPathUnits="objectBoundingBox">
        <path d="M0,0 Q 0.5,0.45 1,0 L1,1 L0,1 Z" />
      </clipPath>
    </defs>
  </svg>

  <footer class="site-footer">
    <div class="container footer-container">
      <div class="row">

        <div class="col-lg-4 col-md-12 footer-column">
          <h3>Links</h3>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">Adote</a></li>
            <li><a href="#">Sobre nós</a></li>
            <li><a href="#">ONGs</a></li>
            <li><a href="#">Histórias</a></li>
            <li><a href="#">Política de privacidade</a></li>
            <li><a href="#">Termos de uso</a></li>
          </ul>
        </div>

        <div class="col-lg-4 col-md-12 footer-column">
          <h3>Contato</h3>
          <p>Endereço</p>
          <p><a href="mailto:adotepatas@gmail.com">adotepatas@gmail.com</a></p>
          <p><a href="tel:+5513992007065">(13) 99200-7065</a></p>
        </div>

        <div class="col-lg-4 col-md-12 footer-column">
          <h3>Redes Sociais</h3>
          <p><a href="https://wa.me/5513992007065" target="_blank"><i class="fab fa-whatsapp"></i> 13992007065</a></p>
          <p><a href="https://instagram.com/adotepatas.tcc" target="_blank"><i class="fab fa-instagram"></i>
              @adotepatas.tcc</a></p>
        </div>

      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
    integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
    crossorigin="anonymous"></script>
  <script src="assets/js/pages/index/patinhas.js"></script>
  <script src="assets/js/pages/index/pet-likes.js"></script>
  <script src="assets/js/pages/index/card-deck.js"></script>
  <script src="assets/js/pages/index/loading.js"></script>
  <script src="assets/js/pages/index/offcanvas-fix.js"></script>

  <!-- Offcanvas Menu (só aparece quando logado) -->
  <?php if ($logado): ?>
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header border-bottom">
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0">
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
          <a class="nav-link <?php echo ($pagina == 'perfil') ? 'active' : ''; ?>" 
             href="perfil.php?page=perfil" 
             <?php echo ($pagina == 'perfil') ? 'aria-current="page"' : ''; ?>>
            <i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil
          </a>
          
          <a class="nav-link <?php echo ($pagina == 'meus-pets') ? 'active' : ''; ?>" 
             href="perfil.php?page=meus-pets"
             <?php echo ($pagina == 'meus-pets') ? 'aria-current="page"' : ''; ?>>
            <i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets
          </a>
          
          <a class="nav-link <?php echo ($pagina == 'pets-curtidos') ? 'active' : ''; ?>" 
             href="perfil.php?page=pets-curtidos"
             <?php echo ($pagina == 'pets-curtidos') ? 'aria-current="page"' : ''; ?>>
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
  <?php endif; ?>

</body>

</html>