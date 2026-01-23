<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo '<!-- DEBUG: Entrou na página listar_usuario.php -->';

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../includes/SecurityConfig.php';

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

?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle - Usuários</title>
    <link rel="stylesheet" href="/global.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/crud.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-gradient">
<div class="app-shell">
    <?php include __DIR__ . '/../../includes/sidebar-navbar.inc.php'; ?>
    <main class="main-content">
        <div class="container-xl py-4">
            <div class="card">
                <div class="card-header d-flex justify-between align-items-center">
                    <h2 class="card-title m-0">Lista de Usuários</h2>
                    <a href="/crud/api/criar_usuario.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Novo Usuário</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Login</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$usuario['id']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['login']); ?></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars((string)$usuario['role']); ?></span></td>
                                    <td>
                                        <?php if ($usuario['ativo']): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/crud/api/editar_usuario.php?id=<?php echo urlencode($usuario['id']); ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <form action="/crud/api/excluir.php" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$usuario['id']); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SecurityConfig::generateCSRFToken()); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>