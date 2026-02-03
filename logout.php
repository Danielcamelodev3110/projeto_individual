<?php
/**
 * logout.php
 * * Script para finalizar a sessão do usuário e redirecionar para o login.
 * */

// 1. Iniciar a Sessão
session_start();

// 2. Destruir todas as variáveis de sessão (limpar a "memória")
$_SESSION = array(); // Limpa o array de sessão

// 3. Destruir a sessão em si
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// 4. Redirecionar para a página de login
header("Location: login.php");
exit;
?>