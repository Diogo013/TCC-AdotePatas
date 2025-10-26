<?php
// Inclui a conexão e a sessão
include_once 'conexao.php';
include_once 'session.php';

// Protege a página: Somente usuários logados podem acessar
requerer_login();

// Pega IDs da sessão
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];

// Pega o ID do pet da URL
$id_pet_para_editar = $_GET['id'] ?? null;
$pet = null;
$erro = '';

if (empty($id_pet_para_editar)) {
    // Se não tiver ID, volta para 'meus-pets' com erro
    $_SESSION['mensagem_status'] = "ID do pet não fornecido.";
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// --- Busca os dados do Pet e VERIFICA A PERMISSÃO ---
try {
    $sql = "SELECT * FROM pet WHERE id_pet = :id_pet";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_pet' => $id_pet_para_editar]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pet) {
        throw new Exception("Pet não encontrado.");
    }

    // VERIFICAÇÃO DE PROPRIEDADE
    $tem_permissao = false;
    if ($user_tipo == 'adotante' && $pet['id_usuario_fk'] == $user_id) {
        $tem_permissao = true;
    } elseif ($user_tipo == 'protetor' && $pet['id_ong_fk'] == $user_id) {
        $tem_permissao = true;
    }

    if (!$tem_permissao) {
        throw new Exception("Você não tem permissão para editar este pet.");
    }

} catch (Exception $e) {
    $_SESSION['mensagem_status'] = $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: perfil.php?page=meus-pets');
    exit;
}

// --- LÊ MENSAGENS DE ERRO (Vindas do atualizar-pet.php) ---
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';
unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Pet - Adote Patas</title>
    <!-- Links de CSS (Idênticos ao cadastrar-pet.php) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <!-- Estilo customizado para o input[file] -->
    <style>
        .file-input-label {
            cursor: pointer; display: flex; align-items: center; gap: 10px;
            border: 2px dashed var(--cor-vermelho-claro); background-color: #fff8f8;
            transition: all 0.3s ease;
        }
        .file-input-label:hover { background-color: #fff0f0; border-color: var(--cor-vermelho); }
        .file-input-label i { color: var(--cor-vermelho); }
        .file-input-label span { color: #555; font-size: 0.95rem; }
        .current-photo { max-width: 100px; max-height: 100px; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<!-- Toast Notification -->
<div id="toast-notification" class="toast p-0" style="display: none;">
    <div id="toast-icon" class="toast-icon"></div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>

<!-- Botão Voltar -->
<a href="perfil.php?page=meus-pets" class="btn-voltar" title="Voltar para Meus Pets">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Voltar</span>
</a>

<!-- Fundo -->
<img src="images/cadastro-login/pata.png" alt="Desenho de Pata" class="pata-fundo">

<div class="w-full max-w-2xl mx-auto">
    
    <!-- Cabeçalho -->
    <div class="w-full flex items-center justify-between mb-6 relative">
        <div>
            <a href="./" title="Voltar para a página inicial">
                <img src="images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" width="70" height="70">
            </a>
        </div>
        <div class="absolute inset-x-0 text-center">
            <h1 id="page-title" class="text-xl md:text-4xl font-bold text-[#666662]">Editar Pet</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <!-- Card Principal -->
    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">
        
        <!-- Div para carregar dados do PHP para o JS (para o Toast) -->
        <?php if (!empty($mensagem_status)): ?>
            <div id="php-data" 
                 data-message="<?php echo htmlspecialchars($mensagem_status); ?>" 
                 data-type="<?php echo htmlspecialchars($tipo_mensagem); ?>" 
                 style="display: none;">
            </div>
        <?php endif; ?>

        <!-- Formulário de Edição -->
        <form action="atualizar-pet.php" method="post" enctype="multipart/form-data" id="form-edit-pet" class="space-y-6">
            
            <!-- IDs escondidos -->
            <input type="hidden" name="id_pet" value="<?php echo $pet['id_pet']; ?>">
            <input type="hidden" name="foto_atual" value="<?php echo htmlspecialchars($pet['foto']); ?>">

            <!-- Linha 1: Nome e Idade -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nome" class="sr-only">Nome do Pet</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Pet" required class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['nome']); ?>">
                </div>
                <div>
                    <label for="idade" class="sr-only">Idade (anos)</label>
                    <input type="number" name="idade" id="idade" placeholder="Idade (anos)" required min="0" class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['idade']); ?>">
                </div>
            </div>

            <!-- Linha 2: Espécie e Gênero -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="especie" class="sr-only">Espécie</label>
                    <select name="especie" id="especie" required class="input-style w-full">
                        <option value="cachorro" <?php echo ($pet['especie'] == 'cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                        <option value="gato" <?php echo ($pet['especie'] == 'gato') ? 'selected' : ''; ?>>Gato</option>
                        <option value="outro" <?php echo ($pet['especie'] == 'outro') ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <div>
                    <label for="sexo" class="sr-only">Sexo</label>
                    <select name="sexo" id="sexo" required class="input-style w-full">
                        <option value="macho" <?php echo ($pet['sexo'] == 'macho') ? 'selected' : ''; ?>>Macho</option>
                        <option value="femea" <?php echo ($pet['sexo'] == 'femea') ? 'selected' : ''; ?>>Fêmea</option>
                    </select>
                </div>
            </div>

            <!-- Linha 3: Porte e Raça -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="porte" class="sr-only">Porte</label>
                    <select name="porte" id="porte" required class="input-style w-full">
                        <option value="pequeno" <?php echo ($pet['porte'] == 'pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                        <option value="medio" <?php echo ($pet['porte'] == 'medio') ? 'selected' : ''; ?>>Médio</option>
                        <option value="grande" <?php echo ($pet['porte'] == 'grande') ? 'selected' : ''; ?>>Grande</option>
                    </select>
                </div>
                <div>
                    <label for="raca" class="sr-only">Raça (Ex: SRD)</label>
                    <input type="text" name="raca" id="raca" placeholder="Raça (Ex: SRD)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['raca']); ?>">
                </div>
            </div>

            <!-- Linha 4: Cor e Vacinado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cor" class="sr-only">Cor</label>
                    <input type="text" name="cor" id="cor" placeholder="Cor (Ex: Caramelo)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($pet['cor']); ?>">
                </div>
                <div>
                    <label for="status_vacinacao" class="sr-only">Vacinado?</label>
                    <select name="status_vacinacao" id="status_vacinacao" required class="input-style w-full">
                        <option value="sim" <?php echo ($pet['status_vacinacao'] == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($pet['status_vacinacao'] == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
            </div>
            
            <!-- Linha 5: Castrado e Status Disponibilidade -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status_castracao" class="sr-only">Castrado?</label>
                    <select name="status_castracao" id="status_castracao" required class="input-style w-full">
                        <option value="sim" <?php echo ($pet['status_castracao'] == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($pet['status_castracao'] == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
                <div>
                    <label for="status_disponibilidade" class="sr-only">Status</label>
                    <select name="status_disponibilidade" id="status_disponibilidade" required class="input-style w-full">
                        <option value="disponivel" <?php echo ($pet['status_disponibilidade'] == 'disponivel') ? 'selected' : ''; ?>>Disponível</option>
                        <option value="adotado" <?php echo ($pet['status_disponibilidade'] == 'adotado') ? 'selected' : ''; ?>>Adotado</option>
                        <option value="indisponivel" <?php echo ($pet['status_disponibilidade'] == 'indisponivel') ? 'selected' : ''; ?>>Indisponível</option>
                    </select>
                </div>
            </div>
            
            <!-- Linha 6: Foto desabilitada temporariamente
            <div>
                <label for="foto" class="input-style w-full file-input-label">
                    <i class="fas fa-upload"></i>
                    <span id="file-name-span">Trocar foto (opcional)</span>
                </label>
                <input type="file" name="foto" id="foto" class="hidden" accept="image/png, image/jpeg">
                <div>
                    <span class="text-sm text-gray-600">Foto atual:</span>
                    <img src="<?php// echo htmlspecialchars($pet['foto']); ?>" alt="Foto atual" class="current-photo" onerror="this.style.display='none'">
                </div>
            </div>
            -->

            <!-- Linha 7: Comportamento -->
            <div>
                <label for="comportamento" class="sr-only">Comportamento (Ex: Dócil, adora crianças...)</label>
                <textarea name="comportamento" id="comportamento" rows="4" placeholder="Conte um pouco sobre o pet..." class="input-style w-full"><?php echo htmlspecialchars($pet['comportamento']); ?></textarea>
            </div>

            <!-- Linha 8: Botão de Envio -->
            <div class="flex justify-center w-55 mx-auto">
                <button type="submit" class="adopt-btn">
                    <div class="heart-background">❤</div><span>Salvar Alterações</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
<!-- O autenticacao.js vai cuidar de mostrar o toast de sucesso ou erro -->
<script src="assets/js/pages/autenticacao/autenticacao.js" type="module"></script>

<!-- Script local para o input[file] -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const fileInput = document.getElementById('foto');
        const fileNameSpan = document.getElementById('file-name-span');

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    fileNameSpan.textContent = fileInput.files[0].name;
                } else {
                    fileNameSpan.textContent = 'Trocar foto (opcional)';
                }
            });
        }
    });
</script>

</body>
</html>
