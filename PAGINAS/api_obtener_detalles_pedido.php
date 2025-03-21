<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "polleria";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$pedidoId = $_GET['pedido_id'] ?? 0;

$sql = "
    SELECT p.nombre, dp.cantidad, p.precio AS precio_unitario, dp.cantidad * p.precio AS precio_total
    FROM detalles_pedidos dp
    JOIN productos p ON dp.producto_id = p.id
    WHERE dp.pedido_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$result = $stmt->get_result();

$detalles = [];
while ($row = $result->fetch_assoc()) {
    $detalles[] = $row;
}

echo json_encode($detalles);
