<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Demandas Judiciales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --judicial-color: #236785;
        }
        .sidebar {
            background-color: var(--judicial-color);
            min-height: 100vh;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 15px;
            transition: 0.3s;
        }
        .sidebar a:hover {
            background-color: #1a4f63;
            border-radius: 5px;
        }
        .sidebar .active {
            background-color: #1a4f63;
            border-radius: 5px;
        }
        .main-content {
            background-color: #f4f6f9;
        }
        .card-demandas {
            border-left: 5px solid var(--judicial-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="text-center py-4">
                    <i class="fas fa-gavel fa-3x"></i>
                    <h5 class="mt-2">Demanda AU</h5>
                </div>
                <nav class="nav flex-column">
                    <a href="#" class="active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a href="#"><i class="fas fa-file-excel me-2"></i> Carga Excel</a>
                    <a href="#"><i class="fas fa-file-pdf me-2"></i> Gestión PDFs</a>
                    <a href="#"><i class="fas fa-balance-scale me-2"></i> Tipos Demanda (18)</a>
                    <a href="#"><i class="fas fa-folder-open me-2"></i> Confeccionadas</a>
                    <a href="#"><i class="fas fa-gavel me-2"></i> Presentadas PJUD</a>
                    <a href="#"><i class="fas fa-users me-2"></i> Usuarios</a>
                    <a href="#"><i class="fas fa-sign-out-alt me-2"></i> Salir</a>
                </nav>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content p-4">
                <h2>Bienvenido, <span id="nombre_usuario">Usuario</span></h2>
                <!-- Aquí van las 18 cajitas -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card card-demandas">
                            <div class="card-body">
                                <h5><i class="fas fa-file-contract"></i> Ejecutivas (Pesos)</h5>
                                <p class="card-text">12 casos pendientes</p>
                                <button class="btn btn-sm btn-primary">Confeccionar</button>
                                <button class="btn btn-sm btn-success">Ver Confeccionadas</button>
                            </div>
                        </div>
                    </div>
                    <!-- Repetir para 18 tipos -->
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>