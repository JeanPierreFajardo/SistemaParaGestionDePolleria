<?php
session_start();
// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['username'])) {
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

// Obtener el ID del usuario actual
$userId = $_SESSION['user_id'] ?? null;

// Simulación de autenticación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT usuarios.id, usuarios.nombre, usuarios.apellido, usuarios.rol_id, roles.nombre AS rol_nombre 
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
        $_SESSION['user_id'] = $user['id']; // Guardar ID del usuario
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['apellido'] = $user['apellido'];
        $_SESSION['username'] = $username;
        $_SESSION['rol_name'] = $user['rol_nombre']; 

        header("Location: mesas.php");
        exit();
    } else {
        echo "Credenciales incorrectas";
        exit();
    }
}
// Verificar si las variables de sesión están definidas
$nombreCompleto = isset($_SESSION['nombre'], $_SESSION['apellido']) ? $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] : 'Nombre no disponible';
$rolName = isset($_SESSION['rol_name']) ? $_SESSION['rol_name'] : 'Rol no definido';

// Consulta para obtener las mesas, pedidos y sus relaciones
$sql = "
    SELECT 
        mesas.numero, 
        mesas.estado, 
        pedidos.usuario_id 
    FROM mesas 
    LEFT JOIN pedidos ON mesas.id = pedidos.mesa_id AND pedidos.estado = 'en_proceso'
    WHERE mesas.numero IS NOT NULL 
    ORDER BY mesas.numero ASC
";
$result = $conn->query($sql);


// Consultar mesas relacionadas con el usuario actual en estado "OCUPADO"
$sqlMesasUsuario = "
    SELECT 
        mesas.numero 
    FROM mesas 
    INNER JOIN pedidos ON mesas.id = pedidos.mesa_id 
    WHERE pedidos.usuario_id = ? 
      AND pedidos.estado = 'en_proceso' 
      AND mesas.estado = 'ocupada'
";
$stmtMesasUsuario = $conn->prepare($sqlMesasUsuario);
$stmtMesasUsuario->bind_param("i", $userId);
$stmtMesasUsuario->execute();
$resultMesasUsuario = $stmtMesasUsuario->get_result();

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
            flex-direction: row; /* Asegura que los paneles estén en una fila horizontal */
            height: 100%; /* Ajusta al alto del viewport */
            width: 100%; /* Ajusta al ancho del viewport */
            gap: 20px; /* Espacio entre los paneles */
        }

        .left-panel {
            flex: 1; /* El panel izquierdo ocupa menos espacio */
            background-color: white; /* Color de fondo blanco */
            padding: 20px; /* Espaciado interno */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .right-panel {
            flex: 2; /* El panel derecho ocupa más espacio */
            background-color: white; /* Color de fondo blanco */
            padding: 20px; /* Espaciado interno */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-y: auto; /* Permite scroll si el contenido es largo */
        }


        .info-panel {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #007BFF; /* Color azul */
            color: white;
            border-radius: 5px;
            text-align: center;
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
        .table.disabled {
            background-color: #888; /* Gris para deshabilitada */
            cursor: not-allowed; /* Cambia el cursor */
            pointer-events: none; /* Deshabilitar clic */
        }
        .tables h3 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 20px;
            color: #007BFF;
        }

    </style>
</head>
<body>

<div class="container">
    <!-- Panel izquierdo -->
    <div class="left-panel">
    <div class="info-panel">
        <div id="user-info">Usuario: <?php echo htmlspecialchars($nombreCompleto); ?></div>
        <div id="role-info">Rol: <?php echo htmlspecialchars($rolName); ?></div>
        <div id="current-date">Fecha: <span id="current-date-span"></span></div>
        <div id="current-time">Hora: <span id="current-time-span"></span></div>
    </div>

    <div class="tables">
    <h3>Mesas Ocupadas</h3>
    <?php
    if ($userId) { // Asegurar que el usuario está autenticado
        $sqlMesasUsuario = "
            SELECT mesas.numero 
            FROM mesas 
            INNER JOIN pedidos ON mesas.id = pedidos.mesa_id 
            WHERE pedidos.usuario_id = ? 
              AND pedidos.estado = 'en_proceso' 
              AND mesas.estado = 'ocupada'
            GROUP BY mesas.numero
        ";
        $stmtMesasUsuario = $conn->prepare($sqlMesasUsuario);
        $stmtMesasUsuario->bind_param("i", $userId);
        $stmtMesasUsuario->execute();
        $resultMesasUsuario = $stmtMesasUsuario->get_result();
    
        if ($resultMesasUsuario->num_rows > 0) {
            while ($mesa = $resultMesasUsuario->fetch_assoc()) {
                echo "<div class='table occupied' onclick='openMenu(" . $mesa['numero'] . ")'>Mesa " . $mesa['numero'] . "</div>";
            }
        } else {
            echo "<p>No tienes mesas ocupadas.</p>";
        }
    } else {
        echo "<p>Error: Usuario no identificado. Inicie sesión nuevamente.</p>";
    }
    
    ?>
</div>


</div>


    <!-- Panel derecho -->
    <div class="right-panel">
        <div class="header-info">
            <h2 style="flex: 1; text-align: center;">POLLERIA LA CALETA</h2>
            <button class="exit-button-top" onclick="logout()">
                <img src="../IMG/exit.png" alt="Salir" />
            </button>
            <button class="exit-button-top" onclick="goToMesas1()">
                <img src="../IMG/llevar.png" alt="Para Llevar" />
            </button>
        </div>
        <div class="tables">
            <?php
            if ($result->num_rows > 0) {
                while ($mesa = $result->fetch_assoc()) {
                    $numero = $mesa['numero'];
                    $estado = $mesa['estado'];
                    $mesaUsuarioId = $mesa['usuario_id'];
                    $class = ($estado == 'ocupada') ? 'occupied' : 'available';
                    $disabled = ($estado == 'ocupada' && $mesaUsuarioId != $_SESSION['usuario_id']) ? 'disabled' : '';
                    $onclick = ($disabled === 'disabled') ? '' : "onclick='openMenu($numero)'";
                    echo "<div class='table $class $disabled' $onclick>Mesa $numero</div>";
                }
            } else {
                echo "<p>No hay mesas registradas.</p>";
            }
            ?>
        </div>
    </div>
</div>

    <script>
        function goToMesas1() {
            window.location.href = "mesas1.php";
        }

        function logout() {
            window.location.href = "logout.php";
        }

        function openMenu(mesaId) {
    window.location.href = "menu.php?mesa=" + mesaId;
}

        // Actualizar fecha y hora
        function updateDateTime() {
            const now = new Date();
            document.getElementById("current-date-span").innerText = now.toLocaleDateString();
            document.getElementById("current-time-span").innerText = now.toLocaleTimeString();
        }

        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>
</body>
</html>
