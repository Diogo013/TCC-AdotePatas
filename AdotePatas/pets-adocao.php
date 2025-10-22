<?php
// INICIE A SESSÃO NO TOPO DE TODA PÁGINA QUE PRECISA SABER SE O USUÁRIO ESTÁ LOGADO
session_start();

// Supondo que, ao fazer login, você define $_SESSION['usuario_id'] ou algo similar.
$logado = isset($_SESSION['usuario_id']);


//if (!$logado){
//    header("Location: login");
//}

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
    crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                            <a class="nav-link" href="#">
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
                        <h1 class="titulo-adocao">Animais para Adoção</h1>
                    </div>
                </div>
                
                <div class="row">

                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Baunilha</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Baunilha</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Baunilha</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="pet-card">
                            <div class="pet-card-img">
                                <img src="images/index/baunilha.webp" alt="Foto da gata Baunilha">
                            </div>
                            <div class="pet-card-body">
                                <h2 class="pet-name">Baunilha</h2>
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="baunilha" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="caramelo" aria-label="Favoritar" role="button"></i>
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
                                <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                <i class="fa-regular fa-heart pet-like" data-pet-id="cookie" aria-label="Favoritar" role="button"></i>
                            </div>
                        </div>
                    </div>

                    </div>
            </div>
        </section>
        </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>