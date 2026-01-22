<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: /login.php");
    exit;
}

require 'fsockopen.php';

$usuario = $_SESSION['usuario'];
$role = $usuario['role'] ?? '001'; // role do banco: '111' (admin), '010' (escrita), '001' (leitura)

/**
 * Converte role para string binária compatível com o código legado
 * '111' (admin) => '111' (todos)
 * '010' (escrita) => '010' (gravação)
 * '001' (leitura) => '001' (leitura)
 */
$permissoes = $role;

function temPermissao($binario, $bit) {
    return isset($binario[$bit]) && $binario[$bit] === '1';
}

function isValidIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function escapar($dados) {
    return htmlspecialchars($dados, ENT_QUOTES, 'UTF-8');
}

function contarSessoesConectadas($dados) {
    return count($dados);
}

if (isset($_GET['count']) && $_GET['count'] == '1') {
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
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'Sem conexão ao concentrador']);
        exit;
    }

    $cabecalho = [];
    $dados = [];
    if (count($linhas) > 0) {
        $cabecalho = array_map('trim', explode('|', array_shift($linhas)));
        foreach ($linhas as $linha) $dados[] = array_map('trim', explode('|', $linha));
    }

    header('Content-Type: application/json');
    echo json_encode(['count' => contarSessoesConectadas($dados)]);
    exit;
}

function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function formatarUptime($uptime) {
    if (preg_match('/^(\d+)\.(\d{2}:\d{2}:\d{2})$/', trim($uptime), $m)) return "{$m[1]}d {$m[2]}";
    return $uptime;
}

// Coleta inicial
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
    $linhas[] = "Erro ao conectar: " . ($errstr ?? 'sem detalhes') . " (" . ($errno ?? '') . ")";
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
    foreach ($linhas as $linha) $dados[] = array_map('trim', explode('|', $linha));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Painel de Controle - Concentradores</title>
<link rel="stylesheet" href="/global.css">
<link rel="stylesheet" href="/assets/css/dashboard.css">
<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body id="panelBody">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand">
            <span class="sidebar__logo"><i class="bi bi-speedometer2"></i></span>
            <div>
                <div class="sidebar__title">Controle de Sistema</div>
                <div class="sidebar__subtitle">Painel de concentradores</div>
            </div>
        </div>

        <div class="sidebar__user">
            <div class="avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="sidebar__user-info">
                <strong><?= escapar($_SESSION['usuario']['nome'] ?? 'Nome'); ?></strong>
                <span><?= escapar($_SESSION['usuario']['email'] ?? 'email@exemplo.com'); ?></span>
            </div>
        </div>

                <nav class="sidebar__nav" aria-label="Menu principal">
            <div class="nav-group">
                <div class="nav-label">Principal</div>
                <a class="nav-item active" href="/index.php"><i class="bi bi-house"></i> Início</a>
            </div>

            <?php if (temPermissao($permissoes,0)||temPermissao($permissoes,1)||temPermissao($permissoes,2)): ?>
            <div class="nav-group">
                <div class="nav-label">Ferramentas</div>
                        <a class="nav-item" href="#stats"><i class="bi bi-activity"></i> Status Conectados</a>
                        <a class="nav-item" href="/backups/"><i class="bi bi-journal-text"></i> Logs accel-ppp</a>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-speedometer"></i> Tráfego</a>
            </div>
            <?php endif; ?>

            <?php if (temPermissao($permissoes,1)||temPermissao($permissoes,2)): ?>
            <div class="nav-group">
                <div class="nav-label">Configurações</div>
                        <a class="nav-item" href="/crud/api/listar_usuario.php" target="_blank"><i class="bi bi-people"></i> Usuários</a>
                        <a class="nav-item" href="/crud/api/criar_usuario.php" target="_blank"><i class="bi bi-person-plus"></i> Cadastrar usuário</a>
            </div>
            <?php endif; ?>

            <?php if (temPermissao($permissoes,2)): ?>
            <div class="nav-group">
                <div class="nav-label">Integração</div>
                        <a class="nav-item" href="/index.php#tableSection"><i class="bi bi-diagram-3"></i> Listar Sessões</a>
                        <a class="nav-item" href="/index.php#tableSection"><i class="bi bi-table"></i> Tabela Completa</a>
            </div>

            <div class="nav-group">
                <div class="nav-label">Administração</div>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-hdd-network"></i> Configuração de VLAN</a>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-shield-lock"></i> Firewall</a>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-diagram-2"></i> Roteamento OSPF</a>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-diagram-3-fill"></i> Roteamento BGP</a>
                        <a class="nav-item" href="#tableSection"><i class="bi bi-gear"></i> Sistema</a>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar__footer">
            <a class="nav-item nav-item--danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <button class="icon-button" id="sidebarToggle" aria-label="Alternar menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar__title">Sistema para Concentradores</div>
            <div class="topbar__actions">
                <button class="icon-button" id="themeToggle" aria-label="Alternar tema">
                    <i class="bi" id="themeIcon"></i>
                </button>
                <div class="user-chip">
                    <div class="avatar avatar--small"><i class="bi bi-person-fill"></i></div>
                    <span><?= escapar($_SESSION['usuario']['nome'] ?? 'Nome'); ?></span>
                </div>
            </div>
        </header>

        <div class="container-fluid mt-4">
            <div class="page-header" id="stats">
                <div class="breadcrumb">Dashboard / Início</div>
                <h1 class="page-title">Sistema para Concentradores</h1>
                <p class="page-subtitle">Monitoramento em tempo real das sessões e ações rápidas por IP.</p>
                <div class="status-badges">
                    <span class="badge badge-success">Total conectado: <strong id="sessions_active"><?= contarSessoesConectadas($dados); ?></strong></span>
                    <span class="badge badge-info" id="liveStatus">Sincronizado</span>
                </div>
            </div>

            <div class="table-wrapper" id="tableSection">
                <div class="table-header">
                    <div>
                        <h3>Sessões ativas</h3>
                        <p class="table-subtitle">Clique no IP para abrir ações rápidas.</p>
                    </div>
                    <div class="table-actions">
                        <button class="btn btn-outline btn-sm" id="refreshTable"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="tabela" class="table table-striped table-bordered table-hover display nowrap text-center">
                    <thead>
                        <tr>
                            <?php foreach($cabecalho as $col): ?><th><?= escapar($col); ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($dados as $linha): ?>
                        <tr>
                        <?php foreach($linha as $j=>$coluna): ?>
                            <?php if($uptime_index!==null && $j===$uptime_index): ?>
                                <td class="uptime" data-uptime="<?= escapar(formatarUptime($coluna)); ?>"><?= escapar(formatarUptime($coluna)); ?></td>
                            <?php elseif($ip_index!==null && $j===$ip_index): ?>
                                <td class="ip-cell" data-ip="<?= escapar($coluna); ?>" title="Clique para ações">
                                    <?= escapar($coluna); ?>
                                    <div class="ip-actions" role="menu" aria-hidden="true">
                                        <button class="btn btn-sm btn-primary ping-btn"><i class="bi bi-diagram-3"></i> Ping</button>
                                        <button class="btn btn-sm btn-danger desconectar-btn"><i class="bi bi-x-circle"></i> Desconectar</button>
                                        <button class="btn btn-sm btn-info conectados-btn"><i class="bi bi-people"></i> Conectados</button>
                                        <button class="btn btn-sm btn-success trafego-btn"><i class="bi bi-graph-up"></i> Tráfego</button>
                                    </div>
                                </td>
                            <?php else: ?>
                                <td><?= escapar($coluna); ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const csrfToken = '<?= gerarTokenCSRF() ?>';

    // Tema escuro/claro baseado em preferência do sistema + localStorage
    const bodyEl = document.getElementById('panelBody');
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const shouldUseDark = savedTheme ? savedTheme === 'dark' : systemPrefersDark;
    if (shouldUseDark) {
        bodyEl.classList.add('dark-mode');
    }

    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const navLinks = document.querySelectorAll('.sidebar__nav .nav-item');
    const closeSidebar = () => sidebar.classList.remove('is-open');

    const setActiveNav = (href) => {
        navLinks.forEach(link => link.classList.remove('active'));
        const match = Array.from(navLinks).find(link => link.getAttribute('href') === href);
        if (match) match.classList.add('active');
    };

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('is-open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                closeSidebar();
            }
        });
        navLinks.forEach(link => link.addEventListener('click', (ev) => {
            setActiveNav(link.getAttribute('href'));
            if (window.innerWidth <= 1024) closeSidebar();
        }));
    }

    const table = $('#tabela').DataTable({responsive:true,stateSave:true,
        language:{url:'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'},pageLength:10,lengthMenu:[[10,20,50,-1],[10,20,50,'Todos']],
        drawCallback:function(){ document.getElementById('liveStatus').textContent='Atualizado'; }
    });

    document.getElementById('refreshTable').addEventListener('click', ()=> table.ajax ? table.ajax.reload() : table.draw(false));

    $('.uptime').each(function(){ 
        let t=$(this).data('uptime'); 
        let s=timeToSeconds(t); 
        if(s!==null) $(this).data('uptime-seconds',s); 
    });
    setInterval(atualizarUptimes,2000);

    const liveStatusEl = document.getElementById('liveStatus');
    const updateStatus = (text) => { if (liveStatusEl) liveStatusEl.textContent = text; };

    setInterval(()=>{$.getJSON('?count=1', function(data){
        if(data && typeof data.count!=='undefined') $('#sessions_active').text(data.count);
        updateStatus('Atualizado');
    });},2000);

    $('#tabela tbody').on('click','.ip-cell', function(e){ e.stopPropagation(); $('.ip-actions').hide(); const $actions=$(this).find('.ip-actions'); $actions.toggle(); $actions.attr('aria-hidden', $actions.is(':visible')?'false':'true'); });
    $(document).on('click',function(){ $('.ip-actions').hide().attr('aria-hidden','true'); });

    $('#tabela tbody').on('click','.ping-btn', function(e){ e.stopPropagation(); pingUsuario($(this).closest('.ip-cell')[0].dataset.ip); });
    $('#tabela tbody').on('click','.desconectar-btn', function(e){ 
        e.stopPropagation(); const td=$(this).closest('.ip-cell')[0]; 
        Swal.fire({title:'Confirma desconexão?',text:`Deseja desconectar ${td.dataset.ip}?`,icon:'warning',showCancelButton:true,confirmButtonText:'Sim, desconectar',cancelButtonText:'Cancelar'}).then(result=>{ if(result.isConfirmed) desconectarUsuario(td, td.dataset.ip); }); 
    });
    $('#tabela tbody').on('click','.conectados-btn', function(e){ e.stopPropagation(); mostrarConectados($(this).closest('.ip-cell')[0].dataset.ip); });
    $('#tabela tbody').on('click','.trafego-btn', function(e){ e.stopPropagation(); mostrarTrafego($(this).closest('.ip-cell')[0].dataset.ip); });
    $('#tabela tbody').on('click','.ip-actions',function(e){ e.stopPropagation(); });

    function pingUsuario(ip){ fetch(`ping.php?ip=${encodeURIComponent(ip)}`).then(r=>r.text()).then(d=>Swal.fire({title:`Ping: ${ip}`, html:`<pre style="text-align:left;">${escapeHtml(d)}</pre>`, width:700})).catch(()=>Swal.fire('Erro','Não foi possível executar o ping','error'));}
    function desconectarUsuario(td,ip){ fetch('scripts/get_desconecta.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`ip=${encodeURIComponent(ip)}&csrf_token=${encodeURIComponent(csrfToken)}`}).then(r=>r.json()).then(resp=>{ if(resp.status==='success'){ table.row($(td).closest('tr')).remove().draw(false); Swal.fire({icon:'success',title:'Desconectado',text:`Usuário ${ip} foi desconectado`,timer:2000,showConfirmButton:false}); } else Swal.fire('Erro',resp.message||'Falha na desconexão','error'); }); }
    function mostrarConectados(ip){
    fetch(`scripts/get_interfaces.php?ip=${encodeURIComponent(ip)}`)
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success'){
            let html = '<pre style="text-align:left;">';
            data.interfaces.forEach(i => {
                html += `Interface: ${i.nome} | RX: ${i.rx} | TX: ${i.tx}\n`;
            });
            html += '</pre>';
            Swal.fire({title:`Conectados de ${ip}`, html: html, width:600});
        } else {
            Swal.fire('Erro', data.message || 'Falha ao obter interfaces','error');
        }
    })
    .catch(() => Swal.fire('Erro','Não foi possível consultar interfaces','error'));
}

function mostrarTrafego(ip){
    fetch(`scripts/get_trafego.php?ip=${encodeURIComponent(ip)}`)
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success'){
            let html = '<pre style="text-align:left;">';
            html += `RX: ${data.rx} | TX: ${data.tx}\n`;
            html += `Pico RX: ${data.pico_rx} | Pico TX: ${data.pico_tx}\n`;
            html += `Média RX: ${data.media_rx} | Média TX: ${data.media_tx}\n`;
            html += '</pre>';
            Swal.fire({title:`Tráfego de ${ip}`, html: html, width:600});
        } else {
            Swal.fire('Erro', data.message || 'Falha ao obter tráfego','error');
        }
    })
    .catch(() => Swal.fire('Erro','Não foi possível consultar tráfego','error'));
}


    function escapeHtml(text){return text.replace(/[&<>"']/g,m=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"})[m]);}

    function timeToSeconds(uptime){ let m=uptime.match(/(\d+)d (\d+):(\d+):(\d+)/); if(m)return parseInt(m[1])*86400+parseInt(m[2])*3600+parseInt(m[3])*60+parseInt(m[4]); return null; }
    function atualizarUptimes(){ $('.uptime').each(function(){ let sec=$(this).data('uptime-seconds'); if(sec!==undefined){ sec++; $(this).data('uptime-seconds',sec); $(this).text(secondsToDHMS(sec)); } }); }
    function secondsToDHMS(sec){ let d=Math.floor(sec/86400); sec%=86400; let h=Math.floor(sec/3600); sec%=3600; let m=Math.floor(sec/60); let s=sec; return `${d}d ${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`; }

    const themeBtn=document.getElementById('themeToggle'),themeIcon=document.getElementById('themeIcon');
    function setTheme(dark){
        if(dark){
            document.body.classList.add('dark-mode');
            themeIcon.className='bi bi-sun';
            localStorage.setItem('theme','dark');
        } else {
            document.body.classList.remove('dark-mode');
            themeIcon.className='bi bi-moon';
            localStorage.setItem('theme','light');
        }
    }
    themeBtn.addEventListener('click',()=>setTheme(!document.body.classList.contains('dark-mode')));
    setTheme(localStorage.getItem('theme')==='dark');

})();

</script>
</body>
</html>