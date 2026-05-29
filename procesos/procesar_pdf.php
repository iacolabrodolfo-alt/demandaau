<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// ---- Configuración ----
$debug = true; // Cambia a true para ver el texto extraído y no guardar en BD

$carpeta_pdfs = '../documentos/pagares_originales/';
$carpeta_destino_base = '../documentos/demandas/';

if (!is_dir($carpeta_pdfs)) die("La carpeta $carpeta_pdfs no existe.");
$archivos = glob($carpeta_pdfs . '*.pdf');
if (empty($archivos)) die("No se encontraron PDFs en $carpeta_pdfs");

// ---- Función para extraer texto usando pdftotext (mejor) ----
function extraerTextoPDF($ruta_pdf) {
    $output = shell_exec("pdftotext -layout \"$ruta_pdf\" - 2>&1");
    if ($output === null || strpos($output, 'Syntax Error') !== false) {
        // Fallback: usar smalot/pdfparser si pdftotext falla
        if (class_exists('Smalot\PdfParser\Parser')) {
            require_once '../vendor/autoload.php';
            $parser = new Smalot\PdfParser\Parser();
            try {
                $pdf = $parser->parseFile($ruta_pdf);
                return $pdf->getText();
            } catch (Exception $e) {
                return "";
            }
        }
        return "";
    }
    return $output;
}

$actualizados = 0;
$errores = [];

foreach ($archivos as $ruta_pdf) {
    $nombre = basename($ruta_pdf);
    
    // Extraer RUT del nombre
    if (preg_match('/^(\d{7,8}-\d)/', $nombre, $matches)) {
        $rut_pdf = $matches[1];
    } elseif (preg_match('/^(\d{1,2}\.\d{3}\.\d{3}-\d)/', $nombre, $matches)) {
        $rut_pdf = str_replace('.', '', $matches[1]);
    } else {
        $errores[] = "No se pudo extraer RUT de: $nombre";
        continue;
    }
    $rut_pdf = strtoupper($rut_pdf);
    
    // Verificar existencia en ExcelStaging (opcional)
    $stmtCheck = $pdo->prepare("SELECT 1 FROM ExcelStaging WHERE REPLACE(RUT_DEM, '.', '') = ?");
    $stmtCheck->execute([$rut_pdf]);
    if (!$stmtCheck->fetch()) {
        $errores[] = "RUT $rut_pdf no encontrado en ExcelStaging";
        continue;
    }
    
    // Extraer texto del PDF
    $texto = extraerTextoPDF($ruta_pdf);
    if (empty($texto)) {
        $errores[] = "No se pudo extraer texto del PDF $nombre";
        continue;
    }
    
    // Modo debug: mostrar el texto y detener
    if ($debug) {
        echo "<h3>Texto extraído de $nombre:</h3><pre>" . htmlspecialchars($texto) . "</pre>";
        continue;
    }
    
    // ---- Detección del tipo de pagaré (priorizar "PAGARÉ A LA VISTA") ----
    $tipo_pagare = 'SIMPLE';
    if (preg_match('/PAGARÉ\s+A\s+LA\s+VISTA/i', $texto)) {
        $tipo_pagare = 'A LA VISTA';
    } elseif (preg_match('/PAGARÉ\s+EN\s+CUOTAS/i', $texto)) {
        $tipo_pagare = 'EN CUOTAS';
    } elseif (preg_match('/CONTRATO/i', $texto)) {
        $tipo_pagare = 'CONTRATO';
    }
    
    // ---- Extracción de campos (más tolerante) ----
    $num_pagare = null;
    $monto_pagare = null;
    $domicilio_pagare = null;
    $comuna_pagare = null;
    $nombre_deudor = null;
    $repertorio = null;
    $fecha_repertorio = null;
    
    // Número de pagaré: busca "N°", "Nº", "NUMERO", etc.
    if (preg_match('/(?:N[°º]|NUMERO)\s*([\d\-]+)/i', $texto, $match)) {
        $num_pagare = $match[1];
    }
    
    // Monto: busca "$" seguido de número (incluye puntos y comas)
    if (preg_match('/\$[\s]*([\d\.,]+)/', $texto, $match)) {
        $monto_pagare = $match[1];
    }
    
    // Domicilio y comuna: busca patrones como "domicilio en calle ..." o "domicilio: ..."
    if (preg_match('/domicilio en calle\s*([^,\.]+?)(?:,|\s+COMUNA DE\s*([A-ZÑÁÉÍÓÚ\s]+))/i', $texto, $match)) {
        $domicilio_pagare = trim($match[1]);
        if (isset($match[2])) $comuna_pagare = trim($match[2]);
    } elseif (preg_match('/domicilio\s*:\s*([^,\.]+?)(?:,|\s+comuna\s*:\s*([A-ZÑÁÉÍÓÚ\s]+))/i', $texto, $match)) {
        $domicilio_pagare = trim($match[1]);
        if (isset($match[2])) $comuna_pagare = trim($match[2]);
    }
    
    // Nombre del deudor: después de "NOMBRE:" o "DEUDOR:"
    if (preg_match('/(?:NOMBRE|DEUDOR)\s*:\s*([A-ZÑÁÉÍÓÚ\s]+)(?=\s*[A-Z0-9]|$)/i', $texto, $match)) {
        $nombre_deudor = trim($match[1]);
    }
    
    // Repertorio y fecha
    if (preg_match('/REPERTORIO\s*N[°º]\s*([\d\-]+).*?fecha\s*(\d{1,2}\s+de\s+\w+\s+de\s+\d{4})/i', $texto, $match)) {
        $repertorio = $match[1];
        $fecha_repertorio = $match[2];
    } elseif (preg_match('/REPERTORIO\s*N[°º]\s*([\d\-]+)/i', $texto, $match)) {
        $repertorio = $match[1];
    }
    
    // Copiar PDF a carpeta por RUT
    $carpeta_destino = $carpeta_destino_base . $rut_pdf . '/';
    if (!is_dir($carpeta_destino)) mkdir($carpeta_destino, 0777, true);
    $destino_pdf = $carpeta_destino . 'pagare.pdf';
    copy($ruta_pdf, $destino_pdf);
    
    // Guardar en tabla Pagares (MERGE)
    $sql = "MERGE INTO Pagares AS target
            USING (SELECT ? AS rut) AS source
            ON target.rut_deudor = source.rut
            WHEN MATCHED THEN
                UPDATE SET 
                    tipo_pagare = ?, num_pagare = ?, monto_pagare = ?, domicilio_pagare = ?,
                    comuna_pagare = ?, nombre_deudor_pagare = ?, repertorio = ?, fecha_repertorio = ?,
                    ruta_actual = ?, fecha_procesado = GETDATE()
            WHEN NOT MATCHED THEN
                INSERT (rut_deudor, nombre_archivo_original, ruta_actual, tipo_pagare, num_pagare, monto_pagare,
                        domicilio_pagare, comuna_pagare, nombre_deudor_pagare, repertorio, fecha_repertorio, fecha_procesado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE());";
    
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute([
        $rut_pdf,
        $tipo_pagare, $num_pagare, $monto_pagare, $domicilio_pagare, $comuna_pagare, $nombre_deudor, $repertorio, $fecha_repertorio, $destino_pdf,
        $rut_pdf, $nombre, $destino_pdf, $tipo_pagare, $num_pagare, $monto_pagare, $domicilio_pagare, $comuna_pagare, $nombre_deudor, $repertorio, $fecha_repertorio
    ]);
    
    if ($ok) $actualizados++;
    else $errores[] = "Error al insertar/actualizar RUT $rut_pdf";
}

// ---- Mostrar resultados ----
if ($debug) exit;
?>
<!DOCTYPE html>
<html>
<head><title>Procesar Pagarés</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container mt-5">
    <h2>Procesamiento de PDFs completado</h2>
    <div class="alert alert-success">Registros procesados en Pagares: <?= $actualizados ?></div>
    <?php if ($errores): ?>
        <div class="alert alert-warning"><ul><?php foreach ($errores as $e) echo "<li>$e</li>"; ?></ul></div>
    <?php endif; ?>
    <a href="../vistas/dashboard.php" class="btn btn-primary">Volver</a>
</body>
</html>