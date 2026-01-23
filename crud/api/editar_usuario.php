<?php
require_once __DIR__ . '/../../includes/SecurityConfig.php';
require_once __DIR__ . '/bootstrap.php';
// Buscar usuário pelo ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuario = $id ? $usuarioService->buscar($id) : null;
?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário</title>
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
                <div class="card-header"><h2 class="card-title m-0">Editar Usuário</h2></div>
                <div class="card-body">
                    <form method="post" action="/crud/api/editar_usuario.php">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($usuario['id'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="input w-100" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="input w-100" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="login" class="form-label">Login</label>
                            <input type="text" class="input w-100" id="login" name="login" value="<?php echo htmlspecialchars($usuario['login'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Permissão</label>
                            <select class="input w-100" id="role" name="role" required>
                                <option value="111" <?php if(($usuario['role'] ?? '')==='111') echo 'selected'; ?>>Administrador</option>
                                <option value="010" <?php if(($usuario['role'] ?? '')==='010') echo 'selected'; ?>>Escrita</option>
                                <option value="001" <?php if(($usuario['role'] ?? '')==='001') echo 'selected'; ?>>Leitura</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Nova Senha</label>
                            <input type="password" class="input w-100" id="senha" name="senha">
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(SecurityConfig::generateCSRFToken()); ?>">
                        <button type="submit" class="btn btn-primary w-100">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
