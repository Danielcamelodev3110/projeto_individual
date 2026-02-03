<?php

define('DB_HOST', 'localhost');
define('DB_USUARIO', 'root');
define('DB_SENHA', '');
define('DB_NOME', 'minha_loja');

$conn = new mysqli(DB_HOST, DB_USUARIO, DB_SENHA, DB_NOME);

if ($conn->connect_error) {
    die("Falaha ao conectar ao MYSQL: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
