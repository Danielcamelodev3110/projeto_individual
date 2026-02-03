<?php
// admin_editar_usuario.php — Edição de Usuário

session_start();
require_once 'config.php';

// 1. Verificação de Segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!$logado || $tipo_usuario !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensagem_status = "";
$usuario_a_editar = null; 

$id_usuario = $_GET['id'] ?? null;

// =========================
// 2. BUSCAR DADOS DO USUÁRIO
// =========================
if (is_numeric($id_usuario)) {
    $id_usuario = intval($id_usuario);

    $sql_select = "SELECT id, nome, email, tipo FROM usuarios WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);

    if ($stmt_select) {
        $stmt_select->bind_param("i", $id_usuario);
        $stmt_select->execute();
        $resultado = $stmt_select->get_result();

        if ($resultado->num_rows === 1) {
            $usuario_a_editar = $resultado->fetch_assoc();
        } else {
            $mensagem_status = "<p class='error'>Usuário não encontrado.</p>";
        }
        $stmt_select->close();
    } else {
        $mensagem_status = "<p class='error'>Erro ao preparar a busca: {$conn->error}</p>";
    }
} else {
    $mensagem_status = "<p class='error'>ID do usuário inválido.</p>";
    // Redireciona se não houver ID válido
    if (!isset($_POST['editar_usuario'])) {
        $conn->close();
        header('Location: admin_usuarios.php');
        exit;
    }
}

// =========================
// 3. PROCESSAR EDIÇÃO (POST)
// =========================
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['editar_usuario']) && $usuario_a_editar) {

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $tipo = trim($_POST['tipo']);
    $id_para_atualizar = $usuario_a_editar['id']; 

    // Opcional: Processar mudança de senha (se o campo estiver preenchido)
    $nova_senha = trim($_POST['nova_senha'] ?? '');
    
    // Inicia a construção da query de UPDATE
    $sql_update = "UPDATE usuarios SET nome = ?, email = ?, tipo = ?";
    $tipos_param = "sssi"; // Tipos para nome, email, tipo e id

    $parametros = [$nome, $email, $tipo];

    if (!empty($nova_senha)) {
        // Adiciona a senha à query e aos parâmetros
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql_update .= ", senha = ?";
        $tipos_param = "ssssi"; // ssss para nome, email, tipo, senha e id
        $parametros[] = $senha_hash;
    }

    $sql_update .= " WHERE id = ?";
    $parametros[] = $id_para_atualizar; // Adiciona o ID no final

    // Ajusta os tipos de parâmetros para mysqli_stmt_bind_param
    // O tipo para a última posição (id) deve ser 'i'
    $tipos_param = substr($tipos_param, 0, -1) . 'i'; 
    
    $stmt_update = $conn->prepare($sql_update);

    if ($stmt_update) {
        // Usando call_user_func_array para passar os parâmetros
        $bind_params = array_merge([$tipos_param], $parametros);
        $refs = [];
        foreach($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }
        call_user_func_array([$stmt_update, 'bind_param'], $refs);


        if ($stmt_update->execute()) {
            $mensagem_status = "<p class='success'>Usuário <b>{$nome}</b> atualizado com sucesso!</p>";
            // Atualiza os dados locais para refletir a mudança no formulário
            $usuario_a_editar = array_merge($usuario_a_editar, ['nome' => $nome, 'email' => $email, 'tipo' => $tipo]);
        } else {
            $mensagem_status = "<p class='error'>Erro ao atualizar: " . $stmt_update->error . "</p>";
        }

        $stmt_update->close();
    } else {
        $mensagem_status = "<p class='error'>Erro ao preparar a atualização: {$conn->error}</p>";
    }
}

$conn->close();

// Se o usuário não foi encontrado
if (!$usuario_a_editar) {
    echo "<!DOCTYPE html><html lang='pt-br'><head><title>Erro</title><style>/*CSS aqui*/</style></head><body><div style='padding: 20px;'><h1>Erro</h1>{$mensagem_status}<p><a href='admin_usuarios.php'>Voltar para Gestão de Usuários</a></p></div></body></html>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário | Admin</title>
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
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
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
            text-decoration: none;
            padding: 0.5rem 1rem;
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

        /* ===== ZONA DE PERIGO ===== */
        .danger-zone {
            border: 2px solid #dc3545;
            padding: 1.5rem;
            border-radius: 6px;
            margin-top: 2rem;
            background-color: #fff5f5;
        }

        .danger-zone h4 {
            color: #dc3545;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .danger-zone p {
            color: #721c24;
            margin-bottom: 1rem;
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

            .form-container {
                padding: 1rem;
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
            <li><a href="admin_pedidos.php">Pedidos</a></li>
            <li class="active"><a href="admin_usuarios.php">Usuários</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <h1>Editar Usuário: <?php echo htmlspecialchars($usuario_a_editar['nome']); ?></h1>
        
        <a href="admin_usuarios.php" class="btn btn-secondary">← Voltar para a lista de usuários</a>

        <?php echo $mensagem_status; ?>

        <div class="form-container">
            <h3>Dados da Conta #<?php echo $usuario_a_editar['id']; ?></h3>

            <form action="admin_editar_usuario.php?id=<?php echo $usuario_a_editar['id']; ?>" method="POST">
                <input type="hidden" name="editar_usuario" value="1">

                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" value="<?php echo htmlspecialchars($usuario_a_editar['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($usuario_a_editar['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Usuário:</label>
                    <select name="tipo" required>
                        <option value="cliente" <?php echo $usuario_a_editar['tipo'] == 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                        <option value="admin" <?php echo $usuario_a_editar['tipo'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <div class="danger-zone">
                    <h4>Alteração de Senha</h4>
                    <p>Preencha apenas se desejar alterar a senha do usuário.</p>
                    <div class="form-group">
                        <label>Nova Senha:</label>
                        <input type="password" name="nova_senha" placeholder="Deixe vazio para não alterar a senha">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </form>
        </div>
    </main>
</div>
</body>
</html>