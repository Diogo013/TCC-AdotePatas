<?php
session_start();
include_once 'conexao.php';
include_once 'session.php';

header('Content-Type: application/json');

// Verifica se o upload excedeu o limite do POST (erro crítico do PHP)
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    http_response_code(413); // Payload Too Large
    echo json_encode(['success' => false, 'message' => 'O arquivo é muito grande para o servidor.']);
    exit;
}

// 1. Auth Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_tipo'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$user_id_logado = $_SESSION['user_id'];
$user_tipo_logado = $_SESSION['user_tipo'];

// 2. Detectar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$conversa_id = null;
$conteudo = null;
$tipo_conteudo = 'texto';
$arquivo_nome_original = null;

// Tenta ler JSON primeiro (para mensagens de texto puro)
$inputJSON = json_decode(file_get_contents('php://input'), true);

if ($inputJSON) {
    $conversa_id = $inputJSON['conversa_id'] ?? null;
    $conteudo = trim($inputJSON['conteudo'] ?? '');
} else {
    // Se não é JSON, assume que é FormData (com ou sem arquivo)
    $conversa_id = $_POST['conversa_id'] ?? null;
    $conteudo = trim($_POST['conteudo'] ?? '');
}

if (empty($conversa_id) || !filter_var($conversa_id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da conversa inválido ou ausente.']);
    exit;
}

try {
    // 3. Segurança: Verifica permissão na conversa
    $sql_check = "SELECT id_conversa FROM conversa 
                  WHERE id_conversa = :conversa_id 
                  AND (
                      (id_adotante_fk = :user_id AND :user_tipo = 'usuario')
                      OR 
                      (id_protetor_fk = :user_id AND tipo_protetor = :user_tipo)
                  )
                  LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->execute([':conversa_id' => $conversa_id, ':user_id' => $user_id_logado, ':user_tipo' => $user_tipo_logado]);

    if ($stmt_check->rowCount() == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado à conversa.']);
        exit;
    }

    // 4. Processamento de Arquivo
    if (isset($_FILES['arquivo'])) {
        // Verifica erros específicos do PHP no upload
        if ($_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            $erro_codigo = $_FILES['arquivo']['error'];
            $msg_erro = "Erro desconhecido no upload ($erro_codigo)";
            
            switch ($erro_codigo) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $msg_erro = "O arquivo excede o tamanho máximo permitido.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg_erro = "O upload foi interrompido.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg_erro = "Nenhum arquivo foi enviado.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $msg_erro = "Pasta temporária ausente no servidor.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $msg_erro = "Falha ao escrever arquivo no disco.";
                    break;
            }
            throw new Exception($msg_erro);
        }

        $file = $_FILES['arquivo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $arquivo_nome_original = $file['name'];
        
        // Tipos permitidos
        $imagens = ['webp', 'gif', 'jpg', 'jpeg', 'png']; 
        $documentos = ['pdf', 'doc', 'docx', 'txt'];
        
        if (in_array($ext, $imagens)) {
            $tipo_conteudo = 'imagem';
        } elseif (in_array($ext, $documentos)) {
            $tipo_conteudo = 'arquivo';
        } else {
            throw new Exception("Formato de arquivo não suportado ($ext).");
        }
        
        // Garante que a pasta existe
        $upload_dir = 'uploads/chat/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Falha ao criar diretório de upload.");
            }
        }
        
        $novo_nome = uniqid('chat_') . '.' . $ext;
        $destino = $upload_dir . $novo_nome;
        
        if (move_uploaded_file($file['tmp_name'], $destino)) {
            // *** CORREÇÃO PARA O ERRO 403 ***
            // Força permissão de leitura pública para o arquivo salvo
            chmod($destino, 0644); 
            $conteudo = $destino; 
        } else {
            throw new Exception("Erro ao mover o arquivo para o destino final.");
        }
    } else {
        // Se não tem arquivo, tem que ter texto
        if (empty($conteudo)) {
            throw new Exception("Mensagem vazia (nenhum texto ou arquivo recebido).");
        }
    }

    // 5. Inserir no Banco
    $sql_insert = "INSERT INTO mensagem 
                    (id_conversa_fk, id_remetente_fk, tipo_remetente, conteudo, tipo_conteudo, arquivo_nome, data_envio)
                   VALUES
                    (:conversa, :remetente_id, :remetente_tipo, :conteudo, :tipo_cont, :arq_nome, NOW())";
                    
    $stmt = $conn->prepare($sql_insert);
    $stmt->execute([
        ':conversa' => $conversa_id,
        ':remetente_id' => $user_id_logado,
        ':remetente_tipo' => $user_tipo_logado,
        ':conteudo' => $conteudo,
        ':tipo_cont' => $tipo_conteudo,
        ':arq_nome' => $arquivo_nome_original
    ]);

    date_default_timezone_set('America/Sao_Paulo');
    echo json_encode([
        'success' => true,
        'message' => 'Enviado com sucesso.',
        'timestamp' => date('H:i, d/m/Y'),
        'conteudo' => $conteudo,
        'tipo' => $tipo_conteudo
    ]);

} catch (Exception $e) {
    // Retorna 200 com success:false para o JS tratar a mensagem de erro amigavelmente
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>