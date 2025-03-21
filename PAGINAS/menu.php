<?php
// Iniciar sesión
session_start();

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

// Obtener el ID del usuario actual desde la sesión
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

if (!$userId) {
    die("Error: No se pudo obtener el ID del usuario. Asegúrate de haber iniciado sesión.");
}

// Verificar si el ID de la mesa está en la URL
if (isset($_GET['mesa'])) {
    $mesaId = $_GET['mesa'];

    // Validar que el ID de la mesa exista
    if (!validarMesaId($mesaId, $conn)) {
        die("Error: El ID de la mesa no es válido.");
    }
} else {
    die("No se seleccionó ninguna mesa.");
}

// Funciones auxiliares
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
    $sql = "SELECT p.nombre, dp.cantidad, p.precio, (dp.cantidad * p.precio) AS total 
            FROM detalles_pedidos dp
            JOIN productos p ON dp.producto_id = p.id
            WHERE dp.pedido_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pedidoId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Verificar si existe un pedido activo en la sesión
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

// Inicializar los detalles del pedido
$detallesPedido = [];
if (isset($_SESSION['pedido_activo'][$mesaId]) && $_SESSION['pedido_activo'][$mesaId] !== null) {
    $pedidoId = $_SESSION['pedido_activo'][$mesaId];
    $detallesPedido = obtenerDetallesPedido($pedidoId, $conn);
}

$productosPorCategoria = obtenerProductosPorCategoria($conn);

// Manejar acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $productos = json_decode($_POST['productos'], true);

    if ($accion === 'guardar') {
        // Crear el pedido si aún no existe
        if (!isset($_SESSION['pedido_activo'][$mesaId]) || $_SESSION['pedido_activo'][$mesaId] === null) {
            $sqlNuevoPedido = "INSERT INTO pedidos (mesa_id, estado, usuario_id) VALUES (?, 'en_proceso', ?)";
            $stmtNuevoPedido = $conn->prepare($sqlNuevoPedido);
            $stmtNuevoPedido->bind_param("ii", $mesaId, $userId);
            $stmtNuevoPedido->execute();
            $_SESSION['pedido_activo'][$mesaId] = $stmtNuevoPedido->insert_id;
        }

        // Registrar productos en el pedido
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

        // Cambiar estado de la mesa a 'ocupada'
        $sqlActualizarMesa = "UPDATE mesas SET estado = 'ocupada' WHERE id = ?";
        $stmtActualizarMesa = $conn->prepare($sqlActualizarMesa);
        $stmtActualizarMesa->bind_param("i", $mesaId);
        $stmtActualizarMesa->execute();

        echo "<script>alert('Pedido actualizado con éxito.'); window.location.href='?mesa={$mesaId}';</script>";
    } elseif ($accion === 'imprimir') {
        /*// Finalizar el pedido
        $pedidoId = $_SESSION['pedido_activo'][$mesaId];
        $sqlFinalizarPedido = "UPDATE pedidos SET estado = 'finalizado' WHERE id = ?";
        $stmtFinalizarPedido = $conn->prepare($sqlFinalizarPedido);
        $stmtFinalizarPedido->bind_param("i", $pedidoId);
        $stmtFinalizarPedido->execute();

        // Cambiar estado de la mesa a 'disponible'
        $sqlLiberarMesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
        $stmtLiberarMesa = $conn->prepare($sqlLiberarMesa);
        $stmtLiberarMesa->bind_param("i", $mesaId);
        $stmtLiberarMesa->execute();

        unset($_SESSION['pedido_activo'][$mesaId]);*/

        // Redirigir según el ID del rol
        if ($_SESSION['rol_id'] == 1 || $_SESSION['rol_id'] == 3) {
            echo "<script>alert('Pre-cuenta impresa con éxito. Mesa liberada.'); window.location.href='paneladministrador.php';</script>";
        } else {
            echo "<script>alert('Pre-cuenta impresa con éxito.')</script>";
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
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .main-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            max-width: 1400px;
            gap: 20px;
        }

        .menu-container {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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

        .selected-items-panel {
            flex: 0 0 40%;
            background-color: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }

        .selected-items-panel h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
            font-size: 18px;
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        #send-order {
            background-color: #007bff;
            color: white;
        }

        #print-receipt {
            background-color: #28a745;
            color: white;
        }
        #descripcion-btn {
            background-color:rgb(172, 100, 19);
            color: white;
        }

        #back-to-panel {
            background-color: #f0ad4e;
            color: white;
            margin-top: 15px;
        }

        #back-to-panel:hover {
            background-color: #ec971f;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>

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


            <form method="POST">
                <input type="hidden" name="accion" id="accion" value="">
                <input type="hidden" id="productos" name="productos">

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

                <p id="nombreUsuarioPedido" style="display: none;">
                    <?php echo htmlspecialchars($nombreUsuarioPedido); ?>
                </p>

                <div class="total" id="total-price">Total: S/ <?php echo number_format(array_sum(array_column($detallesPedido, 'total')), 2); ?></div>
                <button type="button" class="action-btn" id="send-order" onclick="enviarPedido()">Enviar Pedido</button>
                <button type="button" class="action-btn" id="print-receipt" onclick="precuentaYFinalizar()">Imprimir Pre-cuenta</button>
                <button type="button" class="action-btn" id="descripcion-btn" onclick="mostrarDescripcion()">DESCRIPCIÓN</button>
                <input type="text" id="descripcionInput" placeholder="Escribe una descripción..." style="display: none; width: 100%; margin-top: 10px; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
            
            </form>
            <?php if ($_SESSION['rol_id'] == 1): ?>
                <button class="action-btn" id="back-to-panel" onclick="window.location.href='paneladministrador.php'">Regresar al Panel de Administrador</button>
            <?php else: ?>
                <button class="action-btn" id="back-to-panel" onclick="window.location.href='mesas.php'">Regresar</button>
            <?php endif; ?>
        </div>
    </div>

    <script>

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

        function mostrarDescripcion() {
            document.getElementById('descripcionInput').style.display = 'block';
        }

        // Modificar la función de impresión para incluir la descripción
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

            const nombreUsuario = document.getElementById('nombreUsuarioPedido').textContent.trim() || '---';

            let contenidoImpresion = `
                <h2 style="text-align: center; font-size: 18px; margin: 0;">Ticket de Pedido - MESA ${numeroMesa}</h2>
                <p style="text-align: center; font-size: 12px; margin-top: 5px;">
                    <strong>Mozo:</strong> ${nombreUsuario}
                </p>
            `;

            // Recorre cada fila de la tabla de pedido
            const rows = document.querySelectorAll('#selected-items-list tbody tr');
            rows.forEach(row => {
                const cantidad = row.cells[0].innerText;
                const producto = row.cells[1].innerText;
                contenidoImpresion += `<p style="text-align: center; font-size: 14px; margin: 15px 0;">
                    ${producto} <strong>x ${cantidad}</strong>
                </p>`;
            });

            // Agregar la descripción escrita por el usuario
            const descripcion = document.getElementById('descripcionInput').value.trim();
            if (descripcion) {
                contenidoImpresion += `<p style="text-align: center; font-weight: bold; font-size: 12px; margin-top: 20px;">
                    ${descripcion}
                </p>`;
            }
            printJS({
                printable: contenidoImpresion,
                type: 'raw-html',
                style: `
                    body { font-family: 'Courier New', monospace; font-size: 10pt; margin: 0; padding: 5px; }
                    h2 { text-align: center; margin-bottom: 10px; }
                    p { text-align: center; margin: 15px 0; }
                    @page { margin: 0; }
                `
            });
        }

    // Función para imprimir la precuenta
    function imprimirPreCuenta() {
        const nombreUsuario = document.getElementById('nombreUsuarioPedido').textContent.trim() || '---';

        let contenidoImpresion = `
            <h2 style="text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0;">LA CALETA</h2>
            <p style="text-align: center; font-size: 12px; margin: 5px 0;">LIMA, HUAYCAN</p>
            <p style="text-align: center; font-size: 12px; margin: 5px 0;">
                Fecha: ${new Date().toLocaleDateString()} - Hora: ${new Date().toLocaleTimeString()}
            </p>
             <p style="text-align: center; font-size: 12px; margin: 5px 0;">
                <strong>Mozo :</strong> ${nombreUsuario}
            </p>
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            <h3 style="text-align: center; font-size: 14px; margin: 0;">TICKET TOTAL A PAGAR</h3>
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
        // Agregar cada producto
        const rows = document.querySelectorAll('#selected-items-list tbody tr');
        rows.forEach(row => {
            const cantidad = row.cells[0].innerText;
            const producto = row.cells[1].innerText;
            const precioUnitario = row.cells[2].innerText.replace("S/", "");
            const precioTotal = row.cells[3].innerText.replace("S/", "");
            contenidoImpresion += `
                <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd; font-size: 9px;">
                    <div style="width: 45%; font-size: 9px; text-align: left; word-wrap: break-word;">${producto}</div>
                    <div style="width: 15%; font-size: 9px; text-align: center;">${cantidad}</div>
                    <div style="width: 15%; font-size: 9px; text-align: center;">S/ ${precioUnitario}</div>
                    <div style="width: 20%; font-size: 9px; text-align: right;">S/ ${precioTotal}</div>
                </div>
            `;
        });
        // Total final
        const totalPedido = document.getElementById('total-price').innerText.replace("S/", "");
        contenidoImpresion += `
            <div style="display: flex; justify-content: space-between; padding-top: 5px; font-size: 9px; margin-left: 10px;">
                <p style="font-weight: bold; font-size: 10px; margin: 0; text-align: left;"></p>
                <p style="font-weight: bold; font-size: 10px; margin: 15px; text-align: right;">S/ ${totalPedido}</p>
            </div>
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            <div style="text-align: center; font-size: 12px; margin-top: 10px;">
                <p style="font-size: 14px; font-weight: bold;">Gracias por su compra</p>
            </div>
        `;
        printJS({
            printable: contenidoImpresion,
            type: 'raw-html',
            style: `
                body { font-family: 'Arial', sans-serif; font-size: 12pt; margin: 0; padding: 0; }
                h2, h3, p { margin: 0; }
                h2 { font-size: 18px; font-weight: bold; text-align: center; }
                h3 { font-size: 14px; text-align: center; margin-bottom: 5px; }
                p { font-size: 12px; margin: 5px 0; }
                @page { margin: 0; }
            `
            });
        }

        function enviarPedido() {
        // Primero imprime el ticket del pedido
        imprimirPedido();
        // Después de 2 segundos, guarda el pedido (acción 'guardar')
        setTimeout(() => {
            submitForm('guardar');
        }, 2000);
    }

        function precuentaYFinalizar() {
            // Primero imprime la precuenta
            imprimirPreCuenta();
            // Después de 2000 ms, envía el formulario con acción 'imprimir'
            setTimeout(() => {
                submitForm('imprimir');
            }, 2000);
        }

        function submitForm(action) {
            document.getElementById('accion').value = action;
            document.getElementById('productos').value = JSON.stringify(
                Object.entries(selectedItems).map(([id, item]) => ({ id: parseInt(id), cantidad: item.quantity }))
            );
            document.forms[0].submit();
        }

        
    </script>
</body>
</html>
