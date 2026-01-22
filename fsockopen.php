<?php
/**
 * Conectar ao concentrador via fsockopen
 * Tenta estabelecer conexão; se falhar, $fp fica null
 */

$fp = null;
$errno = null;
$errstr = null;

try {
    $fp = @fsockopen(
        CONCENTRADOR_HOST,
        CONCENTRADOR_PORT,
        $errno,
        $errstr,
        CONCENTRADOR_TIMEOUT
    );
    
    if (!$fp) {
        // Falha na conexão - deixa $fp null para que index.php trate graciosamente
        $fp = null;
    }
} catch (Throwable $e) {
    $fp = null;
}

?>
