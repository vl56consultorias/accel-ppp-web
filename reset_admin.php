<?php
/**
 * Script para resetar senha do admin
 */

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/usuarios.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $novaSenha = 'admin123';
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha WHERE login = :login');
    $stmt->execute([
        ':senha' => $senhaHash,
        ':login' => 'admin'
    ]);
    
    echo "✅ Senha do usuário 'admin' resetada com sucesso!\n";
    echo "Login: admin\n";
    echo "Senha: admin123\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
