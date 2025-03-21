<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root"; // Cambia si es necesario
$password = ""; // Cambia si es necesario
$dbname = "polleria"; // Cambia al nombre de tu base de datos

$conn = new mysqli($servername, $username, $password, $dbname);

// Comprobar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    // Si no hay sesión activa, redirigir a la página de inicio de sesión
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

// Eliminar un producto
if (isset($_GET['delete'])) {
    $id = $_GET['delete']; // Obtener el ID del producto a eliminar

    // Eliminar el producto de la base de datos
    $sql = "DELETE FROM productos WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        // Redirigir a la misma página después de eliminar
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error al eliminar el producto: " . $conn->error;
    }
}

// Agregar o actualizar un producto
$mensaje = ""; // Variable para manejar el mensaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar'])) {
    $id = $_POST['product-id']; // ID del producto a editar (si existe)
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];
    $descripcion = $_POST['descripcion'];

    if (!empty($id)) {
        // Actualizar producto existente
        $sql = "UPDATE productos SET nombre = '$nombre', precio = '$precio', categoria = '$categoria', descripcion = '$descripcion' WHERE id = $id";
        $mensaje = "Producto actualizado exitosamente";
    } else {
        // Insertar un nuevo producto
        $sql = "INSERT INTO productos (nombre, precio, categoria, descripcion) 
                VALUES ('$nombre', '$precio', '$categoria', '$descripcion')";
        $mensaje = "Nuevo producto agregado exitosamente";
    }

    if ($conn->query($sql) === TRUE) {
        // Redirigir a la misma página después de guardar
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $mensaje = "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Obtener productos para mostrar en la tabla
$sql = "SELECT * FROM productos";
$result = $conn->query($sql);
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <!-- Enlace a la CDN de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilo para fila seleccionada */
        .selected {
            background-color: #28a745 !important;
            color: white;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
        }
        .container {
            display: flex;
            gap: 10px;
            max-width: 100%;
            width: 90vw;
            height: 90vh;
            background-color: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }
        .form-section {
            width: 30%;
            background-color: #f9f9f9;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .table-section {
            width: 70%;
            overflow-y: auto;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .header h2 {
            flex: 1;
            text-align: left; /* Alinea el título a la izquierda */
            margin: 0; /* Elimina el margen */
        }
        .buttons {
            display: flex;
            gap: 10px;
            margin-left: auto; /* Alinea los botones a la derecha */
        }
        .button-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 35px;
            height: 35px;
            background-color: #007bff;
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        .button-icon:hover {
            background-color: #0056b3;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 8px; /* Reduce el espacio entre los elementos */
            max-height: 80vh; /* Limita la altura del formulario */
            overflow-y: auto; /* Habilita desplazamiento si es necesario */
        }
        label {
            font-weight: bold;
            color: #555;
            font-size: 0.9em;
        }
        input[type="text"], input[type="number"], select {
            padding: 6px; /* Reduce el padding para ahorrar espacio */
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em;
        }
        button {
            padding: 8px;
            font-size: 0.9em;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .guardar-btn {
            background-color: #28a745;
            color: white;
        }
        .guardar-btn:hover {
            background-color: #218838;
        }
        .limpiar-btn {
            background-color: #007bff;
            color: white;
        }
        .limpiar-btn:hover {
            background-color: #0069d9;
        }
        .table-section table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85em;
        }
        th, td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 0.85em;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        /* Estilo para fila seleccionada */
        .selected-row td {
        color: #28a745; /* Cambia solo el color del texto a verde */
}

    </style>
</head>
<body>

<div class="container">
    <div class="form-section">
        <div class="header">
            <h2>Gestión de Productos</h2>
            <div class="buttons">
                <div class="button-icon" title="Regresar" onclick="window.location.href='paneladministrador.php'">
                    <i class="fas fa-arrow-left"></i>
                </div>
                <div class="button-icon" title="Salir" onclick="window.location.href='index.php'">
                    <i class="fas fa-right-from-bracket"></i>
                </div>
            </div>
        </div>

        <!-- Mensaje de éxito o error -->
        <div class="mensaje" id="mensaje">
            <?php echo $mensaje; ?>
        </div>

        <form method="POST">
    <input type="hidden" id="product-id" name="product-id"> <!-- Campo oculto para el ID del producto -->
    <label for="nombre">Nombre del Producto:</label>
    <input type="text" id="nombre" name="nombre" required>

    <label for="precio">Precio:</label>
    <input type="number" id="precio" name="precio" step="0.01" required>

    <label for="categoria">Categoría:</label>
    <select id="categoria" name="categoria" required>
        <option value="COMBOS LOCOS">COMBOS LOCOS</option>
        <option value="PARRILLAS">PARRILLAS</option>
        <option value="OTROS GUSTITOS">OTROS GUSTITOS</option>
        <option value="COMBO CALENTON FAMILIAR">COMBO CALENTON FAMILIAR</option>
        <option value="MEGA COMBO FAMILIAR">MEGA COMBO FAMILIAR</option>
    </select>

    <label for="descripcion">Ingredientes (Descripción):</label>
    <textarea id="descripcion" name="descripcion" rows="4" placeholder="Ejemplo: Papas, ensalada, cremas, arroz, etc." required></textarea>

    <button type="submit" name="guardar" class="guardar-btn">Guardar</button>
    <button type="reset" class="limpiar-btn">Limpiar</button>
</form>

    </div>

    <div class="table-section">
        <h3>Lista de Productos</h3>
        <table>
            <tr>
                <th>Seleccionar</th>
                <th>Nombre del Producto</th>
                <th>Precio</th>
                <th>Categoría</th>
                <th>Descripción (Ingredientes)</th>
                <th>Acciones</th>
            </tr>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr class='product-row' data-id='" . $row['id'] . "'>
                        <td class='checkbox-cell'>
                            <input type='checkbox' class='edit-checkbox' data-id='" . $row['id'] . "'>
                        </td>
                        <td>" . $row["nombre"] . "</td>
                        <td>" . $row["precio"] . "</td>
                        <td>" . $row["categoria"] . "</td>
                        <td>" . $row["descripcion"] . "</td>
                        <td><a href='?delete=" . $row['id'] . "' class='delete-btn'>Eliminar</a></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6'>No hay productos disponibles</td></tr>";
            }
            ?>
        </table>
    </div>
</div>
<script>
    // Función para marcar y cambiar el estilo de la fila cuando se selecciona el checkbox
    // Función para marcar solo una fila a la vez
const checkboxes = document.querySelectorAll('.edit-checkbox');
checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const row = this.closest('tr');
        const productId = this.getAttribute('data-id'); // ID del producto
        const nombre = row.cells[1].innerText;
        const precio = row.cells[2].innerText;
        const categoria = row.cells[3].innerText;
        const descripcion = row.cells[4].innerText;

        if (this.checked) {
            // Limpiar las selecciones anteriores
            document.querySelectorAll('.edit-checkbox').forEach(otherCheckbox => {
                if (otherCheckbox !== this) {
                    otherCheckbox.checked = false; // Desmarcar otras casillas
                    const otherRow = otherCheckbox.closest('tr');
                    otherRow.classList.remove('selected-row'); // Eliminar el estilo de fila seleccionada
                }
            });

            // Rellenar el formulario con los datos del producto
            document.getElementById('nombre').value = nombre;
            document.getElementById('precio').value = precio;
            document.getElementById('categoria').value = categoria;
            document.getElementById('descripcion').value = descripcion;

            // Guardar el ID del producto para actualizarlo
            document.getElementById('product-id').value = productId;

            row.classList.add('selected-row'); // Aplica la clase para cambiar el color del texto
        } else {
            // Limpiar el formulario si se desmarca
            document.getElementById('nombre').value = '';
            document.getElementById('precio').value = '';
            document.getElementById('categoria').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('product-id').value = ''; // Limpiar el ID del producto

            row.classList.remove('selected-row'); // Elimina la clase para restaurar el color
        }
    });
});

</script>

</body>
</html>

