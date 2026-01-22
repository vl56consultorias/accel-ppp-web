<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$input = array_merge($_POST, getJsonInput());
requireCsrf($input);

$usuarioLogado = ensureAuthenticated('admin', $usuarioService);

$id = isset($input['id']) ? (int) $input['id'] : 0;
if ($id <= 0) {
	jsonResponse(400, ['error' => 'ID inválido']);
}

if ($usuarioLogado['id'] === $id) {
	jsonResponse(400, ['error' => 'Não é permitido excluir a si mesmo']);
}

$resultado = $usuarioService->deletar($id);

if ($resultado['success']) {
	jsonResponse(200, [
		'success' => true,
		'message' => $resultado['message']
	]);
}

jsonResponse(400, [
	'success' => false,
	'error' => $resultado['message'] ?? 'Falha ao excluir usuário'
]);

?>
