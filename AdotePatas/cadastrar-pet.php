<?php
// Inclui a conexão e a sessão
include_once 'conexao.php'; //
include_once 'session.php'; //

// Protege a página: Somente usuários logados podem acessar
requerer_login(); //

// --- LÊ MENSAGENS DA SESSÃO (PADRÃO PRG) ---
// Usamos a mesma lógica do autenticacao.php para mostrar mensagens
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$tipo_mensagem = $_SESSION['tipo_mensagem'] ?? '';

unset($_SESSION['mensagem_status']);
unset($_SESSION['tipo_mensagem']);

// --- INICIALIZA VARIÁVEIS DO FORMULÁRIO ---
// Isso é para manter os dados no formulário caso a validação falhe
$nome = '';
$especie = '';
$idade = '';
$porte = '';
$sexo = ''; 
$raca = '';
$cor = '';
$status_vacinacao = '';
$status_castracao = '';
$comportamento = '';
$caracteristicas = [];

// --- PROCESSAMENTO DO FORMULÁRIO (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Coleta de dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $especie = trim($_POST['especie'] ?? ''); //
    $idade = trim($_POST['idade'] ?? ''); //
    $porte = trim($_POST['porte'] ?? ''); //
    $sexo = trim($_POST['sexo'] ?? '');
    $raca = trim($_POST['raca'] ?? 'Não definida'); //
    $cor = trim($_POST['cor'] ?? ''); //
    $status_vacinacao = trim($_POST['status_vacinacao'] ?? ''); //
    $status_castracao = trim($_POST['status_castracao'] ?? ''); //
    $comportamento = trim($_POST['comportamento'] ?? ''); //
    $caracteristicas = $_POST['caracteristicas'] ?? [];
    
    // Coleta dados da sessão
    $id_usuario_logado = $_SESSION['user_id'];
    $tipo_usuario_logado = $_SESSION['user_tipo']; // 'adotante' ou 'protetor'

    $erros = [];
    $caminho_foto_db = null; // Caminho da foto para salvar no banco

    // 2. Validações (PHP)
    
    // Validações de campos obrigatórios
    if (empty($nome)) $erros[] = "O campo 'Nome' é obrigatório.";
    if (empty($especie)) $erros[] = "O campo 'Espécie' é obrigatório.";
    if (empty($idade)) $erros[] = "O campo 'Idade' é obrigatório.";
    if (empty($sexo)) $erros[] = "O campo 'Sexo' é obrigatório.";
    if (empty($porte)) $erros[] = "O campo 'Porte' é obrigatório.";
    if (empty($status_vacinacao)) $erros[] = "O campo 'Vacinado' é obrigatório.";
    if (empty($status_castracao)) $erros[] = "O campo 'Castrado' é obrigatório.";

    // Validações de formato
    if (!empty($idade) && !filter_var($idade, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]])) {
        $erros[] = "A idade deve ser um número válido (0 ou mais).";
    }
    
    // Validações dos ENUMs (baseado no adote_patas.sql)
    $portes_validos = ['pequeno', 'medio', 'grande'];
    $especies_validas = ['cachorro', 'gato'];
    $status_validos = ['sim', 'nao'];
    $sexos_validos = ['macho', 'femea'];

    if (!empty($porte) && !in_array($porte, $portes_validos)) $erros[] = "Porte inválido.";
    if (!empty($especie) && !in_array($especie, $especies_validas)) $erros[] = "Espécie inválida.";
    if (!empty($status_vacinacao) && !in_array($status_vacinacao, $status_validos)) $erros[] = "Status de vacinação inválido.";
    if (!empty($status_castracao) && !in_array($status_castracao, $status_validos)) $erros[] = "Status de castração inválido.";

    if (count($caracteristicas) > 5) {
        $erros[] = "Você só pode selecionar até 5 características.";
    }

    // 3. Validação e Upload da Foto !ATENÇÂO! Desativado temporariamente
    //if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    //    $upload_dir = 'uploads/pets/';
    //    if (!is_dir($upload_dir)) {
    //        mkdir($upload_dir, 0755, true); // Cria o diretório se não existir
    //    }
//
    //    $file_info = $_FILES['foto'];
    //    $file_name = $file_info['name'];
    //    $file_tmp = $file_info['tmp_name'];
    //    $file_size = $file_info['size'];
    //    $file_ext_check = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    //    
    //    $extensoes_permitidas = ['jpg', 'jpeg', 'png'];
//
    //    if (!in_array($file_ext_check, $extensoes_permitidas)) {
    //        $erros[] = "Formato de imagem inválido. (Apenas JPG, JPEG, PNG)";
    //    }
    //    if ($file_size > 5 * 1024 * 1024) { // 5 MB
    //        $erros[] = "A imagem é muito grande (Máx: 5MB).";
    //    }
//
    //    if (empty($erros)) {
    //        // Cria um nome único para o arquivo para evitar sobreposição
    //        $novo_nome_arquivo = uniqid('', true) . '.' . $file_ext_check;
    //        $caminho_completo = $upload_dir . $novo_nome_arquivo;
//
    //        if (move_uploaded_file($file_tmp, $caminho_completo)) {
    //            $caminho_foto_db = $caminho_completo; // Salva o caminho para o DB
    //        } else {
    //            $erros[] = "Falha ao salvar a imagem. Tente novamente.";
    //        }
    //    }z
    //} else {
    //    $erros[] = "A foto do pet é obrigatória.";
    //}
    // 


    // 4. Decisão Final: Inserir no Banco ou Mostrar Erro
    
    if (!empty($erros)) {
        // Se houver erros, mostra a primeira mensagem
        $mensagem_status = $erros[0];
        $tipo_mensagem = 'danger';
    } else {
        // Se NÃO houver erros, insere no banco
        
        // Define quem é o "dono" do pet (ONG ou Usuário)
        $id_usuario_fk = null;
        $id_ong_fk = null;

        if ($tipo_usuario_logado == 'protetor') {
            $id_ong_fk = $id_usuario_logado; //
        } else {
            // Assumindo 'adotante' ou 'doador'
            $id_usuario_fk = $id_usuario_logado;
        }

        $caracteristicas_json = json_encode($caracteristicas, JSON_UNESCAPED_UNICODE);

        try {
            $sql = "INSERT INTO pet (nome, especie, sexo, idade, porte, raca, cor, status_vacinacao, status_castracao, comportamento, foto, id_usuario_fk, id_ong_fk, status_disponibilidade, caracteristicas) 
                    VALUES (:nome, :especie, :sexo, :idade, :porte, :raca, :cor, :status_vacinacao, :status_castracao, :comportamento, :foto, :id_usuario_fk, :id_ong_fk, 'disponivel', :caracteristicas)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':especie' => $especie,
                ':sexo' => $sexo, 
                ':idade' => $idade,
                ':porte' => $porte,
                ':raca' => $raca,
                ':cor' => $cor,
                ':status_vacinacao' => $status_vacinacao,
                ':status_castracao' => $status_castracao,
                ':comportamento' => $comportamento,
                ':foto' => $caminho_foto_db,
                ':id_usuario_fk' => $id_usuario_fk,
                ':id_ong_fk' => $id_ong_fk,
                ':caracteristicas' => $caracteristicas_json
            ]);

            // --- INÍCIO DA LÓGICA PRG (Post-Redirect-Get) ---
            //
            $_SESSION['mensagem_status'] = "Pet cadastrado com sucesso!";
            $_SESSION['tipo_mensagem'] = 'success';

            // Redireciona para o dashboard ou para a página "meus pets"
            header("Location: perfi?page=meus-pets"); // posteriormente alterar para "meus-pets.php"
            exit();
            
        } catch (PDOException $e) {
            $mensagem_status = "Ocorreu uma falha no banco de dados. Tente novamente.";
            $tipo_mensagem = 'danger';
            error_log("Erro no cadastro de pet: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Pet - Adote Patas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="images/global/Logo-AdotePatas.png"/>
    <link rel="stylesheet" href="assets/css/pages/cadastro-pet/caracteristica.css">
    <link rel="stylesheet" href="assets/css/pages/autenticacao/autenticacao.css">


    <style>
        .file-input-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 2px dashed var(--cor-vermelho-claro);
            background-color: #fff8f8;
            transition: all 0.3s ease;
        }
        .file-input-label:hover {
            background-color: #fff0f0;
            border-color: var(--cor-vermelho);
        }
        .file-input-label i {
            color: var(--cor-vermelho);
        }
        .file-input-label span {
            color: #555;
            font-size: 0.95rem;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4">

<div id="toast-notification" class="toast p-0" style="display: none;">
    <div id="toast-icon" class="toast-icon"></div>
    <div class="toast-content">
        <p id="toast-message" class="toast-message">Mensagem de exemplo.</p>
    </div>
    <div class="toast-progress-bar"></div>
</div>

<a href="perfil.php?page=meus-pets" class="btn-voltar" title="Voltar para a página inicial">
    <i class="fa-solid fa-arrow-left"></i>
    <span>Voltar</span>
</a>

<img src="images/cadastro-login/pata.png" alt="Desenho de Pata" class="pata-fundo">

<div class="w-full max-w-2xl mx-auto"> <div class="w-full flex items-center justify-between mb-6 relative">
        <div>
            <a href="./" title="Voltar para a página inicial">
                <img src="images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" width="70" height="70">
            </a>
        </div>
        <div class="absolute inset-x-0 text-center">
            <h1  class="text-xl md:text-4xl font-bold text-[#666662]">Cadastrar Pet</h1>
            <div class="w-24 h-1 bg-[#666662] mx-auto mt-1 rounded-full"></div>
        </div>
        <div class="h-16 w-16 invisible"></div>
    </div>

    <div class="container-card w-full p-6 sm:p-10 rounded-3xl shadow-xl">
        
        <?php if (!empty($mensagem_status)): ?>
            <div id="php-data" 
                 data-message="<?php echo htmlspecialchars($mensagem_status); ?>" 
                 data-type="<?php echo htmlspecialchars($tipo_mensagem); ?>" 
                 style="display: none;">
            </div>
        <?php endif; ?>

         <form action="cadastrar-pet.php" method="post" enctype="multipart/form-data" id="form-cadastro-pet" class="space-y-6">
            
            <!-- Linha 1: Nome e Idade -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="nome" class="sr-only">Nome do Pet</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Pet" required class="input-style w-full"
                           value="<?php echo htmlspecialchars($nome); ?>">
                </div>
                <div>
                    <label for="idade" class="sr-only">Idade (anos)</label>
                    <input type="number" name="idade" id="idade" placeholder="Idade (anos)" required min="0" class="input-style w-full"
                           value="<?php echo htmlspecialchars($idade); ?>">
                </div>
            </div>

            <!-- Linha 2: Espécie e Gênero (NOVA) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="especie" class="sr-only">Espécie</label>
                    <select name="especie" id="especie" required class="input-style w-full">
                        <option value="" disabled <?php echo empty($especie) ? 'selected' : ''; ?>>Espécie</option>
                        <option value="cachorro" <?php echo ($especie == 'cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                        <option value="gato" <?php echo ($especie == 'gato') ? 'selected' : ''; ?>>Gato</option>
                        <option value="outro" <?php echo ($especie == 'outro') ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <div>
                    <label for="sexo" class="sr-only">Sexo</label>
                    <select name="sexo" id="sexo" required class="input-style w-full">
                        <option value="" disabled <?php echo empty($sexo) ? 'selected' : ''; ?>>Gênero</option>
                        <option value="macho" <?php echo ($sexo == 'macho') ? 'selected' : ''; ?>>Macho</option>
                        <option value="femea" <?php echo ($sexo == 'femea') ? 'selected' : ''; ?>>Fêmea</option>
                    </select>
                </div>
            </div>

            <!-- Linha 3: Porte e Raça -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="porte" class="sr-only">Porte</label>
                    <select name="porte" id="porte" required class="input-style w-full">
                        <option value="" disabled <?php echo empty($porte) ? 'selected' : ''; ?>>Porte</option>
                        <option value="pequeno" <?php echo ($porte == 'pequeno') ? 'selected' : ''; ?>>Pequeno</option>
                        <option value="medio" <?php echo ($porte == 'medio') ? 'selected' : ''; ?>>Médio</option>
                        <option value="grande" <?php echo ($porte == 'grande') ? 'selected' : ''; ?>>Grande</option>
                    </select>
                </div>
                <div>
                    <label for="raca" class="sr-only">Raça (Ex: SRD)</label>
                    <input type="text" name="raca" id="raca" placeholder="Raça (Ex: SRD)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($raca); ?>">
                </div>
            </div>

            <!-- Linha 4: Cor e Vacinado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="cor" class="sr-only">Cor</label>
                    <input type="text" name="cor" id="cor" placeholder="Cor (Ex: Caramelo)" class="input-style w-full"
                           value="<?php echo htmlspecialchars($cor); ?>">
                </div>
                <div>
                    <label for="status_vacinacao" class="sr-only">Vacinado?</label>
                    <select name="status_vacinacao" id="status_vacinacao" required class="input-style w-full">
                        <option value="" disabled <?php echo empty($status_vacinacao) ? 'selected' : ''; ?>>Vacinado?</option>
                        <option value="sim" <?php echo ($status_vacinacao == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($status_vacinacao == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
            </div>
            
            <!-- Linha 5: Castrado e Foto -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="status_castracao" class="sr-only">Castrado?</label>
                    <select name="status_castracao" id="status_castracao" required class="input-style w-full">
                        <option value="" disabled <?php echo empty($status_castracao) ? 'selected' : ''; ?>>Castrado?</option>
                        <option value="sim" <?php echo ($status_castracao == 'sim') ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo ($status_castracao == 'nao') ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>
        <!-- desativado temporariamente 
            <div>
                <label for="foto" class="input-style w-full file-input-label">
                    <i class="fas fa-upload"></i>
                    <span id="file-name-span">Escolher foto do pet... (Obrigatório)</span>
                </label>
                <input type="file" name="foto" id="foto" class="hidden" required accept="image/png, image/jpeg">
            </div>
        </div>
        -->
        <div>
                <button type="button" id="openModalBtn" class="input-style w-full">
                    <span id="tagsPlaceholder">Selecionar Características...</span>
                    <span class="tags-preview" id="tagsPreview">
                        
                    </span>
                </button>
                <!-- Este container vai guardar os inputs hidden criados pelo JS -->
                <div id="hidden-tags-container"></div>
            </div>

            <!-- Linha 6: Comportamento -->
            <div>
                <label for="comportamento" class="sr-only">Comportamento (Ex: Dócil, adora crianças...)</label>
                <textarea name="comportamento" id="comportamento" rows="4" placeholder="Conte um pouco sobre o pet (Ex: Dócil, adora crianças...)" class="input-style w-full"><?php echo htmlspecialchars($comportamento); ?></textarea>
            </div>

            <!-- Linha 7: Botão de Envio -->
            <div class="flex justify-center w-55 mx-auto">
                <button type="submit" class="adopt-btn">
                    <div class="heart-background">❤</div><span>Cadastrar Pet</span>
                </button>
            </div>
        </form>
    </div>
</div>


<!-- HTML DO MODAL DE CARACTERÍSTICAS -->
<div id="charModal" class="char-modal">
    <div class="char-modal-content">
        
        <!-- Cabeçalho -->
        <div class="char-modal-header">
            <div>
                <h2>Selecionar Características</h2>
                <p>Escolha até 5 características para o seu pet.</p>
            </div>
            <button type="button" class="char-modal-close" id="closeModalBtn">&times;</button>
        </div>

        <!-- Corpo com as Tags -->
        <div class="char-modal-body">
            <!-- Temperamento -->
            <h3>Temperamento</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="laranja" data-value="Brincalhão"><i class="fas fa-lightbulb"></i> Brincalhão <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Calmo"><i class="fas fa-leaf"></i> Calmo <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Curioso"><i class="fas fa-search"></i> Curioso <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="laranja" data-value="Tímido"><i class="fas fa-user-secret"></i> Tímido <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Protetor"><i class="fas fa-shield-alt"></i> Protetor <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Sociável"><i class="fas fa-users"></i> Sociável <i class="fas fa-check"></i></span>
            </div>

            <!-- Nível de Energia -->
            <h3>Nível de Energia</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="laranja" data-value="Energético"><i class="fas fa-bolt"></i> Energético <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Tranquilo"><i class="fas fa-moon"></i> Tranquilo <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Moderado"><i class="fas fa-balance-scale"></i> Moderado <i class="fas fa-check"></i></span>
            </div>

            <!-- Convivência -->
            <h3>Convivência</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="rosa" data-value="Bom com Crianças"><i class="fas fa-child"></i> Bom com Crianças <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Bom com Gatos"><i class="fas fa-cat"></i> Bom com Gatos <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Bom com Cães"><i class="fas fa-dog"></i> Bom com Cães <i class="fas fa-check"></i></span>
            </div>

            <!-- Outros -->
            <h3>Outros</h3>
            <div class="char-tags-container">
                <span class="char-tag" data-color="laranja" data-value="Adestrado"><i class="fas fa-graduation-cap"></i> Adestrado <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="verde" data-value="Castrado"><i class="fas fa-cut"></i> Castrado <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="roxo" data-value="Peludo"><i class="fas fa-comment-dots"></i> Peludo <i class="fas fa-check"></i></span>
                <span class="char-tag" data-color="rosa" data-value="Alérgico"><i class="fas fa-allergies"></i> Alérgico <i class="fas fa-check"></i></span>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="char-modal-footer">
            <button type="button" class="btn btn-cancelar" id="cancelModalBtn">Cancelar</button>
            <button type="button" class="btn btn-salvar" id="saveModalBtn">Salvar Seleção (0/5)</button>
        </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>

<!-- Trocar assets/js/pages/cadastro-pets/cadastro-pets.js -->
<script src="assets/js/pages/autenticacao/autenticacao.js" type="module"></script>

<script>
    // Este script não precisa ser 'module'
    document.addEventListener("DOMContentLoaded", function () {
        const fileInput = document.getElementById('foto');
        const fileNameSpan = document.getElementById('file-name-span');

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    // Pega o nome do arquivo e mostra no span
                    fileNameSpan.textContent = fileInput.files[0].name;
                } else {
                    fileNameSpan.textContent = 'Escolher foto do pet... (Obrigatório)';
                }
            });
        }
    });
</script>



<!--SCRIPT DO MODAL DE CARACTERÍSTICAS -->
<script type="module">
    
    // Pega os elementos do DOM
    const modal = document.getElementById('charModal');
    const openBtn = document.getElementById('openModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const saveBtn = document.getElementById('saveModalBtn');
    const tags = document.querySelectorAll('.char-tag');
    const hiddenTagsContainer = document.getElementById('hidden-tags-container');
    const tagsPreview = document.getElementById('tagsPreview');
    const tagsPlaceholder = document.getElementById('tagsPlaceholder'); // <-- NOVO
    
    const MAX_SELECTIONS = 5;
    let selectedTags = []; // Armazena objetos {value, iconHTML}

    // --- Funções do Modal ---
    function openModal() {
        if (modal) modal.style.display = 'flex';
        // Sincroniza o modal com os dados já salvos no form
        syncModalStateFromForm();
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }
    
    function updateSelectionCount() {
        const count = selectedTags.length;
        saveBtn.textContent = `Salvar Seleção (${count}/${MAX_SELECTIONS})`;
        saveBtn.disabled = (count === 0);
    }
    
    // --- Sincronização ---
    function syncModalStateFromForm() {
        selectedTags = [];
        const hiddenInputs = hiddenTagsContainer.querySelectorAll('input[name="caracteristicas[]"]');
        
        // Recria o array selectedTags a partir dos inputs e das tags do modal
        hiddenInputs.forEach(input => {
            const value = input.value;
            // Encontra a tag correspondente no modal
            const matchingTag = document.querySelector(`.char-tag[data-value="${value}"]`);
            if (matchingTag) {
                // Pega o HTML do primeiro ícone (ex: <i class="fas fa-lightbulb"></i>)
                const iconHTML = matchingTag.querySelector('i:first-child').outerHTML;
                selectedTags.push({ value: value, iconHTML: iconHTML });
            }
        });
        
        // Atualiza a aparência das tags no modal
        tags.forEach(tag => {
            // Verifica se algum objeto em selectedTags tem o valor desta tag
            if (selectedTags.some(t => t.value === tag.dataset.value)) {
                tag.classList.add('active');
            } else {
                tag.classList.remove('active');
            }
        });
        updateSelectionCount();
    }

    // --- Event Handlers ---

    // Abrir Modal
    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    // Fechar Modal (Botão X e Cancelar)
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    
    // Clicar fora do modal (no backdrop)
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    // Clicar numa Tag 
    tags.forEach(tag => {
        tag.addEventListener('click', () => {
            const value = tag.dataset.value;
            // Pega o HTML do primeiro ícone
            const iconHTML = tag.querySelector('i:first-child').outerHTML; 
            const isActive = tag.classList.contains('active');

            if (isActive) {
                // Desselecionar
                tag.classList.remove('active');
                selectedTags = selectedTags.filter(t => t.value !== value); // Filtra pelo valor
            } else {
                // Selecionar (com limite)
                if (selectedTags.length < MAX_SELECTIONS) {
                    tag.classList.add('active');
                    selectedTags.push({ value: value, iconHTML: iconHTML }); // Adiciona o objeto
                } else {
                    // Atingiu o limite!
                    console.warn(`Limite de ${MAX_SELECTIONS} características atingido.`);
                    // Ex: window.showToast('Limite de 5 características atingido.', 'warning');
                }
            }
            updateSelectionCount();
        });
    });
    
    // Salvar Seleção
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            // 1. Limpa os inputs escondidos e o preview de ícones
            hiddenTagsContainer.innerHTML = '';
            tagsPreview.innerHTML = '';
            
            let hasSelection = selectedTags.length > 0;

            selectedTags.forEach(tag => {
                // 2a. Cria os novos inputs escondidos
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'caracteristicas[]'; // Envia como um array para o PHP
                input.value = tag.value; // Salva apenas o valor
                hiddenTagsContainer.appendChild(input);
                
                // 2b. Adiciona o ícone ao preview no botão
                tagsPreview.innerHTML += tag.iconHTML;
            });
            
            // 3. Mostra/Esconde o placeholder
            if (tagsPlaceholder) {
                tagsPlaceholder.style.display = hasSelection ? 'none' : 'block';
            }
            
            // 4. Fecha o modal
            closeModal();
        });
    }
</script>
</body>
</html>