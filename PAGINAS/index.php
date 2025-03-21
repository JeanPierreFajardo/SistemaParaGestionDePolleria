<?php
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

// Inicializar mensaje de alerta
$alertMessage = "";

// Comprobar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consultar la base de datos para verificar el usuario y obtener su rol
    $sql = "SELECT u.id, u.nombre, u.apellido, u.username, u.password, u.estado, r.nombre AS rol_nombre, u.rol_id 
            FROM usuarios u
            JOIN roles r ON u.rol_id = r.id
            WHERE u.username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Obtener los datos del usuario
        $user = $result->fetch_assoc();

        // Verificar el estado del usuario (Activo o Inactivo)
        if ($user['estado'] === 'Inactivo') {
            $alertMessage = "Tu cuenta está inactiva. Contacta al administrador.";
        } else {
            // Comparar directamente la contraseña ingresada con la almacenada
            if ($password === $user['password']) {
                // Iniciar la sesión y almacenar los datos del usuario
                session_start();
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['apellido'] = $user['apellido'];
                $_SESSION['rol_id'] = $user['rol_id'];
                $_SESSION['rol_name'] = $user['rol_nombre'];  // Guardar el nombre del rol
                $_SESSION['usuario_id'] = $user['id'];  // Almacenar el ID del usuario

                // Redirigir según el rol del usuario
                $rol_id = $user['rol_id'];

                if ($rol_id == 1) {
                    header("Location: paneladministrador.php");
                    exit();
                } elseif ($rol_id == 2) {
                    header("Location: mesas.php"); // Rol 2 (Mozo) va a mesas.php
                    exit();
                } elseif ($rol_id == 3) {
                    header("Location: panelcajero.php"); // Rol 3 (Cajero) va a panelcajero.php
                    exit();
                } else {
                    $alertMessage = "Rol de usuario no reconocido.";
                }
                
            } else {
                $alertMessage = "Contraseña incorrecta.";
            }
        }
    } else {
        $alertMessage = "Usuario no encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login en Pollería</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            background-color: #f4f4f4;
        }

        .left-side {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('../IMG/pollo.jpg');
            background-size: cover;
            background-position: center;
        }

        .right-side {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f4f4f4;
        }

        .container {
            background-color: rgb(252, 252, 252);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 400px;
            border: 2px solid white;
            position: relative;
        }

        .title-card {
            background-color: #f7d929;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 22px;
            font-weight: bold;
            color: white;
        }

        .input-field {
            width: 100%;
            margin-bottom: 15px;
            position: relative;
        }

        .input-field input {
            width: 100%;
            padding: 15px 30px 15px 10px;
            font-size: 18px;
            border: 2px solid #ccc;
            border-radius: 5px;
            outline: none;
            box-sizing: border-box;
        }

        label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }

        .input-field .toggle-password {
            position: absolute;
            right: 10px;
            top: 60%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            color: #999;
        }

        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-gap: 10px;
            margin-bottom: 20px;
        }

        .keypad button {
            padding: 15px;
            font-size: 24px;
            cursor: pointer;
            background-color: #87CEEB;
            color: #000;
            border: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .keypad button:hover {
            background-color: #5faacb;
        }

        .actions {
            display: flex;
            justify-content: space-between;
        }

        .actions button {
            padding: 15px;
            font-size: 22px;
            cursor: pointer;
            color: #333;
            border: none;
            border-radius: 5px;
            width: 30%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: background-color 0.3s;
        }

        .actions button.red {
            color: red;
        }

        .actions button.orange {
            color: white;
            background-color: #f7d929;
            border: none;
            border-radius: 5px;
            width: 30%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .actions button.green {
            color: green;
        }

        .actions button:active.green {
            background-color: lightgreen;
        }

        .actions button:active.red {
            background-color: lightcoral;
        }

        .actions button:active.orange {
            background-color: lightyellow;
        }

        .input-field .toggle-password:hover {
            color: #555;
        }

        .alert {
            background-color: #ffcccc;
            color: #d8000c;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            display: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
<!-- Sección izquierda para la imagen -->
<div class="left-side"></div>

<!-- Sección derecha para el login -->
<div class="right-side">
    <div class="container">

        <!-- Mensaje de alerta -->
        <?php if (!empty($alertMessage)) : ?>
            <div class="alert" id="alertMessage" style="display: block;">
                <strong><?php echo $alertMessage; ?></strong>
            </div>
        <?php endif; ?>

        <!-- Formulario de Login -->
        <form method="POST" action="">
            <!-- Campo de Usuario -->
            <div class="input-field">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required onkeypress="allowOnlyNumbers(event)">
            </div>

            <!-- Campo de Contraseña -->
            <div class="input-field">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required onkeypress="allowOnlyNumbers(event)">
                <span id="passwordToggleIcon" class="toggle-password" onclick="togglePassword()">🔒</span>
            </div>

            <!-- Teclado numérico -->
            <div class="keypad">
                <button type="button" onclick="addNumber('1')"><strong>1</strong></button>
                <button type="button" onclick="addNumber('2')"><strong>2</strong></button>
                <button type="button" onclick="addNumber('3')"><strong>3</strong></button>
                <button type="button" onclick="addNumber('4')"><strong>4</strong></button>
                <button type="button" onclick="addNumber('5')"><strong>5</strong></button>
                <button type="button" onclick="addNumber('6')"><strong>6</strong></button>
                <button type="button" onclick="addNumber('7')"><strong>7</strong></button>
                <button type="button" onclick="addNumber('8')"><strong>8</strong></button>
                <button type="button" onclick="addNumber('9')"><strong>9</strong></button>
                <button type="button" style="grid-column: 2 / 3;" onclick="addNumber('0')"><strong>0</strong></button>
            </div>

            <!-- Botones de acción -->
            <div class="actions">
                <button type="button" class="red" onclick="clearInput()">Limpiar</button>
                <button type="submit" class="orange">Ingresar</button>
                <button type="button" class="green" onclick="deleteLastNumber()">Borrar</button>
            </div>
        </form>
    </div>
</div>

<script>
    let passwordField = document.getElementById('password');
    let usernameField = document.getElementById('username');
    let alertMessage = document.getElementById('alertMessage');
    let currentField = usernameField; // Inicialmente se selecciona el campo de usuario

    // Detectar cuando el campo de usuario o contraseña está enfocado
    usernameField.addEventListener('focus', () => currentField = usernameField);
    passwordField.addEventListener('focus', () => currentField = passwordField);

    // Mostrar el mensaje por solo 3 segundos si existe
    if (alertMessage) {
        setTimeout(() => {
            alertMessage.style.display = 'none';
        }, 3000);
    }

    // Alternar la visibilidad de la contraseña
    function togglePassword() {
        const passwordToggleIcon = document.getElementById('passwordToggleIcon');
        
        if (passwordField.type === "password") {
            passwordField.type = "text"; // Mostrar la contraseña
            passwordToggleIcon.textContent = "🔓"; // Cambiar a candado abierto
        } else {
            passwordField.type = "password"; // Ocultar la contraseña
            passwordToggleIcon.textContent = "🔒"; // Cambiar a candado cerrado
        }
    }

    // Añadir números al campo actual (usuario o contraseña)
    function addNumber(number) {
        currentField.value += number;
    }

    // Borrar el último número del campo actual
    function deleteLastNumber() {
        currentField.value = currentField.value.slice(0, -1);
    }

    // Limpiar los campos de entrada
    function clearInput() {
        passwordField.value = '';
        usernameField.value = ''; // Limpiar también el campo de usuario
    }

    // Permitir solo números en los campos de texto
    function allowOnlyNumbers(event) {
        const keyCode = event.which ? event.which : event.keyCode;
        if (keyCode < 48 || keyCode > 57) {
            event.preventDefault();
            alertMessage.textContent = "Solo se permiten números en este campo.";
            alertMessage.style.display = "block";
            setTimeout(() => {
                alertMessage.style.display = "none";
            }, 3000);
        }
    }
</script>
</body>
</html>
