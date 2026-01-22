<?php
/**
 * Configuração geral da aplicação
 */

// Modo debug (desativar em produção)
define('DEBUG', false);

// Configuração do banco de dados
define('DB_PATH', __DIR__ . '/database/usuarios.db');

// Configuração de concentrador (opcional para a página index)
define('CONCENTRADOR_HOST', '127.0.0.1');
define('CONCENTRADOR_PORT', 2001);
define('CONCENTRADOR_TIMEOUT', 3);

// Configuração de sessão
define('SESSION_TIMEOUT', 1800); // 30 minutos

// Configurações de segurança
define('CSRF_TOKEN_LENGTH', 32);

// Logs
define('LOG_PATH', __DIR__ . '/includes/logs');

// Criar diretório de logs se não existir
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

?>
