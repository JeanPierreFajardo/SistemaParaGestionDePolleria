<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "polleria";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$sqlRol = "SELECT rol_id FROM usuarios WHERE username = ?";
$stmtRol = $conn->prepare($sqlRol);
$stmtRol->bind_param("s", $_SESSION['username']);
$stmtRol->execute();
$resultRol = $stmtRol->get_result();
$rol_id = ($resultRol->num_rows > 0) ? $resultRol->fetch_assoc()['rol_id'] : 0;
if ($rol_id != 1 && $rol_id != 3) {
    header("Location: index.php");
    exit();
}

// Consultamos los pedidos anulados y sus productos relacionados
$sql = "SELECT pa.id, pa.pedido_id, pa.producto_id, p.nombre AS producto_nombre, 
               pa.cantidad_anulada, pa.motivo, pa.fecha_anulacion, 
               u.nombre AS usuario
        FROM pedidos_anulados pa
        LEFT JOIN usuarios u ON pa.usuario_id = u.id
        LEFT JOIN productos p ON pa.producto_id = p.id
        ORDER BY pa.pedido_id, pa.fecha_anulacion DESC";

$resultPedidos = $conn->query($sql);

// Agrupar productos por pedido_id
$pedidosAgrupados = [];
while ($row = $resultPedidos->fetch_assoc()) {
    $pedido_id = $row['pedido_id'];
    if (!isset($pedidosAgrupados[$pedido_id])) {
        $pedidosAgrupados[$pedido_id] = [
            "fecha_anulacion" => $row['fecha_anulacion'],
            "motivo" => $row['motivo'],
            "usuario" => $row['usuario'],
            "productos" => []
        ];
    }
    if (!empty($row['producto_nombre'])) {
        $pedidosAgrupados[$pedido_id]["productos"][] = [
            "nombre" => $row['producto_nombre'],
            "cantidad" => $row['cantidad_anulada']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos y Productos Anulados</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>
</head>
<body>
<div class="container">
        <?php if ($rol_id == 1 || $rol_id == 3): ?>
            <button onclick="window.location.href='<?php echo ($rol_id == 1) ? 'paneladministrador.php' : 'panelcajero.php'; ?>'" 
                    class="btn btn-secondary my-3">
                ⬅ Volver al Panel
            </button>
        <?php endif; ?>
        <h1 class="my-4 text-center">Pedidos y Productos Anulados</h1>
        <div class="row">
            <?php if (!empty($pedidosAgrupados)): ?>
                <?php foreach ($pedidosAgrupados as $pedido_id => $pedido): ?>
                    <div class="col-md-4 mb-4 pedido-card" id="pedido-<?php echo $pedido_id; ?>" data-pedido-id="<?php echo $pedido_id; ?>">
                        <div class="card">
                            <div class="card-header">
                                <strong>Pedido ID:</strong> <?php echo htmlspecialchars($pedido_id); ?>
                            </div>
                            <div class="card-body">
                                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($pedido['fecha_anulacion']); ?></p>
                                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($pedido['usuario']); ?></p>
                                <p><strong>Motivo:</strong> <?php echo htmlspecialchars($pedido['motivo']); ?></p>
                                <hr>
                                <h5>Productos anulados:</h5>
                                <ul>
                                    <?php foreach ($pedido['productos'] as $producto): ?>
                                        <li><?php echo htmlspecialchars($producto['nombre']); ?> - Cantidad: <?php echo htmlspecialchars($producto['cantidad']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button class="btn btn-primary" onclick="imprimirPedido(<?php echo $pedido_id; ?>)">Imprimir</button>
                                <button class="btn btn-danger" onclick="eliminarPedido(<?php echo $pedido_id; ?>)">Finalizar</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay pedidos anulados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Función para imprimir un pedido en formato específico
        function imprimirPedido(id) {
            let pedidoElement = document.getElementById('pedido-' + id);
            let usuario = pedidoElement.querySelector("p:nth-child(2)").textContent.replace("Usuario: ", "").trim();
            let motivo = pedidoElement.querySelector("p:nth-child(3)").textContent.replace("Motivo: ", "").trim();
            let productosLista = pedidoElement.querySelectorAll("ul li");

            let contenido = `
                <div style="font-family: Arial, sans-serif; font-size: 14px; text-align: left; padding: 10px; width: 250px; margin: auto;">
                    <h2 style="text-align: center; font-size: 18px; border-bottom: 2px solid black; padding-bottom: 5px;">PRODUCTOS ANULADOS</h2>
                    <p><strong>Usuario:</strong> ${usuario}</p>
                    <p><strong>Motivo:</strong> ${motivo}</p>
                    <hr>
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding-bottom: 5px;">Producto</th>
                                <th style="text-align: right; padding-bottom: 5px;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Array.from(productosLista).map(prod => {
                                let partes = prod.textContent.split(" - Cantidad: ");
                                let nombre = partes[0].trim();
                                let cantidad = partes[1] ? partes[1].trim() : "1";
                                return `<tr>
                                            <td style="text-align: left; padding: 5px 0;">${nombre}</td>
                                            <td style="text-align: right; font-weight: bold; padding: 5px 0;">${cantidad}</td>
                                        </tr>`;
                            }).join("")}
                        </tbody>
                    </table>
                    <hr>
                    <p style="text-align: center; font-size: 12px; margin-top: 10px;">*** FIN DEL REPORTE ***</p>
                </div>
            `;

            let iframe = document.createElement("iframe");
            iframe.style.position = "absolute";
            iframe.style.width = "0px";
            iframe.style.height = "0px";
            iframe.style.border = "none";
            document.body.appendChild(iframe);

            let doc = iframe.contentWindow.document;
            doc.open();
            doc.write('<html><head><title>Impresión</title></head><body>');
            doc.write(contenido);
            doc.write('</body></html>');
            doc.close();

            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => document.body.removeChild(iframe), 1000);
        }


        // Función para eliminar el pedido de la interfaz
        function eliminarPedido(id) {
            let pedidoElement = document.getElementById('pedido-' + id);
            if (pedidoElement) {
                pedidoElement.remove();
                guardarPedidoEliminado(id);
            }
        }

        // Guardar pedidos eliminados en localStorage
        function guardarPedidoEliminado(id) {
            let pedidosEliminados = JSON.parse(localStorage.getItem('pedidosEliminados')) || [];
            if (!pedidosEliminados.includes(id)) {
                pedidosEliminados.push(id);
                localStorage.setItem('pedidosEliminados', JSON.stringify(pedidosEliminados));
            }
        }

        // Ocultar pedidos eliminados al cargar la página
        function ocultarPedidosEliminados() {
            let pedidosEliminados = JSON.parse(localStorage.getItem('pedidosEliminados')) || [];
            pedidosEliminados.forEach(id => {
                let pedidoElement = document.getElementById('pedido-' + id);
                if (pedidoElement) {
                    pedidoElement.remove();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", ocultarPedidosEliminados);
    </script>
</body>
</html>
