<?php
session_start();
include_once 'conexao.php'; // 1. Inclui a conexão com o banco

// 2. Segurança: Verifica se o usuário está logado
//if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    // Se não estiver logado, redireciona para a página de login
 //   header("Location: login");
 //   exit;
//}

$favoritos_usuario = [];
$usuario_logado = false;

if (isset($_SESSION['user_id'])) {
    $usuario_logado = true;
    try {
        // Busca todos os IDs de pets que o usuário já favoritou
        $sql_fav = "SELECT id_pet FROM favorito WHERE id_usuario = :id_usuario";
        $stmt_fav = $conn->prepare($sql_fav);
        $stmt_fav->execute([':id_usuario' => $_SESSION['user_id']]);
        
        // Converte o resultado em um array simples de IDs [1, 5, 12]
        $favoritos_usuario = $stmt_fav->fetchAll(PDO::FETCH_COLUMN, 0);
        $favoritos_usuario = array_map('intval', $favoritos_usuario); 
        
    } catch (PDOException $e) {
        // Não para a página, apenas loga o erro
        error_log("Erro ao buscar favoritos: " . $e->getMessage());
    }
}

// 3. Lógica para buscar os pets no banco de dados
$pets = [];
$erro = '';
try {
    // Buscamos apenas pets que estão 'disponiveis'
    $sql = "SELECT id_pet, nome, foto, sexo 
            FROM pet 
            WHERE status_disponibilidade = 'disponivel'";
            // Você pode adicionar um 'ORDER BY data_cadastro DESC' aqui se quiser
            
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $erro = "Erro ao buscar os pets. Tente novamente mais tarde.";
    // Para debug: error_log("Erro em pets-adocao.php: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adote Patas - Animais para Adoção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link rel="stylesheet" href="assets/css/pages/pets/pets.css">
</head>
<body>

    <header class="main-header">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="images/global/Logo-Nome.png" alt="Logo Adote Pet" class="logo-img">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse d-flex justify-content-end align-items-end" id="navbarNav">
                    <ul class="navbar-nav d-flex  align-items-center">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="#">Animais para Adoção</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Como Adotar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Contato</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $usuario_logado ? 'perfil.php' : 'login'; ?>" title="<?php echo $usuario_logado ? 'Meu Perfil' : 'Entrar'; ?>">
                                <i class="fa-regular fa-circle-user" style="font-size: 3rem;"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="my-5">

        <section class="pets-section">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="titulo-adocao">Pets para Adoção</h1>
                    </div>
                </div>

                <section class="input-filtros m-5">
                    <h6>Pesquise pelo nome ou Adione Filtros</h6>
                      <div class="row d-flex align-items-center">
                            <div class="col-10"><input type="text"  class="container-fluid"></div>
                             <div class="col-2 d-flex justify-content-center">
                                <button type="button" class="btn btn-outline-primary">Filtros <i class="fa-solid fa-sliders"></i></button>
                            </div>
                    </div>
                </section>
                 <?php if (!empty($erro)): ?>
                    <!-- Mostra erro se a busca no banco falhar -->
                    <div class="alert alert-danger text-center">
                        <?php echo htmlspecialchars($erro); ?>
                    </div>

                <?php elseif (empty($pets)): ?>
                    <!-- Mensagem se não houver pets disponíveis -->
                    <div class="alert alert-info text-center">
                        <i class="fa-solid fa-paw fa-3x mb-3"></i>
                        <h5 class="mb-1">Nenhum pet disponível para adoção no momento.</h5>
                        <p>Volte em breve!</p>
                    </div>

                <?php else: ?>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="petsGrid">

                <?php foreach ($pets as $pet): ?>

                    <?php
                            // --- NOVO: Verifica se este pet está favoritado ---
                            $is_favorito = in_array($pet['id_pet'], $favoritos_usuario);
                        ?>
                    <div class="col">
                        <a href="pet-detalhe.php?id=<?php echo $pet['id_pet']; ?>" class="pet-card-link">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name"><?php echo htmlspecialchars($pet['nome']); ?></h2>
                                <?php if (!empty($pet['sexo'])): ?>
                                            <?php if ($pet['sexo'] == 'femea'): ?>
                                                <!-- [Cite: pets-adocao.php, line 93] -->
                                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                            <?php else: // 'macho' ?>
                                                <!-- [Cite: pets-adocao.php, line 104] -->
                                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <i class="pet-like <?php echo $is_favorito ? 'fa-solid fa-heart favorited' : 'fa-regular fa-heart'; ?>" 
                                       data-pet-id="<?php echo $pet['id_pet']; ?>" 
                                       aria-label="Favoritar" 
                                       role="button">
                                    </i>
                            </div>
                        </div>
                    </a>
                    </div>
                    <?php endforeach; ?>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro caramelo">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Caramelo</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata cookie">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Cookie</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Pipoca">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Pipoca</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="pipoca" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Pudim">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Pudim</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="pudim" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Biscoito">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Biscoito</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="biscoito" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Amora">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Amora</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="amora" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Thor">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Thor</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="thor" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Mia">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Mia</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="mia" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                     <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Luna">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Luna</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="luna" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Max">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Max</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="max" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Bella">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Bella</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="bella" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Nala">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Nala</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="nala" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Simba">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Simba</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="simba" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Frida">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Frida</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="frida" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                     <div class="col">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Loki">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Loki</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="loki" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Zeus">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Zeus 2</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="zeus2" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Chico">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Chico 2</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="chico2" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Mel">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Mel 2</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="mel2" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Billy">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Billy 2</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="billy2" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Baunilha 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro caramelo">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Caramelo 3</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata cookie">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Cookie 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Pipoca">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Pipoca 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="pipoca3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Pudim">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Pudim 3</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="pudim3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Biscoito">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Biscoito 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="biscoito3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Amora">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Amora 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="amora3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Thor">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Thor 3</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="thor3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Mia">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Mia 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="mia3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                     <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Luna">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Luna 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="luna3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/caramelo.webp" alt="Foto do cachorro Max">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Max 3</h2>
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="max3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col pet-hidden d-none">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/cookie.webp" alt="Foto da gata Bella">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Bella 3</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="bella3" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    </div>
                    <?php endif; ?>

                <div class="text-center mt-5">
                    <div class="spinner-border d-none mb-3" role="status" id="loadingSpinner">
                        <span class="visually-hidden">Carregando...</span>
                    </div>

                    <div class="btn-container" id="loadMoreBtnContainer">
                        <button class="adopt-btn" id="loadMorePetsBtn">
                            <div class="heart-background" style="user-select: none;">❤</div>
                            <span id="loadMoreText">Ver mais patinhas</span>
                        </button>
                         </div>
                </div>

            </div>
            
        </section>
    </main>

    <!-- Toast de Notificação (para o JS de favoritos) -->
    <div id="toast-notification" class="toast p-0" style="display: none; position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <div id="toast-icon" class="toast-icon"></div>
        <div class="toast-content">
            <p id="toast-message" class="toast-message">Pet favoritado.</p>
        </div>
        <div class="toast-progress-bar"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadMoreBtnContainer = document.getElementById('loadMoreBtnContainer');
            const loadMoreBtn = document.getElementById('loadMorePetsBtn'); // O botão em si
            const loadMoreText = document.getElementById('loadMoreText'); // O span com o texto
            const loadMoreSpinner = document.getElementById('loadingSpinner');
            const hiddenPets = document.querySelectorAll('#petsGrid .pet-hidden');
            let petsAreVisible = false; // Estado inicial: pets escondidos

            // Esconder o container do botão se não houver pets ocultos
            if (!hiddenPets || hiddenPets.length === 0) {
                 if(loadMoreBtnContainer) loadMoreBtnContainer.style.display = 'none';
                 if(loadMoreSpinner) loadMoreSpinner.style.display = 'none'; // Garante que spinner não apareça
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    // 1. Esconde o botão e desabilita
                    if(loadMoreBtnContainer) loadMoreBtnContainer.style.display = 'none';
                    loadMoreBtn.disabled = true;

                    // 2. Mostra o spinner
                    if(loadMoreSpinner) loadMoreSpinner.classList.remove('d-none');

                    // 3. Simula o carregamento (500ms)
                    setTimeout(() => {
                        // 4. Esconde o spinner
                        if(loadMoreSpinner) loadMoreSpinner.classList.add('d-none');

                        // 5. Alterna o estado de visibilidade dos pets
                        petsAreVisible = !petsAreVisible;

                        // 6. Mostra ou esconde os pets
                        hiddenPets.forEach(pet => {
                            if (petsAreVisible) {
                                pet.classList.remove('d-none');
                            } else {
                                pet.classList.add('d-none');
                            }
                        });

                        // 7. Atualiza o texto do botão
                        if(loadMoreText) {
                            loadMoreText.innerText = petsAreVisible ? "Ver Menos Patinhas" : "Ver Mais Patinhas";
                        }

                        // 8. Mostra o botão novamente e reabilita
                        if(loadMoreBtnContainer) loadMoreBtnContainer.style.display = 'inline-block'; // Ou 'block' se preferir
                        loadMoreBtn.disabled = false;

                    }, 500); // Delay de 500ms
                });
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const petsGrid = document.getElementById('petsGrid');
        
        // Função para mostrar o Toast (copiada do seu 'autenticacao.js')
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            const toastIcon = document.getElementById('toast-icon');
            const toastMessage = document.getElementById('toast-message');
            
            if (!toast || !toastIcon || !toastMessage) return;

            toastMessage.textContent = message;
            
            // Remove classes antigas
            toast.classList.remove('success', 'danger', 'warning');
            toastIcon.className = 'toast-icon'; 

            // Adiciona novas classes
            toast.classList.add(type);
            if (type === 'success') {
                toastIcon.classList.add('fas', 'fa-check');
            } else if (type === 'danger') {
                toastIcon.classList.add('fas', 'fa-times');
            } else if (type === 'warning') {
                toastIcon.classList.add('fas', 'fa-exclamation-triangle');
            }

            toast.style.display = 'block';
            
            // Reinicia a animação da barra de progresso
            const progressBar = toast.querySelector('.toast-progress-bar');
            progressBar.style.animation = 'none';
            void progressBar.offsetWidth; // Força o 'reflow'
            progressBar.style.animation = 'progress 3s linear forwards';

            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        if (petsGrid) {
            petsGrid.addEventListener('click', function(event) {
                // Verifica se o clique foi no coração
                const heartIcon = event.target.closest('.pet-like');
                
                if (heartIcon) {
                    // Impede que o clique no coração ative o link do card
                    event.preventDefault();
                    event.stopPropagation();
                    
                    const petId = heartIcon.dataset.petId;
                    toggleFavorite(petId, heartIcon);
                }
            });
        }

        async function toggleFavorite(petId, iconElement) {
            try {
                const response = await fetch('favoritar-pet.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ id_pet: petId })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Sucesso! Atualiza o ícone
                    if (result.action === 'favorited') {
                        iconElement.classList.remove('fa-regular');
                        iconElement.classList.add('fa-solid', 'favorited');
                        showToast(result.message, 'success');
                    } else if (result.action === 'unfavorited') {
                        iconElement.classList.remove('fa-solid', 'favorited');
                        iconElement.classList.add('fa-regular');
                        showToast(result.message, 'warning');
                    }
                } else {
                    // Se o erro for 403 (Não logado), redireciona
                    if (response.status === 403) {
                        showToast(result.message, 'danger');
                        setTimeout(() => {
                            window.location.href = 'login'; // Redireciona para a página de login
                        }, 1500);
                    } else {
                        // Outros erros
                        showToast(result.message || 'Erro ao favoritar.', 'danger');
                    }
                }
            } catch (error) {
                console.error('Erro no fetch:', error);
                showToast('Erro de conexão. Tente novamente.', 'danger');
            }
        }
    });
    </script>
</body>
</html>