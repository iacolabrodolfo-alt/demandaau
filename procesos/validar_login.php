<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    $stmt = $pdo->prepare("SELECT id_usuario, nombre_usuario, clave_hash, rol FROM Usuarios WHERE nombre_usuario = ? AND activo = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && password_verify($clave, $user['clave_hash'])) {
        $_SESSION['id_usuario'] = $user['id_usuario'];
        $_SESSION['nombre_usuario'] = $user['nombre_usuario'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: ../vistas/dashboard.php");
        exit;
    } else {
        header("Location: ../vistas/login.php?error=1");
        exit;
    }
}