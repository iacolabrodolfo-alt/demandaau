<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../vendor/autoload.php';

use Smalot\PdfParser\Parser;

// Directorio donde están los PDFs (debe existir)
$carpeta_pdfs = '../documentos/pagares_originales/';
if (!is_dir($carpeta_pdfs)) {
    die("La carpeta $carpeta_pdfs no existe. Créela y coloque los PDFs allí.");
}

$archivos = glob($carpeta_pdfs . '*.pdf');
if (empty($archivos)) {
    die("No se encontraron archivos PDF en $carpeta_pdfs");
}

$parser = new Parser();
$actualizados = 0;
$errores = [];

foreach ($archivos as $ruta_pdf) {
    $nombre = basename($ruta_pdf);
    // Extraer RUT del nombre (formato: 11111111-1 o 11111111-1 seguido de texto)
    preg_match('/^(\d{7,8}-\d)/', $nombre, $matches);
    $rut = $matches[1] ?? null;
    if (!$rut) {
        $errores[] = "No se pudo extraer RUT de: $nombre";
        continue;
    }

    // Verificar si existe el RUT en la tabla Demandas
    $stmt = $pdo->prepare("SELECT id_demanda FROM Demandas WHERE RUT_DEM = ?");
    $stmt->execute([$rut]);
    if (!$stmt->fetch()) {
        $errores[] = "RUT $rut no encontrado en la base de datos (Excel no importado?)";
        continue;
    }

    // Extraer texto del PDF
    try {
        $pdf = $parser->parseFile($ruta_pdf);
        $texto = $pdf->getText();
    } catch (Exception $e) {
        $errores[] = "Error al leer PDF $nombre: " . $e->getMessage();
        continue;
    }

    // --- Extraer datos relevantes (ejemplo básico) ---
    $capital = null;
    if (preg_match('/CANTIDAD\s+DE\s+[\d\s.,]+(?:\(?\$?([\d.,]+)\)?)?/', $texto, $cap_match)) {
        $capital = str_replace(['.', ','], ['', '.'], trim($cap_match[1]));
        $capital = floatval($capital);
    }

    $cuotas_totales = null;
    if (preg_match('/(\d+)\s*CUOTAS?/', $texto, $cuotas_match)) {
        $cuotas_totales = intval($cuotas_match[1]);
    }

    $valor_cuota = null;
    if (preg_match('/de\s*\$([\d.,]+)\s*cada\s*una/', $texto, $valor_match)) {
        $valor_cuota = str_replace(['.', ','], ['', '.'], $valor_match[1]);
        $valor_cuota = floatval($valor_cuota);
    }

    $tasa = null;
    if (preg_match('/TASA\s+DE\s+INTERÉS\s+(\d+(?:,\d+)?)/', $texto, $tasa_match)) {
        $tasa = str_replace(',', '.', $tasa_match[1]);
        $tasa = floatval($tasa);
    }

    $fecha_primer_vencimiento = null;
    if (preg_match('/VENCIENDO\s+LA\s+PRIMERA\s+EL\s+(\d{2}-\d{2}-\d{4})/', $texto, $fecha_match)) {
        $fecha_primer_vencimiento = date('Y-m-d', strtotime($fecha_match[1]));
    }

    // Actualizar la tabla con los datos extraídos
    $sql = "UPDATE Demandas SET 
                CAPITAL = COALESCE(?, CAPITAL),
                CUOTAS_TOTALES = COALESCE(?, CUOTAS_TOTALES),
                VALOR_PRIMERAS = COALESCE(?, VALOR_PRIMERAS),
                TASA = COALESCE(?, TASA),
                PRIMER_VENCIMIENTO = COALESCE(?, PRIMER_VENCIMIENTO),
                ruta_pagare = ?,
                estado_proceso = 'PDF_ASOCIADO'
            WHERE RUT_DEM = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$capital, $cuotas_totales, $valor_cuota, $tasa, $fecha_primer_vencimiento, $ruta_pdf, $rut]);
    $actualizados++;
}

// Mostrar resultado
echo "<h2>Procesamiento de PDFs completado</h2>";
echo "<p>Actualizados: $actualizados</p>";
if (!empty($errores)) {
    echo "<h3>Errores:</h3><ul>";
    foreach ($errores as $err) echo "<li>$err</li>";
    echo "</ul>";
}
?>