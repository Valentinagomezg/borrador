<?php
// Incluye el archivo con la conexión a la base de datos
include 'conexion.php';

// Inicia la sesión para guardar datos del usuario si inicia sesión con éxito
session_start();

// ------------------- RECOGER DATOS DEL FORMULARIO -------------------

$email = trim($_POST['email']);
$contrasena = trim($_POST['password']);

// ------------------- CONSULTAR USUARIO EN LA BASE DE DATOS -------------------

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();

    if (password_verify($contrasena, $usuario['contrasena'])) {
        // Guarda datos del usuario en la sesión
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['rol'];

        // También guarda en localStorage usando JavaScript
        echo "<script>
            localStorage.setItem('id_usuario', '{$usuario['id_usuario']}');
            localStorage.setItem('rol', '{$usuario['rol']}');
            window.location.href = 'cupo.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Correo o contraseña incorrectos.');
            window.history.back();
        </script>";
    }
} else {
    echo "<script>
        alert('Correo o contraseña incorrectos.');
        window.history.back();
    </script>";
}

$stmt->close();
$conexion->close();
?>
