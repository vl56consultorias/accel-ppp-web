<?php
/**
 * Logout - Destrói a sessão e redireciona para login
 */
session_start();
session_unset();
session_destroy();
header('Location: /login.php');
exit;
?>
