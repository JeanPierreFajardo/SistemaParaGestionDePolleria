<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "polleria";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}
$userId = $_SESSION['usuario_id'];
$sql = "SELECT CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo, r.nombre AS rol 
        FROM usuarios u
        LEFT JOIN roles r ON u.rol_id = r.id
        WHERE u.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();


$sqlRol = "SELECT rol_id FROM usuarios WHERE id = ?";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("i", $userId);
$stmtRol->execute();
$resultRol = $stmtRol->get_result();
$rol_id = ($resultRol->num_rows > 0) ? $resultRol->fetch_assoc()['rol_id'] : 0;
$_SESSION['rol_id'] = $rol_id; // Asegura que el rol esté almacenado en la sesión
if (!in_array($rol_id, [1, 3])) {
    header("Location: index.php");
    exit();
}
if (isset($_GET['mesa'])) {
    $mesaId = $_GET['mesa'];
    if (!validarMesaId($mesaId, $conn)) {
        die("Error: El ID de la mesa no es válido.");
    }
} else {
    die("No se seleccionó ninguna mesa.");
}
function validarMesaId($mesaId, $conn) {
    $sql = "SELECT id FROM mesas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $mesaId);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0; // Retorna true si hay filas
}
function obtenerProductosPorCategoria($conn) {
    $sql = "SELECT id, nombre, precio, categoria FROM productos ORDER BY categoria";
    $result = $conn->query($sql);
    $productosPorCategoria = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $productosPorCategoria[$row['categoria']][] = $row;
        }
    }
    return $productosPorCategoria;
}
function obtenerDetallesPedido($pedidoId, $conn) {
    $sql = "SELECT dp.id AS detalle_id, p.id AS producto_id, p.nombre, dp.cantidad, p.precio, (dp.cantidad * p.precio) AS total 
            FROM detalles_pedidos dp
            JOIN productos p ON dp.producto_id = p.id
            WHERE dp.pedido_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
if (!isset($_SESSION['pedido_activo'][$mesaId])) {
    // Verificar si ya existe un pedido activo para esta mesa
    $sqlPedidoActivo = "SELECT id FROM pedidos WHERE mesa_id = ? AND estado = 'en_proceso'";
    $stmtPedidoActivo = $conn->prepare($sqlPedidoActivo);
    $stmtPedidoActivo->bind_param("i", $mesaId);
    $stmtPedidoActivo->execute();
    $resultPedidoActivo = $stmtPedidoActivo->get_result();

    if ($resultPedidoActivo->num_rows > 0) {
        // Si ya existe un pedido activo, usar ese ID
        $pedidoActivo = $resultPedidoActivo->fetch_assoc();
        $_SESSION['pedido_activo'][$mesaId] = $pedidoActivo['id'];
    } else {
        // Si no hay un pedido activo, inicializamos sin crear uno nuevo
        $_SESSION['pedido_activo'][$mesaId] = null;
    }
}
$detallesPedido = [];
if (isset($_SESSION['pedido_activo'][$mesaId]) && $_SESSION['pedido_activo'][$mesaId] !== null) {
    $pedidoId = $_SESSION['pedido_activo'][$mesaId];
    $detallesPedido = obtenerDetallesPedido($pedidoId, $conn);
}
$productosPorCategoria = obtenerProductosPorCategoria($conn);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $productos = json_decode($_POST['productos'], true);
    if ($accion === 'guardar') {
        if (!isset($_SESSION['pedido_activo'][$mesaId]) || $_SESSION['pedido_activo'][$mesaId] === null) {
            // Guardar el usuario que atendió el pedido en la sesión
            if (!isset($_SESSION['usuario_atendio'][$mesaId])) {
                $_SESSION['usuario_atendio'][$mesaId] = $userId; 
            }
    
            $sqlNuevoPedido = "INSERT INTO pedidos (mesa_id, estado, usuario_id) VALUES (?, 'en_proceso', ?)";
            $stmtNuevoPedido = $conn->prepare($sqlNuevoPedido);
            $stmtNuevoPedido->bind_param("ii", $mesaId, $_SESSION['usuario_atendio'][$mesaId]); 
            $stmtNuevoPedido->execute();
            $_SESSION['pedido_activo'][$mesaId] = $stmtNuevoPedido->insert_id;
        }
        
        $pedidoId = $_SESSION['pedido_activo'][$mesaId];
    
        $sqlDetalle = "
            INSERT INTO detalles_pedidos (pedido_id, producto_id, cantidad) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
        $stmtDetalle = $conn->prepare($sqlDetalle);
        foreach ($productos as $producto) {
            $stmtDetalle->bind_param("iii", $pedidoId, $producto['id'], $producto['cantidad']);
            $stmtDetalle->execute();
        }
    
        $sqlActualizarMesa = "UPDATE mesas SET estado = 'ocupada' WHERE id = ?";
        $stmtActualizarMesa = $conn->prepare($sqlActualizarMesa);
        $stmtActualizarMesa->bind_param("i", $mesaId);
        $stmtActualizarMesa->execute();
    
        echo "<script>alert('Pedido actualizado con éxito.'); window.location.href='?mesa={$mesaId}';</script>";
    }
       elseif ($accion === 'imprimir') {
        $pedidoId = $_SESSION['pedido_activo'][$mesaId];
        $detallesPedido = obtenerDetallesPedido($pedidoId, $conn);
        $contenidoImpresion = "<h2 style='text-align:center;'>POLLERIA LA CALETA</h2>";
        $contenidoImpresion .= "<h3 style='text-align:center;'>Pedido Actual</h3>";
        $contenidoImpresion .= "<table style='width:100%; text-align:left; border-collapse: collapse;'>
            <thead>
                <tr style='border-bottom: 1px solid #000;'>
                    <th style='padding: 5px;'>Cantidad</th>
                    <th style='padding: 5px;'>Producto</th>
                    <th style='padding: 5px;'>Precio Unitario</th>
                    <th style='padding: 5px;'>Precio Total</th>
                </tr>
            </thead>
            <tbody>";    
        foreach ($detallesPedido as $detalle) {
            $contenidoImpresion .= "<tr>
                <td style='padding: 5px;'>{$detalle['cantidad']}</td>
                <td style='padding: 5px;'>{$detalle['nombre']}</td>
                <td style='padding: 5px;'>S/ {$detalle['precio']}</td>
                <td style='padding: 5px;'>S/ {$detalle['total']}</td>
            </tr>";
        }    
        $totalPedido = array_sum(array_column($detallesPedido, 'total'));
        $contenidoImpresion .= "</tbody>
            <tfoot>
                <tr style='border-top: 1px solid #000;'>
                    <td colspan='3' style='padding: 5px; text-align: right;'>Total:</td>
                    <td style='padding: 5px;'>S/ " . number_format($totalPedido, 2) . "</td>
                </tr>
            </tfoot>
        </table>";
    
        echo "<script>
                printJS({
                    printable: '".addslashes($contenidoImpresion)."',
                    type: 'raw-html'
                });
              </script>";
    }elseif ($accion === 'liberar') {
        // Obtener el usuario que atendió el último pedido de esta mesa
        $sqlPedido = "SELECT usuario_id FROM pedidos WHERE mesa_id = ? AND estado = 'pendiente' ORDER BY fecha DESC LIMIT 1";
        $stmtPedido = $conn->prepare($sqlPedido);
        $stmtPedido->bind_param("i", $mesaId);
        $stmtPedido->execute();
        $resultPedido = $stmtPedido->get_result();
        $usuarioAtendio = ($resultPedido->num_rows > 0) ? $resultPedido->fetch_assoc()['usuario_id'] : null;


        // Finalizar el pedido
        $sqlFinalizarPedido = "UPDATE pedidos SET estado = 'finalizado' WHERE id = ?";
        $stmtFinalizarPedido = $conn->prepare($sqlFinalizarPedido);
        $stmtFinalizarPedido->bind_param("i", $pedidoId);
        $stmtFinalizarPedido->execute();
    
        // Cambiar estado de la mesa a 'disponible' y eliminar usuario asignado
        $sqlLiberarMesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
        $stmtLiberarMesa = $conn->prepare($sqlLiberarMesa);
        $stmtLiberarMesa->bind_param("i", $mesaId);
        $stmtLiberarMesa->execute();
    
        // Eliminar el pedido Y EL USUARIO de la sesión
        unset($_SESSION['pedido_activo'][$mesaId]);
        unset($_SESSION['usuario_atendio'][$mesaId]); // 💡 Agregar esta línea

    
        // Definir la redirección según el rol del usuario
        if ($_SESSION['rol_id'] == 1) {
            $redirectUrl = 'paneladministrador.php';
        } elseif ($_SESSION['rol_id'] == 3) {
            $redirectUrl = 'panelcajero.php';
        } else {
            $redirectUrl = 'index.php'; // Página por defecto si no es admin ni cajero
        }
    
        // Redirigir con mensaje de éxito
        echo "<script>
            alert('Pedido finalizado y mesa liberada correctamente.');
            window.location.href = '{$redirectUrl}';
        </script>";
}   elseif ($accion === 'anular_pedido') {
    $pedidoId = $_SESSION['pedido_activo'][$mesaId];
    $motivo = $_POST['motivo'] ?? 'Sin especificar';

    // Insertar en la tabla pedidos_anulados con el usuario_id
    $sqlAnularPedido = "INSERT INTO pedidos_anulados (pedido_id, motivo, usuario_id) VALUES (?, ?, ?)";
    $stmtAnularPedido = $conn->prepare($sqlAnularPedido);
    $stmtAnularPedido->bind_param("isi", $pedidoId, $motivo, $userId);
    $stmtAnularPedido->execute();

    // Actualizar estado del pedido a "anulado"
    $sqlActualizarPedido = "UPDATE pedidos SET estado = 'anulado' WHERE id = ?";
    $stmtActualizarPedido = $conn->prepare($sqlActualizarPedido);
    $stmtActualizarPedido->bind_param("i", $pedidoId);
    $stmtActualizarPedido->execute();

    // Liberar la mesa
    $sqlLiberarMesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
    $stmtLiberarMesa = $conn->prepare($sqlLiberarMesa);
    $stmtLiberarMesa->bind_param("i", $mesaId);
    $stmtLiberarMesa->execute();

    unset($_SESSION['pedido_activo'][$mesaId]);

    echo "<script>
        alert('Pedido anulado con éxito. Mesa liberada.');
        var rolId = {$_SESSION['rol_id']}; 
        if (rolId == 1) {
            window.location.href = 'paneladministrador.php';
        } else if (rolId == 3) {
            window.location.href = 'panelcajero.php';
        } else {
            window.location.href = 'index.php'; // Redirección por defecto
        }
    </script>";
} 
elseif ($accion === 'anular_producto') {
    $detalleId = $_POST['detalle_id'] ?? null;
    $cantidadAnulada = $_POST['cantidad_anulada'] ?? null;
    $motivo = $_POST['motivo'] ?? 'Sin especificar';

    if ($detalleId && $cantidadAnulada) {
        // Insertar en la tabla pedidos_anulados con usuario_id
        $sqlAnularProducto = "INSERT INTO pedidos_anulados (pedido_id, producto_id, cantidad_anulada, motivo, usuario_id) 
                              SELECT dp.pedido_id, dp.producto_id, ?, ?, ? 
                              FROM detalles_pedidos dp 
                              WHERE dp.id = ?";
        $stmtAnularProducto = $conn->prepare($sqlAnularProducto);
        $stmtAnularProducto->bind_param("isii", $cantidadAnulada, $motivo, $userId, $detalleId);
        $stmtAnularProducto->execute();

        // Reducir la cantidad del producto anulado
        $sqlActualizarDetalle = "UPDATE detalles_pedidos 
                                 SET cantidad = cantidad - ? 
                                 WHERE id = ?";
        $stmtActualizarDetalle = $conn->prepare($sqlActualizarDetalle);
        $stmtActualizarDetalle->bind_param("ii", $cantidadAnulada, $detalleId);
        $stmtActualizarDetalle->execute();

        // Eliminar el producto si la cantidad llega a 0
        $sqlEliminarDetalle = "DELETE FROM detalles_pedidos 
                               WHERE id = ? AND cantidad <= 0";
        $stmtEliminarDetalle = $conn->prepare($sqlEliminarDetalle);
        $stmtEliminarDetalle->bind_param("i", $detalleId);
        $stmtEliminarDetalle->execute();

        echo "<script>alert('Producto anulado con éxito.'); window.location.href='?mesa={$mesaId}';</script>";
    } else {
        echo "<script>alert('Error al anular el producto. Verifica los datos.'); window.location.href='?mesa={$mesaId}';</script>";
    }
}
}    
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Mesa <?php echo htmlspecialchars($_GET['mesa'] ?? ''); ?></title>
    <link rel="stylesheet" href="ESTILOS.css">
</head>
<body>
<div class="main-container">
    <div class="menu-container">
        <h1>Menú de Mesa <?php echo htmlspecialchars($_GET['mesa'] ?? ''); ?></h1>
        <div class="menu">
            <?php foreach ($productosPorCategoria as $categoria => $productos): ?>
                <div class="categoria">
                    <div class="categoria-title"><?php echo htmlspecialchars($categoria); ?></div>
                    <div class="productos">
                        <?php foreach ($productos as $producto): ?>
                            <div class="menu-item" onclick="addItem('<?php echo $producto['id']; ?>', '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['precio']; ?>)"><?php echo htmlspecialchars($producto['nombre']) . " (S/" . number_format($producto['precio'], 2) . ")"; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="selected-items-panel">
    <h2>Pedido Actual</h2>
    <?php
        // Obtener el usuario que realizó el último pedido activo de la mesa actual
        $sqlPedido = "SELECT usuario_id FROM pedidos WHERE mesa_id = ? AND estado IN ('pendiente', 'en_proceso') ORDER BY fecha DESC LIMIT 1";
        $stmtPedido = $conn->prepare($sqlPedido);

        if ($stmtPedido) {
            $stmtPedido->bind_param("i", $mesaId);
            $stmtPedido->execute();
            $resultPedido = $stmtPedido->get_result();

            if ($rowPedido = $resultPedido->fetch_assoc()) {
                $usuarioPedido = $rowPedido['usuario_id']; // Usuario que hizo el pedido activo
            } else {
                $usuarioPedido = null; // No hay pedido activo
            }
            $stmtPedido->close();
        } else {
            die("Error en la consulta de pedidos: " . $conn->error);
        }

        // Si hay un usuario asociado a un pedido activo, obtener su nombre
        if ($usuarioPedido) {
            $sqlUsuario = "SELECT nombre, apellido FROM usuarios WHERE id = ?";
            $stmtUsuario = $conn->prepare($sqlUsuario);

            if ($stmtUsuario) {
                $stmtUsuario->bind_param("i", $usuarioPedido);
                $stmtUsuario->execute();
                $resultUsuario = $stmtUsuario->get_result();

                if ($rowUsuario = $resultUsuario->fetch_assoc()) {
                    $nombreUsuarioPedido = $rowUsuario['nombre'] . ' ' . $rowUsuario['apellido'];
                } else {
                    $nombreUsuarioPedido = "Desconocido";
                }

                $stmtUsuario->close();
            } else {
                die("Error en la consulta de usuarios: " . $conn->error);
            }
        } else {
            // Si la mesa está liberada y no hay pedidos activos, asignar el usuario que ha iniciado sesión
            if (isset($_SESSION['usuario_id'])) {
                $usuarioPedido = $_SESSION['usuario_id'];

                $sqlUsuarioSesion = "SELECT nombre, apellido FROM usuarios WHERE id = ?";
                $stmtUsuarioSesion = $conn->prepare($sqlUsuarioSesion);

                if ($stmtUsuarioSesion) {
                    $stmtUsuarioSesion->bind_param("i", $usuarioPedido);
                    $stmtUsuarioSesion->execute();
                    $resultUsuarioSesion = $stmtUsuarioSesion->get_result();

                    if ($rowUsuarioSesion = $resultUsuarioSesion->fetch_assoc()) {
                        $nombreUsuarioPedido = $rowUsuarioSesion['nombre'] . ' ' . $rowUsuarioSesion['apellido'];
                    } else {
                        $nombreUsuarioPedido = "Desconocido";
                    }

                    $stmtUsuarioSesion->close();
                } else {
                    die("Error en la consulta de usuarios: " . $conn->error);
                }
            } else {
                $nombreUsuarioPedido = "No asignado"; // Si no hay usuario en sesión
            }
        }
        ?>

        <p><strong>Atendido por:</strong> <?php echo htmlspecialchars($nombreUsuarioPedido); ?></p>
        <span id="nombreUsuarioPedido" style="display: none;"><?php echo htmlspecialchars($nombreUsuarioPedido); ?></span>


    <!-- Tabla de productos seleccionados -->
    <div id="printArea">
    <table id="selected-items-list">
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>Precio Unitario</th>
                <th>Precio Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detallesPedido as $detalle): ?>
                <tr>
                    <td><?php echo $detalle['cantidad']; ?></td>
                    <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                    <td>S/<?php echo number_format($detalle['precio'], 2); ?></td>
                    <td>S/<?php echo number_format($detalle['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>


    <!-- Total -->
    <div class="total" id="total-price">
        Total: S/ <?php echo number_format(array_sum(array_column($detallesPedido, 'total')), 2); ?>
    </div>

    <!-- Descripción Actual -->
    <div id="descripcionActual" style="margin-top: 5px; font-style: italic; color: #555;"></div>

    
    <!-- Botón ENVIAR PEDIDO -->
        <button type="button" class="action-btn" id="send-order" onclick="enviarPedido()">
            Enviar Pedido
        </button>

    <!-- Botón IMPRIMIR PRE-CUENTA -->
    <button type="button" class="action-btn" id="print-receipt" onclick="imprimirPreCuenta()">
        Imprimir Pre-cuenta
    </button>

    <button type="button" class="action-btn" id="back-to-panel" onclick="confirmarFinalizarPedido()">
        Finalizar Pedido
    </button>


    <button type="button" class="action-btn" id="descripcion-btn" onclick="mostrarCuadroDescripcion()">DESCRIPCIÓN</button>

    <!-- Cuadro para agregar descripción del pedido -->
    <div id="cuadroDescripcion" style="display: none; margin-top: 10px;">
        <label for="descripcionPedido">Indicación del Pedido:</label>
        <textarea id="descripcionPedido" rows="3" style="width: 100%; padding: 5px;"></textarea>
        <button type="button" onclick="guardarDescripcion()">Guardar Descripción</button>
    </div>


    <!-- Formulario para enviar pedido -->
    <form method="POST" action="">
        <input type="hidden" name="accion" id="accion" value="guardar">
        <input type="hidden" name="productos" id="productos"> <!-- Se llenará con los productos seleccionados -->
    </form>


        </form>
        <div class="action-buttons" style="margin-top: 20px;">
    <div class="anular-pedido" style="border: 1px solid #dc3545; padding: 15px; border-radius: 8px; margin-bottom: 20px; background-color: #f8d7da;">
        <h3 style="color: #dc3545; margin-bottom: 10px;">Anular Pedido</h3>
        <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas anular este pedido?');">
            <input type="hidden" name="accion" value="anular_pedido">
            <label for="motivo_pedido" style="display: block; margin-bottom: 5px;">Motivo de Anulación:</label>
            <input type="text" name="motivo" id="motivo_pedido" placeholder="Motivo de la anulación" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #dc3545; border-radius: 5px;">
            <button type="submit" class="action-btn" style="background-color: #dc3545; color: white; width: 100%;">Anular Pedido</button>
        </form>
    </div>
<div class="action-buttons" style="margin-top: 20px;">
    <div class="anular-producto" style="border: 1px solid #ffc107; padding: 15px; border-radius: 8px; background-color: #fff3cd;">
    <h3 style="color: #856404; margin-bottom: 10px;">Anular Producto</h3>
    <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas anular este producto?');">
        <input type="hidden" name="accion" value="anular_producto">
        <label for="producto_anulado" style="display: block; margin-bottom: 5px;">Producto:</label>
        <select name="detalle_id" id="producto_anulado" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ffc107; border-radius: 5px;"> 
    <?php foreach ($detallesPedido as $detalle): ?>
        <option value="<?php echo $detalle['detalle_id']; ?>">
            <?php echo htmlspecialchars($detalle['nombre']); ?> (Cantidad: <?php echo $detalle['cantidad']; ?> - ID Detalle: <?php echo $detalle['detalle_id']; ?>)
        </option>
    <?php endforeach; ?>
</select>
        <label for="cantidad_anulada" style="display: block; margin-bottom: 5px;">Cantidad a Anular:</label>
        <input type="number" name="cantidad_anulada" id="cantidad_anulada" min="1" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ffc107; border-radius: 5px;">
        <label for="motivo_producto" style="display: block; margin-bottom: 5px;">Motivo:</label>
        <input type="text" name="motivo" id="motivo_producto" placeholder="Motivo de la anulación" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ffc107; border-radius: 5px;">
        <button type="submit" class="action-btn" style="background-color: #ffc107; color: white; width: 100%;">Anular Producto</button>
    </form>
</div>
</div>  
<?php if ($_SESSION['rol_id'] == 1): ?>
    <button class="action-btn" id="back-to-panel" onclick="window.location.href='paneladministrador.php'">Regresar al Panel de Administrador</button>
<?php elseif ($_SESSION['rol_id'] == 3): ?>
    <button class="action-btn" id="back-to-panel" onclick="window.location.href='panelcajero.php'">Regresar al Panel de Cajero</button>
<?php else: ?>
    <button class="action-btn" id="back-to-panel" onclick="window.location.href='index.php'">Regresar</button>
<?php endif; ?>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>

<script>

function imprimirPedido() {
    // Obtener el número de la mesa desde el <h1>
    const mesaElemento = document.querySelector('h1'); 
    let numeroMesa = "Desconocida";

    if (mesaElemento) {
        const match = mesaElemento.innerText.match(/\d+/);
        if (match) {
            numeroMesa = match[0];
        }
    }

    // Obtener el nombre del usuario desde el HTML
    const nombreUsuario = document.getElementById('nombreUsuarioPedido')?.innerText || "Desconocido";

    // Crear el título con el número de la mesa
    let contenidoImpresion = `<h2 style='text-align: center; font-size: 18px; margin: 0;'>Ticket de Pedido - MESA ${numeroMesa}</h2>`;
    
    // Agregar el nombre del usuario que atendió
    contenidoImpresion += `<p style="text-align: center; font-size: 14px; margin-top: 20px;"><strong>Atendido por:</strong> ${nombreUsuario}</p>`;

    // Agregar productos y cantidades
    const productosTable = document.getElementById('selected-items-list').getElementsByTagName('tbody')[0];
    const rows = productosTable.getElementsByTagName('tr');

    for (let row of rows) {
        const cantidad = row.cells[0].innerText;
        const producto = row.cells[1].innerText;
        contenidoImpresion += `
            <p style="text-align: center; font-size: 14px; margin: 15px 0;">
                ${producto} <span style="font-weight: bold; font-size: 16px;">x ${cantidad}</span>
            </p>
        `;
    }

    // Agregar descripción si existe
    const descripcion = document.getElementById('descripcionPedido').value;
    if (descripcion) {
        contenidoImpresion += `<p style="text-align: center; font-weight: bold; font-size: 12px; margin-top: 20px;">${descripcion}</p>`;
    }

    // Usamos printJS para imprimir el contenido
    printJS({
        printable: contenidoImpresion,
        type: 'raw-html',
        style: `
            body {
                font-family: 'Courier New', monospace;
                font-size: 10pt;
                margin: 0;
                padding: 0;
            }
            h2, p {
                text-align: center;
                margin: 0;
            }
            h2 {
                font-size: 18px;
                font-weight: bold;
            }
            p {
                font-size: 14px;
                margin: 15px 0;
            }
            p.italic {
                font-style: italic;
            }
            .bold-description {
                font-weight: bold;
            }
            @page {
                size: auto;
                margin: 0;
            }
        `
    });
}


function enviarPedido() {
    // Primero imprime
    imprimirPedido();

    // Después de un breve retardo, envía el formulario
    setTimeout(() => {
      submitForm('guardar');
    }, 500);
  }
  // Función para confirmar si desea finalizar el pedido y liberar la mesa
function confirmarFinalizarPedido() {
    let confirmar = confirm("¿Estás seguro de que quieres finalizar el pedido y liberar la mesa?");
    if (confirmar) {
        submitForm('liberar');
    }
}

function imprimirPreCuenta() {
     // Obtener el nombre del usuario desde el elemento en la página
     const nombreUsuario = document.getElementById('nombreUsuarioPedido')?.innerText || "Desconocido";

    let contenidoImpresion = `
        <h2 style='text-align: center; font-size: 20px; font-weight: bold; margin: 10px 0;'>LA CALETA</h2>
        <p style='text-align: center; font-size: 12px; margin: 5px 0;'>LIMA, HUAYCAN</p>
        <p style='text-align: center; font-size: 12px; margin: 5px 0;'>Fecha: ${new Date().toLocaleDateString()} - Hora: ${new Date().toLocaleTimeString()}</p>
        <p style="text-align: center; font-size: 14px; margin-top: 10px;"><strong>Atendido por:</strong> ${nombreUsuario}</p>

        <hr style="border: 1px dashed #000; margin: 10px 0;">
        <h3 style='text-align: center; font-size: 14px; margin: 0;'>TICKET TOTAL A PAGAR</h3>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
        <div style="width: 85%; margin: 12px auto 0 auto; text-align: left; font-size: 9px;">
            <div style="
                display: flex; 
                border-bottom: 1px solid #000; 
                padding: 8px 0; 
                font-weight: bold;
            ">
                <span style="width: 35%; padding-left: 5px;">Item</span>
                <span style="width: 20%; text-align: center;">Cantidad</span>
                <span style="width: 20%; text-align: center;">Precio</span>
                <span style="width: 25%; text-align: right; padding-right: 5px;">Total</span>
            </div>
`;
    
 // Agregar productos y detalles organizados con negrita
const productosTable = document.getElementById('selected-items-list').getElementsByTagName('tbody')[0];
const rows = productosTable.getElementsByTagName('tr');

for (let row of rows) {
    const cantidad = row.cells[0].innerText;
    const producto = row.cells[1].innerText;
    const precioUnitario = row.cells[2].innerText.replace("S/", "").trim();
    const precioTotal = row.cells[3].innerText.replace("S/", "").trim();

    contenidoImpresion += `
        <div style="display: flex; font-family: 'Courier New', monospace; padding: 4px 0; font-size: 10px; border-bottom: 1px dashed #000; font-weight: bold;">
            <div style="width: 43%; font-size: 9px; text-align: left; word-wrap: break-word;">${producto}</div>
                <div style="width: 30%; font-size: 9px; text-align: center;">${cantidad}</div>
                <div style="width: 25%; font-size: 9px; text-align: center;">S/ ${precioUnitario}</div>
                <div style="width: 33%; font-size: 9px; text-align: right;">S/ ${precioTotal}</div>
            </div>
    `;
}
    // Agregar el total a pagar alineado más a la izquierda, pero sin cortar el texto
    const totalPedido = document.getElementById('total-price').innerText.replace("S/", "");
    contenidoImpresion += `
        <div style="display: flex; justify-content: space-between; padding-top: 5px; font-size: 9px; margin-left: 10px;">
            <p style="font-weight: bold; font-size: 10px; margin: 0; text-align: left;"></p>
            <p style="font-weight: bold; font-size: 10px; margin: 15px; text-align: right;">S/ ${totalPedido}</p>
        </div>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
    `;

    contenidoImpresion += `
        <div style="text-align: center; font-size: 12px; margin-top: 10px;">
            <p style="font-size: 14px; font-weight: bold;">Gracias por su compra</p>
        </div>
    `;

    // Usamos printJS para imprimir el contenido
    printJS({
        printable: contenidoImpresion,
        type: 'raw-html',
        style: `
            body {
                font-family: 'Arial', sans-serif;
                font-size: 12pt;
                margin: 0;
                padding: 0;
            }
            h2, h3, p {
                margin: 0;
            }
            h2 {
                font-size: 18px;
                font-weight: bold;
                margin-top: 0;
                text-align: center;
            }
            h3 {
                font-size: 14px;
                text-align: center;
                margin-bottom: 5px;
            }
            p {
                font-size: 12px;
                margin: 5px 0;
            }
            .total {
                font-weight: bold;
                font-size: 14px;
                text-align: right;
            }
            @page {
                size: auto;
                margin: 0;
            }
        `
    });
    }
    function precuentaYFinalizar() {
    // Primero imprime
    imprimirPreCuenta();

    // Después de un breve retardo, envía el formulario
    setTimeout(() => {
      submitForm('imprimir');
    }, 500);
  }

    function mostrarCuadroDescripcion() {
        const cuadro = document.getElementById('cuadroDescripcion');
        cuadro.style.display = cuadro.style.display === 'none' ? 'block' : 'none';
    }

    function guardarDescripcion() {
        const descripcion = document.getElementById('descripcionPedido').value;
        document.getElementById('descripcionActual').innerText = descripcion ? `Indicación: ${descripcion}` : '';
        document.getElementById('cuadroDescripcion').style.display = 'none';
    }

    let selectedItems = {};
    function addItem(id, name, price) {
        if (!selectedItems[id]) {
            selectedItems[id] = { name, price, quantity: 0, total: 0 };
        }
        selectedItems[id].quantity++;
        selectedItems[id].total = selectedItems[id].quantity * selectedItems[id].price;
        updateSelectedItems();
    }
    function updateSelectedItems() {
        const tbody = document.querySelector('#selected-items-list tbody');
        tbody.innerHTML = '';

        Object.values(selectedItems).forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.quantity}</td>
                <td>${item.name}</td>
                <td>S/${item.price.toFixed(2)}</td>
                <td>S/${item.total.toFixed(2)}</td>
            `;
            tbody.appendChild(row);
        });
        const total = Object.values(selectedItems).reduce((sum, item) => sum + item.total, 0);
        document.getElementById('total-price').textContent = `Total: S/ ${total.toFixed(2)}`;
    }
    function submitForm(action) {
    document.getElementById('accion').value = action;
    
    // Convertimos 'selectedItems' a JSON y lo ponemos en el hidden "productos"
    document.getElementById('productos').value = JSON.stringify(
      Object.entries(selectedItems).map(([id, item]) => ({
        id: parseInt(id),
        cantidad: item.quantity
      }))
    );

    // Enviamos el formulario al servidor
    document.forms[0].submit();
  }
</script>
</body>
</html>
