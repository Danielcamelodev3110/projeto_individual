<?php
session_start();
require_once 'config.php';

// Variáveis de sessão
$logado = $_SESSION['logado'] ?? false;
$usuario_id = $_SESSION['usuario_id'] ?? null;
$carrinho = $_SESSION['carrinho'] ?? [];

$mensagem_status = "";
$pedido_finalizado = false;
$novo_pedido_id = null;
$total_final = 0.00;

if (!$logado) {
    header("Location: login.php?redirect=checkout");
    exit;
}

if (empty($carrinho)) {
    header("Location: carrinho.php");
    exit;
}

$ids_carrinho = array_keys($carrinho);
$ids_string = implode(',', $ids_carrinho);
$produtos_compra = [];

$sql_carrinho = "SELECT id, nome, preco FROM produtos WHERE id IN ($ids_string)";
$resultado = $conn->query($sql_carrinho);

if ($resultado && $resultado->num_rows > 0) {
    while ($produto = $resultado->fetch_assoc()) {
        $produto_id = $produto['id'];
        $quantidade = $carrinho[$produto_id];
        $preco_unitario = $produto['preco'];

        $subtotal_item = $preco_unitario * $quantidade;
        $total_final += $subtotal_item;

        $produtos_compra[] = [
            'id'             => $produto_id,
            'nome'           => $produto['nome'],
            'preco_unitario' => $preco_unitario,
            'quantidade'     => $quantidade,
            'subtotal'       => $subtotal_item
        ];
    }
}

if (!empty($produtos_compra)) {
    $conn->begin_transaction();
    try {
        $status_inicial = "Processando Pagamento";

        $sql_pedido = "INSERT INTO pedidos (usuario_id, valor_total, status_pedido) VALUES (?, ?, ?)";
        $stmt_pedido = $conn->prepare($sql_pedido);
        $stmt_pedido->bind_param("ids", $usuario_id, $total_final, $status_inicial);

        if (!$stmt_pedido->execute()) {
            throw new Exception("Erro ao criar pedido.");
        }

        $novo_pedido_id = $conn->insert_id;
        $stmt_pedido->close();

        $sql_item = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);

        foreach ($produtos_compra as $item) {
            $stmt_item->bind_param(
                "iidi",
                $novo_pedido_id,
                $item['id'],
                $item['quantidade'],
                $item['preco_unitario']
            );
            if (!$stmt_item->execute()) {
                throw new Exception("Erro ao inserir item.");
            }
        }
        $stmt_item->close();

        $conn->commit();
        $pedido_finalizado = true;

        unset($_SESSION['carrinho']);

        $mensagem_status = "<p class='sucesso'>Obrigado! O Pedido <strong>#{$novo_pedido_id}</strong> foi realizado com sucesso.</p>";

    } catch (Exception $e) {
        $conn->rollback();
        $mensagem_status = "<p class='erro'>Erro ao finalizar: {$e->getMessage()}</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | Minha Loja</title>

<style>

body {
    margin: 0;
    font-family: 'Times New Roman', Times, serif;
    background-color: rgb(189, 153, 153);
}

h1 {
    color: #ffffff;
    margin: 10px;
}

#primeiro {
    color: #ffffff;
    margin: 10px;
}


/* ======= CABEÇALHO E MENU ======= */

.container {
    background-color: #661d1d;
    border-bottom: 1px solid #c9c3b4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
}

.main-header {
    background-color: #e6e0d3;
    border-bottom: 1px solid #c9c3b4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 50px;
}


/* ======= NAV ORIGINAL (mantida da primeira programação) ======= */

.nav-bar {
    flex-grow: 1;
}

.nav-bar li {
    list-style: none;
    display: inline;
    align-items: center;
    gap: 5px;
    margin: 10px;
}

.nav-bar li a {
    text-decoration: none;
    color: #661d1d;
    font-weight: bold;
    font-size: 14px;
    letter-spacing: 5px;
    padding: 5px 0px;
    transition: color 0.1s;
}

.nav-bar li a:hover {
    color: #300e0e;
    cursor: pointer;
}

/* ====== CONTAINER DO CHECKOUT ====== */
.checkout-box {
    max-width: 1000px;
    margin: 4rem auto;
    padding: 2.5rem;
    background-color: rgb(182, 69, 69);
    color: white;
    border: 1px solid rgb(131, 41, 41);
    border-radius: 15px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    text-align: center;
}

.checkout-box h1,
.checkout-box h2 {
    margin-bottom: 1.5rem;
}

/* ====== RESUMO FINAL ====== */
.resumo-final {
    margin: 2rem 0;
    padding: 1.5rem;
    border-radius: 10px;
    background-color: white;
    color: #333;
    border: 1px solid rgb(131, 41, 41);
}

.resumo-final p {
    font-size: 1.1rem;
    margin: 0.6rem 0;
}

.resumo-final strong {
    font-size: 1.4rem;
    color: rgb(131, 41, 41);
}

/* ====== BOTÕES ====== */
.checkout-btn {
    display: block;
    padding: 1rem;
    text-align: center;
    background-color: rgb(131, 41, 41);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 1.2rem;
    margin-top: 1.2rem;
    transition: 0.3s;
}

.checkout-btn:hover {
    background-color: rgb(102, 29, 29);
}

/* ====== MENSAGENS ====== */
.sucesso, .erro {
    padding: 1rem;
    border-radius: 8px;
    font-weight: bold;
    margin-bottom: 1.5rem;
    border: 1px solid rgb(131, 41, 41);
}

.sucesso {
    background-color: #d4edda;
    color: #155724;
}

.erro {
    background-color: #f8d7da;
    color: #721c24;
}

/* ===== FOOTER ===== */
footer {
    background-color: #661d1d;
    padding: 40px 20px;
    margin-top: 60px;
    border-top: 5px solid #c9c3b4;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
}

footer h3 {
    color: #ffffff;
}

footer p {
    color: #ffffff;
}

.footerimg img {
    width: 30px;
    padding-left: 10px;
}

.espacoaolado,
.esquerda {
    display: flex;
    flex-direction: column;
}


/* ======= RESPONSIVIDADE ======= */

@media (max-width: 768px) {
    .slider {
        height: 60vw;
    }
    .navigation-manual,
    .navigation-auto {
        bottom: 10px;
    }
    .botoes-datas button {
        width: 150px;
        height: 100px;
        margin: 10px;
    }
    footer {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
}

</style>
</head>

<body>

<!-- NAVBAR -->
 <div class="container"> 
    <h1> Desesperados.com </h1>
    <br>
    <p id="primeiro"> Para pessoas esquecidas que não lembraram de comprar presente para alguem especial em virtude da vida corrida. </p>
    </div>
    <header class="main-header">
    <nav> 
        <ul class="nav-bar"> 
        <li> <a href="index.html">Home </a> </li>
                   <li><a href="sobre.html"> Sobre</a></li>
                <li><a href="produtos.html">Produtos</a> </li>
                <li><a href="carrinho.php"> Carrinho </a></li>
                <li><a href="login.php"> Entrar</a> </li>
                 <li><a href="login.php"> Sair</a> </li>
        </ul>
    </nav>
    </header>

<!-- CAIXA DO CHECKOUT -->
<div class="checkout-box">

    <?php echo $mensagem_status; ?>

    <?php if ($pedido_finalizado): ?>
        <h1>Compra Finalizada!</h1>
        <p>Seu pedido foi registrado com sucesso.</p>

        <div class="resumo-final">
            <p>Número do Pedido: <strong>#<?php echo $novo_pedido_id; ?></strong></p>
            <p>Total Pago: <strong>R$ <?php echo number_format($total_final, 2, ',', '.'); ?></strong></p>
        </div>

        <a href="index.html" class="checkout-btn">Continuar Comprando</a>

    <?php else: ?>
        <h2>Ocorreu um Problema</h2>
        <p>Não foi possível finalizar seu pedido.</p>

        <a href="carrinho.php" class="checkout-btn">Voltar ao Carrinho</a>
    <?php endif; ?>

</div>

 <footer> 
        <div class="esquerda">
    <h3>Desesperados.com </h3>
    <p> Conheça mais do nosso trabalho: </p>
    <div class="footerimg">
    <img src="img/instagram.png" alt="instagram"> 
    <img src="img/facebook.png" alt="facebook"> 
    <img src="img/tiktok.png" alt="facebook"> 
    </div>
    </div>
    <div class="espacoaolado"> 
    <h3> Contato: </h3>
    <p>Telefone para contato: (99) 9999-9999 </p>
    <p> Email para contato: desesperados@gmail.com</p>
    </div>
    </footer>

</body>
</html>
