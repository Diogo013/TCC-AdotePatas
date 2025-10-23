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

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="petsGrid">

                    <div class="col">
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
</body>
</html>