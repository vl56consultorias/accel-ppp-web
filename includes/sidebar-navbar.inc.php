<?php
// Sidebar e Navbar modernos para CRUD
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        <span class="sidebar__logo"><i class="bi bi-people"></i></span>
        <div class="sidebar__brand-text">
            <span class="sidebar__title">Usuários</span>
            <span class="sidebar__subtitle">Gestão CRUD</span>
        </div>
    </div>
    <nav class="sidebar__nav" aria-label="Menu CRUD">
        <a class="nav-item" href="/crud/api/listar_usuario.php" data-title="Usuários">
            <i class="bi bi-list-ul"></i> <span>Listar Usuários</span>
        </a>
        <a class="nav-item" href="/crud/api/criar_usuario.php" data-title="Cadastrar usuário">
            <i class="bi bi-person-plus"></i> <span>Cadastrar Usuário</span>
        </a>
    </nav>
</aside>
<header class="topbar">
    <div class="topbar__left">
        <button class="icon-button sidebar-toggle" id="sidebarToggle" aria-label="Alternar menu">
            <i class="bi bi-list"></i>
        </button>
        <span class="topbar__title">Painel de Usuários</span>
    </div>
    <div class="topbar__actions">
        <div class="dropdown profile-dropdown">
            <button class="icon-button" id="profileDropdownBtn" aria-label="Perfil">
                <i class="bi bi-person-circle"></i>
            </button>
            <div class="dropdown-menu" id="profileDropdownMenu">
                <div class="dropdown-header">
                    <div class="avatar-gradient"><i class="bi bi-person"></i></div>
                    <div class="user-info">
                        <div class="user-name">Usuário</div>
                        <div class="user-email">email@exemplo.com</div>
                    </div>
                </div>
                <a class="dropdown-item" href="/index.php">
                    <i class="bi bi-grid"></i> Painel
                </a>
                <a class="dropdown-item" href="#">
                    <i class="bi bi-gear"></i> Configurações
                </a>
                <a class="dropdown-item dropdown-item-danger" href="/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </a>
            </div>
        </div>
    </div>
</header>
