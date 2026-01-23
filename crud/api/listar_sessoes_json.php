<?php
// Endpoint JSON para DataTables - Sessões Ativas
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

require_once __DIR__ . '/../../fsockopen.php';

function escapar($dados) {
    return htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
}

function formatarUptime($uptime) {
    if (preg_match('/^(\d+)\.(\d{2}:\d{2}:\d{2})$/', trim($uptime), $m)) return "{$m[1]}d {$m[2]}";
    return $uptime;
}

$linhas = [];
if (isset($fp) && $fp) {
    stream_set_timeout($fp, 1);
    fwrite($fp, "show sessions\n");
    while (!feof($fp)) {
        $linha = fgets($fp, 4096);
        if ($linha === false) break;
        $linha = trim($linha);
        if ($linha !== '' && !preg_match('/^-+\+-+/', $linha)) $linhas[] = $linha;
        if (stream_get_meta_data($fp)['timed_out']) break;
    }
    fclose($fp);
} else {
    echo json_encode(['data' => [], 'error' => 'Erro ao conectar ao concentrador']);
    exit;
}

$cabecalho = [];
$dados = [];
$ip_index = $uptime_index = null;
if (count($linhas) > 0) {
    $cabecalho = array_map('trim', explode('|', array_shift($linhas)));
    foreach ($cabecalho as $idx => $col) {
        if (strtolower($col) === 'ip') $ip_index = $idx;
        if (strtolower($col) === 'uptime') $uptime_index = $idx;
    }
    foreach ($linhas as $linha) {
        $cols = array_map('trim', explode('|', $linha));
        foreach ($cols as $j => $coluna) {
            if ($uptime_index !== null && $j === $uptime_index) {
                $cols[$j] = formatarUptime($coluna);
            }
        }
        $dados[] = $cols;
    }
}

// DataTables espera o array 'data' com as linhas
echo json_encode(['data' => $dados]);
