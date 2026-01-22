<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$input = array_merge($_POST, getJsonInput());

$login = trim($input['login'] ?? '');
$senha = (string) ($input['senha'] ?? '');

if ($login === '' || $senha === '') {
	jsonResponse(400, ['error' => 'Login e senha são obrigatórios']);
}

if (!SecurityConfig::checkRateLimit($login)) {
	jsonResponse(429, ['error' => 'Muitas tentativas. Aguarde e tente novamente.']);
}

$resultado = $usuarioService->autenticar($login, $senha);

if (!$resultado['success']) {
	SecurityConfig::logSecurityEvent('login_failed', ['login' => $login]);
	jsonResponse(401, ['error' => $resultado['message'] ?? 'Credenciais inválidas']);
}

SecurityConfig::clearRateLimit($login);

$_SESSION['logado'] = true;
$_SESSION['usuario_id'] = $resultado['usuario']['id'];
$_SESSION['usuario'] = $resultado['usuario'];
$csrf = SecurityConfig::generateCSRFToken();

jsonResponse(200, [
	'success' => true,
	'usuario' => sanitizeUser($resultado['usuario']),
	'csrf_token' => $csrf
]);

?>
