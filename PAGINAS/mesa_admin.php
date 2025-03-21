<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
    // Si no hay sesión activa, redirigir a la página de inicio de sesión
    header("Location: index.php");
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


// Verificar si las variables de sesión están definidas
$nombreCompleto = isset($_SESSION['nombre'], $_SESSION['apellido']) ? $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] : 'Nombre no disponible';
$rolName = isset($_SESSION['rol_name']) ? $_SESSION['rol_name'] : 'Rol no definido';

// Simulación de autenticación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario de inicio de sesión
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta para validar usuario y obtener rol
    $sql = "SELECT usuarios.nombre, usuarios.apellido, usuarios.rol_id, roles.nombre AS rol_nombre 
            FROM usuarios 
            JOIN roles ON usuarios.rol_id = roles.id 
            WHERE usuarios.username = ? AND usuarios.password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Guardar en sesión los datos del usuario
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['username'] = $username;
        $_SESSION['rol_name'] = $user['rol_nombre'];  // Guardar el nombre del rol en la sesión

        // Redirigir al panel de control
        header("Location: mesas.php");
        exit();
    } else {
        echo "Credenciales incorrectas";
        exit();
    }
}

// Consulta para obtener solo las mesas con letras
$sql = "SELECT letra AS numero, estado FROM mesas WHERE letra IS NOT NULL ORDER BY letra ASC";
$result = $conn->query($sql);

?>



<!DOCTYPE html> 
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Panel de Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            overflow: hidden; /* Evita el desbordamiento */
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            height: 100%;
        }

        .left-panel {
            flex: 1; /* Panel izquierdo */
            padding: 20px;
            background-color: white; /* Color de fondo blanco */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
            display: flex;
            flex-direction: column; /* Apilar elementos en columna */
            justify-content: flex-start; /* Alineación al inicio */
        }

        .info-panel {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #007BFF; /* Color azul */
            color: white;
            border-radius: 5px;
            text-align: center;
        }

        .right-panel {
            flex: 2; /* Panel derecho para las mesas */
            padding: 20px;
            background-color: white; /* Color de fondo blanco */
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-y: auto; /* Permite hacer scroll vertical si hay contenido */
            display: flex;
            flex-direction: column; /* Para apilar elementos */
            justify-content: flex-start; /* Alineación al inicio */
        }

        h2 {
            margin-top: 0;
            text-align: center; /* Centrar el título */
            color: black; /* Color de texto negro para el título */
        }

        .button {
            padding: 15px; /* Aumentar tamaño de botones */
            background-color: #4CAF50; /* Color verde */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px; /* Espacio entre botones */
            display: flex;
            align-items: center;
            justify-content: center; /* Centrar el texto */
            width: calc(50% - 10px); /* Dos columnas */
            font-size: 16px; /* Aumentar tamaño de fuente */
            transition: background-color 0.3s; /* Transición para el efecto hover */
        }

        .button:hover {
            background-color: #45a049; /* Color verde más oscuro al pasar el mouse */
        }

        .tables {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* Cuatro columnas */
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px; /* Espacio inferior para evitar que quede al ras */
        }

        .table {
            background-color: #66EB32; /* Color verde para las mesas */
            color: white;
            padding: 30px; /* Tamaño aumentado */
            border-radius: 5px;
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer; /* Cambia el cursor al pasar sobre la mesa */
            font-weight: bold; /* Negrita */
            font-size: 18px; /* Tamaño de texto */
        }

        .table:hover {
            transform: scale(1.05); /* Aumenta el tamaño al pasar el mouse */
        }

        .table.occupied {
            background-color: #dc3545; /* Rojo para mesas ocupadas */
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px; /* Espacio entre título y mesas */
        }

        .exit-button-top {
            background: transparent; /* Fondo transparente */
            border: none; /* Sin bordes */
            cursor: pointer; /* Cambia el cursor al pasar sobre el botón */
            padding: 5px; /* Espaciado interno para aumentar el área clickeable */
            margin-left: auto; /* Empuja el botón a la derecha */
        }

        .exit-button-top img {
            width: 24px; /* Ajusta el tamaño de la imagen */
            height: 24px; /* Asegúrate de que la imagen tenga un tamaño adecuado */
        }

        .image-container {
            margin-top: auto;
            text-align: center;
        }

        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        /* Nueva regla para cerrar el panel donde acaban los botones */
        .left-panel, .right-panel {
            padding-bottom: 0; /* Eliminar espacio en la parte inferior */
        }

        /* Asegurar que el contenedor no se expanda */
        .container {
            height: auto; /* Permitir que el contenedor ajuste su altura */
        }
        .table.available {
        background-color: #28a745; /* Verde para disponible */
        }

        .table.occupied {
        background-color: #dc3545; /* Rojo para ocupada */
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Panel izquierdo -->
    <div class="left-panel">
        <div class="info-panel">
            <!-- Mostrar el nombre completo y el rol del usuario -->
            <div id="user-info">Usuario: <?php echo htmlspecialchars($nombreCompleto); ?></div>
            <div id="role-info">Rol: <?php echo htmlspecialchars($rolName); ?></div>
            <div id="current-date">Fecha: <span id="current-date-span"></span></div>
            <div id="current-time">Hora: <span id="current-time-span"></span></div>
        </div>
    </div>    
    <div class="right-panel">
        <div class="header-info">
            <h2 style="flex: 1; text-align: center;">POLLERIA LA CALETA - PARA LLEVAR</h2>

            <!-- Botón de cerrar sesión -->
            <button class="exit-button-top" onclick="logout()">
                <img src="../IMG/exit.png" alt="Salir" />
            </button>

            <!-- Botón dinámico para regresar -->
    <button class="exit-button-top" 
        onclick="window.location.href='<?php echo ($rol_id == 1) ? 'paneladministrador.php' : (($rol_id == 3) ? 'panelcajero.php' : 'index.php'); ?>'">
        <img src="../IMG/atras.png" alt="Regresar" style="width: 24px; height: 24px; margin-right: 5px;">
    </button>
    
        </div>

        <div class="tables">
            <?php
            // Mostrar mesas de forma dinámica
            if ($result->num_rows > 0) {
                while ($mesa = $result->fetch_assoc()) {
                    $numero = $mesa['numero']; // Aquí 'numero' corresponde al valor en la columna 'letra'
                    $estado = $mesa['estado'];
                    $class = ($estado == 'ocupada') ? 'occupied' : 'available'; // Asignar clase según estado
                    echo "<div class='table $class' onclick='openMenu(\"$numero\")'>Mesa $numero</div>";
                }
            } else {
                echo "<p>No hay mesas registradas.</p>";
            }
            ?>
        </div>
    </div>
</div>

<script>
    function logout() {
        window.location.href = "logout.php"; // Cambia a la ruta correspondiente
    }

    function openMenu(mesaId) {
        // Redirigir a gestionarllevar.php con el parámetro mesa
        window.location.href = "gestionar_llevar_admin.php?mesa=" + mesaId;
    }

    // Actualizar fecha y hora
    function updateDateTime() {
        const now = new Date();
        document.getElementById("current-date-span").innerText = now.toLocaleDateString();
        document.getElementById("current-time-span").innerText = now.toLocaleTimeString();
    }

    setInterval(updateDateTime, 1000); // Actualiza cada segundo
    updateDateTime(); // Inicializa la fecha y hora
</script>


</body>
</html>
