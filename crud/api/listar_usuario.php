<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('GET');
ensureAuthenticated('leitura', $usuarioService);

$filtros = [];
if (isset($_GET['ativo']) && $_GET['ativo'] !== '') {
    $filtros['ativo'] = (int) $_GET['ativo'] === 1 ? 1 : 0;
}
if (!empty($_GET['busca'])) {
    $filtros['busca'] = trim((string) $_GET['busca']);
}

$usuarios = array_map(static function ($u) {
    return sanitizeUser($u);
}, $usuarioService->listar($filtros));

jsonResponse(200, [
    'data' => $usuarios,
    'count' => count($usuarios),
    'csrf_token' => SecurityConfig::generateCSRFToken()
]);

?>