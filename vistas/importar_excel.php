<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Excel - DemandaAU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { background-color: #236785; min-height: 100vh; width: 260px; position: fixed; left: 0; top: 0; }
        .main-content { margin-left: 260px; padding: 20px; }
        .btn-custom { background-color: #236785; color: white; }
        .btn-custom:hover { background-color: #1a4f6b; }
    </style>
</head>
<body>
    <div class="sidebar p-3">
        <?php include '../includes/sidebar.php'; ?>
    </div>
    <div class="main-content">
        <h2>Importar Planilla Excel (formato "como se recibe")</h2>
        <div class="card mt-3">
            <div class="card-body">
                <form action="../procesos/subir_excel.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Archivo Excel (.xlsx, .xls)</label>
                        <input type="file" name="archivo_excel" class="form-control" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" class="btn btn-custom">Subir y Procesar</button>
                </form>
            </div>
        </div>
        <?php if(isset($_GET['success']) && isset($_GET['insertados'])): ?>
            <div class="alert alert-success mt-3">
                Se importaron <?php echo htmlspecialchars($_GET['insertados']); ?> registros correctamente.
            </div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger mt-3">
                Error: <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>