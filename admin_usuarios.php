<?php
/*
* admin_usuarios.php
* Visualização e gestão dos usuários (clientes e admins) do sistema.
*
* LÓGICA DE SEGURANÇA E CONSULTA PHP
* ---
*/ 

// 1. Iniciar a sessão e segurança
session_start();
require_once 'config.php';

// Verificação de segurança (igual aos outros painéis admin)
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';

if (!$logado || $tipo_usuario !== 'admin') {
    header("Location: login.php");
    exit;
}

$nome_admin = $_SESSION['usuario_nome'];
$usuarios = [];
$mensagem_status = "";

// 2. Consultar a Lista de Usuários.
// Não selecionamos a coluna 'senha' diretamente do banco por segurança
$sql_select_usuarios = "
    SELECT
        id,
        nome,
        email,
        tipo,
        data_criacao
    FROM
        usuarios
    ORDER BY
        data_criacao DESC
";

$resultado = $conn->query($sql_select_usuarios);

if ($resultado) {
    if ($resultado->num_rows > 0) {
        // Loop para guardar cada linha do banco de dados no nosso array $usuarios
        while($row = $resultado->fetch_assoc()) {
            $usuarios[] = $row;
        }
    } else {
        $mensagem_status = "<p class='info'>Ainda não há usuários registados no sistema.</p>";
    }
} else {
    $mensagem_status = "<p class='error'>Erro ao consultar usuários: " . $conn->error . "</p>";
}

// Fechar a conexão no final
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários | Admin</title>
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

        .badge.admin {
            background-color: #e6d539;
            color: #333;
        }

        .badge.cliente {
            background-color: #007bff;
            color: white;
        }

        .badge.anfitriao {
            background-color: #28a745;
            color: white;
        }

        /* ===== BOTÕES DE AÇÃO ===== */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-edit {
            background-color: #007bff;
            color: white;
        }

        .btn-edit:hover {
            background-color: #0056b3;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
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

        .info {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
            <li><a href="admin_produtos.php">Produtos</a></li>
            <li><a href="admin_pedidos.php">Pedidos</a></li>
            <li class="active"><a href="admin_usuarios.php">Usuários</a></li>
        </ul>
    </aside>

    <main class="admin-content">
        <h1>Gestão de Usuários</h1>
                <?php echo $mensagem_status; ?>
        
        <div class="table-container">
            <h3>Lista de Usuários Registrados</h3>

            <?php if (count($usuarios) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Data de Registro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario):
                            // Formatamos a data
                            $data_formatada = (new DateTime($usuario['data_criacao']))->format('d/m/Y H:i');
                        ?>
                        <tr>
                            <td>#<?php echo $usuario['id']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $usuario['tipo']; ?>">
                                    <?php echo htmlspecialchars($usuario['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo $data_formatada; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-edit">Editar</a>
                                    <a href="admin_excluir_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-delete" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nenhum usuário encontrado no sistema.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>