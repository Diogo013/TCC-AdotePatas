<?php
session_start();

include_once 'conexao.php';

$logado = isset($_SESSION['user_id']);
$usuario = null;
$user_id = null;
$user_tipo = null;
$primeiro_nome = '';
$pagina = "";

// Carrega dados do usuário se estiver logado (mesma lógica do index.php)
if ($logado) {
    $user_id = $_SESSION['user_id'];
    $user_tipo = $_SESSION['user_tipo'] ?? null;

    try {
        if ($user_tipo == 'adotante') {
            $sql = "SELECT nome, email, cpf FROM usuario WHERE id_usuario = :id LIMIT 1";
            $idField = 'id_usuario';
        } elseif ($user_tipo == 'protetor') {
            $sql = "SELECT nome, email, cnpj FROM ong WHERE id_ong = :id LIMIT 1";
            $idField = 'id_ong';
        } else {
            $sql = null;
        }

        if (!empty($sql)) {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // silencioso — manter UX
    }

    if ($logado && isset($_SESSION['nome'])) {
        $partes = explode(' ', $_SESSION['nome']);
        $primeiro_nome = $partes[0] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós - Adote Patas</title>
    <link rel="stylesheet" href="assets/css/pages/sobre/sobre.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>


<header>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid" style="margin: 0 8%;">
      <a class="navbar-brand" href="./">
        <img src="./images/global/logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
      </a>

      <?php if ($logado): ?>
        <div class="profile-container w-100 d-flex align-items-center justify-content-end gap-3">
          <ul class="navbar-nav d-flex align-items-center">
            <li class="nav-item">
              <a class="nav-link navlink active" href="sobre-nos.php">Sobre Nós</a>
            </li>
            <li class="nav-item">
              <a class="nav-link navlink" href="#">Ajuda</a>
            </li>
          </ul>

          <a href="perfil?page=perfil" class="profile-info-link" title="Ver meu perfil">
            <div class="profile-info d-flex flex-row-reverse gap-2 ms-4">
              <i class="fa-regular fa-circle-user profile-icon"></i>
              <span class="profile-name fs-5" style="color: var(--cor-vermelho);"><?php echo htmlspecialchars($primeiro_nome); ?></span>
            </div>
          </a>

          <!-- Botão que abre o offcanvas do perfil (mantido) -->
          <button class="border-0 bg-transparent offcanvas-toggle-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Menu Perfil">
            <span class="fas fa-bars nav-icon" style="font-size: 2rem;"></span>
          </button>
        </div>

      <?php else: ?>
        <div class="navbar-links w-100 d-flex align-items-center justify-content-end">
          <ul class="navbar-nav d-flex align-items-center">
            <li class="nav-item">
              <a class="nav-link navlink active" href="sobre-nos.php">Sobre Nós</a>
            </li>
            <li class="nav-item">
              <a class="nav-link navlink" href="#">Ajuda</a>
            </li>
            <li class="nav-item">
              <a class="nav-link loginlink" href="login">Entrar</a>
            </li>
          </ul>
        </div>
      <?php endif; ?>

    </div>
  </nav>
</header>

<main class="hero-sobre">
  </main>


  
  <section class="about container-fluid mt-5 gap-2 p-0" style="margin-bottom: 3rem; margin-left: 3%; margin-right: 3%;">
    <h1 class="titulo-about mb-3" style="max-width: 800px" >Como nós surgimos?</h1>

    <div class="row align-items-center">
      <div class="col-lg-8 col-md-12 mb-2 pe-0">
        <div class="about-content">
          <p class="about-text m-0">
            Nosso projeto surgiu do amor pelos animais e do desejo de ajudá-los de forma concreta. Ao percebermos a
            quantidade de
            cães e gatos abandonados e ignorados pela sociedade, sentimos a necessidade de agir. Muitos desses animais
            não são
            adotados por falta de informação ou visibilidade. Assim, criamos este espaço com o objetivo de promover a
            adoção
            responsável, conectando pessoas dispostas a amar com animais que só precisam de uma chance.
          </p>
            <br>
          <h3 class="conclusao-about">
            Aqui, cada adoção é celebrada como uma nova história de
            <span class="destaque-amor">AMOR</span>,
            <span class="destaque-confianca">CONFIANÇA</span>
            e
            <span class="destaque-recomeco">RECOMEÇO</span>
          </h3>
        </div>
      </div>

      <div class="col-lg-4 col-md-12 p-0" style="max-width: 500px">

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

  <section class="valores-section container-fluid">
  <div class="container-fluid">
    <h1 class="titulo-about ms-3" >Nossos Valores</h1>
    
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 justify-content-center g-4 mt-4">

      <div class="col d-flex justify-content-center">
        <div class="valor-card-item">
          <div class="card-icon">
            <i class="fa-solid fa-heart"></i>
          </div>
          <h3>Amor</h3>
        </div>
      </div>

      <div class="col d-flex justify-content-center">
        <div class="valor-card-item">
          <div class="card-icon">
            <i class="fa-solid fa-users"></i> 
          </div>
          <h3>Respeito</h3>
        </div>
      </div>

      <div class="col d-flex justify-content-center">
        <div class="valor-card-item">
          <div class="card-icon">
            <i class="fa-solid fa-people-arrows"></i>
          </div>
          <h3>Transparência</h3>
        </div>
      </div>

      <div class="col d-flex justify-content-center">
        <div class="valor-card-item">
          <div class="card-icon">
            <i class="fa-solid fa-volume-high"></i>
          </div>
          <h3>Comunicação</h3>
        </div>
      </div>

      <div class="col d-flex justify-content-center">
        <div class="valor-card-item">
          <div class="card-icon">
            <i class="fa-solid fa-handshake"></i>
          </div>
          <h3>Compromisso</h3>
        </div>
      </div>

    </div>
  </div>
</section>

 <h1 class="titulo-about ms-5">Nossa Equipe</h1>

<div class="team-carousel-wrapper container-fluid mt-4">
  <div class="team-carousel" id="teamCarousel" aria-label="Carrossel da equipe">
    <button class="team-prev" aria-label="Anterior">&larr;</button>
    <div class="team-track" id="teamTrack">
      <!-- 5 imagens (inicialmente mostram 3) -->
      <div class="team-item">
        <img class="team-avatar" src="./images/index/" alt="Cristian Lira">
        <div class="team-name">Cristian Lira</div>
      </div>
      <div class="team-item">
        <img class="team-avatar" src="./images/index/" alt="Diogo Rodrigues">
        <div class="team-name">Diogo Rodrigues</div>
      </div>
      <div class="team-item">
        <img class="team-avatar" src="./images/index/" alt="Marcella Rossinoli">
        <div class="team-name">Marcella Rossinoli</div>
      </div>
      <div class="team-item">
        <img class="team-avatar" src="./images/index/" alt="Inácio Andrade">
        <div class="team-name">Inácio Andrade</div>
      </div>
      <div class="team-item">
        <img class="team-avatar" src="./images/index/" alt="Giulia Baptista">
        <div class="team-name">Giulia Baptista</div>
      </div>
    </div>
    <button class="team-next" aria-label="Próximo">&rarr;</button>
  </div>
</div>

<center>
  <h1 style="color: var(--cor-verde-pastel); font-weight: bold; font-size: 3rem; margin-bottom: 3rem; text-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);">Junte-se a nós nessa causa!</h1>
</center>


<!-- Offcanvas Menu (replicado do index.php, aparece quando logado) -->
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
            <?php echo htmlspecialchars(ucfirst($user_tipo ?? '')); ?>
          </small>
        </div>
        
        <nav class="nav nav-pills flex-column profile-nav">
          <a class="nav-link <?php echo ($pagina == 'perfil') ? 'active' : ''; ?>" 
             href="perfil.php?page=perfil" 
             <?php echo ($pagina == 'perfil') ? 'aria-current=\"page\"' : ''; ?>>
            <i class="fa-regular fa-circle-user fa-fw me-2"></i> Meu Perfil
          </a>
          
          <a class="nav-link <?php echo ($pagina == 'meus-pets') ? 'active' : ''; ?>" 
             href="perfil.php?page=meus-pets"
             <?php echo ($pagina == 'meus-pets') ? 'aria-current=\"page\"' : ''; ?>>
            <i class="fa-solid fa-paw fa-fw me-2"></i> Meus Pets
          </a>

          <a class="nav-link <?php echo ($pagina == 'pets-curtidos') ? 'active' : ''; ?>" 
             href="perfil.php?page=pets-curtidos"
             <?php echo ($pagina == 'pets-curtidos') ? 'aria-current=\"page\"' : ''; ?>>
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

  
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
<script src="assets/js/pages/index/card-deck.js"></script>
<script src="assets/js/pages/index/offcanvas-fix.js"></script>
<script src="assets/js/pages/sobre/slide-equipe.js"></script>
</body>
</html>