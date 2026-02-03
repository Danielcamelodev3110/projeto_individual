<?php
// admin_apagar_produto.php — Exclusão de Produto

session_start();
require_once 'config.php';

// Verificação de segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!$logado || $tipo_usuario !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensagem_status = "";

// Processar exclusão
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Primeiro, buscar o nome do produto para a mensagem
    $sql_select = "SELECT nome FROM produtos WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $nome_produto = "";
    
    if ($stmt_select) {
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $resultado = $stmt_select->get_result();
        
        if ($resultado->num_rows > 0) {
            $produto = $resultado->fetch_assoc();
            $nome_produto = $produto['nome'];
        }
        $stmt_select->close();
    }
    
    // Agora excluir o produto
    $sql_delete = "DELETE FROM produtos WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            $mensagem_status = "<p class='sucesso'>Produto <b>$nome_produto</b> excluído com sucesso!</p>";
        } else {
            $mensagem_status = "<p class='erro'>Erro ao excluir produto: " . $stmt_delete->error . "</p>";
        }
        $stmt_delete->close();
    } else {
        $mensagem_status = "<p class='erro'>Erro ao preparar exclusão: {$conn->error}</p>";
    }
} else {
    $mensagem_status = "<p class='erro'>ID do produto não especificado.</p>";
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

// Redirecionar de volta para a lista de produtos
header("Location: admin_produtos.php?mensagem=" . urlencode($mensagem_status));
exit;
?>