<?php
session_start();
if (!empty($_SESSION['usuario'])) {
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Painel</title>
  <link rel="stylesheet" href="/global.css">
  <link rel="stylesheet" href="/assets/css/login.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body id="loginBody">
  <div class="login-wrapper">
    <div class="login-header">
      <div class="login-icon"><i class="bi bi-shield-lock"></i></div>
      <div>
        <div class="login-title">Painel de Controle</div>
        <div class="login-subtitle">Acesse com seu usuário e senha</div>
      </div>
      <div id="alert" class="alert" role="alert"></div>
    </div>
    <form id="loginForm" class="login-form" autocomplete="on">
      <div class="form-group">
        <label class="form-label" for="login">Usuário</label>
        <input class="form-input" type="text" name="login" id="login" placeholder="Digite seu usuário" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="senha">Senha</label>
        <input class="form-input" type="password" name="senha" id="senha" placeholder="Digite sua senha" required>
      </div>
      <button type="submit" id="submitBtn" class="login-btn">Entrar</button>
    </form>
    <div class="login-footer">
      <span>Esqueceu a senha? Fale com o administrador.</span>
    </div>
  </div>

<script>
(() => {
  const form = document.getElementById('loginForm');
  const alertBox = document.getElementById('alert');
  const submitBtn = document.getElementById('submitBtn');

  function showAlert(message, type = 'error') {
    alertBox.textContent = message;
    alertBox.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showAlert('');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    const formData = new FormData(form);
    try {
      const resp = await fetch('/crud/api/login.php', {
        method: 'POST',
        body: formData,
        credentials: 'include'
      });
      const data = await resp.json().catch(() => null);
      if (!resp.ok || !data || !data.success) {
        const msg = data && data.error ? data.error : 'Falha no login. Verifique suas credenciais.';
        showAlert(msg, 'error');
      } else {
        showAlert('Login realizado com sucesso!', 'success');
        // pequeno delay para UX
        setTimeout(() => { window.location.href = '/index.php'; }, 400);
      }
    } catch (err) {
      showAlert('Erro ao conectar ao servidor.');
    } finally {
      submitBtn.classList.remove('btn-loading');
      submitBtn.disabled = false;
    }
  });

  // Dark mode toggle
  const loginBody = document.getElementById('loginBody');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const savedTheme = localStorage.getItem('theme') || (prefersDark ? 'dark' : 'light');
  if (savedTheme === 'dark') loginBody.classList.add('dark-mode');
})();
</script>
</body>
</html>
