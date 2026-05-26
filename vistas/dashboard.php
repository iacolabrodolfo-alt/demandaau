<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Si no está logueado, auth.php ya redirige, pero por seguridad:
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - DemandaAU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin: 0; padding: 0; background: #f4f6f9; }
        .sidebar {
            background-color: #236785;
            min-height: 100vh;
            width: 260px;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
        }
        .btn-custom {
            background-color: #236785;
            color: white;
            border: none;
        }
        .btn-custom:hover {
            background-color: #1a4f6b;
        }
        .card-dashboard {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            transition: all 0.2s;
            cursor: pointer;
        }
        .card-dashboard:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .icon-circle {
            width: 50px;
            height: 50px;
            background-color: rgba(35,103,133,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .icon-circle i {
            font-size: 24px;
            color: #236785;
        }
        .nav-link {
            color: white !important;
            transition: 0.2s;
        }
        .nav-link:hover {
            background-color: #1a4f6b;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar fijo -->
    <div class="sidebar p-3">
        <h4 class="text-white text-center mt-2">⚖️ DemandaAU</h4>
        <hr class="bg-light">
        <ul class="nav flex-column">
            <li class="nav-item mb-2"><a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
            <li class="nav-item mb-2"><a href="importar_excel.php" class="nav-link"><i class="bi bi-file-earmark-excel me-2"></i> Importar Excel</a></li>
            <li class="nav-item mb-2"><a href="gestion_pdf.php" class="nav-link"><i class="bi bi-file-earmark-pdf me-2"></i> Gestionar Pagarés</a></li>
            <li class="nav-item mb-2"><a href="listado_demandas.php" class="nav-link"><i class="bi bi-table me-2"></i> Listado de Demandas</a></li>
            <?php if($_SESSION['rol'] == 'ADMIN'): ?>
            <li class="nav-item mb-2"><a href="admin_usuarios.php" class="nav-link"><i class="bi bi-people me-2"></i> Usuarios</a></li>
            <?php endif; ?>
            <li class="nav-item mt-5"><a href="../procesos/logout.php" class="nav-link"><i class="bi bi-box-arrow-right me-2"></i> Salir</a></li>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h2>
            <div class="badge bg-secondary p-2">Rol: <?php echo $_SESSION['rol']; ?></div>
        </div>
        <div class="row" id="contenedorCajitas">
            <!-- Las 18 cajitas se generarán con JavaScript -->
        </div>
    </div>

    <script>
        // Lista de 18 tipos de demanda (después se cargará desde base de datos)
        const tiposDemanda = [
            "Ejecutiva simple", "Ejecutiva con garantía", "Ordinaria laboral",
            "Sumaria", "Monitorio", "Ejecutiva hipotecaria", "Juicio de arrendamiento",
            "Cobranza previsional", "Quiebra", "Nulidad de derecho público", "Protección",
            "Reclamación judicial", "Cumplimiento de contrato", "Indemnización de perjuicios",
            "Desalojo", "Posesoria", "Interdicto", "Tercería"
        ];
        const container = document.getElementById('contenedorCajitas');
        tiposDemanda.forEach((tipo, idx) => {
            const col = document.createElement('div');
            col.className = 'col-md-4 col-lg-3 mb-4';
            col.innerHTML = `
                <div class="card card-dashboard h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle mx-auto">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h5 class="card-title">${tipo}</h5>
                        <p class="card-text text-muted">Casos pendientes: <strong>0</strong></p>
                        <button class="btn btn-sm btn-custom" onclick="alert('Funcionalidad en construcción')">Ver casos</button>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>