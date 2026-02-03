<?php
// admin_produtos.php — Gestão de Produtos
if (isset($_GET['mensagem'])) {
    $mensagem_status = $_GET['mensagem'];
}
session_start();
require_once 'config.php';

// Verificação de segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!$logado || $tipo_usuario !== 'admin') {
    header('Location: login.php');
    exit;
}

$nome_admin = $_SESSION['usuario_nome'] ?? "Administrador";
$mensagem_status = "";

// =========================
// PROCESSAR CADASTRO
// =========================
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['adicionar_produto'])) {

    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $imagem_url = trim($_POST['imagem_url']);
    $estoque = intval($_POST['estoque']);

    if (empty($nome) || $preco <= 0) {
        $mensagem_status = "<p class='error'>Preencha nome e preço válido.</p>";
    } else {
        $sql = "INSERT INTO produtos (nome, descricao, preco, imagem_url, estoque) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssdsi", $nome, $descricao, $preco, $imagem_url, $estoque);

            if ($stmt->execute()) {
                $mensagem_status = "<p class='success'>Produto <b>$nome</b> cadastrado com sucesso!</p>";
            } else {
                $mensagem_status = "<p class='error'>Erro: " . $stmt->error . "</p>";
            }

            $stmt->close();
        } else {
            $mensagem_status = "<p class='error'>Erro ao preparar: {$conn->error}</p>";
        }
    }
}

// =========================
// BUSCAR PRODUTOS
// =========================

$produtos = [];
$sql_select = "SELECT id, nome, preco, estoque, imagem_url FROM produtos ORDER BY nome ASC";
$resultado = $conn->query($sql_select);

if ($resultado && $resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        $produtos[] = $row;
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Produtos | Admin</title>
    <link rel="stylesheet" href="style.css">
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

        /* ===== FORMULÁRIO ===== */
        .form-container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
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

        .btn-edit {
            background-color: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-edit:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-delete:hover {
            background-color: #c82333;
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

        /* ===== ESTADOS VAZIOS ===== */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        /* ===== BADGES DE ESTOQUE ===== */
        .stock-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .stock-high {
            background-color: #28a745;
            color: white;
        }

        .stock-medium {
            background-color: #ffc107;
            color: #333;
        }

        .stock-low {
            background-color: #dc3545;
            color: white;
        }

        .stock-out {
            background-color: #6c757d;
            color: white;
        }

        /* ===== AÇÕES ===== */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
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

            .table-container {
                padding: 1rem;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
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
    <aside class="admin-sidebar">
        <h3>Gestão da Loja</h3>
        <ul>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li class="active"><a href="admin_produtos.php">Produtos</a></li>
            <li><a href="admin_pedidos.php">Pedidos</a></li>
            <li><a href="admin_usuarios.php">Usuários</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <h1>Gestão de Produtos</h1>
        <?php echo $mensagem_status; ?>

        <!-- FORMULÁRIO DE ADIÇÃO -->
        <div class="form-container">
            <h3>Cadastrar Novo Produto</h3>
            <form action="" method="POST">
                <input type="hidden" name="adicionar_produto" value="1">

                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" required>
                </div>

                <div class="form-group">
                    <label>Descrição:</label>
                    <textarea name="descricao" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Preço (R$):</label>
                    <input type="number" step="0.01" name="preco" required>
                </div>

                <div class="form-group">
                    <label>URL da Imagem:</label>
                    <input type="text" name="imagem_url" required>
                </div>

                <div class="form-group">
                    <label>Estoque:</label>
                    <input type="number" name="estoque" required>
                </div>

                <button type="submit" class="btn btn-primary">Cadastrar Produto</button>
            </form>
        </div>

        <!-- LISTA DE PRODUTOS -->
        <div class="table-container">
            <h3>Catálogo Atual</h3>
            <?php if (count($produtos) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): 
                            // Definir badge de estoque
                            $estoque_class = '';
                            if ($p['estoque'] == 0) {
                                $estoque_class = 'stock-out';
                            } elseif ($p['estoque'] <= 5) {
                                $estoque_class = 'stock-low';
                            } elseif ($p['estoque'] <= 15) {
                                $estoque_class = 'stock-medium';
                            } else {
                                $estoque_class = 'stock-high';
                            }
                        ?>
                            <tr>
                                <td>#<?= $p['id'] ?></td>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="stock-badge <?= $estoque_class ?>">
                                        <?= $p['estoque'] ?> unid.
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admin_editar_produto.php?id=<?= $p['id'] ?>" class="btn btn-edit">Editar</a>
                                        <a href="admin_apagar_produto.php?id=<?= $p['id'] ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Tem certeza que deseja excluir este produto?');">
                                           Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nenhum produto cadastrado.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>