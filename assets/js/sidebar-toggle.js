/**
 * Sidebar Toggle Controller
 * Controla o abrir/fechar da sidebar com botão de toggle
 */

(function() {
  'use strict';

  // Elementos
  const appShell = document.querySelector('.app-shell');
  const sidebar = document.querySelector('.sidebar');
  
  // Criar botão de toggle se não existir
  let toggleBtn = document.querySelector('.sidebar-toggle');
  
  if (!toggleBtn && sidebar) {
    toggleBtn = document.createElement('button');
    toggleBtn.className = 'sidebar-toggle';
    toggleBtn.innerHTML = '&lt;';
    toggleBtn.setAttribute('aria-label', 'Toggle Sidebar');
    toggleBtn.setAttribute('title', 'Recolher/Expandir Menu');
    
    // Adicionar botão ao brand da sidebar
    const sidebarBrand = sidebar.querySelector('.sidebar__brand');
    if (sidebarBrand) {
      sidebarBrand.style.position = 'relative';
      sidebarBrand.appendChild(toggleBtn);
    }
  }

  // Estado da sidebar (recuperar do localStorage)
  let isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

  // Aplicar estado inicial
  if (isCollapsed) {
    sidebar?.classList.add('collapsed');
    appShell?.classList.add('sidebar-collapsed');
    if (toggleBtn) toggleBtn.innerHTML = '&gt;';
  }

  // Função de toggle
  function toggleSidebar() {
    isCollapsed = !isCollapsed;
    
    sidebar?.classList.toggle('collapsed');
    appShell?.classList.toggle('sidebar-collapsed');
    
    if (toggleBtn) {
      toggleBtn.innerHTML = isCollapsed ? '&gt;' : '&lt;';
    }
    
    // Salvar estado no localStorage
    localStorage.setItem('sidebarCollapsed', isCollapsed);
    
    // Disparar evento customizado
    window.dispatchEvent(new CustomEvent('sidebarToggle', { 
      detail: { collapsed: isCollapsed } 
    }));
  }

  // Event listener do botão
  if (toggleBtn) {
    toggleBtn.addEventListener('click', toggleSidebar);
  }

  // Atalho de teclado (Ctrl + B)
  document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.key === 'b') {
      e.preventDefault();
      toggleSidebar();
    }
  });

  // Para mobile: fechar sidebar ao clicar fora
  if (window.innerWidth <= 1024) {
    document.addEventListener('click', (e) => {
      if (!sidebar?.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
        if (sidebar?.classList.contains('open')) {
          sidebar.classList.remove('open');
        }
      }
    });
  }

  // Adicionar botão de menu mobile se necessário
  function addMobileMenuToggle() {
    if (window.innerWidth <= 1024) {
      let mobileToggle = document.querySelector('.mobile-menu-toggle');
      
      if (!mobileToggle) {
        mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-menu-toggle icon-button';
        mobileToggle.innerHTML = '<i class="bi bi-list"></i>';
        mobileToggle.setAttribute('aria-label', 'Abrir Menu');
        
        const topbar = document.querySelector('.topbar');
        const topbarActions = topbar?.querySelector('.topbar__actions');
        
        if (topbarActions) {
          topbarActions.prepend(mobileToggle);
        }
        
        mobileToggle.addEventListener('click', () => {
          sidebar?.classList.toggle('open');
        });
      }
    }
  }

  // Executar ao carregar
  addMobileMenuToggle();

  // Reexecutar ao redimensionar
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(addMobileMenuToggle, 200);
  });

  // Animação suave de scroll nos links de navegação
  const navLinks = document.querySelectorAll('.nav-item');
  navLinks.forEach(link => {
    // Adicionar data-title para tooltip quando collapsed
    const span = link.querySelector('span');
    if (span && !link.hasAttribute('data-title')) {
      link.setAttribute('data-title', span.textContent.trim());
    }
    
    link.addEventListener('click', function(e) {
      // Remover active de todos
      navLinks.forEach(l => l.classList.remove('active'));
      // Adicionar active no clicado
      this.classList.add('active');
      
      // Se mobile, fechar sidebar
      if (window.innerWidth <= 1024) {
        setTimeout(() => {
          sidebar?.classList.remove('open');
        }, 300);
      }
    });
  });

  // Indicador de posição do scroll na sidebar
  if (sidebar) {
    const updateScrollIndicator = () => {
      const scrollPercentage = (sidebar.scrollTop / (sidebar.scrollHeight - sidebar.clientHeight)) * 100;
      sidebar.style.setProperty('--scroll-progress', `${scrollPercentage}%`);
    };
    
    sidebar.addEventListener('scroll', updateScrollIndicator);
    updateScrollIndicator();
  }

  console.log('✅ Sidebar Toggle inicializado');
})();
