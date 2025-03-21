<?php
session_start();
// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "polleria";
$conn = new mysqli($servername, $username, $password, $dbname);
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

// Verificar si el usuario tiene rol_id = 1 o rol_id = 3
if ($rol_id != 1 && $rol_id != 3) {
    header("Location: index.php");
    exit();
}
// Lógica para finalizar el pedido
if (isset($_POST['finalizar_pedido'])) {
    $pedidoId = (int)$_POST['pedido_id'];

    // Cambiar el estado del pedido a 'finalizado'
    $sqlFinalizarPedido = "UPDATE pedidos SET estado = 'finalizado' WHERE id = ?";
    $stmtFinalizarPedido = $conn->prepare($sqlFinalizarPedido);
    $stmtFinalizarPedido->bind_param("i", $pedidoId);
    $stmtFinalizarPedido->execute();

    // Obtener el ID de la mesa asociada al pedido
    $sqlMesaId = "SELECT mesa_id FROM pedidos WHERE id = ?";
    $stmtMesaId = $conn->prepare($sqlMesaId);
    $stmtMesaId->bind_param("i", $pedidoId);
    $stmtMesaId->execute();
    $resultMesaId = $stmtMesaId->get_result();
    if ($resultMesaId->num_rows > 0) {
        $mesaId = $resultMesaId->fetch_assoc()['mesa_id'];

        // Cambiar el estado de la mesa a 'disponible'
        $sqlLiberarMesa = "UPDATE mesas SET estado = 'disponible' WHERE id = ?";
        $stmtLiberarMesa = $conn->prepare($sqlLiberarMesa);
        $stmtLiberarMesa->bind_param("i", $mesaId);
        $stmtLiberarMesa->execute();
    }
    $redireccion = ($rol_id == 1) ? "paneladministrador.php" : "panelcajero.php";
    header("Location: $redireccion");
    exit();
}
// Consultar pedidos en proceso
$sqlPedidos = "
    SELECT 
        p.id AS pedido_id,
        p.nombre_cliente,
        m.numero AS mesa_numero,
        m.letra AS mesa_letra
    FROM pedidos p
    JOIN mesas m ON p.mesa_id = m.id
    WHERE p.estado = 'en_proceso'
    ORDER BY m.numero, m.letra
";
$resultPedidos = $conn->query($sqlPedidos);
// Consultar productos de un pedido (si se solicita)
$productos = [];
if (isset($_GET['pedido_id'])) {
    $pedidoId = (int)$_GET['pedido_id'];
    $sqlProductos = "
        SELECT 
            dp.id AS detalle_id,
            pr.nombre AS producto_nombre,
            dp.cantidad,
            pr.precio
        FROM detalles_pedidos dp
        JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ";
    $stmtProductos = $conn->prepare($sqlProductos);
    $stmtProductos->bind_param("i", $pedidoId);
    $stmtProductos->execute();
    $resultProductos = $stmtProductos->get_result();

    if ($resultProductos->num_rows > 0) {
        while ($row = $resultProductos->fetch_assoc()) {
            $productos[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos Separados</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .mesa-highlight {
            font-size: 1.5em;
            font-weight: bold;
            color: #007bff;
            text-align: center;
        }
        .productos-container, .cuentas-container {
            margin-top: 20px;
        }
        .cuentas-container {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }
        .cuenta {
            margin-top: 10px;
        }
    </style>    
<script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- Botón para regresar al panel administrativo -->
<div class="mt-3">
    <a href="<?php echo ($rol_id == 1) ? 'paneladministrador.php' : 'panelcajero.php'; ?>" class="btn btn-secondary">
        Regresar al Panel
    </a>
</div>
        <h1 class="my-4 text-center">Pedidos en Proceso</h1>
        <!-- Lista de pedidos en proceso -->
        <div class="row">
            <?php if ($resultPedidos->num_rows > 0): ?>
                <?php while ($pedido = $resultPedidos->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header mesa-highlight">
                                Mesa <?php echo htmlspecialchars($pedido['mesa_numero']) . " - " . htmlspecialchars($pedido['mesa_letra']); ?>
                            </div>
                            <div class="card-body">
                                <p><strong>Pedido ID:</strong> <?php echo htmlspecialchars($pedido['pedido_id']); ?></p>
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['nombre_cliente']); ?></p>
                                <a href="?pedido_id=<?php echo $pedido['pedido_id']; ?>" class="btn btn-primary btn-block">Seleccionar Pedido</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay pedidos en proceso.</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- Productos del pedido seleccionado -->
        <?php if (!empty($productos)): ?>
            <div class="productos-container">
                <h2 class="text-center">Productos del Pedido</h2>
                <div id="productos-list">
                <?php foreach ($productos as $producto): ?>
    <div class="cuentas-container" id="producto-<?php echo $producto['detalle_id']; ?>">
        <p><strong>Producto:</strong> <?php echo htmlspecialchars($producto['producto_nombre']); ?></p>
        <p><strong>Cantidad restante:</strong> <span class="cantidad-restante"><?php echo $producto['cantidad']; ?></span></p>
        <p><strong>Precio:</strong> S/ <?php echo number_format($producto['precio'], 2); ?></p>
        <button 
            class="btn btn-success"
            onclick="seleccionarProducto(
                <?php echo $producto['detalle_id']; ?>,
                '<?php echo htmlspecialchars($producto['producto_nombre']); ?>',
                <?php echo $producto['precio']; ?>,
                <?php echo $producto['cantidad']; ?>
            )">
            Seleccionar
        </button>
                </div>
            <?php endforeach; ?>
                </div>
            </div>
            <!-- Cuenta actual -->
            <div class="productos-container">
                <h2 class="text-center">Cuenta Actual</h2>
                <table class="table" id="selected-items-list">
                    <thead>
                        <tr>
                            <th>Cantidad</th>
                            <th>Producto</th>
                            <th>Precio Unitario</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí se agregarán dinámicamente los productos -->
                    </tbody>
                </table>
                <p id="total-price" class="mt-3"><strong>Total: S/ 0.00</strong></p>
                <button class="btn btn-primary mt-2" onclick="imprimirCuenta()">Imprimir Cuenta</button>
                <button class="btn btn-danger mt-2" onclick="finalizarPedido()">Finalizar Pedido</button>
            </div>

        <?php endif; ?>
    </div>
    <script>
    let productosSeleccionados = [];
    let cuentas = [];
    let cuentaActual = [];

    function seleccionarProducto(id, nombre, precio, cantidad) {
    let productoElemento = document.querySelector(`#producto-${id} .cantidad-restante`);
    let cantidadDisponible = parseInt(productoElemento.innerText); // Obtener cantidad actualizada desde el HTML
    
    if (cantidadDisponible === 1) {
        agregarProductoATabla(id, nombre, precio, 1);
        actualizarProductos(id, 0);
    } else if (cantidadDisponible > 1) {
        let cantidadSeleccionada = parseInt(prompt(`¿Cuántas unidades de "${nombre}" deseas agregar? (Máximo: ${cantidadDisponible})`));
        if (isNaN(cantidadSeleccionada) || cantidadSeleccionada < 1 || cantidadSeleccionada > cantidadDisponible) {
            alert("Cantidad no válida.");
            return;
        }
        agregarProductoATabla(id, nombre, precio, cantidadSeleccionada);
        actualizarProductos(id, cantidadDisponible - cantidadSeleccionada);
    }
}

function agregarProductoATabla(id, nombre, precio, cantidad) {
    const tabla = document.getElementById('selected-items-list').getElementsByTagName('tbody')[0];
    const nuevaFila = tabla.insertRow();

    nuevaFila.innerHTML = `
        <td>${cantidad}</td>
        <td>${nombre}</td>
        <td>S/ ${precio.toFixed(2)}</td>
        <td>S/ ${(cantidad * precio).toFixed(2)}</td>
    `;

    actualizarTotal();
}
function actualizarTotal() {
    let total = 0;
    document.querySelectorAll("#selected-items-list tbody tr").forEach(row => {
        total += parseFloat(row.cells[3].innerText.replace("S/", ""));
    });
    document.getElementById('total-price').innerHTML = `<strong>Total: S/ ${total.toFixed(2)}</strong>`;
}
    function actualizarCuentaActual() {
        const listaCuenta = document.getElementById('cuenta-actual');
        listaCuenta.innerHTML = "";
        let total = 0;
        cuentaActual.forEach(producto => {
            total += producto.cantidad * producto.precio;
            const item = document.createElement('li');
            item.innerText = `${producto.nombre} - ${producto.cantidad} x S/${producto.precio.toFixed(2)} = S/${(producto.cantidad * producto.precio).toFixed(2)}`;
            listaCuenta.appendChild(item);
        });
        document.getElementById('total-cuenta-actual').innerText = `Total: S/ ${total.toFixed(2)}`;
    }
    function actualizarProductos(id, nuevaCantidad) {
    const productoElemento = document.querySelector(`#producto-${id}`);
    const cantidadElemento = productoElemento.querySelector('.cantidad-restante');

    if (nuevaCantidad <= 0) {
        productoElemento.style.display = "none"; // Ocultar el producto si ya no hay stock
    } else {
        cantidadElemento.innerText = nuevaCantidad; // Actualizar la cantidad restante en el HTML
    }
}

function imprimirCuenta() {
    let productosTable = document.getElementById('selected-items-list').getElementsByTagName('tbody')[0];
    let totalElement = document.getElementById('total-price');
    // Verificar si hay productos en la cuenta actual
    if (!productosTable || productosTable.rows.length === 0) {
        alert("No hay productos en la cuenta actual.");
        return;
    }
    // Clonamos los datos antes de limpiar
    let contenidoClonado = productosTable.cloneNode(true);
    let totalPedido = totalElement.innerText;
    let contenidoImpresion = `
        <h2 style='text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0;'>LA CALETA</h2>
        <p style='text-align: center; font-size: 12px; margin: 5px 0;'>LIMA, HUAYCAN</p>
        <p style='text-align: center; font-size: 12px; margin: 5px 0;'>Fecha: ${new Date().toLocaleDateString()} - Hora: ${new Date().toLocaleTimeString()}</p>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
        <h3 style='text-align: center; font-size: 14px; margin: 0;'>TICKET TOTAL A PAGAR</h3>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
        <div style="width: 85%; margin: 12px auto 0 auto; text-align: left; font-size: 9px;">
            <div style="display: flex; border-bottom: 1px solid #000; padding: 8px 0; font-weight: bold;">
                <span style="width: 35%; padding-left: 5px;">Item</span>
                <span style="width: 20%; text-align: center;">Cantidad</span>
                <span style="width: 20%; text-align: center;">Precio</span>
                <span style="width: 25%; text-align: right; padding-right: 5px;">Total</span>
            </div>
    `;
    // Agregar productos desde el contenido clonado
    for (let row of contenidoClonado.rows) {
        let cantidad = row.cells[0].innerText;
        let producto = row.cells[1].innerText;
        let precioUnitario = row.cells[2].innerText.replace("S/", "");
        let precioTotal = row.cells[3].innerText.replace("S/", "");

        contenidoImpresion += `
            <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #ddd; font-size: 9px;">
                <div style="width: 45%; text-align: left;">${producto}</div>
                <div style="width: 15%; text-align: center;">${cantidad}</div>
                <div style="width: 15%; text-align: center;">S/ ${precioUnitario}</div>
                <div style="width: 20%; text-align: right;">S/ ${precioTotal}</div>
            </div>
        `;
    }
    // Total
    contenidoImpresion += `
        <div style="display: flex; justify-content: space-between; padding-top: 5px; font-size: 9px;">
            <p style="font-weight: bold; font-size: 10px; margin: 0; text-align: left;"></p>
            <p style="font-weight: bold; font-size: 10px; margin: 15px; text-align: right;">${totalPedido}</p>
        </div>
        <hr style="border: 1px dashed #000; margin: 10px 0;">
    `;
    contenidoImpresion += `
        <div style="text-align: center; font-size: 12px; margin-top: 10px;">
            <p style="font-size: 14px; font-weight: bold;">Gracias por su compra</p>
        </div>
    `;
    // Imprimir antes de limpiar
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

    // Esperar un pequeño tiempo antes de limpiar (para que la impresión ocurra correctamente)
    setTimeout(() => {
        productosTable.innerHTML = ""; // BORRAR LOS PRODUCTOS
        totalElement.innerText = "S/ 0.00"; // RESET TOTAL
        alert("Cuenta impresa.");
    }, 500); // Pequeño retraso de 500ms
}
    function finalizarPedido() {
    if (confirm("¿Deseas finalizar el pedido y liberar la mesa?")) {
        // Obtener el ID del pedido actual
        const pedidoId = <?php echo isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 'null'; ?>;
        if (pedidoId) {
            // Crear un formulario para enviar los datos
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = ''; // Enviar al mismo archivo PHP

            const inputPedidoId = document.createElement('input');
            inputPedidoId.type = 'hidden';
            inputPedidoId.name = 'pedido_id';
            inputPedidoId.value = pedidoId;
            form.appendChild(inputPedidoId);

            const inputFinalizar = document.createElement('input');
            inputFinalizar.type = 'hidden';
            inputFinalizar.name = 'finalizar_pedido';
            inputFinalizar.value = '1';
            form.appendChild(inputFinalizar);

            document.body.appendChild(form);
            form.submit();
        } else {
            alert("No se puede finalizar el pedido. Pedido no encontrado.");
        }
    }
}
</script>
</body>
</html>
