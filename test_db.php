<?php
require_once 'config/database.php';
echo "<h2>Conectado a SQL Server correctamente</h2>";
// Prueba de consulta
$stmt = $pdo->query("SELECT @@VERSION as version");
$row = $stmt->fetch();
echo "<pre>Versión: " . $row['version'] . "</pre>";
?>