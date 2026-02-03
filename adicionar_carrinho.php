<?php
/**
 * adicionar_carrinho.php
 * Script para processar a adição de um produto ao carrinho.
 */

session_start(); // Sempre Inicie a sessão
require_once 'config.php'; // Inclui a conexão (para o futuro, se necessário)

// 1. Inicializar o carrinho na sessão se ele não existir
if (!isset($_SESSION['carrinho'])) {
    // O carrinho será um array associativo: [produto_id => quantidade]
    $_SESSION['carrinho'] = [];
}

// 2. Verificar se o ID do produto foi enviado
if (isset($_POST['produto_id']) && is_numeric($_POST['produto_id'])) {
    $produto_id = intval($_POST['produto_id']);
    $quantidade_a_adicionar = 1; // Por padrão, adicionamos 1

    // 3. Adicionar ou atualizar a quantidade no carrinho
    if (array_key_exists($produto_id, $_SESSION['carrinho'])) {
        // Se o produto já está no carrinho, apenas aumentamos a quantidade
        $_SESSION['carrinho'][$produto_id] += $quantidade_a_adicionar;
    } else {
        // Se é a primeira vez, definimos a quantidade como 1
        $_SESSION['carrinho'][$produto_id] = $quantidade_a_adicionar;
    }

    // 4. Redirecionar o usuário de volta para a página anterior (ou para o carrinho)
    // Se veio do índice, volta para lá.
    $origem = $_SERVER['HTTP_REFERER'] ?? 'produtos.html'; // Corrigido 'index.html' para 'index.php' ou a página de origem mais comum

    // Redirecionamos para o carrinho para o cliente ver o resultado
    header("Location: carrinho.php");
    exit;

} else {
    // Redireciona para a página principal se não houver produto_id
    header("Location: produtos.html");
    exit;
}
?>