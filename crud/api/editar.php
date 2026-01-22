<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

requireMethod('POST');
$input = array_merge($_POST, getJsonInput());
requireCsrf($input);

ensureAuthenticated('escrita', $usuarioService);

$id = isset($input['id']) ? (int) $input['id'] : 0;
if ($id <= 0) {
	jsonResponse(400, ['error' => 'ID inválido']);
}

$dados = [
	'nome' => trim($input['nome'] ?? ''),
	'email' => trim($input['email'] ?? ''),
	'role' => trim($input['role'] ?? ''),
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

// Remover campos vazios para não sobrepor valores existentes com string vazia
foreach (['telefone','cpf','data_nascimento','genero','endereco','cidade','estado','cep','foto','email','role','nome'] as $campo) {
	if ($dados[$campo] === '') unset($dados[$campo]);
}

$resultado = $usuarioService->atualizar($id, $dados);

if ($resultado['success']) {
	jsonResponse(200, [
		'success' => true,
		'message' => $resultado['message']
	]);
}

jsonResponse(400, [
	'success' => false,
	'error' => $resultado['message'] ?? 'Falha ao atualizar usuário'
]);

?>
