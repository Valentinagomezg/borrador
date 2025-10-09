<?php
// Incluye el archivo de conexión a la base de datos
include 'conexion.php';

// Inicia la sesión para almacenar variables de sesión
session_start();

// ------------------- RECOGER DATOS DEL FORMULARIO -------------------

// Elimina espacios en blanco al inicio y al final del nombre
$nombre = trim($_POST['nombre']);

// Elimina espacios en blanco al inicio y al final del teléfono
$telefono = trim($_POST['telefono']);

// Elimina espacios en blanco al inicio y al final del email
$email = trim($_POST['email']);

// Recoge la contraseña sin modificarla (ya que puede tener espacios válidos)
$contrasena = $_POST['contrasena'];

// Recoge la confirmación de la contraseña
$confirmar_contrasena = $_POST['confirmar_contrasena'];

// Recoge la fecha de nacimiento
$fecha_nacimiento = $_POST['fecha_nacimiento'];

// Recoge el rol del usuario (por ejemplo: usuario o administrador)
$rol = $_POST['rol'];

// Si el código de administrador fue enviado, se asigna, si no, se deja como cadena vacía
$codigo_admin = $_POST['codigo_admin'] ?? '';

// ------------------- VALIDACIONES -------------------

// Verifica si alguno de los campos obligatorios está vacío
if (empty($nombre) || empty($telefono) || empty($email) || empty($contrasena) || empty($confirmar_contrasena) || empty($fecha_nacimiento) || empty($rol)) {
    echo "<script>
            alert('Todos los campos son obligatorios.');
            window.history.back();
          </script>";
    exit(); // Detiene la ejecución del script
}

// Verifica si las contraseñas no coinciden
if ($contrasena !== $confirmar_contrasena) {
    echo "<script>
            alert('Las contraseñas no coinciden.');
            window.history.back();
          </script>";
    exit();
}

// Verifica si el email tiene un formato válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
            alert('Correo electrónico no válido.');
            window.history.back();
          </script>";
    exit();
}

// ------------------- VERIFICACIÓN DE CORREO REPETIDO -------------------

// Consulta SQL para comprobar si ya existe un usuario con ese correo
$sql_check = "SELECT id_usuario FROM usuarios WHERE email = ?";

// Prepara la consulta para evitar inyecciones SQL
$stmt_check = $conexion->prepare($sql_check);

// Vincula el email al parámetro de la consulta
$stmt_check->bind_param("s", $email);


$stmt_check->execute();


$stmt_check->store_result();


if ($stmt_check->num_rows > 0) {
    echo "<script>
            alert('El correo electrónico ya está registrado.');
            window.history.back();
          </script>";
    exit();
}


$stmt_check->close();


if ($rol === 'administrador' && $codigo_admin !== '1075652100') {
    echo "<script>
        alert('Código de administrador incorrecto.');
        window.history.back();
    </script>";
    exit();
}


$hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);


$sql = "INSERT INTO usuarios (nombre, telefono, email, contrasena, fecha_nacimiento, rol)
        VALUES (?, ?, ?, ?, ?, ?)";

// Prepara la sentencia SQL
$stmt = $conexion->prepare($sql);

// Vincula los datos del formulario a los parámetros de la consulta
$stmt->bind_param("ssssss", $nombre, $telefono, $email, $hash_contrasena, $fecha_nacimiento, $rol);

// Ejecuta la consulta para insertar el usuario
if ($stmt->execute()) {
    // Si se inserta correctamente, guarda el ID del usuario y su rol en la sesión
    $_SESSION['id_usuario'] = $conexion->insert_id;
    $_SESSION['rol'] = $rol;

    // Redirige a la página "cupo.php"
    header("Location: cupo.php");
    exit();
} else {
    // Si ocurre un error, lo muestra en una alerta
    echo "<script>
        alert('Error al registrar: " . addslashes($stmt->error) . "');
        window.history.back();
    </script>";
}

// Cierra la consulta
$stmt->close();

// Cierra la conexión a la base de datos
$conexion->close();
?>
