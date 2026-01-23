<?php
/**
 * Script de teste de autenticação
 */
require_once __DIR__ . '/database/database.php';
require_once __DIR__ . '/includes/Usuario.php';

$usuarioService = new Usuario();

echo "=== TESTE DE AUTENTICAÇÃO ===\n\n";

// 1. Verificar usuários existentes
echo "1. Verificando usuários cadastrados...\n";
$usuarios = $usuarioService->listar();
if ($usuarios['success'] && !empty($usuarios['data'])) {
    echo "Usuários encontrados: " . count($usuarios['data']) . "\n";
    foreach ($usuarios['data'] as $user) {
        echo "  - ID: {$user['id']} | Login: {$user['login']} | Nome: {$user['nome']} | Ativo: {$user['ativo']}\n";
    }
} else {
    echo "Nenhum usuário encontrado.\n";
    echo "\n2. Criando usuário padrão de teste...\n";
    
    $resultado = $usuarioService->criar([
        'nome' => 'Administrador',
        'email' => 'admin@sistema.com',
        'login' => 'admin',
        'senha' => 'admin123',
        'role' => '111', // Todas permissões
        'telefone' => '(00) 00000-0000'
    ]);
    
    if ($resultado['success']) {
        echo "✓ Usuário padrão criado com sucesso!\n";
        echo "  Login: admin\n";
        echo "  Senha: admin123\n";
    } else {
        echo "✗ Erro ao criar usuário: {$resultado['message']}\n";
    }
}

// 3. Testar autenticação
echo "\n3. Testando autenticação com credenciais...\n";
$testLogin = 'admin';
$testSenha = 'admin123';

echo "Tentando login com: $testLogin / $testSenha\n";
$auth = $usuarioService->autenticar($testLogin, $testSenha);

if ($auth['success']) {
    echo "✓ AUTENTICAÇÃO BEM-SUCEDIDA!\n";
    echo "  Usuário: {$auth['usuario']['nome']}\n";
    echo "  Email: {$auth['usuario']['email']}\n";
    echo "  Role: {$auth['usuario']['role']}\n";
} else {
    echo "✗ FALHA NA AUTENTICAÇÃO: {$auth['message']}\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
