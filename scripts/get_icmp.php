<?php
session_start();

function hasPermission(string $permissoes, int $bit): bool {
    return isset($permissoes[$bit]) && $permissoes[$bit] === '1';
}

if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    exit('Não autenticado');
}

$permissoes = $_SESSION['usuario']['permissoes'] ?? '000';
if (!hasPermission($permissoes, 2) && !hasPermission($permissoes, 0)) { // leitura ou admin
    http_response_code(403);
    exit('Sem permissão para executar ping');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método não permitido');
}

if (isset($_GET['ip'])) {
    $ip = $_GET['ip'];
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        exit('IP inválido');
    }

    $output = shell_exec('ping -s 1024 -c 5 ' . escapeshellarg($ip));
    echo nl2br(htmlspecialchars($output));
    exit;
}

http_response_code(400);
echo 'IP não fornecido';
?>