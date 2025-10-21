<?php
// INICIE A SESSÃO NO TOPO DE TODA PÁGINA QUE PRECISA SABER SE O USUÁRIO ESTÁ LOGADO
session_start();

// Supondo que, ao fazer login, você define $_SESSION['usuario_id'] ou algo similar.
$logado = isset($_SESSION['usuario_id']); 

if (!$logado){
    header("Location: login");
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pets para Adoção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>