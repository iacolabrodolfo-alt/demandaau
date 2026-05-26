<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/importar_excel.php");
    exit;
}

if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../vistas/importar_excel.php?error=No se pudo subir el archivo");
    exit;
}

$archivo_tmp = $_FILES['archivo_excel']['tmp_name'];
$extension = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));

if (!in_array($extension, ['xls', 'xlsx'])) {
    header("Location: ../vistas/importar_excel.php?error=Solo archivos XLS o XLSX");
    exit;
}

// Incluir la librería SimpleXLSX (sin namespace)
require_once '../clases/SimpleXLSX.php';

// Verificar que la clase exista
if (!class_exists('SimpleXLSX')) {
    die("Error: La clase SimpleXLSX no está definida. Verifica que el archivo se haya descargado correctamente.");
}

$xlsx = SimpleXLSX::parse($archivo_tmp);
if ($xlsx) {
    $filas = $xlsx->rows();
    if (count($filas) < 2) {
        header("Location: ../vistas/importar_excel.php?error=El archivo no contiene datos");
        exit;
    }
    
    $encabezados = array_map('trim', $filas[0]);
    $encabezados_may = array_map('strtoupper', $encabezados);
    
    $col_rut = array_search('RUT_DEM', $encabezados_may);
    $col_nombre = array_search('NOM_DEM', $encabezados_may);
    if ($col_rut === false) {
        header("Location: ../vistas/importar_excel.php?error=No se encontró la columna RUT_DEM");
        exit;
    }
    
    $insertados = 0;
    for ($i = 1; $i < count($filas); $i++) {
        $fila = $filas[$i];
        if (count($fila) < max($col_rut, $col_nombre) + 1) continue;
        
        $rut = trim($fila[$col_rut]);
        if (empty($rut)) continue;
        
        $nombre = ($col_nombre !== false) ? trim($fila[$col_nombre]) : '';
        
        $datos_fila = [];
        foreach ($encabezados as $idx => $col) {
            $datos_fila[$col] = $fila[$idx] ?? null;
        }
        $json_original = json_encode($datos_fila, JSON_UNESCAPED_UNICODE);
        
        $stmt = $pdo->prepare("INSERT INTO Demandas (rut_deudor, nom_deudor, datos_originales, estado) VALUES (?, ?, ?, 'PENDIENTE')");
        $stmt->execute([$rut, $nombre, $json_original]);
        $insertados++;
    }
    
    header("Location: ../vistas/importar_excel.php?success=1&insertados=$insertados");
    exit;
} else {
    header("Location: ../vistas/importar_excel.php?error=" . urlencode(SimpleXLSX::parseError()));
    exit;
}
?>