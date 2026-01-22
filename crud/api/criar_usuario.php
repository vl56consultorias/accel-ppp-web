<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$input = array_merge($_POST, getJsonInput());
requireCsrf($input);

ensureAuthenticated('escrita', $usuarioService);

$dados = [
	'nome' => trim($input['nome'] ?? ''),
	'email' => trim($input['email'] ?? ''),
	'login' => trim($input['login'] ?? ''),
	'senha' => (string) ($input['senha'] ?? ''),
	'role' => trim($input['role'] ?? '001'),
	'telefone' => trim($input['telefone'] ?? ''),
	'cpf' => trim($input['cpf'] ?? ''),
	'data_nascimento' => trim($input['data_nascimento'] ?? ''),
	'genero' => trim($input['genero'] ?? ''),
	'endereco' => trim($input['endereco'] ?? ''),
	'cidade' => trim($input['cidade'] ?? ''),
	'estado' => trim($input['estado'] ?? ''),
	'cep' => trim($input['cep'] ?? ''),
	'foto' => trim($input['foto'] ?? '')
];

$resultado = $usuarioService->criar($dados);

if ($resultado['success']) {
	jsonResponse(201, [
		'success' => true,
		'id' => $resultado['id'],
		'message' => $resultado['message']
	]);
}

jsonResponse(400, [
	'success' => false,
	'error' => $resultado['message'] ?? 'Falha ao criar usuário'
]);

?>
