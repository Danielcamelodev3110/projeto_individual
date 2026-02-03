<?php
// admin_editar_produto.php — Edição de Produto

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
$produto = null;

// Buscar produto para edição
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $produto = $resultado->fetch_assoc();
        } else {
            $mensagem_status = "<p class='error'>Produto não encontrado.</p>";
        }
        $stmt->close();
    }
}

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['atualizar_produto'])) {
    $id = intval($_POST['id']);
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $imagem_url = trim($_POST['imagem_url']);
    $estoque = intval($_POST['estoque']);

    if (empty($nome) || $preco <= 0) {
        $mensagem_status = "<p class='error'>Preencha nome e preço válido.</p>";
    } else {
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, imagem_url = ?, estoque = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssdsii", $nome, $descricao, $preco, $imagem_url, $estoque, $id);

            if ($stmt->execute()) {
                $mensagem_status = "<p class='success'>Produto <b>$nome</b> atualizado com sucesso!</p>";
                // Atualizar dados do produto na tela
                $produto = [
                    'id' => $id,
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'preco' => $preco,
                    'imagem_url' => $imagem_url,
                    'estoque' => $estoque
                ];
            } else {
                $mensagem_status = "<p class='error'>Erro ao atualizar: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $mensagem_status = "<p class='error'>Erro ao preparar: {$conn->error}</p>";
        }
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
    <title>Editar Produto | Admin</title>
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
            max-width: 600px;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        /* ===== PREVIEW DA IMAGEM ===== */
        .image-preview {
            margin-top: 0.5rem;
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid #ddd;
            display: block;
        }

        .image-preview.hidden {
            display: none;
        }

        /* ===== BOTÕES ===== */
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

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

            .form-container {
                padding: 1rem;
                max-width: 100%;
            }

            .btn-group {
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
        <h1>Editar Produto</h1>
        
        <a href="admin_produtos.php" class="btn btn-secondary">← Voltar para Produtos</a>

        <?php echo $mensagem_status; ?>

        <?php if ($produto): ?>
            <div class="form-container">
                <h3>Editar Produto #<?php echo $produto['id']; ?></h3>

                <form action="" method="POST">
                    <input type="hidden" name="atualizar_produto" value="1">
                    <input type="hidden" name="id" value="<?php echo $produto['id']; ?>">

                    <div class="form-group">
                        <label>Nome:</label>
                        <input type="text" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Descrição:</label>
                        <textarea name="descricao"><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Preço (R$):</label>
                        <input type="number" step="0.01" name="preco" value="<?php echo $produto['preco']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>URL da Imagem:</label>
                        <input type="text" name="imagem_url" value="<?php echo htmlspecialchars($produto['imagem_url']); ?>" required 
                               oninput="updateImagePreview(this.value)">
                        <?php if (!empty($produto['imagem_url'])): ?>
                            <img src="<?php echo htmlspecialchars($produto['imagem_url']); ?>" 
                                 alt="Preview" 
                                 class="image-preview" 
                                 id="imagePreview">
                        <?php else: ?>
                            <img src="" alt="Preview" class="image-preview hidden" id="imagePreview">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Estoque:</label>
                        <input type="number" name="estoque" value="<?php echo $produto['estoque']; ?>" required>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="admin_produtos.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>

            <script>
                function updateImagePreview(url) {
                    const preview = document.getElementById('imagePreview');
                    if (url.trim() !== '') {
                        preview.src = url;
                        preview.classList.remove('hidden');
                    } else {
                        preview.classList.add('hidden');
                    }
                }

                // Handle image loading errors
                document.getElementById('imagePreview').onerror = function() {
                    this.classList.add('hidden');
                };
            </script>
        <?php else: ?>
            <div class="empty-state">
                <p>Produto não encontrado.</p>
                <a href="admin_produtos.php" class="btn btn-secondary">Voltar para Produtos</a>
            </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>