<?php
// admin_apagar_usuario.php — Processa a exclusão de um usuário

session_start();
require_once 'config.php';

// 1. Verificação de Segurança
$logado = $_SESSION['logado'] ?? false;
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'cliente';
$usuario_logado_id = $_SESSION['usuario_id'] ?? 0;

if (!$logado || $tipo_usuario !== 'admin') {
    header('Location: login.php');
    exit;
}

// 2. Coleta e Validação do ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_usuarios.php');
    exit;
}

$id_usuario_a_apagar = intval($_GET['id']);

// 3. Verificação Crítica: Impedir que o administrador apague a própria conta!
if ($id_usuario_a_apagar === $usuario_logado_id) {
    // Redireciona com mensagem de erro, pois não se pode apagar a si mesmo
    // Você precisará adaptar a exibição de mensagens de status em admin_usuarios.php
    header('Location: admin_usuarios.php?status=erro&msg=' . urlencode('Você não pode apagar sua própria conta de administrador.'));
    exit;
}

// 4. Excluir do Banco de Dados
$sql = "DELETE FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id_usuario_a_apagar);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        // Redireciona com mensagem de sucesso
        header('Location: admin_usuarios.php?status=sucesso&msg=' . urlencode('Usuário ID ' . $id_usuario_a_apagar . ' excluído com sucesso!'));
        exit;
    } else {
        $erro = $stmt->error;
        $stmt->close();
        $conn->close();
        // Redireciona com erro
        header('Location: admin_usuarios.php?status=erro&msg=' . urlencode('Erro ao excluir usuário: ' . $erro));
        exit;
    }

} else {
    // Erro na preparação
    $conn->close();
    header('Location: admin_usuarios.php?status=erro&msg=' . urlencode('Erro ao preparar a exclusão: ' . $conn->error));
    exit;
}

?>