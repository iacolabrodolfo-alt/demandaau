<?php
// Si ya está logueado, redirigir al dashboard
if(isset($_SESSION['id_usuario'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - DemandaAU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #236785; }
        .card { border-radius: 1rem; }
        .btn-primary { background-color: #236785; border: none; }
        .btn-primary:hover { background-color: #1a4f6b; }
    </style>
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4" style="color:#236785;">DemandaAU</h2>
                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger">Usuario o contraseña incorrectos</div>
                        <?php endif; ?>
                        <form action="../procesos/validar_login.php" method="POST">
                            <div class="mb-3">
                                <label>Usuario</label>
                                <input type="text" name="usuario" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Contraseña</label>
                                <input type="password" name="clave" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="recuperar.php" style="color:#236785;">¿Olvidó su contraseña?</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>