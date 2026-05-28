<nav class="nav flex-column">
    <a class="navbar-brand text-white mb-4" href="/demandaau/vistas/dashboard.php">⚖️ DemandaAU</a>
    <ul class="nav nav-pills flex-column">
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/vistas/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/vistas/importar_excel.php"><i class="bi bi-upload"></i> Importar Excel</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/procesos/procesar_pdf.php"><i class="bi bi-file-earmark-pdf"></i> Leer Pagarés</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/vistas/listado_demandas.php"><i class="bi bi-list"></i> Demandas</a></li>
        <?php if($_SESSION['rol'] == 'ADMIN'): ?>
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/vistas/admin_usuarios.php"><i class="bi bi-people"></i> Usuarios</a></li>
        <?php endif; ?>
        <li class="nav-item mt-5"><a class="nav-link text-white" href="/demandaau/procesos/logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="/demandaau/procesos/procesar_pdf.php"><i class="bi bi-file-earmark-pdf"></i> Leer Pagarés</a></li>
    </ul>
</nav>