<?php
/**
 * admin_dashboard.php
 * Painel de controle restrito para o administrador.
 */

session_start();
require_once 'config.php';

// Verificação de segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!($logado && $tipo_usuario === 'admin')) {
    header('Location: login.php');
    exit;
}

$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";

// =========================
// CONSULTAS PARA ESTATÍSTICAS
// =========================

// Total de Produtos
$sql_produtos = "SELECT COUNT(*) as total FROM produtos";
$result_produtos = $conn->query($sql_produtos);
$total_produtos = $result_produtos ? $result_produtos->fetch_assoc()['total'] : 0;

// Total de Pedidos do Dia
$hoje = date('Y-m-d');
$sql_pedidos_hoje = "SELECT COUNT(*) as total FROM pedidos WHERE DATE(data_pedido) = '$hoje'";
$result_pedidos_hoje = $conn->query($sql_pedidos_hoje);
$total_pedidos_hoje = $result_pedidos_hoje ? $result_pedidos_hoje->fetch_assoc()['total'] : 0;

// Total de Usuários
$sql_usuarios = "SELECT COUNT(*) as total FROM usuarios";
$result_usuarios = $conn->query($sql_usuarios);
$total_usuarios = $result_usuarios ? $result_usuarios->fetch_assoc()['total'] : 0;

// Receita do Mês
$mes_atual = date('Y-m');
$sql_receita_mes = "SELECT SUM(valor_total) as total FROM pedidos WHERE DATE_FORMAT(data_pedido, '%Y-%m') = '$mes_atual' AND status_pedido != 'cancelado'";
$result_receita_mes = $conn->query($sql_receita_mes);
$receita_mes = $result_receita_mes ? $result_receita_mes->fetch_assoc()['total'] : 0;
$receita_mes = $receita_mes ? $receita_mes : 0;

// Pedidos Pendentes
$sql_pedidos_pendentes = "SELECT COUNT(*) as total FROM pedidos WHERE status_pedido IN ('processando', 'pendente')";
$result_pedidos_pendentes = $conn->query($sql_pedidos_pendentes);
$pedidos_pendentes = $result_pedidos_pendentes ? $result_pedidos_pendentes->fetch_assoc()['total'] : 0;

// Produtos com Estoque Baixo (menos de 10 unidades)
$sql_estoque_baixo = "SELECT COUNT(*) as total FROM produtos WHERE estoque < 10";
$result_estoque_baixo = $conn->query($sql_estoque_baixo);
$estoque_baixo = $result_estoque_baixo ? $result_estoque_baixo->fetch_assoc()['total'] : 0;

// Últimos Pedidos
$sql_ultimos_pedidos = "
    SELECT p.id, u.nome as cliente, p.valor_total, p.status_pedido, p.data_pedido 
    FROM pedidos p 
    JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.data_pedido DESC 
    LIMIT 5
";
$result_ultimos_pedidos = $conn->query($sql_ultimos_pedidos);
$ultimos_pedidos = [];
if ($result_ultimos_pedidos && $result_ultimos_pedidos->num_rows > 0) {
    while($row = $result_ultimos_pedidos->fetch_assoc()) {
        $ultimos_pedidos[] = $row;
    }
}

// Fechar conexão
$conn->close();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin | Minha Loja</title>
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

        /* ===== CARDS DE RESUMO ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.warning {
            border-top: 4px solid #ffc107;
        }

        .stat-card.danger {
            border-top: 4px solid #dc3545;
        }

        .stat-card h4 {
            color: #555;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card .number {
            font-size: 2rem;
            font-weight: bold;
            color: rgb(131, 41, 41);
            margin-bottom: 0.5rem;
        }

        .stat-card.warning .number {
            color: #ffc107;
        }

        .stat-card.danger .number {
            color: #dc3545;
        }

        .stat-card .description {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* ===== WELCOME BOX ===== */
        .welcome-box {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid rgb(131, 41, 41);
        }

        .welcome-box h2 {
            color: rgb(131, 41, 41);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .welcome-box p {
            color: #6c757d;
            margin: 0;
        }

        /* ===== SEÇÃO DE RESUMO ===== */
        .summary-section {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .summary-section h3 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 0.5rem;
        }

        .summary-section p {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        /* ===== TABELAS ===== */
        .table-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 1rem;
            overflow-x: auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            min-width: 600px;
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

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
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

        /* ===== AÇÕES RÁPIDAS ===== */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .action-btn {
            background-color: rgb(131, 41, 41);
            color: white;
            padding: 1rem;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }

        .action-btn:hover {
            background-color: rgb(102, 29, 29);
        }

        /* ===== CARDS RESPONSIVOS PARA ÚLTIMOS PEDIDOS ===== */
        .orders-cards {
            display: none;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .order-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid rgb(131, 41, 41);
        }

        .order-card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-id {
            font-weight: bold;
            color: rgb(131, 41, 41);
            font-size: 1.1rem;
        }

        .order-card-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .order-info-item {
            margin-bottom: 0.5rem;
        }

        .order-info-item strong {
            display: block;
            color: #555;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .order-info-item span {
            color: #333;
            font-size: 0.9rem;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            /* Mostrar cards e esconder tabela em mobile */
            .table-container table {
                display: none;
            }

            .orders-cards {
                display: grid;
            }

            .order-card-info {
                grid-template-columns: 1fr;
            }

            .admin-content {
                padding: 1rem;
            }

            .table-container {
                padding: 1rem;
            }
        }

        @media (min-width: 769px) {
            .orders-cards {
                display: none;
            }

            .table-container table {
                display: table;
            }
        }

        /* Para telas muito pequenas */
        @media (max-width: 480px) {
            .order-card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-card {
                padding: 1rem;
            }

            .navbar .container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .navbar ul {
                flex-direction: column;
                gap: 0.5rem;
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
                <li>Olá, <?php echo htmlspecialchars($nome_admin); ?></li>
                <li><a href="logout.php" class="logout-btn">Sair</a></li>
            </ul>
        </nav>
    </div>
</header>

<div class="admin-layout">

    <!-- MENU LATERAL -->
    <aside class="admin-sidebar">
        <h3>Gestão da Loja</h3>
        <ul>
            <li class="active"><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_produtos.php">Produtos</a></li>
            <li><a href="admin_pedidos.php">Pedidos</a></li>
            <li><a href="admin_usuarios.php">Usuários</a></li>
        </ul>
    </aside>

    <!-- CONTEÚDO -->
    <main class="admin-content">

        <div class="welcome-box">
            <h2>Bem-vindo, <?php echo htmlspecialchars($nome_admin); ?>!</h2>
            <p>Utilize o menu lateral para gerenciar produtos, pedidos e usuários do sistema.</p>
        </div>

        <!-- CARDS DE ESTATÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total de Produtos</h4>
                <div class="number"><?php echo $total_produtos; ?></div>
                <div class="description">Produtos cadastrados</div>
            </div>
            <div class="stat-card">
                <h4>Pedidos Hoje</h4>
                <div class="number"><?php echo $total_pedidos_hoje; ?></div>
                <div class="description">Pedidos realizados hoje</div>
            </div>
            <div class="stat-card">
                <h4>Total de Usuários</h4>
                <div class="number"><?php echo $total_usuarios; ?></div>
                <div class="description">Usuários registrados</div>
            </div>
            <div class="stat-card">
                <h4>Receita Mensal</h4>
                <div class="number">R$ <?php echo number_format($receita_mes, 2, ',', '.'); ?></div>
                <div class="description">Total deste mês</div>
            </div>
        </div>

        <!-- ALERTAS E INDICADORES -->
        <div class="stats-grid">
            <div class="stat-card <?php echo $pedidos_pendentes > 0 ? 'warning' : ''; ?>">
                <h4>Pedidos Pendentes</h4>
                <div class="number"><?php echo $pedidos_pendentes; ?></div>
                <div class="description">Aguardando processamento</div>
            </div>
            <div class="stat_card <?php echo $estoque_baixo > 0 ? 'danger' : ''; ?>">
                <h4>Estoque Baixo</h4>
                <div class="number"><?php echo $estoque_baixo; ?></div>
                <div class="description">Produtos com menos de 10 unidades</div>
            </div>
        </div>

        <!-- ÚLTIMOS PEDIDOS - TABELA (Desktop) -->
        <div class="table-container">
            <h3>Últimos Pedidos</h3>
            <?php if (count($ultimos_pedidos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pedidos as $pedido): 
                            $data_formatada = (new DateTime($pedido['data_pedido']))->format('d/m/Y H:i');
                        ?>
                        <tr>
                            <td>#<?php echo $pedido['id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['cliente']); ?></td>
                            <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="badge <?php echo $pedido['status_pedido']; ?>">
                                    <?php echo htmlspecialchars($pedido['status_pedido']); ?>
                                </span>
                            </td>
                            <td><?php echo $data_formatada; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ÚLTIMOS PEDIDOS - CARDS (Mobile) -->
                <div class="orders-cards">
                    <?php foreach ($ultimos_pedidos as $pedido): 
                        $data_formatada = (new DateTime($pedido['data_pedido']))->format('d/m/Y H:i');
                    ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <span class="order-id">#<?php echo $pedido['id']; ?></span>
                            <span class="badge <?php echo $pedido['status_pedido']; ?>">
                                <?php echo htmlspecialchars($pedido['status_pedido']); ?>
                            </span>
                        </div>
                        <div class="order-card-info">
                            <div class="order-info-item">
                                <strong>Cliente</strong>
                                <span><?php echo htmlspecialchars($pedido['cliente']); ?></span>
                            </div>
                            <div class="order-info-item">
                                <strong>Valor</strong>
                                <span>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="order-info-item">
                                <strong>Data</strong>
                                <span><?php echo $data_formatada; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <p>Nenhum pedido encontrado.</p>
            <?php endif; ?>
        </div>

        <!-- AÇÕES RÁPIDAS -->
        <section class="summary-section">
            <h3>Ações Rápidas</h3>
            <p>Acesse rapidamente as principais funcionalidades do sistema:</p>
            
            <div class="quick-actions">
                <a href="admin_produtos.php" class="action-btn">Gerenciar Produtos</a>
                <a href="admin_pedidos.php" class="action-btn">Ver Todos os Pedidos</a>
                <a href="admin_usuarios.php" class="action-btn">Gerenciar Usuários</a>
                <?php if ($estoque_baixo > 0): ?>
                    <a href="admin_produtos.php?filter=low_stock" class="action-btn" style="background-color: #dc3545;">Repor Estoque</a>
                <?php endif; ?>
            </div>
        </section>

    </main>

</div>

</body>
</html>