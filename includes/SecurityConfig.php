<?php
/**
 * Configurações de Segurança do Sistema
 */

class SecurityConfig {
    /**
     * Inicializar configurações de segurança
     */
    public static function init() {
        // Headers de segurança
        self::setSecurityHeaders();
        
        // Configurações de sessão
        self::configureSession();
    }
    
    /**
     * Configurar headers de segurança
     */
    private static function setSecurityHeaders() {
        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (básico)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:; img-src 'self' data:;");
        
        // HTTPS Strict Transport Security (em produção)
        // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    /**
     * Configurar sessão segura
     */
    private static function configureSession() {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '0'); // Mudar para '1' em produção com HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '1800'); // 30 minutos
    }
    
    /**
     * Verificar timeout de sessão (30 minutos)
     */
    public static function checkSessionTimeout() {
        if (isset($_SESSION['last_activity'])) {
            $timeout = 1800; // 30 minutos em segundos
            
            if (time() - $_SESSION['last_activity'] > $timeout) {
                session_unset();
                session_destroy();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Gerar token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitizar output (prevenir XSS)
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar permissão de acesso
     */
    public static function checkPermission($requiredRole) {
        if (!isset($_SESSION['logado']) || !$_SESSION['logado']) {
            return false;
        }
        
        // Carrega a classe de usuário; o arquivo já está no mesmo diretório de SecurityConfig
        require_once __DIR__ . '/Usuario.php';
        $usuario = new Usuario();
        $userId = $_SESSION['usuario_id'];
        
        switch ($requiredRole) {
            case 'admin':
                return $usuario->isAdmin($userId);
            case 'escrita':
                return $usuario->temPermissaoEscrita($userId);
            case 'leitura':
                return $usuario->temPermissaoLeitura($userId);
            default:
                return false;
        }
    }
    
    /**
     * Rate limiting simples para login
     */
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $cacheDir = dirname(__DIR__) . '/data';
        $cacheFile = $cacheDir . '/rate_limit.json';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        // Criar arquivo se não existir
        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, json_encode([]));
        }
        
        $attempts = json_decode(file_get_contents($cacheFile), true);
        $now = time();
        
        // Limpar tentativas antigas
        $attempts = array_filter($attempts, function($data) use ($now, $timeWindow) {
            return ($now - $data['time']) < $timeWindow;
        });
        
        // Contar tentativas do identificador
        $userAttempts = array_filter($attempts, function($data) use ($identifier) {
            return $data['id'] === $identifier;
        });
        
        if (count($userAttempts) >= $maxAttempts) {
            return false; // Bloqueado
        }
        
        // Registrar tentativa
        $attempts[] = [
            'id' => $identifier,
            'time' => $now
        ];
        
        file_put_contents($cacheFile, json_encode($attempts));
        return true; // Permitido
    }
    
    /**
     * Limpar dados de rate limit
     */
    public static function clearRateLimit($identifier) {
        $cacheDir = dirname(__DIR__) . '/data';
        $cacheFile = $cacheDir . '/rate_limit.json';
        
        if (!file_exists($cacheFile)) {
            return;
        }
        
        $attempts = json_decode(file_get_contents($cacheFile), true);
        
        $attempts = array_filter($attempts, function($data) use ($identifier) {
            return $data['id'] !== $identifier;
        });
        
        file_put_contents($cacheFile, json_encode(array_values($attempts)));
    }
    
    /**
     * Validar origem da requisição (CORS controlado)
     */
    public static function validateOrigin() {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Se não há origin (requisições diretas, curl, etc), permitir
        if (empty($origin)) {
            return true;
        }
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
            return true;
        }
        
        return false;
    }
    
    /**
     * Log de tentativas suspeitas
     */
    public static function logSecurityEvent($event, $details = []) {
        $logFile = __DIR__ . '/logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'details' => $details
        ];
        
        file_put_contents(
            $logFile,
            json_encode($entry) . PHP_EOL,
            FILE_APPEND
        );
    }
}
?>
