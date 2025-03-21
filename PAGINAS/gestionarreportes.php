<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia según tu configuración
$password = "";     // Cambia según tu configuración
$dbname = "polleria";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    // Redirigir al inicio de sesión si no hay sesión activa
    header("Location: index.php");
    exit();
}

// Verificar rol del usuario
$sqlRol = "SELECT rol_id FROM usuarios WHERE username = ?";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("s", $_SESSION['username']);
$stmtRol->execute();
$resultRol = $stmtRol->get_result();
$rol_id = ($resultRol->num_rows > 0) ? $resultRol->fetch_assoc()['rol_id'] : 0;

// Verificar si el usuario tiene rol_id = 1
if ($rol_id != 1) {
    header("Location: index.php");
    exit();
}

// Configuración de la paginación
$registrosPorPagina = 10; // Número de registros por página
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaActual - 1) * $registrosPorPagina;

// Obtener la fecha filtrada
$fechaFiltrada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// *** Consulta para calcular el total del día sin incluir pedidos anulados ***
$sqlTotalDia = "
    SELECT SUM(dp.cantidad * pr.precio) AS total_dia
    FROM pedidos p
    JOIN detalles_pedidos dp ON p.id = dp.pedido_id
    JOIN productos pr ON dp.producto_id = pr.id
    LEFT JOIN pedidos_anulados pa ON p.id = pa.pedido_id AND dp.producto_id = pa.producto_id
    WHERE DATE(p.fecha) = ? 
    AND p.estado != 'anulado'
    AND pa.producto_id IS NULL -- Excluir productos anulados
";

$stmtTotalDia = $conn->prepare($sqlTotalDia);
$stmtTotalDia->bind_param("s", $fechaFiltrada);
$stmtTotalDia->execute();
$resultTotalDia = $stmtTotalDia->get_result();
$totalDiaGeneral = $resultTotalDia->fetch_assoc()['total_dia'] ?? 0;

// *** Consulta para contar todos los registros del día ***
$sqlCount = "
    SELECT COUNT(*) AS total_registros
    FROM pedidos p
    JOIN detalles_pedidos dp ON p.id = dp.pedido_id
    WHERE DATE(p.fecha) = ?
";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("s", $fechaFiltrada);
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$totalRegistros = $resultCount->fetch_assoc()['total_registros'];
$totalPaginas = ceil($totalRegistros / $registrosPorPagina);

// *** Consulta para obtener los registros con paginación ***
$sql = "
    SELECT 
        p.id AS pedido_id,
        p.fecha,
        p.nombre_cliente,
        p.direccion,
        u.nombre AS usuario_nombre,
        u.apellido AS usuario_apellido,
        m.numero AS mesa_numero,
        m.letra AS mesa_letra,
        COALESCE(dp.producto_id, pa.producto_id) AS producto_id,
        COALESCE(pr.nombre, 'ANULADO') AS producto_nombre,
        COALESCE(dp.cantidad, pa.cantidad_anulada, 0) AS cantidad,
        COALESCE(pr.precio, 0) AS precio,
        (COALESCE(dp.cantidad, pa.cantidad_anulada, 0) * COALESCE(pr.precio, 0)) AS total_producto,
        CASE 
            WHEN p.estado = 'anulado' OR pa.producto_id IS NOT NULL THEN 'anulado' 
            ELSE 'activo' 
        END AS pedido_estado
    FROM pedidos p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN mesas m ON p.mesa_id = m.id
    LEFT JOIN detalles_pedidos dp ON p.id = dp.pedido_id
    LEFT JOIN productos pr ON dp.producto_id = pr.id
    LEFT JOIN pedidos_anulados pa ON p.id = pa.pedido_id AND (dp.producto_id = pa.producto_id OR pa.producto_id IS NOT NULL)
    WHERE DATE(p.fecha) = ?
    ORDER BY p.id, producto_id
    LIMIT ?, ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $fechaFiltrada, $inicio, $registrosPorPagina);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1 class="my-4">Reporte de Ventas - <?php echo htmlspecialchars($fechaFiltrada); ?></h1>

        <!-- Botón para redirigir al panel administrador -->
        <div class="mb-4">
            <a href="paneladministrador.php" class="btn btn-secondary">Volver al Panel Administrador</a>
        </div>

        <!-- Formulario para filtrar por fecha -->
        <form method="GET" class="mb-4">
            <div class="form-group row">
                <label for="fecha" class="col-sm-2 col-form-label">Filtrar por Fecha</label>
                <div class="col-sm-4">
                    <input type="date" name="fecha" id="fecha" class="form-control" value="<?php echo htmlspecialchars($fechaFiltrada); ?>">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </div>
        </form>

        <!-- Tabla de reportes -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Dirección</th>
                    <th>Usuario</th>
                    <th>Mesa (Número - Letra)</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Total Producto</th>
                </tr>
            </thead>
            <tbody>
    <?php
    $totalDia = 0; // Total para pedidos no anulados
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Verificar si el pedido está anulado
            $esAnulado = ($row['pedido_estado'] === 'anulado');
            if (!$esAnulado) {
                $totalDia += $row['total_producto'];
            }
            echo "<tr>
                <td>" . htmlspecialchars($row['pedido_id']) . "</td>
                <td>" . htmlspecialchars($row['fecha']) . "</td>
                <td>" . htmlspecialchars($row['nombre_cliente']) . "</td>
                <td>" . htmlspecialchars($row['direccion']) . "</td>
                <td>" . htmlspecialchars($row['usuario_nombre'] . ' ' . $row['usuario_apellido']) . "</td>
                <td>Mesa " . htmlspecialchars($row['mesa_numero']) . " - " . htmlspecialchars($row['mesa_letra']) . "</td>
                <td>" . htmlspecialchars($row['producto_nombre']) . "</td>
                <td>" . htmlspecialchars($row['cantidad']) . "</td>
                <td>S/ " . number_format($row['precio'], 2) . "</td>
                <td" . ($row['pedido_estado'] === 'anulado' ? ' style="color: red;"' : '') . ">
                    " . ($row['pedido_estado'] === 'anulado' ? "ANULADO" : "S/ " . number_format($row['total_producto'], 2)) . "
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='10' class='text-center'>No se encontraron resultados para la fecha seleccionada.</td></tr>";
    }
    ?>
</tbody>

<tfoot>
    <tr>
        <th colspan="9" class="text-right">Total del Día:</th>
        <th>S/ <?php echo number_format($totalDiaGeneral, 2); ?></th>
    </tr>
</tfoot>

        </table>

        <!-- Navegación de páginas -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($paginaActual > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?fecha=<?php echo htmlspecialchars($fechaFiltrada); ?>&pagina=<?php echo $paginaActual - 1; ?>">Anterior</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?php echo $i === $paginaActual ? 'active' : ''; ?>">
                        <a class="page-link" href="?fecha=<?php echo htmlspecialchars($fechaFiltrada); ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($paginaActual < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?fecha=<?php echo htmlspecialchars($fechaFiltrada); ?>&pagina=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>
</html>

<?php
// Cerrar conexión
$conn->close();
?>
