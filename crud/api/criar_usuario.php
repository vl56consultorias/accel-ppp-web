<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo '<!-- DEBUG: Entrou na página criar_usuario.php -->';

require_once __DIR__ . '/../../includes/SecurityConfig.php';
require_once __DIR__ . '/bootstrap.php';

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $mensagem = 'Usuário criado com sucesso!';
    } else {
        $mensagem = $resultado['message'] ?? 'Falha ao criar usuário';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Usuário</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/crud.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-gradient">
<div class="app-shell">
    <?php include __DIR__ . '/../../includes/sidebar-navbar.inc.php'; ?>
    <main class="main-content">
        <div class="container-xl py-4">
            <div class="card mx-auto" style="max-width: 520px;">
                <div class="card-header"><h2 class="card-title m-0">Criar Usuário</h2></div>
                <div class="card-body">
                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($mensagem); ?></div>
                    <?php endif; ?>
                    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$resultado['success']): ?>
                    <form method="post" action="/crud/api/criar_usuario.php" autocomplete="off">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="input w-100" id="nome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="input w-100" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="login" class="form-label">Login</label>
                            <input type="text" class="input w-100" id="login" name="login" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Permissão</label>
                            <select class="input w-100" id="role" name="role" required>
                                <option value="111">Administrador</option>
                                <option value="010">Escrita</option>
                                <option value="001">Leitura</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="input w-100" id="senha" name="senha" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SecurityConfig::generateCSRFToken()); ?>">
                        <button type="submit" class="btn btn-primary w-100">Criar Usuário</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
