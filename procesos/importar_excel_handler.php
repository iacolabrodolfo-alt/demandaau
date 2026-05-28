<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

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
    $filas = $hoja->toArray(null, true, true, false);
    
    if (count($filas) < 2) {
        header("Location: ../vistas/importar_excel.php?error=El archivo no contiene datos");
        exit;
    }
    
    // Obtener encabezados de la primera fila
    $encabezados = array_map('trim', $filas[0]);
    // Limpiar encabezados vacíos
    $encabezados = array_filter($encabezados, function($val) { return $val !== ''; });
    $encabezados = array_values($encabezados);
    
    // Mapeo de los encabezados del Excel a los nombres de columna de la tabla ExcelStaging
    // Como los nombres son iguales (salvo espacios), usamos directamente los mismos.
    // Pero necesitamos generar dinámicamente la lista de columnas para el INSERT.
    // Como la tabla tiene muchas columnas, lo más fácil es armar un INSERT con todas las columnas conocidas,
    // y asignar valor si el encabezado coincide, o NULL si no.
    
    // Obtener la lista de columnas de la tabla ExcelStaging desde INFORMATION_SCHEMA
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ExcelStaging' ORDER BY ORDINAL_POSITION");
    $columnas_staging = [];
    while ($row = $stmt->fetch()) {
        $col = $row['COLUMN_NAME'];
        if ($col !== 'id_staging' && $col !== 'fecha_importacion') {
            $columnas_staging[] = $col;
        }
    }
    
    // Construir el INSERT dinámico: todas las columnas de staging reciben un valor según el índice del encabezado
    $sql = "INSERT INTO ExcelStaging (" . implode(', ', array_map(function($c) { return "[$c]"; }, $columnas_staging)) . ") VALUES (" . implode(', ', array_fill(0, count($columnas_staging), '?')) . ")";
    
    $insertados = 0;
    $errores = [];
    
    // Recorrer filas de datos (desde índice 1)
    for ($i = 1; $i < count($filas); $i++) {
        $fila = $filas[$i];
        // Crear array asociativo encabezado => valor
        $datos_fila = [];
        foreach ($encabezados as $idx => $col) {
            $datos_fila[$col] = isset($fila[$idx]) ? $fila[$idx] : null;
        }
        
        // Preparar valores en el orden de las columnas staging
        $valores = [];
        foreach ($columnas_staging as $col_staging) {
            // Buscar si existe un encabezado igual (insensible a mayúsculas)
            $encontrado = null;
            foreach ($datos_fila as $cabecera => $valor) {
                if (strtoupper($cabecera) === strtoupper($col_staging)) {
                    $encontrado = $valor;
                    break;
                }
            }
            $valores[] = $encontrado;
        }
        
        $stmtIns = $pdo->prepare($sql);
        if ($stmtIns->execute($valores)) {
            $insertados++;
        } else {
            $errorInfo = $stmtIns->errorInfo();
            $errores[] = "Fila $i: " . implode(' - ', $errorInfo);
        }
    }
    
    if (!empty($errores)) {
        header("Location: ../vistas/importar_excel.php?error=" . urlencode(implode('; ', array_slice($errores, 0, 5))));
        exit;
    }
    
    header("Location: ../vistas/importar_excel.php?success=1&insertados=$insertados");
    exit;
    
} catch (Exception $e) {
    header("Location: ../vistas/importar_excel.php?error=" . urlencode("Error interno: " . $e->getMessage()));
    exit;
}
?>