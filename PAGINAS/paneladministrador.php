<?php
session_start();

// Conexión a la base de datos
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') ?: "";
$dbname = getenv('DB_NAME') ?: "polleria";

// Crear conexión
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar sesión activa
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
} else {
    session_regenerate_id(true);
}

// Variables de sesión
$nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Nombre no disponible', ENT_QUOTES, 'UTF-8');
$apellido = htmlspecialchars($_SESSION['apellido'] ?? 'Apellido no disponible', ENT_QUOTES, 'UTF-8');
$nombreCompleto = $nombre . ' ' . $apellido;
$username = htmlspecialchars($_SESSION['username'] ?? 'Invitado', ENT_QUOTES, 'UTF-8');

// Obtener rol del usuario
$sql = "SELECT rol_id FROM usuarios WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$rol_id = ($result->num_rows > 0) ? $result->fetch_assoc()['rol_id'] : 0;

// Verificar si el usuario tiene rol_id = 1
if ($rol_id != 1) {
    header("Location: index.php");
    exit();
}

// Asignar nombre del rol
$roles = [1 => 'Administrador', 2 => 'Mozo', 3 => 'Cajero'];
$rol_name = $roles[$rol_id] ?? 'Invitado';

// Consultar mesas que tienen número (excluyendo las que tienen solo letra)
$mesas_sql = "SELECT numero, estado FROM mesas WHERE numero IS NOT NULL ORDER BY numero ASC";
$mesas_result = $conn->query($mesas_sql);
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
            overflow: hidden;
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            height: 100%;
        }

        .left-panel {
            flex: 1;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .info-panel {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #007BFF;
            color: white;
            border-radius: 5px;
            text-align: center;
        }

        .right-panel {
            flex: 2;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: black;
        }

        .button {
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(50% - 10px);
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #45a049;
        }

        .tables {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .table {
            padding: 30px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            color: white;
        }

        .table.occupied {
            background-color: #dc3545; /* Rojo para ocupada */
        }

        .table.available {
            background-color: #28a745; /* Verde para disponible */
        }

        .header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .exit-button-top {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px;
            margin-left: auto;
        }

        .exit-button-top img {
            width: 24px;
            height: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Panel izquierdo -->
        <div class="left-panel">
            <div class="info-panel">
                <!-- Muestra el nombre del usuario -->
                <div id="user-info">Usuario: <?php echo htmlspecialchars($nombreCompleto); ?></div>
                <!-- Muestra el rol del usuario -->
                <div id="role-info">Rol: <?php echo htmlspecialchars($rol_name); ?></div>
                <div id="current-date">Fecha: <span id="current-date-span"></span></div>
                <div id="current-time">Hora: <span id="current-time-span"></span></div>
            </div>

            <!-- Botones adicionales para gestionar -->
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between;">
                <button class="button" onclick="gestionUsuarios()">Gestionar Usuarios</button>
                <button class="button" onclick="gestionMesas()">Gestionar Mesas</button>
                <button class="button" onclick="gestionCuentas()">Gestionar Pedidos por separado</button>
                <button class="button" onclick="gestionarproductos()">Gestionar Productos</button>     
                <button class="button" onclick="gestionarllevar()">Gestionar Pedidos para Llevar</button>
                <button class="button" onclick="gestionarreportes()">Gestionar Reportes</button>
                <button class="button" onclick="gestionAnulados()">Pedidos/productos anulados</button>
               
            </div>
        </div>

        <!-- Panel derecho para el contenido -->
        <div class="right-panel">
            <div class="header-info">
                <h2 style="flex: 1; text-align: center;">POLLERIA LA CALETA</h2>
                <button class="exit-button-top" onclick="window.location.href='logout.php'">
                    <img src="../IMG/exit.png" alt="Salir" />
                </button>
            </div>
            <div class="tables">
                <?php
                // Mostrar mesas de forma dinámica
                if ($mesas_result->num_rows > 0) {
                    while ($mesa = $mesas_result->fetch_assoc()) {
                        $numero = $mesa['numero'];
                        $estado = $mesa['estado'];
                        $class = ($estado == 'ocupada') ? 'occupied' : 'available';
                        echo "<div class='table $class' onclick='openMenu($numero)'>Mesa $numero</div>";
                    }
                } else {
                    echo "<p>No hay mesas registradas.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Actualiza la fecha y la hora cada segundo
        function updateTime() {
            var now = new Date();
            var date = now.toLocaleDateString();
            var time = now.toLocaleTimeString();
            document.getElementById('current-date-span').textContent = date;
            document.getElementById('current-time-span').textContent = time;
        }

        setInterval(updateTime, 1000); // Llama a updateTime cada segundo

        function logout() {
            window.location.href = 'index.php'; // Redirige a la página de inicio de sesión
        }

        function gestionUsuarios() {
            window.location.href = 'gestionarusuarios.php'; // Redirige a la página de gestionar usuarios
        }

        function gestionMesas() {
            window.location.href = 'gestionarmesas.php'; // Redirige a la página de gestionar usuarios
        }
        function gestionarllevar() {
            window.location.href = 'mesa_admin.php'; // Redirige a mesas1.php
        }
        function gestionCuentas() {
            window.location.href = 'pedidos_separados.php'; // Redirige a la página de gestionar llevar 
            
        }
        function gestionarreportes() {
            window.location.href = 'gestionarreportes.php'; // Redirige a la página de gestionar llevar 
            
        }
        function gestionAnulados() {
            window.location.href = 'gestionaranulados.php'; // Redirige a la página de gestionar productos/pedidos anulados 
            
        }
        
        function gestionarproductos() {
            window.location.href = 'gestionarproductos.php'; // Redirige a la página de gestionar productos 
            
        }

        function openMenu(mesaId) {
            window.location.href = 'menu_admin.php?mesa=' + mesaId;
        }

        
    </script>
</body>
</html>