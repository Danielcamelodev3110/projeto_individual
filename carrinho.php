<?php
session_start();
require_once 'config.php';

// Inicializa carrinho
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$carrinho = $_SESSION['carrinho'];

// Remover item
if (isset($_GET['acao']) && $_GET['acao'] == 'remover' && isset($_GET['id'])) {
    $produto_id_remover = intval($_GET['id']);
    
    if (array_key_exists($produto_id_remover, $carrinho)) {
        unset($carrinho[$produto_id_remover]);
        $_SESSION['carrinho'] = $carrinho;
    }

    header('Location: carrinho.php');
    exit;
}

// Buscar produtos do carrinho no banco
$ids_carrinho = array_keys($carrinho);
$produtos_carrinho = [];
$total_carrinho = 0.00;

if (!empty($ids_carrinho)) {
    $ids_string = implode(',', $ids_carrinho);

    $sql = "SELECT id, nome, preco, imagem_url FROM produtos WHERE id IN ($ids_string)";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($produto = $result->fetch_assoc()) {
            $id = $produto['id'];
            $qtd = $carrinho[$id];
            $subtotal = $produto['preco'] * $qtd;
            $total_carrinho += $subtotal;

            $produtos_carrinho[] = [
                "id" => $id,
                "nome" => $produto['nome'],
                "preco" => $produto['preco'],
                "quantidade" => $qtd,
                "subtotal" => $subtotal,
                "imagem" => $produto['imagem_url']
            ];
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho | Minha Loja</title>
    <link rel="stylesheet" href="css/carrinho.css">


</head>
<body>

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

<!-- CONTEÚDO -->
<div class="carrinho-container">
    <h1 style="text-align:center;">Seu Carrinho de Compras</h1>

<?php if (empty($produtos_carrinho)): ?>
    <p style="text-align:center;">Seu carrinho está vazio. <a href="produtos.html">Clique aqui</a> para ver os produtos.</p>

<?php else: ?>
    <table class="carrinho-tabela">
        <tr>
            <th>Produto</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th>Subtotal</th>
            <th></th>
        </tr>

        <?php foreach ($produtos_carrinho as $item): ?>
            <tr>
                <td>
                    <img src="<?php echo $item['imagem']; ?>">
                    <?php echo $item['nome']; ?>
                </td>
                <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                <td><?php echo $item['quantidade']; ?></td>
                <td>R$ <?php echo number_format($item['subtotal'], 2, ',', '.'); ?></td>
                <td>
                    <a href="carrinho.php?acao=remover&id=<?php echo $item['id']; ?>" class="remover-item">
                        Remover
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="carrinho-resumo">
        <div class="resumo-box">
            <h3>Resumo do Pedido</h3>

            <p style="color:black;">Subtotal: <strong>R$
                <?php echo number_format($total_carrinho, 2, ',', '.'); ?>
            </strong></p>

            <p style="color:black;">Envio: <strong>R$ 0,00</strong></p>

            <hr>

            <p style="color:black;">Total: <strong>R$
                <?php echo number_format($total_carrinho, 2, ',', '.'); ?>
            </strong></p>

            <a href="checkout.php" class="checkout-btn">Finalizar Compra</a>
        </div>
    </div>

<?php endif; ?>
</div>
<!-- FOOTER -->
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
