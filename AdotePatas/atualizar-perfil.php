<?php
session_start();
include_once 'conexao.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Ocorreu um erro inesperado.'];

// 1. Validações de Segurança Iniciais
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de requisição inválido.';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['user_id'], $_SESSION['user_tipo'])) {
    $response['message'] = 'Sessão inválida. Faça login novamente.';
    echo json_encode($response);
    exit;
}

// 2. Coleta e Limpeza dos Dados
$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['user_tipo'];
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$documento = preg_replace('/[^0-9]/', '', trim($_POST['documento'] ?? ''));

// --- Funções de Validação ---
function validarCPF($cpf) {
    if (empty($cpf) || strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function validarCNPJ($cnpj) {
    if (empty($cnpj) || strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
        return false;
    }
    
    // Valida DVs
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
        return false;
    }
    
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $j;
        $j = ($j == 2) ? 9 : $j - 1;
    }
    $resto = $soma % 11;
    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
}

// --- 3. Execução da Validação Back-end ---
$erros = [];

// Valida campos obrigatórios
if (empty($nome)) {
    $erros[] = "O campo nome é obrigatório.";
}
if (empty($email)) {
    $erros[] = "O campo e-mail é obrigatório.";
}
if (empty($documento)) {
    $erros[] = "O campo documento é obrigatório.";
}

// Se não há erros de campos obrigatórios, valida o formato
if (empty($erros)) {
    // Valida nome
    if (strpos($nome, ' ') === false) {
        $erros[] = "Por favor, digite seu nome completo.";
    }
    
    // Valida email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O formato do e-mail é inválido.";
    }
    
    // Valida documento (CPF ou CNPJ)
    if ($user_tipo == 'adotante') {
        if (strlen($documento) !== 11) {
            $erros[] = "CPF deve conter 11 dígitos.";
        } elseif (!validarCPF($documento)) {
            $erros[] = "O CPF informado é inválido.";
        }
    } elseif ($user_tipo == 'protetor') {
        if (strlen($documento) !== 14) {
            $erros[] = "CNPJ deve conter 14 dígitos.";
        } elseif (!validarCNPJ($documento)) {
            $erros[] = "O CNPJ informado é inválido.";
        }
    }
}

// 4. Verificação de Duplicidade
if (empty($erros)) {
    try {
        if ($user_tipo == 'adotante') {
            // Verifica email
            $sql = "SELECT id_usuario FROM usuario WHERE email = :email AND id_usuario != :id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email, ':id' => $user_id]);
            if ($stmt->fetch()) {
                $erros[] = "Este e-mail já está sendo usado por outra conta.";
            }
            
            // Verifica CPF
            $sql = "SELECT id_usuario FROM usuario WHERE cpf = :cpf AND id_usuario != :id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':cpf' => $documento, ':id' => $user_id]);
            if ($stmt->fetch()) {
                $erros[] = "Este CPF já está sendo usado por outra conta.";
            }
        } elseif ($user_tipo == 'protetor') {
            // Verifica email
            $sql = "SELECT id_ong FROM ong WHERE email = :email AND id_ong != :id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':email' => $email, ':id' => $user_id]);
            if ($stmt->fetch()) {
                $erros[] = "Este e-mail já está sendo usado por outra conta.";
            }
            
            // Verifica CNPJ
            $sql = "SELECT id_ong FROM ong WHERE cnpj = :cnpj AND id_ong != :id LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':cnpj' => $documento, ':id' => $user_id]);
            if ($stmt->fetch()) {
                $erros[] = "Este CNPJ já está sendo usado por outra conta.";
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar duplicidade: " . $e->getMessage());
        $erros[] = "Erro ao verificar dados. Tente novamente.";
    }
}

// 5. Se houver erros, retorna a primeira mensagem de erro
if (!empty($erros)) {
    $response['message'] = $erros[0];
    echo json_encode($response);
    exit;
}

// --- 6. ATUALIZAÇÃO NO BANCO DE DADOS ---
try {
    if ($user_tipo == 'adotante') {
        $sql = "UPDATE usuario SET nome = :nome, email = :email, cpf = :cpf WHERE id_usuario = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':cpf' => $documento,
            ':id' => $user_id
        ]);
    } elseif ($user_tipo == 'protetor') {
        $sql = "UPDATE ong SET nome = :nome, email = :email, cnpj = :cnpj WHERE id_ong = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':cnpj' => $documento,
            ':id' => $user_id
        ]);
    }
    
    // Atualiza o nome na sessão
    $_SESSION['user_nome'] = $nome;
    $response['success'] = true;
    $response['message'] = 'Perfil atualizado com sucesso!';
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Erro no banco de dados: " . $e->getMessage());
    $response['message'] = 'Erro no banco de dados. Tente novamente.';
    echo json_encode($response);
}