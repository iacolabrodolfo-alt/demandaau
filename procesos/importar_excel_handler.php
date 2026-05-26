<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';  // Cargar PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../vistas/importar_excel.php?error=Método no permitido");
    exit;
}

if (!isset($_FILES['archivo_excel']) || $_FILES['archivo_excel']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../vistas/importar_excel.php?error=Error al subir el archivo");
    exit;
}

$archivo_tmp = $_FILES['archivo_excel']['tmp_name'];
$extension = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ['xls', 'xlsx'])) {
    header("Location: ../vistas/importar_excel.php?error=Solo archivos .xls o .xlsx");
    exit;
}

try {
    $spreadsheet = IOFactory::load($archivo_tmp);
    $hoja = $spreadsheet->getActiveSheet();
    $filas = $hoja->toArray();

    if (count($filas) < 2) {
        header("Location: ../vistas/importar_excel.php?error=El archivo no contiene datos");
        exit;
    }

    $encabezados = array_map('trim', $filas[0]);
    $encabezados_may = array_map('strtoupper', $encabezados);

    $col_rut = array_search('RUT_DEM', $encabezados_may);
    if ($col_rut === false) {
        header("Location: ../vistas/importar_excel.php?error=No se encontró la columna RUT_DEM");
        exit;
    }
    $col_nombre = array_search('NOM_DEM', $encabezados_may);

    $insertados = 0;
    for ($i = 1; $i < count($filas); $i++) {
        $fila = $filas[$i];
        if (count($fila) <= $col_rut) continue;

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

} catch (Exception $e) {
    header("Location: ../vistas/importar_excel.php?error=" . urlencode("Error interno: " . $e->getMessage()));
    exit;
}
?>