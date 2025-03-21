<?php
session_start();

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$dbname = "polleria";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
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

// Verificar si el usuario tiene rol_id = 1 o rol_id = 3
if ($rol_id != 1 && $rol_id != 3) {
    header("Location: index.php");
    exit();
}


// Inicializar variables para mensajes y errores
$message = "";
$error = "";

// Manejar las acciones (agregar, editar, borrar)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];

        if ($accion == 'agregar') {
            $numero = $_POST['numero'];
            $letra = $_POST['letra'];
            $estado = $_POST['estado'];

            // Verifica si el campo número está vacío, y lo define como NULL si es necesario
            $numero = empty($numero) ? null : $numero;

            if (!empty($numero) || !empty($letra)) {
                $sql = "INSERT INTO mesas (numero, letra, estado) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $numero, $letra, $estado);
                if ($stmt->execute()) {
                    $message = "Mesa agregada exitosamente.";
                } else {
                    $error = "Error al agregar la mesa.";
                }
            } else {
                $error = "Debe ingresar un número o una letra para identificar la mesa.";
            }
        } elseif ($accion == 'editar') {
            $id = $_POST['id'];
            $numero = $_POST['numero'];
            $letra = $_POST['letra'];
            $estado = $_POST['estado'];

            if (!empty($id) && (!empty($numero) || !empty($letra))) {
                $sql = "UPDATE mesas SET numero = ?, letra = ?, estado = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $numero, $letra, $estado, $id);
                if ($stmt->execute()) {
                    $message = "Mesa actualizada exitosamente.";
                } else {
                    $error = "Error al actualizar la mesa.";
                }
            } else {
                $error = "Todos los campos son obligatorios.";
            }
        } elseif ($accion == 'borrar') {
            $id = $_POST['id'];
            if (!empty($id)) {
                $sql = "DELETE FROM mesas WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = "Mesa eliminada exitosamente.";
                } else {
                    $error = "Error al eliminar la mesa.";
                }
            } else {
                $error = "ID de mesa inválido.";
            }
        }
    }
}

// Obtener todas las mesas
$sql = "SELECT * FROM mesas";
$mesas = $conn->query($sql);

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mesas</title>
    <!-- Enlace a la CDN de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 80%;
            max-width: 800px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        button:hover {
            background-color: #0056b3;
        }

        .mesa-list {
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        td button {
            background-color: #dc3545;
        }

        td button:hover {
            background-color: #c82333;
        }

        td .edit {
            background-color: #28a745;
        }

        td .edit:hover {
            background-color: #218838;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        form .form-group {
            width: 100%;
        }

        .form-group-inline {
            display: flex;
            justify-content: space-between;
        }

        .form-group-inline .form-group {
            width: 48%;
        }

    </style>
</head>
<body>

<div class="container">
    <!-- Botón para regresar al panel del administrador -->
    <div style="text-align: right; margin-bottom: 20px;">
    <button style="background: transparent; border: none; cursor: pointer;"
        onclick="window.location.href='<?php echo ($rol_id == 1) ? 'paneladministrador.php' : 'panelcajero.php'; ?>'">
        <img src="../IMG/atras.png" alt="Regresar" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 5px;">
        <span style="font-size: 16px; color: #007BFF; font-weight: bold;">Regresar</span>
    </button>
</div>


    <h2>Gestión de Mesas</h2>

    <?php if (!empty($message)): ?>
        <div class="message" style="display: block;"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error" style="display: block;"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Formulario para agregar o editar mesa -->
    <form method="POST" action="">
        <input type="hidden" name="accion" value="agregar" id="form-accion">
        <input type="hidden" name="id" value="" id="mesa-id">

        <div class="form-group">
            <label for="numero">Número de Mesa</label>
            <input type="text" name="numero" id="mesa-numero">
        </div>

        <div class="form-group">
            <label for="letra">Letra de Mesa</label>
            <input type="text" name="letra" id="mesa-letra" maxlength="1">
        </div>

        <div class="form-group">
            <label for="estado">Estado</label>
            <select name="estado" id="mesa-estado">
                <option value="disponible">Disponible</option>
                <option value="ocupada">Ocupada</option>
            </select>
        </div>

        <button type="submit">Guardar Mesa</button>
        <button type="button" onclick="resetForm()">Cancelar</button>
    </form>

    <!-- Lista de mesas -->
    <div class="mesa-list">
        <h3>Lista de Mesas</h3>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Número</th>
                <th>Letra</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($mesas->num_rows > 0): ?>
                <?php while ($mesa = $mesas->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $mesa['id']; ?></td>
                        <td><?php echo $mesa['numero']; ?></td>
                        <td><?php echo $mesa['letra']; ?></td>
                        <td><?php echo $mesa['estado']; ?></td>
                        <td>
                            <button class="edit" onclick="editarMesa(<?php echo $mesa['id']; ?>, '<?php echo $mesa['numero']; ?>', '<?php echo $mesa['letra']; ?>', '<?php echo $mesa['estado']; ?>')">Editar</button>
                            <form method="POST" action="" style="display: inline-block;">
                                <input type="hidden" name="accion" value="borrar">
                                <input type="hidden" name="id" value="<?php echo $mesa['id']; ?>">
                                <button type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay mesas registradas.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Función para editar mesa
    function editarMesa(id, numero, letra, estado) {
        document.getElementById('mesa-id').value = id;
        document.getElementById('mesa-numero').value = numero;
        document.getElementById('mesa-letra').value = letra;
        document.getElementById('mesa-estado').value = estado;
        document.getElementById('form-accion').value = 'editar';
    }

    // Función para restablecer el formulario
    function resetForm() {
        document.getElementById('mesa-id').value = '';
        document.getElementById('mesa-numero').value = '';
        document.getElementById('mesa-letra').value = '';
        document.getElementById('mesa-estado').value = 'disponible';
        document.getElementById('form-accion').value = 'agregar';
    }
</script>

</body>
</html>
