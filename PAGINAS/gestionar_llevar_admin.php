<?php
// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión y tiene un usuario_id
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "polleria";

$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
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

// Verificar rol del usuario
$sqlRol = "SELECT rol_id FROM usuarios WHERE id = ?";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("i", $_SESSION['usuario_id']);
$stmtRol->execute();
$resultRol = $stmtRol->get_result();
$rol_id = ($resultRol->num_rows > 0) ? $resultRol->fetch_assoc()['rol_id'] : 0;

// Verificar si el usuario tiene rol_id = 1 o rol_id = 3
if (!in_array($rol_id, [1, 3])) {
    header("Location: index.php");
    exit();
}


// Variables iniciales
$mesaId = null;
$nombreCliente = '';
$direccion = '';
$pedidoId = null;

// Verificar mesa seleccionada
if (isset($_GET['mesa'])) {
    $mesaLetra = $_GET['mesa'];

    // Obtener el ID de la mesa
    $sqlMesa = "SELECT id FROM mesas WHERE letra = ?";
    $stmtMesa = $conn->prepare($sqlMesa);
    $stmtMesa->bind_param("s", $mesaLetra);
    $stmtMesa->execute();
    $mesaResult = $stmtMesa->get_result();
    $mesa = $mesaResult->fetch_assoc();
    $mesaId = $mesa['id'] ?? null;

    if (!$mesaId) {
        die("Error: Mesa no válida.");
    }

    // Verificar si hay un pedido activo para esta mesa
    if (!isset($_SESSION['pedido_activo'][$mesaId])) {
        // Buscar un pedido activo en la base de datos
        $sqlPedidoActivo = "SELECT id, nombre_cliente, direccion FROM pedidos WHERE mesa_id = ? AND estado = 'en_proceso'";
        $stmtPedidoActivo = $conn->prepare($sqlPedidoActivo);
        $stmtPedidoActivo->bind_param("i", $mesaId);
        $stmtPedidoActivo->execute();
        $pedidoActivoResult = $stmtPedidoActivo->get_result();

        if ($pedidoActivoResult->num_rows > 0) {
            // Si existe un pedido activo, cargarlo en la sesión
            $pedidoActivo = $pedidoActivoResult->fetch_assoc();
            $pedidoId = $pedidoActivo['id'];
            $_SESSION['pedido_activo'][$mesaId] = $pedidoId;

            // Cargar los datos del cliente asociados al pedido
            $nombreCliente = $pedidoActivo['nombre_cliente'] ?? '';
            $direccion = $pedidoActivo['direccion'] ?? '';
        } else {
            // Si no hay un pedido activo, inicializar sin crear un pedido
            $_SESSION['pedido_activo'][$mesaId] = null;
        }
    } else {
        $pedidoId = $_SESSION['pedido_activo'][$mesaId];

        // Recuperar los datos del pedido activo desde la base de datos
        $sqlPedidoActivo = "SELECT nombre_cliente, direccion FROM pedidos WHERE id = ?";
        $stmtPedidoActivo = $conn->prepare($sqlPedidoActivo);
        $stmtPedidoActivo->bind_param("i", $pedidoId);
        $stmtPedidoActivo->execute();
        $pedidoActivoResult = $stmtPedidoActivo->get_result();
        $pedidoActivo = $pedidoActivoResult->fetch_assoc();
        $nombreCliente = $pedidoActivo['nombre_cliente'] ?? '';
        $direccion = $pedidoActivo['direccion'] ?? '';
    }
} else {
    die("Error: Mesa no seleccionada.");
}

// Obtener los productos del pedido
function obtenerDetallesPedido($pedidoId, $conn) {
    if (!$pedidoId) return [];
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


// Obtener los productos existentes en el pedido
$detallesPedido = $pedidoId ? obtenerDetallesPedido($pedidoId, $conn) : [];

// Enviar los productos existentes al frontend
$productosExistentes = json_encode($detallesPedido);

// Obtener productos agrupados por categoría
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

$productosPorCategoria = obtenerProductosPorCategoria($conn);

// Manejo de acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $productos = json_decode($_POST['productos'], true);
        $nombreCliente = $_POST['nombre_cliente'] ?? '';
        $direccion = $_POST['direccion'] ?? '';

        if (!empty($productos)) {
            // Crear pedido si no existe
            if (!$pedidoId) {
                $sqlNuevoPedido = "INSERT INTO pedidos (mesa_id, usuario_id, estado, nombre_cliente, direccion, fecha) VALUES (?, ?, 'en_proceso', ?, ?, NOW())";
                $stmtNuevoPedido = $conn->prepare($sqlNuevoPedido);
                $stmtNuevoPedido->bind_param("iiss", $mesaId, $_SESSION['usuario_id'], $nombreCliente, $direccion);
                $stmtNuevoPedido->execute();
                $pedidoId = $stmtNuevoPedido->insert_id;

                $_SESSION['pedido_activo'][$mesaId] = $pedidoId;

                // Cambiar el estado de la mesa a 'ocupada'
                $sqlActualizarMesa = "UPDATE mesas SET estado = 'ocupada' WHERE id = ?";
                $stmtActualizarMesa = $conn->prepare($sqlActualizarMesa);
                $stmtActualizarMesa->bind_param("i", $mesaId);
                $stmtActualizarMesa->execute();
            }

            foreach ($productos as $producto) {
                $sqlDetalle = "INSERT INTO detalles_pedidos (pedido_id, producto_id, cantidad) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
                $stmtDetalle = $conn->prepare($sqlDetalle);
                $stmtDetalle->bind_param("iii", $pedidoId, $producto['id'], $producto['cantidad']);
                $stmtDetalle->execute();
            }

            echo "<script>alert('Pedido enviado y mesa marcada como ocupada.'); window.location.href='?mesa={$mesaLetra}';</script>";
        } else {
            echo "<script>alert('No se seleccionaron productos.');</script>";
        }
    } elseif ($accion === 'imprimir') {
        echo "<script> 
            imprimirPreCuenta(); 
            alert('Precuenta impresa correctamente.'); 
            window.location.href = 'mesa_admin.php'; 
        </script>";
    }
     elseif ($accion === 'finalizar') {
        // Finalizar el pedido
        $sqlFinalizarPedido = "UPDATE pedidos SET estado = 'finalizado' WHERE id = ?";
        $stmtFinalizarPedido = $conn->prepare($sqlFinalizarPedido);
        $stmtFinalizarPedido->bind_param("i", $pedidoId);
        $stmtFinalizarPedido->execute();
    
        // Cambiar estado de la mesa a 'disponible'
        $sqlLiberarMesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
        $stmtLiberarMesa = $conn->prepare($sqlLiberarMesa);
        $stmtLiberarMesa->bind_param("i", $mesaId);
        $stmtLiberarMesa->execute();
    
        // Eliminar el pedido de la sesión
        unset($_SESSION['pedido_activo'][$mesaId]);
    
        echo "<script>alert('Pedido finalizado y mesa liberada correctamente.'); window.location.href = 'mesa_admin.php';</script>";
    }
    
    elseif ($accion === 'anular_pedido') {
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
                window.location.href = 'mesa_admin.php';
            } else if (rolId == 3) {
                window.location.href = 'mesa_admin.php';
            } else {
                window.location.href = 'index.php'; // Redirección por defecto
            }
        </script>";
    } 
    elseif ($accion === 'anular_producto') {
        // Obtener los datos del formulario
        $detalleId = $_POST['detalle_id'] ?? null; // Usar detalle_id en lugar de producto_id
        $cantidadAnulada = $_POST['cantidad_anulada'] ?? null;
        $motivo = $_POST['motivo'] ?? 'Sin especificar';
        $usuarioId = $_SESSION['usuario_id'] ?? null; // Obtener el ID del usuario que inició sesión
    
        if ($detalleId && $cantidadAnulada && $usuarioId) {
            // Registrar la anulación en pedidos_anulados
            $sqlAnularProducto = "INSERT INTO pedidos_anulados (pedido_id, producto_id, cantidad_anulada, motivo, usuario_id) 
                                  SELECT dp.pedido_id, dp.producto_id, ?, ?, ? 
                                  FROM detalles_pedidos dp 
                                  WHERE dp.id = ?";
            $stmtAnularProducto = $conn->prepare($sqlAnularProducto);
            $stmtAnularProducto->bind_param("isii", $cantidadAnulada, $motivo, $usuarioId, $detalleId);
            $stmtAnularProducto->execute();
    
            // Actualizar la cantidad en el registro específico de detalles_pedidos
            $sqlActualizarDetalle = "UPDATE detalles_pedidos 
                                     SET cantidad = cantidad - ? 
                                     WHERE id = ?";
            $stmtActualizarDetalle = $conn->prepare($sqlActualizarDetalle);
            $stmtActualizarDetalle->bind_param("ii", $cantidadAnulada, $detalleId);
            $stmtActualizarDetalle->execute();
    
            // Eliminar el registro si la cantidad llega a 0
            $sqlEliminarDetalle = "DELETE FROM detalles_pedidos 
                                   WHERE id = ? AND cantidad <= 0";
            $stmtEliminarDetalle = $conn->prepare($sqlEliminarDetalle);
            $stmtEliminarDetalle->bind_param("i", $detalleId);
            $stmtEliminarDetalle->execute();
    
            echo "<script>alert('Producto anulado correctamente.'); window.location.href='?mesa={$mesaLetra}';</script>";
        } else {
            echo "<script>alert('Error al anular el producto. Verifica los datos.'); window.location.href='?mesa={$mesaLetra}';</script>";
        }
    }   
}
    
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Pedido para Llevar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            display: flex;
            justify-content: space-between;
        }

        .menu-container {
            width: 60%;
        }

        .menu {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .categoria {
            flex: 1 1 calc(33.33% - 30px);
            margin-bottom: 40px;
            min-width: 300px;
        }

        .categoria-title {
            background-color: #007bff;
            color: #fff;
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            text-align: left;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .productos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .menu-item {
            background-color: #ffcc00;
            padding: 20px;
            font-size: 16px;
            color: #333;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, background-color 0.3s;
        }

        .menu-item:hover {
            background-color: #f1f1f1;
            transform: scale(1.05);
        }


        .menu-item {
            background-color: #ffcc00;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            transition: transform 0.3s;
            cursor: pointer;
        }

        .menu-item:hover {
            transform: scale(1.05);
        }

        .selected-items-panel {
            flex: 0 0 40%;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f9f9f9;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .quantity-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 5px;
        }

        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
        }
        .action-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #send-order {
            background-color:rgb(56, 148, 247);
            color: white;
        }
        #print-receipt {
            background-color: #28a745;
            color: white;
        }
        #finalizar-pedido {
            background-color:rgb(8, 56, 145);
            color: white;
        }
        #btnDescripcion {
            background-color:rgb(151, 0, 13);
            color: white;
        }
        .disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>
</head>
<body>

<div class="menu-container">
    <h1>PEDIDOS PARA LLEVAR</h1>
    <div class="menu">
        <?php foreach ($productosPorCategoria as $categoria => $productos): ?>
            <div class="categoria">
                <div class="categoria-title"><?php echo htmlspecialchars($categoria); ?></div>
                <div class="productos">
                    <?php foreach ($productos as $producto): ?>
                        <div class="menu-item" onclick="addItem('<?php echo $producto['id']; ?>', '<?php echo htmlspecialchars($producto['nombre']); ?>', <?php echo $producto['precio']; ?>)">
                            <?php echo htmlspecialchars($producto['nombre']) . " (S/" . number_format($producto['precio'], 2) . ")"; ?>
                        </div>
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


    <form method="POST" action="">
        <!-- Campo de Nombre del Cliente -->
        <label for="nombre_cliente">Nombre del Cliente:</label>
        <input type="text" id="nombre_cliente" name="nombre_cliente" 
               placeholder="Ingrese el nombre del cliente" 
               value="<?php echo htmlspecialchars($nombreCliente); ?>" required>
        
        <br><br>
        
        <!-- Campo de Dirección -->
        <label for="direccion">Dirección (opcional):</label>
        <input type="text" id="direccion" name="direccion" 
               placeholder="Ingrese la dirección de entrega" 
               value="<?php echo htmlspecialchars($direccion); ?>">

        <!-- Inputs ocultos -->
        <input type="hidden" name="productos" id="productos">
        <input type="hidden" name="mesa_letra" value="A">
        <input type="hidden" name="accion" id="accion" value="guardar">

        <!-- Tabla de productos -->
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
        <p id="nombreUsuarioPedido" style="display: none;"><?php echo htmlspecialchars($nombreUsuarioPedido); ?></p>


        <div class="total" id="total-price">Total: S/ 0.00</div>
        <div id="descripcionActual" style="margin-top: 5px; font-style: italic; color: #555;"></div>
       
        <!-- Botones de acción -->
        <button type="button" class="action-btn" id="send-order" onclick="submitForm('guardar')">Enviar Pedido</button>
        <!-- Botón para imprimir la precuenta -->
        <button type="button" class="action-btn" id="print-receipt" onclick="imprimirPreCuenta()">Imprimir Precuenta</button>

        <!-- Botón para finalizar pedido y liberar mesa -->
        <button type="button" class="action-btn" id="finalizar-pedido" onclick="submitForm('finalizar')">Finalizar Pedido</button>
        <button type="button" class="action-btn" id="btnDescripcion" onclick="mostrarCuadroDescripcion()">DESCRIPCIÓN</button>
            <div id="cuadroDescripcion" style="display: none; margin-top: 10px;">
    <label for="descripcionPedido">Indicación del Pedido:</label>
    <textarea id="descripcionPedido" rows="3" style="width: 100%; padding: 5px;"></textarea>
    <button type="button" onclick="guardarDescripcion()">Guardar Descripción</button>
</div>
    </form>

    <div class="action-buttons" style="margin-top: 20px;">
        <div class="anular-pedido" style="border: 1px solid #dc3545; padding: 15px; border-radius: 8px; margin-bottom: 20px; background-color: #f8d7da;">
            <h3 style="color: #dc3545; margin-bottom: 10px;">Anular Pedido</h3>
            <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas anular este pedido?');">
                <input type="hidden" name="accion" value="anular_pedido">
                <label for="motivo_pedido" style="display: block; margin-bottom: 5px;">Motivo de anulación:</label>
                <input type="text" name="motivo" id="motivo_pedido" placeholder="Motivo de la anulación" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #dc3545; border-radius: 5px;">
                <button type="submit" class="action-btn" style="background-color: #dc3545; color: white; width: 100%;">Anular Pedido</button>
            </form>
        </div>
        
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
    <label for="cantidad_anulada" style="display: block; margin-bottom: 5px;">Cantidad a anular:</label>
    <input type="number" name="cantidad_anulada" id="cantidad_anulada" min="1" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ffc107; border-radius: 5px;">
    <label for="motivo_producto" style="display: block; margin-bottom: 5px;">Motivo:</label>
    <input type="text" name="motivo" id="motivo_producto" placeholder="Motivo de la anulación" required style="width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ffc107; border-radius: 5px;">
    <button type="submit" class="action-btn" style="background-color: #ffc107; color: white; width: 100%;">Anular Producto</button>
</form>

        </div>
    </div>

    <!-- Botón de regresar -->
    <button class="action-btn" id="send-order" onclick="window.location.href='mesa_admin.php'">Regresar</button>
</div>


<script>

    let existingItems = <?php echo $productosExistentes; ?>; // Productos del pedido existente
    let selectedItems = {}; // Productos nuevos seleccionados
        
    // Función para agregar un producto nuevo
    function addItem(id, name, price) {
        if (selectedItems[id]) {
            selectedItems[id].quantity++;
            selectedItems[id].total = selectedItems[id].quantity * selectedItems[id].price;
        } else {
            selectedItems[id] = { id, name, price, quantity: 1, total: price };
        }
        updateSelectedItems(); // Actualiza el panel de productos nuevos
    }
    function imprimirPedido() {
    // Obtenemos la descripción guardada en la parte de "PEDIDO ACTUAL"
    const descripcion = document.getElementById('descripcionActual').textContent;

    // Obtener el nombre del usuario desde el HTML (desde un elemento oculto)
    const nombreUsuario = document.getElementById('nombreUsuarioPedido')?.innerText || "Desconocido";

    // Iniciamos el contenido que se imprimirá
    let contenidoImpresion = "<h2 style='text-align:center;'>PEDIDOS PARA LLEVAR</h2>";

    // Agregamos el nombre del usuario
    contenidoImpresion += `
        <p style="text-align:center; font-weight:bold; margin-bottom: 10px;">
            <strong>Atendido por:</strong> ${nombreUsuario}
        </p>
    `;

    // Obtenemos las filas de la tabla #selected-items-list
    const rows = document.querySelectorAll('#selected-items-list tbody tr');

    // Recorremos cada fila para extraer solo CANTIDAD y PRODUCTO
    rows.forEach(row => {
        const cantidad = row.cells[0].innerText;  // Columna de Cantidad
        const producto = row.cells[1].innerText;  // Columna de Producto
        contenidoImpresion += `
            <p style="text-align:center; margin: 5px 0;">
                ${producto} <strong>x ${cantidad}</strong>
            </p>
        `;
    });

    // Si existe alguna descripción, la agregamos
    if (descripcion) {
        contenidoImpresion += `
            <p style="text-align:center; margin-top: 10px; font-weight:bold;">
                ${descripcion}
            </p>
        `;
    }

    // Usamos printJS para imprimir
    printJS({
        printable: contenidoImpresion,
        type: 'raw-html',
        style: `
            body {
                font-family: monospace;
                font-size: 10pt;
                margin: 0;
                padding: 5px;
            }
            h2 {
                text-align: center;
                margin-bottom: 10px;
            }
            p {
                margin: 0;
                padding: 0;
            }
            @page {
                margin: 0;
            }
        `
    });
}


    function imprimirPreCuenta() {
        const nombreUsuario = document.getElementById('nombreUsuarioPedido').textContent.trim() || '---';

        // 1. Obtenemos datos de nombre y dirección
        const nombreCliente = document.getElementById('nombre_cliente').value.trim() || '';
        const direccion = document.getElementById('direccion').value.trim() || '';

        // 2. Iniciamos contenido del ticket
        let contenidoImpresion = `
            <h2 style="text-align:center; font-size: 18px; font-weight: bold; margin: 10px 0;">LA CALETA</h2>
            <p style="text-align:center; font-size: 12px; margin: 5px 0;">LIMA, HUAYCAN</p>
            <p style="text-align:center; font-size: 12px; margin: 5px 0;">
                Fecha: ${new Date().toLocaleDateString()} - Hora: ${new Date().toLocaleTimeString()}
            </p>
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            <h3 style="text-align:center; font-size: 14px; margin: 0;">TICKET TOTAL A PAGAR</h3>
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
        <div style="width: 90%; margin: 0 auto; text-align: left; font-size: 10px;">
                <p style="margin: 3px 0;">
                    <!-- Etiqueta fija a la derecha -->
                    <span style="display: inline-block; width: 60px; text-align: right;"><strong>Cliente:</strong></span>
                    <span style="margin-left: 5px; white-space: nowrap;">
                        ${nombreCliente || '---'}
                    </span>
                </p>
                <p style="margin: 3px 0;">
                    <span style="display: inline-block; width: 60px; text-align: right;"><strong>Dirección:</strong></span>
                    <span style="margin-left: 5px; white-space: nowrap;">
                        ${direccion || '---'}
                    </span>
                </p>
                <p style="margin: 3px 0;">
                    <span style="display: inline-block; width: 60px; text-align: right;"><strong>Mozo:</strong></span>
                    <span style="margin-left: 5px; white-space: nowrap;">
                        ${nombreUsuario || '---'}
                    </span>
                </p>
        </div>


            <div style="width: 90%; margin: 12px auto 0 auto; text-align: left; font-size: 9px;">
                <div style="
                    display: flex; 
                    border-bottom: 1px solid #000; 
                    padding: 8px 0; 
                    font-weight: bold;
                ">
                    <span style="width: 35%; padding-left: 5px;">Item</span>
                    <span style="width: 20%; text-align: center;">Cantidad</span>
                    <span style="width: 20%; text-align: center;">Precio</span>
                    <span style="width: 20%; text-align: right; padding-right: 5px;">Total</span>
                </div>
        `;

    // 3. Recorremos filas de la tabla principal (#selected-items-list)
    const filas = document.querySelectorAll('#selected-items-list tbody tr');
    let totalGeneral = 0;

    filas.forEach(fila => {
        const cantidad = fila.cells[0].innerText;  // Cantidad
        const producto = fila.cells[1].innerText;  // Producto
        const precioU = fila.cells[2].innerText;   // Precio Unitario
        const precioT = fila.cells[3].innerText;   // Precio Total

        // Convertimos a número para sumar
        const valorNum = parseFloat(precioT.replace('S/', '').trim()) || 0;
        totalGeneral += valorNum;

        contenidoImpresion += `
            <div style="
                display: flex; 
                justify-content: space-between; 
                border-bottom: 1px solid #ddd; 
                padding: 6px 0;
            ">
                <span style="width: 40%; padding-left: 5px;">${producto}</span>
                <span style="width: 20%; text-align: center;">${cantidad}</span>
                <span style="width: 20%; text-align: center;">${precioU}</span>
                <span style="width: 20%; text-align: right; padding-right: 5px;">${precioT}</span>
            </div>
        `;
    });

    // 4. Mostramos total
    contenidoImpresion += `
        </div>
        <h3 style="text-align:right; margin: 10px 20px; font-size: 14px;">
            S/ Total: ${totalGeneral.toFixed(2)}
        </h3>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
        <p style="text-align:center; font-size: 14px; font-weight: bold; margin: 0;">
            Gracias por su compra
        </p>
    `;

    // 5. Usamos printJS con un estilo tipo ticket
    printJS({
        printable: contenidoImpresion,
        type: 'raw-html',
        style: `
            body {
                font-family: "Arial", sans-serif;
                font-size: 12px;
                margin: 0;
                padding: 0;
            }
            h2, h3, p {
                margin: 0;
                padding: 0;
            }
            @page {
                size: auto;
                margin: 10px;
            }
        `
    });
}

// Actualizar el panel de "PEDIDO ACTUAL" con nuevos productos
function updateSelectedItems() {
    const tbody = document.querySelector('#selected-items-list tbody');
    tbody.innerHTML = ''; // Limpia las filas previas

    Object.values(selectedItems).forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.quantity}</td>
            <td>${item.name}</td>
            <td>S/${item.price.toFixed(2)}</td>
            <td>S/${item.total.toFixed(2)}</td>
            <td>
                <button class="quantity-btn" onclick="changeQuantity(${item.id}, 1)">+</button>
                <button class="quantity-btn" onclick="changeQuantity(${item.id}, -1)">-</button>
                <button class="remove-btn" onclick="removeItem(${item.id})">Eliminar</button>
            </td>
        `;
        tbody.appendChild(row);
    });

    const total = Object.values(selectedItems).reduce((sum, item) => sum + item.total, 0);
    document.getElementById('total-price').textContent = `Total: S/ ${total.toFixed(2)}`;
}

// Función para enviar productos nuevos
function submitForm(action) {
    document.getElementById('accion').value = action;
    document.getElementById('productos').value = JSON.stringify(
        Object.values(selectedItems).map(item => ({ id: item.id, cantidad: item.quantity }))
    );

    if (action === 'guardar') {
        imprimirPedido();
    } else if (action === 'imprimir') {
        imprimirPreCuenta();
        return; // 🚨 Evita que se envíe el formulario, solo imprime
    } else if (action === 'finalizar') {
        if (!confirm("¿Estás seguro de finalizar el pedido y liberar la mesa?")) {
            return; // Si el usuario cancela, no se hace nada
        }
    }

    setTimeout(function() {
        document.forms[0].submit();
    }, 1000);
}




// Cambiar la cantidad de productos seleccionados
function changeQuantity(id, change) {
    if (selectedItems[id]) {
        selectedItems[id].quantity += change;
        if (selectedItems[id].quantity <= 0) {
            delete selectedItems[id];
        } else {
            selectedItems[id].total = selectedItems[id].quantity * selectedItems[id].price;
        }
        updateSelectedItems();
    }
}

// Eliminar un producto nuevo
    function removeItem(id) {
        delete selectedItems[id];
        updateSelectedItems();
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

    </script>
</body>
</html>
