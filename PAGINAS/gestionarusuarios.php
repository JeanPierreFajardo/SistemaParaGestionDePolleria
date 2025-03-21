<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "polleria";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificación de conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// Verificar si el usuario tiene rol_id = 1
if ($rol_id != 1) {
    header("Location: index.php");
    exit();
}

// Funciones para manejar usuarios
function obtenerUsuarios($conn) {
    // Consulta con JOIN para obtener el nombre del rol
    $sql = "SELECT usuarios.*, roles.nombre AS rol FROM usuarios
            LEFT JOIN roles ON usuarios.rol_id = roles.id";
    return $conn->query($sql);
}

function agregarUsuario($documento, $nombreCompleto, $fechaCreacion, $contraseña, $rol_id, $estado, $conn) {
    // Dividir el nombre completo en palabras
    $nombreCompletoArray = explode(' ', $nombreCompleto);
    $nombre = array_shift($nombreCompletoArray); // La primera palabra será el nombre
    $apellido = implode(' ', $nombreCompletoArray); // El resto serán los apellidos
    $username = $documento; // Usamos el documento como username
    $sql = "INSERT INTO usuarios (documento, nombre, apellido, fecha_creacion, username, password, rol_id, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $documento, $nombre, $apellido, $fechaCreacion, $username, $contraseña, $rol_id, $estado);
    return $stmt->execute();
}

function eliminarUsuario($id, $conn) {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function actualizarUsuario($id, $documento, $nombreCompleto, $fechaCreacion, $contraseña, $rol_id, $estado, $conn) {
    $nombreCompletoArray = explode(' ', $nombreCompleto);
    $nombre = array_shift($nombreCompletoArray); // Nombre
    $apellido = implode(' ', $nombreCompletoArray); // Apellidos
    $username = $documento; // Usamos el documento como username
    
    // Actualizar también el campo username en la consulta
    $sql = "UPDATE usuarios SET documento = ?, nombre = ?, apellido = ?, fecha_creacion = ?, password = ?, rol_id = ?, estado = ?, username = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $documento, $nombre, $apellido, $fechaCreacion, $contraseña, $rol_id, $estado, $username, $id);
    return $stmt->execute();
}

// Procesar acciones del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['accion'])) {
        $accion = $_POST['accion'];
        $id = $_POST['id'] ?? null;
        $documento = $_POST['documento'] ?? '';
        $nombre = $_POST['nombre'] ?? '';
        $fechaCreacion = $_POST['fecha_creacion'] ?? '';
        $contraseña = $_POST['contraseña'] ?? '';
        $rol_id = $_POST['rol_id'] ?? ''; 
        $estado = $_POST['estado'] ?? '';

        // Solo validamos los campos si la acción es guardar o actualizar
        if (($accion === 'guardar' || $accion === 'actualizar') && (empty($documento) || empty($nombre) || empty($fechaCreacion) || empty($contraseña) || empty($rol_id) || empty($estado))) {
            echo json_encode(['mensaje' => 'Todos los campos son obligatorios.', 'tipo' => 'error']);
            exit;
        }

        if ($accion === 'guardar') {
            $resultado = agregarUsuario($documento, $nombre, $fechaCreacion, $contraseña, $rol_id, $estado, $conn);
            echo json_encode(['mensaje' => $resultado ? 'Usuario guardado exitosamente.' : 'Error al guardar el usuario.', 'tipo' => $resultado ? 'success' : 'error']);
        } else if ($accion === 'actualizar' && $id) {
            $resultado = actualizarUsuario($id, $documento, $nombre, $fechaCreacion, $contraseña, $rol_id, $estado, $conn);
            echo json_encode(['mensaje' => $resultado ? 'Usuario actualizado exitosamente.' : 'Error al actualizar el usuario.', 'tipo' => $resultado ? 'success' : 'error']);
        } else if ($accion === 'eliminar' && $id) {
            $resultado = eliminarUsuario($id, $conn);
            echo json_encode(['mensaje' => $resultado ? 'Usuario eliminado exitosamente.' : 'Error al eliminar el usuario.', 'tipo' => $resultado ? 'success' : 'error']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <style>
      <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            height: 100vh;
            overflow: hidden;
        }

        .container {
            display: flex;
            flex-direction: row;
            height: 100%;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .left-panel, .right-panel {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-y: auto;
        }

        .left-panel {
            margin-right: 20px;
            width: 30%;
        }

        .right-panel {
            width: 70%;
            max-height: calc(100vh - 40px);
        }

        h3, h2 {
            margin-bottom: 20px;
        }

        .button, .btn-limpiar, .btn-buscar {
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s;
            flex: 1;
            min-width: 100px;
            margin-right: 10px;
        }

        .button {
            background-color: #4CAF50;
            color: white;
        }

        .button:hover {
            background-color: #45a049;
        }

        .btn-limpiar {
            background-color: #007BFF;
            color: white;
        }

        .btn-limpiar:hover {
            background-color: #0056b3;
        }

        .btn-buscar {
            background-color: #feb40a;
            color: white;
        }

        .btn-buscar:hover {
            background-color: #218838;
        }

        .btn-eliminar {
            background-color: red;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
        }

        .btn-eliminar:hover {
            background-color: #cc0000;
        }

        table {
            width: 100%;
           /* table-layout: fixed;*/
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 8px;
            text-align: left;
        }

        th, td {
            border: 1px solid #ddd;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
            color: black;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .search-bar {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-bar label {
            margin-right: 10px;
        }

        .search-bar select, .search-bar input {
            margin-right: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-bar select {
            width: 250px;
        }

        .search-bar input {
            width: calc(100% - 140px);
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        input, select {
            margin-bottom: 10px;
            width: calc(100% - 10px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .button-container {
            margin-top: 20px;
            display: flex;
        }

        .col-id {
            width: 40px;
        }

        .col-rol {
            width: 80px;
        }

        .col-estado {
            width: 60px;
        }

        .col-select {
            width: 30px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .modal-button {
            margin: 5px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-button.yes {
            background-color: #4CAF50;
            color: white;
        }

        .modal-button.no {
            background-color: #f44336;
            color: white;
        }

        .password-container {
            position: relative;
            margin-bottom: 10px;
        }

        .password-container input {
            padding-right: 10px;
        }

        select {
            background-color: #b2e0e6; /* Celeste bebé */
        }    
        
        .btn-retroceder, .btn-salir {
    padding: 8px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    color: white;
    background-color: #007BFF;
    display: inline-flex;
    align-items: center;
    margin-left: 5px;
}

.btn-retroceder:hover, .btn-salir:hover {
    background-color: #0056b3;
}

.btn-retroceder i, .btn-salir i {
    font-size: 18px;
}

   
    </style>
      <script>
        // Validación del campo "Nº de Documento" para aceptar solo números
        function validarDocumento(event) {
            const key = event.key;
            if (!/^[0-9]$/.test(key) && key !== "Backspace") {
                event.preventDefault();
                alert('Solo se permiten números en el campo "Nº de Documento".');
            }
        }

        // Validación del campo "Nombre Completo" para aceptar solo letras
        function validarNombre(event) {
            const key = event.key;
            if (!/^[a-zA-Z\s]$/.test(key) && key !== "Backspace") {
                event.preventDefault();
                alert('Solo se permiten letras en el campo "Nombre Completo".');
            }
        }

        // Validación del campo "Contraseña" para aceptar solo números
        function validarContraseña(event) {
            const key = event.key;
            if (!/^[0-9]$/.test(key) && key !== "Backspace") {
                event.preventDefault();
                alert('Solo se permiten números en el campo "Contraseña".');
            }
        }
    </script>
</head>
<body>

<div class="container">
<div class="left-panel">
    <div id="formulario">
        <!-- Contenedor para mantener el título y los botones en la misma línea -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 id="form-titulo" style="margin: 0; flex: 1;">Detalle Usuario</h3>
            <div>
                <!-- Botón para retroceder -->
                <button class="btn-retroceder" onclick="window.history.back()" title="Retroceder">
                    <i class="fas fa-arrow-left"></i>
                </button>

                <!-- Botón para salir -->
                <button class="btn-salir" onclick="window.location.href='index.php'" title="Salir">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
            <input type="hidden" id="id" name="id"> <!-- Input oculto para el ID del usuario -->
             <!-- Validación en el campo Nº de Documento -->
             <div class="form-group">
                <label for="documento">Nº de Documento:</label>
                <input type="text" id="documento" name="documento" placeholder="Ingrese número de documento" onkeypress="validarDocumento(event)">
            </div>
             <!-- Validación en el campo Nombre Completo -->
             <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ingrese nombre completo" onkeypress="validarNombre(event)">
            </div>

            <div class="form-group">
                <label for="fecha_creacion">Fecha de Creación:</label>
                <input type="date" id="fecha_creacion" name="fecha_creacion">
            </div>
             <!-- Validación en el campo Contraseña -->
             <div class="form-group password-container">
             <label for="contraseña">Contraseña:</label>
             <input type="text" id="contraseña" name="contraseña" placeholder="Ingrese contraseña" onkeypress="validarContraseña(event)">
             </div>

            <div class="form-group">
                <label for="rol_id">Rol:</label>
                <select id="rol_id" name="rol_id">
                    <option value="">Seleccione un rol</option>
                    <option value="1">Admin</option>
                    <option value="2">Mozo</option>
                    <option value="3">Cajero</option>
                </select>
            </div>

            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
            </div>

            <div class="button-container">
                <button class="button" onclick="guardarUsuario()">Guardar</button>
                <button class="btn-limpiar" onclick="limpiarFormulario()">Limpiar</button>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <div class="search-bar">
            <label for="filtro-rol">Buscar por:</label>
            <select id="filtro-rol" onchange="filtrarUsuarios()">
                <option value="">Todos</option>
                <option value="Administrador">Admin</option>
                <option value="Mozo">Mozo</option>
                <option value="Cajero">Cajero</option>
            </select>
            <input type="text" id="filtro-nombre" placeholder="Buscar por nombre" oninput="filtrarUsuarios()">
            <button class="btn-buscar" onclick="filtrarUsuarios()">Buscar</button>
            
        </div>

        <h3>Lista de Usuarios</h3>
        <table id="tabla-usuarios">
            <thead>
                <tr>
                    <th class="col-select"></th>
                    <th>Nº</th>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Fecha de Creación</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th class="col-select">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resultado = obtenerUsuarios($conn);
                $contador = 1; // Contador para enumerar
                if ($resultado->num_rows > 0) {
                    while ($fila = $resultado->fetch_assoc()) {
                        echo "<tr onclick='cargarDatos(" . json_encode($fila) . ")'>";
                        echo "<td class='col-select'><input type='checkbox' class='checkbox-fila' data-id='" . $fila['id'] . "' onclick='seleccionarFila(this)'></td>";
                        echo "<td>" . $contador++ . "</td>";
                        echo "<td>" . $fila['documento'] . "</td>";
                        echo "<td>" . $fila['nombre'] . " " . $fila['apellido'] . "</td>";
                        echo "<td>" . $fila['fecha_creacion'] . "</td>";
                        echo "<td>" . $fila['rol'] . "</td>";
                        echo "<td>" . $fila['estado'] . "</td>";
                        echo "<td><button class='btn-eliminar' onclick='confirmarEliminacion(" . $fila['id'] . ")'>Eliminar</button></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8'>No hay usuarios registrados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div id="mensaje" class="message"></div>

<!-- Modal de Confirmación de Eliminación -->
<div id="modal-eliminar" class="modal">
    <div class="modal-content">
        <p>¿Estás seguro de que deseas eliminar este usuario?</p>
        <button class="modal-button yes" onclick="eliminarUsuario()">Sí</button>
        <button class="modal-button no" onclick="cerrarModal()">No</button>
    </div>
</div>

<script>
let modoEditar = false;
let idUsuarioEditar = null;
let usuarioIdEliminar = null;

function cargarDatos(usuario) {
    document.getElementById('id').value = usuario.id;
    document.getElementById('documento').value = usuario.documento;
    document.getElementById('nombre').value = usuario.nombre + " " + usuario.apellido;
    document.getElementById('fecha_creacion').value = usuario.fecha_creacion;
    document.getElementById('contraseña').value = usuario.password;
    document.getElementById('rol_id').value = usuario.rol_id;
    document.getElementById('estado').value = usuario.estado;

    modoEditar = true;
    idUsuarioEditar = usuario.id;
}

function guardarUsuario() {
    const documento = document.getElementById('documento').value;
    const nombre = document.getElementById('nombre').value;
    const fechaCreacion = document.getElementById('fecha_creacion').value;
    const contraseña = document.getElementById('contraseña').value;
    const rol_id = document.getElementById('rol_id').value;
    const estado = document.getElementById('estado').value;

    if (!documento || !nombre || !fechaCreacion || !contraseña || !rol_id || !estado) {
        mostrarMensaje('Todos los campos son obligatorios.', 'error');
        return;
    }

    const accion = modoEditar ? 'actualizar' : 'guardar';
    const formData = new FormData();
    formData.append('accion', accion);
    if (modoEditar) formData.append('id', idUsuarioEditar);
    formData.append('documento', documento);
    formData.append('nombre', nombre);
    formData.append('fecha_creacion', fechaCreacion);
    formData.append('contraseña', contraseña);
    formData.append('rol_id', rol_id);
    formData.append('estado', estado);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        mostrarMensaje(data.mensaje, data.tipo);
        if (data.tipo === 'success') {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        mostrarMensaje('Error al guardar el usuario: ' + error.message, 'error');
    });

    modoEditar = false;
    idUsuarioEditar = null;
}
function limpiarFormulario() {
    // Limpiar los campos del formulario de usuario
    document.getElementById('id').value = '';  // Limpiar el campo oculto ID
    document.getElementById('documento').value = '';  // Limpiar el campo de documento
    document.getElementById('nombre').value = '';  // Limpiar el campo de nombre completo
    document.getElementById('fecha_creacion').value = '';  // Limpiar el campo de fecha de creación
    document.getElementById('contraseña').value = '';  // Limpiar el campo de contraseña
    document.getElementById('rol_id').value = '';  // Limpiar el campo de rol
    document.getElementById('estado').value = '';  // Limpiar el campo de estado
    
    // Limpiar el cuadro de búsqueda
    document.getElementById('filtro-nombre').value = '';  // Limpiar el campo de búsqueda por nombre
    document.getElementById('filtro-rol').value = '';  // Limpiar el campo de filtro por rol
    
    // Limpiar las filas seleccionadas
    const checkboxes = document.querySelectorAll('.checkbox-fila');
    checkboxes.forEach(chk => chk.checked = false);  // Deseleccionar todas las filas
    
    // Resetear variables de edición
    modoEditar = false;
    idUsuarioEditar = null;
    
    // Ocultar cualquier mensaje de error o éxito que esté visible
    document.getElementById('mensaje').style.display = 'none';

       // Mostrar todas las filas de la tabla
       const filas = document.querySelectorAll('#tabla-usuarios tbody tr');
    filas.forEach(fila => {
        fila.style.display = ''; // Mostrar todas las filas
    });
}

function filtrarUsuarios() {
    const rol = document.getElementById('filtro-rol').value.toLowerCase(); // Obtener el valor del filtro de rol
    const nombre = document.getElementById('filtro-nombre').value.toLowerCase(); // Obtener el valor del filtro de nombre
    const filas = document.querySelectorAll('#tabla-usuarios tbody tr'); // Obtener todas las filas de la tabla

    // Recorrer cada fila de la tabla
    filas.forEach(fila => {
        const rolCelda = fila.cells[5].textContent.toLowerCase(); // Columna del rol (6ta posición en la tabla)
        const nombreCelda = fila.cells[3].textContent.toLowerCase(); // Columna del nombre completo (4ta posición en la tabla)
        
        // Verificar si la fila cumple con el filtro de rol y de nombre
        const cumpleFiltroRol = rol ? rolCelda === rol : true;
        const cumpleFiltroNombre = nombre ? nombreCelda.includes(nombre) : true;

        // Mostrar u ocultar la fila dependiendo si cumple con los filtros
        fila.style.display = cumpleFiltroRol && cumpleFiltroNombre ? '' : 'none';
    });

    // Desmarcar todas las casillas de selección cuando se hace una búsqueda por rol o nombre
    const checkboxes = document.querySelectorAll('.checkbox-fila');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false; // Desmarcar cada casilla
    });

    
}




function seleccionarFila(checkbox) {
    console.log("Checkbox marcado: " + checkbox.checked);

    const checkboxes = document.querySelectorAll('.checkbox-fila');

    checkboxes.forEach(chk => {
        if (chk !== checkbox) {
            chk.checked = false;
        }
    });

    if (checkbox.checked) {
        const fila = checkbox.closest('tr');
        const datosUsuario = {
            id: fila.cells[1].textContent.trim(),
            documento: fila.cells[2].textContent.trim(),
            nombre: fila.cells[3].textContent.trim(),
            fecha_creacion: fila.cells[4].textContent.trim(),
            rol_id: fila.cells[5].textContent.trim(),
            estado: fila.cells[6].textContent.trim()
        };

        cargarDatos(datosUsuario);
    } else {
        limpiarFormulario();
    }
}


function eliminarUsuario() {
    if (usuarioIdEliminar === null) return;

    const formData = new FormData();
    formData.append('accion', 'eliminar');
    formData.append('id', usuarioIdEliminar);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        mostrarMensaje(data.mensaje, data.tipo);
        if (data.tipo === 'success') {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        mostrarMensaje('Error al eliminar el usuario: ' + error.message, 'error');
    });

    cerrarModal();
}

function mostrarMensaje(mensaje, tipo) {
    const mensajeDiv = document.getElementById('mensaje');
    mensajeDiv.textContent = mensaje;
    mensajeDiv.className = 'message ' + tipo;
    mensajeDiv.style.display = 'block';
    setTimeout(() => {
        mensajeDiv.style.display = 'none';
    }, 3000);
}

function confirmarEliminacion(id) {
    usuarioIdEliminar = id;
    document.getElementById('modal-eliminar').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-eliminar').style.display = 'none';
}
</script>
</body>
</html>
