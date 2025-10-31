<?php
session_start();
include_once 'conexao.php'; // Inclui a conexão
include_once 'session.php'; // Inclui as funções de sessão

$base_path = '/TCC-AdotePatas/AdotePatas/';


// --- 1. IDENTIFICAR PET E USUÁRIO ---
$id_pet = $_GET['id'] ?? 0;
$id_pet = (int)$id_pet;

$id_usuario_logado = $_SESSION['user_id'] ?? null;
$pet = null;
$doador = null;
$caracteristicas = [];
$outros_pets = [];

if (empty($id_pet)) {
    // Redireciona se nenhum ID for fornecido
    header('Location: pets-adocao.php');
    exit;
}

// --- 2. BUSCAR DADOS DO PET E DOADOR ---
try {
    // Query principal: Pega o pet e também o nome/localização do doador (ONG ou Usuário)
    $sql = "SELECT 
                p.*, 
                COALESCE(o.nome, u.nome) as doador_nome,
                u.cidade as doador_cidade,     -- Apenas de 'usuario' (OK)
                u.estado as doador_estado,     -- Apenas de 'usuario' (OK)
                o.endereco as ong_endereco,    -- Apenas de 'ong' (OK)
                o.telefone as doador_telefone  -- Apenas de 'ong' (OK)
            FROM pet AS p
            LEFT JOIN ong AS o ON p.id_ong_fk = o.id_ong
            LEFT JOIN usuario AS u ON p.id_usuario_fk = u.id_usuario
            WHERE p.id_pet = :id_pet AND p.status_disponibilidade = 'disponivel'";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_pet' => $id_pet]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        // Se o pet não for encontrado, volta para a listagem
        header('Location: pets-adocao.php');
        exit;
    }

    // Decodifica as características salvas no banco
    $caracteristicas = json_decode($pet['caracteristicas'] ?? '[]', true);

    // --- 3. BUSCAR OUTROS PETS (SUGESTÕES) ---
    $sql_outros = "SELECT id_pet, nome, foto 
                   FROM pet 
                   WHERE id_pet != :id_pet AND status_disponibilidade = 'disponivel' 
                   LIMIT 4"; // Limita a 4 sugestões
    $stmt_outros = $conn->prepare($sql_outros);
    $stmt_outros->execute([':id_pet' => $id_pet]);
    $outros_pets = $stmt_outros->fetchAll(PDO::FETCH_ASSOC);
    
    // --- 4. VERIFICAR SE É FAVORITO (igual da listagem) ---
    $is_favorito = false;
    if ($id_usuario_logado) {
        $sql_fav = "SELECT id_favorito FROM favorito WHERE id_usuario = :id_usuario AND id_pet = :id_pet LIMIT 1";
        $stmt_fav = $conn->prepare($sql_fav);
        $stmt_fav->execute([':id_usuario' => $id_usuario_logado, ':id_pet' => $id_pet]);
        if ($stmt_fav->fetch()) {
            $is_favorito = true;
        }
    }

} catch (PDOException $e) {
    // Em caso de erro, redireciona
    echo("Erro em pet-detalhe.php: " . $e->getMessage());
    
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pet['nome']); ?> - Adote Patas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png"/>
    
    <!-- CSS da página de perfil (para o toast) e o novo CSS de detalhes -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global/toast.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/pages/detalhe-pet/detalhe-pet.css"> <!-- NOVO CSS -->
    <style>
    
    </style>
</head>
<body class="pet-detalhe-body">

    <!-- NAV/HEADER (você pode incluir seu header.php aqui se tiver) -->
    <header class="main-header shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="<?php echo $base_path; ?>./">
                    <img src="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png" alt="Logo Adote Pet" class="logo-img">
                </a>
                <a href="perfil.php" class="ms-auto me-3">
                    <i class="fa-regular fa-circle-user" style="font-size: 2.5rem; color: #666;"></i>
                </a>
            </div>
        </nav>
    </header>

    <!-- Toast (para o JS de favoritar) -->
    <div id="toast-notification" class="toast p-0" style="display: none;">
        <div id="toast-icon" class="toast-icon"></div>
        <div class="toast-content"><p id="toast-message" class="toast-message"></p></div>
        <div class="toast-progress-bar"></div>
    </div>

    <main class="container my-4">
        
        <!-- Breadcrumb (Adote > Caramelo) -->
        <nav aria-label="breadcrumb" class="detalhe-breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_path; ?>pets-adocao.php">Adote</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pet['nome']); ?></li>
            </ol>
        </nav>

        <!-- Conteúdo Principal (Duas Colunas) -->
        <div class="detalhe-main-content">
            
            <!-- Coluna da Esquerda (Fotos) -->
            <div class="detalhe-fotos">
                <div class="detalhe-foto-principal shadow-sm">
                    <img src="<?php echo $base_path; ?><?php echo htmlspecialchars($pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" 
                         alt="Foto principal de <?php echo htmlspecialchars($pet['nome']); ?>"
                         onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
                </div>
                <!-- Galeria de Thumbnails (usando a mesma foto como placeholder) -->
                <div class="detalhe-thumbnails">
                    <img class="shadow-sm active" src="<?php echo $base_path; ?><?php echo htmlspecialchars($pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" alt="thumbnail 1">
                    <img class="shadow-sm" src="<?php echo $base_path; ?><?php echo htmlspecialchars($pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" alt="thumbnail 2">
                    <img class="shadow-sm" src="<?php echo $base_path; ?><?php echo htmlspecialchars($pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" alt="thumbnail 3">
                    <img class="shadow-sm" src="<?php echo $base_path; ?><?php echo htmlspecialchars($pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" alt="thumbnail 4">
                </div>
            </div>

            <!-- Coluna da Direita (Info Card) -->
            <div class="detalhe-info-card shadow-sm">
                <div class="d-flex justify-content-between align-items-start">
                    <h1>
                        <?php echo htmlspecialchars($pet['nome']); ?>
                        <!-- Ícone de Gênero -->
                        <?php if ($pet['sexo'] == 'femea'): ?>
                            <i class="fa-solid fa-venus pet-gender-female" title="Fêmea"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-mars pet-gender-male" title="Macho"></i>
                        <?php endif; ?>
                    </h1>
                    <!-- Coração de Favorito -->
                    <i class="pet-like <?php echo $is_favorito ? 'fa-solid fa-heart favorited' : 'fa-regular fa-heart'; ?>" 
                       data-pet-id="<?php echo $pet['id_pet']; ?>" 
                       aria-label="Favoritar" 
                       role="button">
                    </i>
                </div>

                <!-- Tags de Info -->
                <div class="info-tags-container">
                    <span><?php echo htmlspecialchars($pet['idade']); ?> Ano(s)</span>
                    <span class="<?php echo ($pet['status_vacinacao'] == 'sim') ? 'tag-vacinado' : ''; ?>">
                        <?php echo ($pet['status_vacinacao'] == 'sim') ? 'Vacinado' : 'Não Vacinado'; ?>
                    </span>
                    <span class="<?php echo ($pet['status_castracao'] == 'sim') ? 'tag-castrado' : ''; ?>">
                        <?php echo ($pet['status_castracao'] == 'sim') ? 'Castrado' : 'Não Castrado'; ?>
                    </span>
                    <span>Porte <?php echo htmlspecialchars($pet['porte']); ?></span>
                    <span><?php echo htmlspecialchars($pet['raca']); ?></span>
                </div>

                <!-- Botão Adotar -->
                <a href="formulario-adocao.php?id_pet=<?php echo $pet['id_pet']; ?>" class="btn-adotar">
                    Quero Adotar!
                </a>

                <!-- Localização -->
                <div class="location">
                    <i class="fa-solid fa-location-dot"></i>
                    <?php 
                        $cidade = $pet['doador_cidade'] ?? 'Localização';
                        $estado = $pet['doador_estado'] ?? 'não informada';
                        echo htmlspecialchars($cidade) . ' - ' . htmlspecialchars($estado);
                    ?>
                </div>
            </div>
        </div>

        <!-- Seção "Sobre" -->
        <div class="detalhe-secao shadow-sm">
            <h2>Sobre o <?php echo htmlspecialchars($pet['nome']); ?></h2>
            <p><?php echo nl2br(htmlspecialchars($pet['comportamento'] ?? 'Nenhuma descrição fornecida.')); ?></p>
        </div>

        <!-- Seção "Características" -->
        <?php if (!empty($caracteristicas)): ?>
        <div class="detalhe-secao shadow-sm">
            <h2>Características</h2>
            <div class="caracteristicas-container">
                <?php foreach ($caracteristicas as $carac): ?>
                    <!-- Usando as classes de cor do modal-caracteristicas.css -->
                    <span class="char-tag-display">
                        <?php echo htmlspecialchars($carac); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção "Outros Pets" -->
        <?php if (!empty($outros_pets)): ?>
        <div class="detalhe-secao">
            <h2>Outros Pets que Você Pode Gostar</h2>
            <div class="outros-pets-grid">
                <?php foreach ($outros_pets as $outro_pet): ?>
                    <a href="<?php echo $base_path; ?>pet-detalhe/<?php echo $outro_pet['id_pet']; ?>" class="outro-pet-card shadow-sm">
                        <img src="<?php echo $base_path; ?><?php echo htmlspecialchars($outro_pet['foto'] ?? 'images/perfil/teste.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($outro_pet['nome']); ?>"
                             onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
                        <h3><?php echo htmlspecialchars($outro_pet['nome']); ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script de Favoritar (copiado do pets-adocao.php) -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para mostrar o Toast
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

        const heartIcon = document.querySelector('.pet-like');
        if (heartIcon) {
            heartIcon.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                const petId = heartIcon.dataset.petId;
                toggleFavorite(petId, heartIcon);
            });
        }

        async function toggleFavorite(petId, iconElement) {
            try {
                const response = await fetch('<?php echo $base_path; ?>favoritar-pet.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
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
