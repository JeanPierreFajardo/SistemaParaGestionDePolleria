<?php
if (!isset($_GET["id"])) {
    exit("No hay id");
}
$id = $_GET["id"];
$datos = [
    "id" => $id,
    "productos" => [
        [
            "nombre" => "Alcohol isopropílico",
            "precio" => 20,
        ],
        [
            "nombre" => "Mouse razer",
            "precio" => 500,
        ],
    ],
    "fecha" => date("Y-m-d"),
];
echo json_encode($datos);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir ticket</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/print-js/1.6.0/print.min.js"></script>
</head>

<body>
    <a href="#" onclick="imprimirTicket()">Imprimir</a>

    <div id="printArea" style="display: none;">
        <!-- Aquí se generará el contenido del ticket a imprimir -->
        <h2>Ticket de venta</h2>
        <p id="fecha"></p>
        <table id="productos">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                </tr>
            </thead>
            <tbody>
                <!-- Productos se agregarán dinámicamente aquí -->
            </tbody>
        </table>
    </div>

    <script>
        // Obtenemos el parámetro 'id' de la URL
        const urlSearchParams = new URLSearchParams(window.location.search);
        const id = urlSearchParams.get("id");

        // Realizamos la petición para obtener los datos del ticket
        fetch("<?php echo $_SERVER['PHP_SELF']; ?>?id=" + id)
            .then(response => response.json())
            .then(detallesTicket => {
                // Rellenamos la información del ticket
                document.getElementById('fecha').innerText = 'Fecha: ' + detallesTicket.fecha;

                const productosTable = document.getElementById('productos').getElementsByTagName('tbody')[0];

                detallesTicket.productos.forEach(producto => {
                    const row = productosTable.insertRow();
                    const cellNombre = row.insertCell(0);
                    const cellPrecio = row.insertCell(1);
                    cellNombre.textContent = producto.nombre;
                    cellPrecio.textContent = 'S/ ' + producto.precio;
                });
            })
            .catch(error => console.error('Error al cargar el ticket:', error));

        // Función de impresión
        function imprimirTicket() {
            const contenidoImpresion = document.getElementById('printArea').innerHTML;

            // Usamos printJS para imprimir el contenido
            printJS({
                printable: contenidoImpresion,
                type: 'raw-html',
                style: `
                    body {
                        font-family: monospace;
                        font-size: 10pt;
                        margin: 0;
                        padding: 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 0;
                        padding: 0;
                    }
                    th, td {
                        border: 1px solid #000;
                        padding: 5px;
                        text-align: left;
                    }
                    h2, p {
                        text-align: center;
                        margin: 0;
                    }
                    #productos {
                        width: 100%;
                    }
                    #productos td, #productos th {
                        padding: 5px;
                        text-align: left;
                    }
                    @page {
                        size: auto;
                        margin: 0;
                    }
                    .no-padding {
                        padding: 0;
                    }
                    /* Ajuste adicional para el contenido */
                    #productos {
                        page-break-inside: avoid;
                    }
                `
            });
        }
    </script>
</body>

</html>
