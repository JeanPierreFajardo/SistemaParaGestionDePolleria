<?php
session_start();

// Conexión a la base de datos
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";
$dbname = getenv('DB_NAME') ?: "polleria";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar sesión activa
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
} else {
    session_regenerate_id(true);
}

// Consulta para contar registros en pedidos_anulados
$sql = "SELECT COUNT(*) as total FROM pedidos_anulados";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

// Devolver el total en formato JSON
echo json_encode(["total" => $row['total']]);

// Cerrar conexión
$conn->close();
?>
