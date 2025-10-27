<?php
session_start();
include_once 'conexao.php'; // 1. Inclui a conexão com o banco

// 2. Segurança: Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    // Se não estiver logado, redireciona para a página de login
    header("Location: login");
    exit;
}

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
                    <ul class="navbar-nav d-flex align-items-center">
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

                <section class="search-filter-section mb-4">
                    <form class="search-filter-container" role="search">
                        <div class="search-bar-wrapper">
                            <input type="search" class="form-control search-input"
                                   placeholder="Pesquise pelo nome ou adicione filtros"
                                   aria-label="Pesquisar pet pelo nome">
                            <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        </div>

                        <button type="button" class="btn filter-btn"
                                data-bs-toggle="collapse"
                                data-bs-target="#filterOptionsCollapse"
                                aria-expanded="false"
                                aria-controls="filterOptionsCollapse"
                                aria-label="Mostrar/Esconder filtros de pesquisa">
                            <span>Filtros</span>
                            <i class="fa-solid fa-sliders"></i>
                        </button>
                    </form>
                </section>

                <div class="collapse mb-4" id="filterOptionsCollapse">
                    <div class="filter-options-container card card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 filter-category mb-3">
                                <h5>1. Temperamento</h5>
                                <p class="filter-category-description">O comportamento principal do pet</p>
                                <div class="filter-category-options">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="docil" id="temp_docil"><label class="form-check-label" for="temp_docil">Dócil</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="brincalhao" id="temp_brincalhao"><label class="form-check-label" for="temp_brincalhao">Brincalhão</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="calmo" id="temp_calmo"><label class="form-check-label" for="temp_calmo">Calmo</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="carinhoso" id="temp_carinhoso"><label class="form-check-label" for="temp_carinhoso">Carinhoso</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="independente" id="temp_independente"><label class="form-check-label" for="temp_independente">Independente</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="timido" id="temp_timido"><label class="form-check-label" for="temp_timido">Tímido</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="sociavel" id="temp_sociavel"><label class="form-check-label" for="temp_sociavel">Sociável</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="protetor" id="temp_protetor"><label class="form-check-label" for="temp_protetor">Protetor</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="curioso" id="temp_curioso"><label class="form-check-label" for="temp_curioso">Curioso</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="alerta" id="temp_alerta"><label class="form-check-label" for="temp_alerta">Alerta</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="silencioso" id="temp_silencioso"><label class="form-check-label" for="temp_silencioso">Silencioso</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="vocal" id="temp_vocal"><label class="form-check-label" for="temp_vocal">Vocal</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="medroso" id="temp_medroso"><label class="form-check-label" for="temp_medroso">Medroso</label></div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="filter-category mb-3">
                                    <h5>2. Nível de Energia</h5>
                                    <p class="filter-category-description">Necessidade de atividade física</p>
                                    <div class="filter-category-options">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="baixa_energia" id="energia_baixa"><label class="form-check-label" for="energia_baixa">Baixa Energia</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="media_energia" id="energia_media"><label class="form-check-label" for="energia_media">Média Energia</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="alta_energia" id="energia_alta"><label class="form-check-label" for="energia_alta">Alta Energia</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="hiperativo" id="energia_hiperativo"><label class="form-check-label" for="energia_hiperativo">Hiperativo</label></div>
                                    </div>
                                </div>
                                <div class="filter-category">
                                    <h5>3. Sociabilidade</h5>
                                    <p class="filter-category-description">Interação com outros</p>
                                    <div class="filter-category-options">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="com_criancas" id="soc_criancas"><label class="form-check-label" for="soc_criancas">Com Crianças</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="com_caes" id="soc_caes"><label class="form-check-label" for="soc_caes">Com Cães</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="com_gatos" id="soc_gatos"><label class="form-check-label" for="soc_gatos">Com Gatos</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="com_estranhos" id="soc_estranhos"><label class="form-check-label" for="soc_estranhos">Com Estranhos</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="pet_unico" id="soc_unico"><label class="form-check-label" for="soc_unico">Pet Único</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="com_idosos" id="soc_idosos"><label class="form-check-label" for="soc_idosos">Com Idosos</label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="filter-category mb-3">
                                    <h5>5. Cuidados Especiais</h5>
                                    <p class="filter-category-description">Necessidades que exigem atenção extra</p>
                                    <div class="filter-category-options">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="medicacao" id="cuidado_medicacao"><label class="form-check-label" for="cuidado_medicacao">Medicação</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="dieta_especial" id="cuidado_dieta"><label class="form-check-label" for="cuidado_dieta">Dieta Especial</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="alergia" id="cuidado_alergia"><label class="form-check-label" for="cuidado_alergia">Alergia</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="def_fisica" id="cuidado_def_fisica"><label class="form-check-label" for="cuidado_def_fisica">Deficiência Física</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="def_visual" id="cuidado_def_visual"><label class="form-check-label" for="cuidado_def_visual">Deficiência Visual</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="def_auditiva" id="cuidado_def_auditiva"><label class="form-check-label" for="cuidado_def_auditiva">Deficiência Auditiva</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="pos_operatorio" id="cuidado_pos_op"><label class="form-check-label" for="cuidado_pos_op">Pós-operatório</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="traumatico" id="cuidado_trauma"><label class="form-check-label" for="cuidado_trauma">Traumático</label></div>
                                    </div>
                                </div>
                                <div class="filter-category">
                                    <h5>6. Treinamento e Hábitos</h5>
                                    <p class="filter-category-description">Nível de educação e costumes</p>
                                    <div class="filter-category-options">
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="adestrado" id="habito_adestrado"><label class="form-check-label" for="habito_adestrado">Adestrado</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="educado_higiene" id="habito_higiene"><label class="form-check-label" for="habito_higiene">Educado (higiene)</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="em_treinamento" id="habito_treinamento"><label class="form-check-label" for="habito_treinamento">Em Treinamento</label></div>
                                        <div class="form-check"><input class="form-check-input" type="checkbox" value="bom_viajante" id="habito_viajante"><label class="form-check-label" for="habito_viajante">Bom Viajante</label></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 filter-category mb-3">
                                <h5>7. Ambiente Ideal</h5>
                                <p class="filter-category-description">Tipo de lar recomendado</p>
                                <div class="filter-category-options">
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="apartamento" id="ambiente_apto"><label class="form-check-label" for="ambiente_apto">Para Apartamento</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="precisa_quintal" id="ambiente_quintal"><label class="form-check-label" for="ambiente_quintal">Precisa Quintal</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="para_casa" id="ambiente_casa"><label class="form-check-label" for="ambiente_casa">Para Casa</label></div>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" value="primeira_adocao" id="ambiente_primeira"><label class="form-check-label" for="ambiente_primeira">1ª Adoção</label></div>
                                </div>
                            </div>
                        </div> </div> </div> <?php if (!empty($erro)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo htmlspecialchars($erro); ?>
                    </div>
                <?php elseif (empty($pets)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fa-solid fa-paw fa-3x mb-3"></i>
                        <h5 class="mb-1">Nenhum pet disponível para adoção no momento.</h5>
                        <p>Volte em breve!</p>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4" id="petsGrid">
                        <?php foreach ($pets as $pet): ?>
                            <?php
                                // Verifica se este pet está favoritado
                                $is_favorito = in_array($pet['id_pet'], $favoritos_usuario);
                                // Define um caminho padrão para a foto se não houver uma específica
                                $foto_path = !empty($pet['foto']) ? $pet['foto'] : 'images/placeholder-pet.png'; // Crie uma imagem placeholder
                                $alt_text = "Foto de " . htmlspecialchars($pet['nome']);
                            ?>
                            <div class="col">
                                <a href="pet-detalhe.php?id=<?php echo $pet['id_pet']; ?>" class="pet-card-link">
                                    <div class="pet-card">
                                        <div class="pet-card-img">
                                            <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="<?php echo $alt_text; ?>">
                                        </div>
                                        <div class="pet-card-body">
                                            <h2 class="pet-name"><?php echo htmlspecialchars($pet['nome']); ?></h2>
                                            <?php if (!empty($pet['sexo'])): ?>
                                                <?php if ($pet['sexo'] == 'femea'): ?>
                                                    <i class="fa-solid fa-venus pet-gender-female" aria-label="Fêmea" title="Fêmea"></i>
                                                <?php else: // 'macho' ?>
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

                         <div class="col pet-hidden d-none">
                            <div class="pet-card">
                                <div class="pet-card-img"><img src="images/index/caramelo.webp" alt="Foto do cachorro Zeus"></div>
                                <div class="pet-card-body">
                                    <h2 class="pet-name">Zeus 2</h2>
                                    <i class="fa-solid fa-mars pet-gender-male" aria-label="Macho" title="Macho"></i>
                                    <i class="fa-regular fa-heart pet-like" data-pet-id="zeus2" aria-label="Favoritar" role="button"></i>
                                </div>
                            </div>
                         </div>
                        </div> <div class="text-center mt-5">
                        <div class="spinner-border d-none mb-3" role="status" id="loadingSpinner">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <div class="btn-container" id="loadMoreBtnContainer" style="display: none;">
                            <button class="adopt-btn" id="loadMorePetsBtn">
                                <div class="heart-background" style="user-select: none;">❤</div>
                                <span id="loadMoreText">Ver mais patinhas</span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

            </div> </section> </main>

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
            // --- Script do botão "Ver Mais/Menos" ---
            const loadMoreBtnContainer = document.getElementById('loadMoreBtnContainer');
            const loadMoreBtn = document.getElementById('loadMorePetsBtn');
            const loadMoreText = document.getElementById('loadMoreText');
            const loadMoreSpinner = document.getElementById('loadingSpinner');
            // Seleciona APENAS os cards que foram marcados como ocultos inicialmente
            const hiddenPets = document.querySelectorAll('#petsGrid .pet-hidden');
            let petsAreVisible = false;

            // Mostra o botão APENAS se houver pets ocultos para mostrar/esconder
            if (hiddenPets && hiddenPets.length > 0 && loadMoreBtnContainer) {
                loadMoreBtnContainer.style.display = 'inline-block'; // Mostra o container
            } else if (loadMoreBtnContainer) {
                 loadMoreBtnContainer.style.display = 'none'; // Garante que está escondido se não houver pets ocultos
            }

            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    if(loadMoreBtnContainer) loadMoreBtnContainer.style.display = 'none';
                    loadMoreBtn.disabled = true;
                    if(loadMoreSpinner) loadMoreSpinner.classList.remove('d-none');

                    setTimeout(() => {
                        if(loadMoreSpinner) loadMoreSpinner.classList.add('d-none');
                        petsAreVisible = !petsAreVisible;
                        hiddenPets.forEach(pet => {
                            pet.classList.toggle('d-none', !petsAreVisible);
                        });
                        if(loadMoreText) {
                            loadMoreText.innerText = petsAreVisible ? "Ver Menos Patinhas" : "Ver Mais Patinhas";
                        }
                        if(loadMoreBtnContainer) loadMoreBtnContainer.style.display = 'inline-block';
                        loadMoreBtn.disabled = false;
                    }, 500);
                });
            }

            // --- Script do Favoritar ---
            const petsGrid = document.getElementById('petsGrid');

            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast-notification');
                const toastIcon = document.getElementById('toast-icon');
                const toastMessage = document.getElementById('toast-message');
                if (!toast || !toastIcon || !toastMessage) return;
                toastMessage.textContent = message;
                toast.classList.remove('success', 'danger', 'warning');
                toastIcon.className = 'toast-icon';
                toast.classList.add(type);
                if (type === 'success') toastIcon.classList.add('fas', 'fa-check');
                else if (type === 'danger') toastIcon.classList.add('fas', 'fa-times');
                else if (type === 'warning') toastIcon.classList.add('fas', 'fa-exclamation-triangle');
                toast.style.display = 'block';
                const progressBar = toast.querySelector('.toast-progress-bar');
                progressBar.style.animation = 'none';
                void progressBar.offsetWidth;
                progressBar.style.animation = 'progress 3s linear forwards';
                setTimeout(() => { toast.style.display = 'none'; }, 3000);
            }

            if (petsGrid) {
                petsGrid.addEventListener('click', function(event) {
                    const heartIcon = event.target.closest('.pet-like');
                    if (heartIcon) {
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
                        if (response.status === 403) {
                            showToast(result.message, 'danger');
                            setTimeout(() => { window.location.href = 'login'; }, 1500);
                        } else {
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