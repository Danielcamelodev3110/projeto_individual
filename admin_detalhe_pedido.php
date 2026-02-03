<?php
// admin_detalhe_pedido.php — Detalhes e Ações do Pedido

session_start();
require_once 'config.php';

// Verificação de Segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!($logado) || $tipo_usuario !== 'admin') {
    header("Location: login.php");
    exit();
}

$mensagem_status = "";
$pedido_id = $_GET['id'] ?? null;
$pedido = null;
$itens_pedido = [];

if (is_numeric($pedido_id)) {
    $pedido_id = intval($pedido_id);
    
    // =========================
    // A. PROCESSAR ATUALIZAÇÃO DE STATUS (POST)
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['novo_status'])) {
        $novo_status = trim($_POST['novo_status']);
        
        $sql_update = "UPDATE pedidos SET status_pedido = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update) {
            $stmt_update->bind_param("si", $novo_status, $pedido_id);
            if ($stmt_update->execute()) {
                $mensagem_status = "<p class='success'>Status do Pedido #{$pedido_id} atualizado para <b>{$novo_status}</b> com sucesso!</p>";
            } else {
                $mensagem_status = "<p class='error'>Erro ao atualizar status: " . $stmt_update->error . "</p>";
            }
            $stmt_update->close();
        }
    }
    
    // =========================
    // B. BUSCAR DADOS DO PEDIDO
    // =========================
    $sql_pedido = "
        SELECT 
            p.*, u.nome AS nome_cliente, u.email AS email_cliente
        FROM pedidos p 
        JOIN usuarios u ON p.usuario_id = u.id 
        WHERE p.id = ?
    ";
    
    $stmt_pedido = $conn->prepare($sql_pedido);
    if ($stmt_pedido) {
        $stmt_pedido->bind_param("i", $pedido_id);
        $stmt_pedido->execute();
        $resultado_pedido = $stmt_pedido->get_result();
        $pedido = $resultado_pedido->fetch_assoc();
        $stmt_pedido->close();
    }
    
    // =========================
    // C. BUSCAR ITENS DO PEDIDO
    // =========================
    $sql_itens = "
        SELECT 
            i.quantidade, i.preco_unitario, pr.nome AS nome_produto
        FROM itens_pedido i
        JOIN produtos pr ON i.produto_id = pr.id
        WHERE i.pedido_id = ?
    ";
    
    $stmt_itens = $conn->prepare($sql_itens);
    if ($stmt_itens) {
        $stmt_itens->bind_param("i", $pedido_id);
        $stmt_itens->execute();
        $resultado_itens = $stmt_itens->get_result();
        while ($item = $resultado_itens->fetch_assoc()) {
            $itens_pedido[] = $item;
        }
        $stmt_itens->close();
    }

} else {
    $mensagem_status = "<p class='error'>ID do pedido inválido.</p>";
}

// Se o pedido não foi encontrado, redireciona ou exibe uma mensagem de erro
if (!$pedido && empty($mensagem_status)) {
    $mensagem_status = "<p class='error'>Pedido não encontrado.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido #<?php echo $pedido_id; ?> | Admin</title>
    <style>
        /* ===== RESET E ESTILOS GERAIS ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background-color: rgb(131, 41, 41);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }

        .navbar ul {
            list-style: none;
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .navbar li {
            color: white;
            font-weight: 500;
        }

        .logout-btn {
            background-color: rgba(199, 106, 106, 1);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: rgba(180, 80, 80, 1);
        }

        /* ===== LAYOUT ADMIN ===== */
        .admin-layout {
            display: flex;
            min-height: calc(100vh - 80px);
            max-width: 1200px;
        }

        .admin-sidebar {
            width: 280px;
            background-color: rgb(131, 41, 41);
            color: white;
            padding: 2rem 0;
        }

        .admin-sidebar h3 {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
            font-size: 1.3rem;
            padding: 0 1.5rem;
        }

        .admin-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .admin-sidebar li a {
            display: block;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-left: 5px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .admin-sidebar li:hover a,
        .admin-sidebar li.active a {
            background-color: rgba(199, 106, 106, 1);
            border-left-color: white;
            color: white;
        }

        .admin-content {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }

        /* ===== CONTEÚDO PRINCIPAL ===== */
        .admin-content h1 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 600;
        }

        .admin-content h3 {
            color: #555;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            font-weight: 500;
        }

        /* ===== CARDS DE DETALHES ===== */
        .card {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h3 {
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-item strong {
            display: block;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item span {
            color: #333;
            font-size: 1rem;
        }

        /* ===== FORMULÁRIO DE STATUS ===== */
        .status-form {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
        }

        .status-form h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .status-form .form-group {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-form select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            min-width: 200px;
        }

        /* ===== TABELAS ===== */
        .table-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table-container th {
            background-color: #f8f9fa;
            color: #555;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-container td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }

        .table-container tr:hover {
            background-color: #f8f9fa;
        }

        .table-container .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.processando {
            background-color: #ffc107;
            color: #333;
        }

        .badge.enviado {
            background-color: #007bff;
            color: white;
        }

        .badge.entregue {
            background-color: #28a745;
            color: white;
        }

        .badge.cancelado {
            background-color: #dc3545;
            color: white;
        }

        /* ===== BOTÕES ===== */
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: rgb(131, 41, 41);
            color: white;
        }

        .btn-primary:hover {
            background-color: rgb(102, 29, 29);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        /* ===== MENSAGENS DE STATUS ===== */
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-weight: 500;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .info {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        /* ===== RESPONSIVIDADE ===== */
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }

            .admin-sidebar {
                width: 100%;
                padding: 1rem 0;
            }

            .admin-sidebar ul {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }

            .admin-sidebar li {
                flex: 1;
                min-width: 120px;
            }

            .admin-sidebar li a {
                text-align: center;
                border-left: none;
                border-bottom: 3px solid transparent;
            }

            .admin-sidebar li:hover a,
            .admin-sidebar li.active a {
                border-left-color: transparent;
                border-bottom-color: white;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .status-form .form-group {
                flex-direction: column;
                align-items: stretch;
            }

            .status-form select {
                min-width: auto;
            }

            .table-container {
                padding: 1rem;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="container">
        <h1 class="logo">MinhaLoja - ADMIN</h1>
        <nav>
            <ul>
                <li>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Admin'); ?></li>
                <li><a href="logout.php" class="logout-btn">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <h3>Gestão da Loja</h3>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_produtos.php">Produtos</a></li>
            <li class="active"><a href="admin_pedidos.php">Pedidos</a></li>
            <li><a href="admin_usuarios.php">Usuários</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <h1>Detalhes do Pedido #<?php echo $pedido_id; ?></h1>
        
        <a href="admin_pedidos.php" class="btn btn-secondary">← Voltar para a lista de pedidos</a>

        <?php echo $mensagem_status; ?>

        <?php if ($pedido): ?>

            <!-- Informações Gerais do Pedido -->
            <div class="card">
                <h3>Informações Gerais</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Cliente</strong>
                        <span><?php echo htmlspecialchars($pedido['nome_cliente']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>E-mail</strong>
                        <span><?php echo htmlspecialchars($pedido['email_cliente']); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Data do Pedido</strong>
                        <span><?php echo (new DateTime($pedido['data_pedido']))->format('d/m/Y H:i'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Valor Total</strong>
                        <span>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Atualização de Status -->
            <div class="status-form">
                <h3>
                    Status Atual: 
                    <span class="badge <?php echo htmlspecialchars($pedido['status_pedido']); ?>">
                        <?php echo htmlspecialchars($pedido['status_pedido']); ?>
                    </span>
                </h3>
                
                <form action="admin_detalhe_pedido.php?id=<?php echo $pedido_id; ?>" method="POST">
                    <div class="form-group">
                        <select name="novo_status" id="novo_status" required>
                            <option value="processando" <?php echo $pedido['status_pedido'] == 'processando' ? 'selected' : ''; ?>>Processando</option>
                            <option value="enviado" <?php echo $pedido['status_pedido'] == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                            <option value="entregue" <?php echo $pedido['status_pedido'] == 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                            <option value="cancelado" <?php echo $pedido['status_pedido'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Atualizar Status</button>
                    </div>
                </form>
            </div>
            
            <!-- Itens do Pedido -->
            <div class="table-container">
                <h3>Itens do Pedido (<?php echo count($itens_pedido); ?>)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unitário</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $subtotal_geral = 0; ?>
                        <?php foreach ($itens_pedido as $item): ?>
                            <?php $subtotal_item = $item['preco_unitario'] * $item['quantidade']; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                                <td>R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                <td><?php echo $item['quantidade']; ?></td>
                                <td>R$ <?php echo number_format($subtotal_item, 2, ',', '.'); ?></td>
                            </tr>
                            <?php $subtotal_geral += $subtotal_item; ?>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right; font-weight: bold;">Total Final:</td>
                            <td style="font-weight: bold;">R$ <?php echo number_format($subtotal_geral, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="card">
                <p>Pedido não encontrado.</p>
            </div>
        <?php endif; ?>

    </main>
</div>
</body>
</html>