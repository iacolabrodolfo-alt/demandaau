<?php
// Conexión a SQL Server usando PDO_ODBC (no requiere extensión sqlsrv)
$odbc_driver = "{ODBC Driver 17 for SQL Server}";
$server = "NTBKSYM065\PROYECTO";  // o "localhost" si es instancia por defecto
$database = "DemandaAU";
$uid = "app_user";
$pass = "Rh13550+";

$dsn = "odbc:Driver=$odbc_driver;Server=$server;Database=$database";

try {
    $pdo = new PDO($dsn, $uid, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>