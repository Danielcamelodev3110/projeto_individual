<?php 

require_once 'config.php';

echo "Inicializando atualização da senha do admin...<br>";

$senha_plana = 'admin123';
$email_admin = 'admin@loja.com';
$senha_hash = password_hash($senha_plana, PASSWORD_DEFAULT);
$sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Erro ao preparar o query: " . $conn->error);
}

$stmt->bind_param("ss", $senha_hash, $email_admin);

if ($stmt->execute()) {
    echo "Sucesso! A senha do admin 'admin@loja.com' foi atualizada para um hash seguro.";
} else {
    echo "Erro ao atualizar a senha: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>