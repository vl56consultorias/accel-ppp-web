<?php
declare(strict_types=1);

// Inicialização comum para as rotas de API
require_once __DIR__ . '/../../includes/SecurityConfig.php';
require_once __DIR__ . '/../../includes/Usuario.php';

SecurityConfig::init();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioService = new Usuario();

/**
 * Responde em JSON e encerra a execução.
 */
function jsonResponse(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/**
 * Obtém o corpo JSON ou retorna array vazio se não houver.
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Garante que o método HTTP seja o esperado.
 */
function requireMethod(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        jsonResponse(405, ['error' => 'Método não permitido']);
    }
}

/**
 * Extrai token CSRF do header ou do corpo.
 */
function extractCsrfToken(array $input): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headers = is_array($headers) ? array_change_key_case($headers, CASE_LOWER) : [];
    if (!empty($headers['x-csrf-token'])) {
        return $headers['x-csrf-token'];
    }
    if (isset($input['csrf_token'])) {
        return (string) $input['csrf_token'];
    }
    return null;
}

/**
 * Valida token CSRF presente na requisição.
 */
function requireCsrf(array $input): void {
    $token = extractCsrfToken($input);
    if (!$token || !SecurityConfig::validateCSRFToken($token)) {
        jsonResponse(419, ['error' => 'CSRF token inválido ou ausente']);
    }
}

/**
 * Garante sessão ativa e permissões.
 */
function ensureAuthenticated(string $permission = 'leitura', Usuario $usuarioService = null): array {
    if (empty($_SESSION['logado']) || empty($_SESSION['usuario_id'])) {
        jsonResponse(401, ['error' => 'Não autenticado']);
    }

    if (!SecurityConfig::checkSessionTimeout()) {
        jsonResponse(401, ['error' => 'Sessão expirada']);
    }

    $usuarioService = $usuarioService ?? new Usuario();
    $usuario = $usuarioService->buscarPorId((int) $_SESSION['usuario_id']);
    if (!$usuario || !$usuario['ativo']) {
        session_unset();
        session_destroy();
        jsonResponse(401, ['error' => 'Usuário não encontrado ou inativo']);
    }

    $permitido = match ($permission) {
        'admin' => $usuarioService->isAdmin((int) $usuario['id']),
        'escrita' => $usuarioService->temPermissaoEscrita((int) $usuario['id']),
        default => $usuarioService->temPermissaoLeitura((int) $usuario['id']),
    };

    if (!$permitido) {
        jsonResponse(403, ['error' => 'Permissão negada']);
    }

    return $usuario;
}

/**
 * Remove campos sensíveis antes de retornar o usuário.
 */
function sanitizeUser(array $usuario): array {
    unset($usuario['senha_hash']);
    return $usuario;
}

// Pré-válida CORS controlado quando origem é enviada
SecurityConfig::validateOrigin();

?>
