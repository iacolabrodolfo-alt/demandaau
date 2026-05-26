<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Probando carga de extensión sqlsrv...<br>";
if (extension_loaded('sqlsrv')) {
    echo "Extensión sqlsrv cargada correctamente.";
} else {
    echo "ERROR: Extensión sqlsrv NO cargada.<br>";
    echo "Intentando cargar dinámicamente: ";
    if (dl('php_sqlsrv_74_ts_x64.dll')) {
        echo "cargada.";
    } else {
        echo "no se pudo cargar dinámicamente.";
    }
}
?>