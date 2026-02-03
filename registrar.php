<?php
/**
 * registrar.php
 * Script para o registro de novos clientes na loja.
 *
 * LÓGICA DE PROCESSAMENTO PHP
 *
 */

session_start(); // 1. Iniciamos a sessão (útil para, talvez, logar o cliente após o registo)
require_once 'config.php'; // ⚠️ Assume que 'config.php' contém a conexão com o DB ($conn)

$mensagem_status = ""; // Para guardar mensagens de sucesso ou erro

// 1. Verificar se o formulário de registro foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_cliente'])) {

    // 2. Obter e Limpar os dados do formulário
    $nome             = trim($_POST['nome']);
    $email            = trim($_POST['email']);
    $senha            = $_POST['senha'];
    $confirma_senha   = $_POST['confirma_senha'];

    // 3. Validação Inicial
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $mensagem_status = "<p class='erro'>Todos os campos são obrigatórios.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_status = "<p class='erro'>O formato do email é inválido.</p>";
    } elseif ($senha !== $confirma_senha) {
        $mensagem_status = "<p class='erro'>As senhas não coincidem. Por favor, verifique.</p>";
    } elseif (strlen($senha) < 6) {
        $mensagem_status = "<p class='erro'>A senha deve ter pelo menos 6 caracteres.</p>";
    } else {

        // 4. Verificar se o Email já existe no Banco de Dados
        $sql_verificar = "SELECT id FROM usuarios WHERE email = ?";
        $stmt_verificar = $conn->prepare($sql_verificar);
        $stmt_verificar->bind_param("s", $email);
        $stmt_verificar->execute();
        $stmt_verificar->store_result();

        if ($stmt_verificar->num_rows > 0) {
            $mensagem_status = "<p class='erro'>Este email já está registrado. Tente fazer login.</p>";
        } else {
            // 5. CRIPTOGRAFAR A SENHA (PASSO DE SEGURANÇA VITAL!)
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // 6. Inserir o novo usuário (com tipo 'cliente')
            $tipo_usuario = 'cliente'; // Definimos explicitamente o tipo

            $sql_inserir = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";

            $stmt_inserir = $conn->prepare($sql_inserir);
            // "ssss" => quatro strings (nome, email, senha_hash, tipo)
            $stmt_inserir->bind_param("ssss", $nome, $email, $senha_hash, $tipo_usuario);

            if ($stmt_inserir->execute()) {
                // Sucesso no Registo
                $mensagem_status = "<p class='sucesso'>Registro realizado com sucesso! Pode agora fazer login.</p>";

                // Opcional: Redirecionar para login após o registo (para manter a página limpa)
                // header("Location: login.php?registro=sucesso");
                // exit;
            } else {
                // Erro ao Registrar
                $mensagem_status = "<p class='erro'>Erro ao registrar: " . $stmt_inserir->error . "</p>";
            }
            $stmt_inserir->close();
        }
        $stmt_verificar->close();
    }
}

// ⚠️ A conexão com o banco de dados ($conn) deve ser fechada, provavelmente no final do script.
// Onde exatamente depende da estrutura (ex: pode estar em 'config.php' ou no final do arquivo).
// Exemplo:
$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Novo Cliente</title>
    <link rel="stylesheet" href="css/cadastro.css">
</head>
<body>

   <div class="container">
        <h1> Desesperados.com </h1>
        <br>
<p id="primeiro" style="color:white;">
  Para pessoas esquecidas que não lembraram de comprar presente para alguém especial em virtude da vida corrida.
</p>  
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
    <br> <br>

    <div class="container2">
            <h1>Crie sua Conta de Cliente</h1>

            <?php echo $mensagem_status; // Exibe a mensagem de status (sucesso/erro) ?>

            <form action="registrar.php" method="POST" class="form-register">
                <input type="hidden" name="registrar_cliente" value="1">

                <div>
                    <label for="nome" style="display: none;">Nome Completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu Nome Completo" required>
                </div>

                <div>
                    <label for="email" style="display: none;">Email</label>
                    <input type="email" id="email" name="email" placeholder="Seu Melhor Email" required>
                </div>

                <div>
                    <label for="senha" style="display: none;">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Crie uma Senha (min. 6 caracteres)" required>
                </div>

                <div>
                    <label for="confirma_senha" style="display: none;">Confirme a Senha</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Confirme a Senha" required>
                </div>

                <button type="submit">Registrar</button>
            </form>

            <p style="margin-top: 1.5rem; color: #ffffffff;">
                Já tem conta? <a href="login.php">Faça Login</a>
            </p>

        </div>
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
</body>
</html>