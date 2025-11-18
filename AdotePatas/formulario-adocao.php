<?php
session_start();
include_once 'conexao.php'; // Nosso script de conexão
include_once 'session.php'; // Nosso script de sessão

// --- CORREÇÃO AQUI ---
// O $base_path precisa ser definido ANTES de ser usado no header()
if ($_SERVER['SERVER_NAME'] == 'localhost') {
    $base_path = '/TCC-AdotePatas/AdotePatas/';
} else {
    $base_path = '/'; 
}
// --- FIM CORREÇÃO ---

// 1. Segurança: Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    // Se não estiver logado, redireciona para a página de login
    
    // --- CORREÇÃO AQUI ---
    // Agora o $base_path funciona e o parâmetro 'id' está correto
    // para o caso do usuário recarregar a página de login e voltar.
    header("Location: " . $base_path . "login?redirect=formulario&id=" . ($_GET['id'] ?? ''));
    exit;
}

// 2. Pega os dados básicos da sessão
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$primeiro_nome = $_SESSION['primeiro_nome'] ?? 'Usuário';

// 3. Pega o ID do Pet pela URL
$id_pet = $_GET['id'] ?? 0;
$id_pet = filter_var($id_pet, FILTER_SANITIZE_NUMBER_INT);

$pet = null;
$foto_principal = $base_path . 'images/perfil/teste.jpg';

if (empty($id_pet)) {
    // Se não tem ID, volta pra lista de pets
    header('Location: ' . $base_path . 'pets');
    exit;
}

// 4. Busca os dados do Pet para mostrar no formulário
try {
    // Busca o pet e sua foto principal
    $sql = "SELECT p.id_pet, p.nome, pf.caminho_foto 
            FROM pet p
            LEFT JOIN (
                SELECT id_pet_fk, MIN(id_foto) as min_id_foto
                FROM pet_fotos
                GROUP BY id_pet_fk
            ) pf_min ON p.id_pet = pf_min.id_pet_fk
            LEFT JOIN pet_fotos pf ON pf.id_foto = pf_min.min_id_foto
            WHERE p.id_pet = :id_pet AND p.status_disponibilidade = 'disponivel'";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_pet' => $id_pet]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o pet não for encontrado ou não estiver disponível
    if (!$pet) {
        header('Location: ' . $base_path . 'pets');
        exit;
    }

    if (!empty($pet['caminho_foto'])) {
        $foto_principal = $base_path . htmlspecialchars($pet['caminho_foto']);
    }

} catch (PDOException $e) {
    // Em caso de erro, podemos registrar ou mostrar uma mensagem amigável
    error_log("Erro ao buscar pet para formulário: " . $e->getMessage());
    echo "Erro ao carregar dados do pet. Tente novamente.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Adoção - Adote Patas</title>
    
    <!-- Bootstrap e Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png"/>
    
    <!-- Nosso CSS customizado para o formulário -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/pages/formulario-adocao/fomulario-adocao.css"> 
</head>
<body class="form-body">

    <!-- Header Simples -->
    <header class="form-header shadow-sm">
      <nav class="navbar">
        <div class="container">
          <a class="navbar-brand" href="<?php echo $base_path; ?>./">
            <img src="<?php echo $base_path; ?>images/global/Logo-AdotePatas.png" alt="Logo Adote Patas" class="navbar-logo">
          </a>
          <a href="<?php echo $base_path; ?>perfil?page=perfil" class="profile-info-link d-flex align-items-center gap-2 text-decoration-none">
            <i class="fa-regular fa-circle-user profile-icon logged-in"></i>
            <span class="profile-name fs-5"><?php echo htmlspecialchars($primeiro_nome); ?></span>
          </a>
        </div>
      </nav>
    </header>

    <!-- O Modal de Termos (Baseado no Mobile .jpg) -->
    <!-- data-bs-backdrop="static" e data-bs-keyboard="false" impedem o usuário de fechar clicando fora ou apertando ESC -->
    <div class="modal fade" id="termoModal" tabindex="-1" aria-labelledby="termoModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termoModalLabel">Formulário de Interesse em Adoção</h5>
                    <!-- Sem botão de fechar (X) -->
                </div>
                <div class="modal-body">
                    <p>Olá! É um prazer ter você aqui ❤️</p>
                    <p>Ao preencher este formulário, você está dando um passo importante para transformar a vida de um cão ou gato que espera por uma família. Queremos conhecer um pouquinho sobre você, seu lar e sua rotina para encontrar o pet que mais combina com seu perfil.</p>
                    <p>O AdotePatas funciona como ponte entre você e os protetores/ONGs parceiros. Eles são responsáveis pela entrevista e pelo processo final de adoção. Após o envio do formulário, eles têm até 48h para entrar em contato.</p>
                    
                    <div class="form-check-box mt-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="termo-ciencia">
                            <label class="form-check-label" for="termo-ciencia">
                                <strong>Declaro ter mais de 18 anos</strong> e autorizo o AdotePatas a compartilhar meus dados com protetores/ONGs parceiros para contato. Entendo que este formulário representa meu interesse em adotar e <strong>não garante reserva</strong> do animal.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- O botão de confirmar começa desabilitado -->
                    <button type="button" class="btn btn-primary w-100" id="btn-confirmar-modal" disabled>Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal: O Formulário -->
    <!-- Ele começa escondido (display: none) e só aparece após o modal -->
    <main class="container my-4" id="form-principal" style="display: none;">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                
                <div class="form-pet-info-header">
                    <img src="<?php echo $foto_principal; ?>" alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>" class="pet-form-img shadow-sm" onerror="this.src='<?php echo $base_path; ?>images/perfil/teste.jpg';">
                    <h2>Você está aplicando para adotar<br><strong><?php echo htmlspecialchars($pet['nome']); ?></strong></h2>
                </div>

                <div class="form-container shadow-sm">
                    <h3 class="form-section-title">Nos conte mais sobre você</h3>
                    
                    <!-- Nosso formulário que envia os dados para o back-end -->
                    <form action="<?php echo $base_path; ?>processa-adocao.php" method="POST" id="adoption-form">
                        
                        <!-- ID do Pet (escondido, mas essencial) -->
                        <input type="hidden" name="id_pet" value="<?php echo $id_pet; ?>">

                        <!-- Pergunta 1: Crianças (Baseado em 8.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Há crianças em sua casa?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tem_criancas" id="criancas_sim" value="sim" required>
                                    <label class="form-check-label" for="criancas_sim">Sim</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tem_criancas" id="criancas_nao" value="nao" required>
                                    <label class="form-check-label" for="criancas_nao">Não</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 2: Todos apoiam (Baseado em 8.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Todos em sua casa apoiam a Adoção?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="todos_apoiam" id="apoiam_sim" value="sim" required>
                                    <label class="form-check-label" for="apoiam_sim">Sim</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="todos_apoiam" id="apoiam_nao" value="nao" required>
                                    <label class="form-check-label" for="apoiam_nao">Não</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 3: Moradia (Baseado em 10.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Você mora em um(a):</label>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_moradia" id="moradia_casa_g" value="Casa grande" required>
                                    <label class="form-check-label" for="moradia_casa_g">Casa grande</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_moradia" id="moradia_casa_p" value="Casa pequena" required>
                                    <label class="form-check-label" for="moradia_casa_p">Casa pequena</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_moradia" id="moradia_ap_s" value="Apartamento seguro" required>
                                    <label class="form-check-label" for="moradia_ap_s">Apartamento seguro (com telas)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_moradia" id="moradia_ap_ns" value="Apartamento s/ proteção" required>
                                    <label class="form-check-label" for="moradia_ap_ns">Apartamento s/ proteção</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pergunta 4: Presente (Baseado em 9.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">O Pet será seu ou será um presente?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pet_sera_presente" id="presente_sim" value="sim" required>
                                    <label class="form-check-label" for="presente_sim">Será um presente</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="pet_sera_presente" id="presente_nao" value="nao" required>
                                    <label class="form-check-label" for="presente_nao">Será meu</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 5: Responsável Presente (Baseado em 9.jpg) -->
                        <!-- Esta pergunta só deve aparecer se a anterior for "sim" (vamos usar JS pra isso) -->
                        <div class="form-question-box" id="div-responsavel-presente" style="display: none;">
                            <label class="form-label">Caso seja um presente, essa pessoa se responsabilizará?</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="presente_responsavel" id="responsavel_sim" value="sim">
                                    <label class="form-check-label" for="responsavel_sim">Sim</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="presente_responsavel" id="responsavel_nao" value="nao">
                                    <label class="form-check-label" for="responsavel_nao">Não</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 6: Teve Pets (Baseado em 11.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Você tem ou já teve pets?</label>
                            <div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="teve_pets" id="pets_tenho" value="Sim, eu tenho" required>
                                    <label class="form-check-label" for="pets_tenho">Sim, eu tenho</Fim>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="teve_pets" id="pets_tive" value="Sim, já tive" required>
                                    <label class="form-check-label" for="pets_tive">Sim, já tive</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="teve_pets" id="pets_nao_tenho" value="Não tenho" required>
                                    <label class="form-check-label" for="pets_nao_tenho">Não tenho (mas já tive)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="teve_pets" id="pets_nunca" value="Nunca tive" required>
                                    <label class="form-check-label" for="pets_nunca">Nunca tive</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 7: Visita (Baseado em 12.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Autorização para visita domiciliar</label>
                            <p class="form-text">Para garantir a segurança e o bem-estar do animal, você autoriza que um protetor ou ONG parceira realize uma visita ao seu lar, caso julgue necessário?</p>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="autoriza_visita" id="visita_sim" value="sim" required>
                                    <label class="form-check-label" for="visita_sim">Autorizo</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="autoriza_visita" id="visita_nao" value="nao" required>
                                    <label class="form-check-label" for="visita_nao">Não autorizo</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 8: Devolução (Baseado em 13.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Devolução em caso de impossibilidade</label>
                            <p class="form-text">Se, em algum momento, você não puder continuar com o pet, compromete-se a devolvê-lo ao protetor ou ONG parceira, garantindo que ele volte para um ambiente seguro?</p>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ciente_devolucao" id="devolucao_sim" value="sim" required>
                                    <label class="form-check-label" for="devolucao_sim">Sim</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ciente_devolucao" id="devolucao_nao" value="nao" required>
                                    <label class="form-check-label" for="devolucao_nao">Não</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta 9: Aprovação (Baseado em 14.jpg) -->
                        <div class="form-question-box">
                            <label class="form-label">Em caso de aprovação</label>
                            <p class="form-text">Se sua adoção for aprovada após entrevista, você se compromete a assinar um termo de responsabilidade no ato da entrega do pet?</p>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ciente_termo_responsabilidade" id="termo_sim" value="sim" required>
                                    <label class="form-check-label" for="termo_sim">Sim</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="ciente_termo_responsabilidade" id="termo_nao" value="nao" required>
                                    <label class="form-check-label" for="termo_nao">Não</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-adocao-submit btn-lg">
                                <i class="bi bi-heart-fill"></i>
                                Enviar Solicitação de Adoção
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Nosso script para controlar o modal e o formulário
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Pega os elementos que vamos usar
        const termoModalElement = document.getElementById('termoModal');
        const termoCheckbox = document.getElementById('termo-ciencia');
        const btnConfirmarModal = document.getElementById('btn-confirmar-modal');
        const formPrincipal = document.getElementById('form-principal');

        // 2. Cria e mostra o modal do Bootstrap assim que a página carrega
        var myModal = new bootstrap.Modal(termoModalElement, {
            keyboard: false, // Impede de fechar com ESC
            backdrop: 'static' // Impede de fechar clicando fora
        });
        myModal.show();

        // 3. Monitora o checkbox de termos
        termoCheckbox.addEventListener('change', function() {
            // Se estiver marcado, habilita o botão de confirmar
            // Se não, desabilita
            btnConfirmarModal.disabled = !this.checked;
        });

        // 4. Monitora o clique no botão "Confirmar" do modal
        btnConfirmarModal.addEventListener('click', function() {
            // Esconde o modal
            myModal.hide();
            // Mostra o formulário principal
            formPrincipal.style.display = 'block';
        });

        // 5. [BÔNUS] Script para mostrar a pergunta sobre "responsável pelo presente"
        const radiosPresente = document.querySelectorAll('input[name="pet_sera_presente"]');
        const divResponsavel = document.getElementById('div-responsavel-presente');
        const radiosResponsavel = document.querySelectorAll('input[name="presente_responsavel"]');

        radiosPresente.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value === 'sim') {
                    // Se for presente, mostra a pergunta e torna ela obrigatória (required)
                    divResponsavel.style.display = 'block';
                    radiosResponsavel.forEach(r => r.required = true);
                } else {
                    // Se não for presente, esconde e remove o obrigatório
                    divResponsavel.style.display = 'none';
                    radiosResponsavel.forEach(r => r.required = false);
                }
            });
        });
    });
    </script>
</body>
</html>