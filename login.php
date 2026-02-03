<?php
session_start();

require_once 'config.php';

$erro_login = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $senha_digitada = $_POST['senha'];

    $sql = "SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);

    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if (password_verify($senha_digitada, $usuario['senha'])) {

        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['logado'] = true;

        if ($usuario['tipo'] == 'admin') {
            header("Location: admin_dashboard.php");
            exit;
        } else {
            header("Location: index.html");
            exit;
        } 

} else {
    $erro_login = "Email ou senha inválidos";
}

$stmt->close();

}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar</title>
    <link rel="stylesheet" href="css/entrar.css">
</head>

<body>
    <div class="container">
        <h1> Desesperados.com </h1>
        <br>
        <p id="primeiro" style="color:white;"> Para pessoas esquecidas que não lembraram de comprar presente para alguem especial em virtude da vida corrida. </p>
    </div>
    <header class="main-header">
        <nav>
            <ul class="nav-bar">
             <li> <a href="index.html">Home </a> </li>
                <li><a href="sobre.html"> Sobre</a></li>
                <li><a href="produtos.html">Produtos</a> </li>
                <li><a href="carrinho.php"> Carrinho </a></li>
                <li><a href="login.php"> Entrar</a> </li>
                <li><a href="logout.php"> Sair </a> </li>
            </ul>
        </nav>
    </header>
    <br> <br>
    <div class="container2">
        <h1> Login </h1>
      <form action="login.php" method="POST">
            <label for="email">Email: </label> <br>
            <input type="email" id="email" name="email" required> <br> <br>
            <label for="senha">Senha: </label> <br>
            <input type="password" id="senha" name="senha" required> <br> <br>
            <button type="submit"> Entrar </button>

        </form>

        <br>
        <hr>
         <p>Não tem conta? <a href="registrar.php">Cadastre-se</a></p>
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
</footer>
</body>

</html>


</body>
</html>s