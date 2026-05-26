<?php
// Verificar que la sesión esté activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay usuario logueado, redirigir al login
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../vistas/login.php");
    exit;
}
?>