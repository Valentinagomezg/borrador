<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: index.html');
    exit();
}

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = "";
$database = "dbparking";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener cupos ocupados
$ocupados = [];
$resultCupos = $conn->query("SELECT numero_cupo FROM reservas");
if ($resultCupos) {
    while ($row = $resultCupos->fetch_assoc()) {
        $ocupados[] = (int)$row['numero_cupo'];
    }
}

// Obtener usuarios
$usuarios = [];
$resultUsuarios = $conn->query("SELECT id_usuario, nombre FROM usuarios");
if ($resultUsuarios) {
    while ($row = $resultUsuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Eliminar usuario si se recibe solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    $idEliminar = (int)$_POST['eliminar_id'];

    if (isset($_POST['confirmar_eliminacion']) && $_POST['confirmar_eliminacion'] === 'sí') {
        $conn->query("DELETE FROM reservas WHERE id_usuario = $idEliminar");
        $conn->query("DELETE FROM usuarios WHERE id_usuario = $idEliminar");
        header("Location: admin.php?mensaje=eliminado");
        exit();
    }
}

// Obtener pagos por método
$pagos = [
    "nequi" => 0,
    "tarjeta" => 0
];

$resultPagos = $conn->query("SELECT metodo_pago, monto FROM reservas");
if ($resultPagos) {
    while ($row = $resultPagos->fetch_assoc()) {
        $metodo = strtolower(trim($row['metodo_pago']));
        if (array_key_exists($metodo, $pagos)) {
            $pagos[$metodo] += (float)$row['monto'];
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Panel Administrador</title>
  <link rel="stylesheet" href="css/admin.css" />
</head>
<body>
  <div class="phone-container">
      <!-- Modal -->
      <div id="modal">
        <div id="modal-content"></div>
        <h3>Información de Reserva</h3>
        <button onclick="document.getElementById('modal').style.display='none'">Cerrar</button>
      </div>
      
      <div class="header">
        <h1>Panel del Administrador</h1>
        <button class="logout-btn">Cerrar sesión</button>
      </div>

      <div class="container">
        <h2>Zona A</h2>
        <div class="parking-lot">
          <?php
          // Generar automáticamente los 1000 cupos con imágenes cada 4
          for ($i = 1; $i <= 1000; $i++) {
              echo '<div class="parking-spot" data-cupo="' . $i . '">' . $i . '</div>';
              if ($i % 4 === 0 && $i < 1000) {
                  echo '<div class="parking-image">
                          <img src="imagenes/1.1.png" alt="Imagen zona A" />
                        </div>';
              }
          }
          ?>
        </div>
      </div>

      <section class="eliminar-usuario-section">
        <h2>Eliminar Usuarios</h2>
        <form method="POST" class="eliminar-usuario-form">
          <label for="eliminar_id">Selecciona un usuario:</label>
          <select name="eliminar_id" id="eliminar_id" required>
            <option value="">-- Selecciona un usuario --</option>
            <?php foreach ($usuarios as $usuario): ?>
              <option value="<?= $usuario['id_usuario'] ?>">
                <?= htmlspecialchars($usuario['nombre']) ?> (ID: <?= $usuario['id_usuario'] ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <div class="confirmar-eliminacion">
            <input type="checkbox" id="confirmar_eliminacion" name="confirmar_eliminacion" value="sí" required>
            <label for="confirmar_eliminacion">Confirmo eliminar al usuario</label>
          </div>

          <button type="submit">Eliminar</button>
        </form>
      </section>

      <section id="pagos-section">
        <h2>Total de Pagos</h2>
        <ul>
          <li>Nequi: $<?= number_format($pagos['nequi'], 2) ?></li>
          <li>Tarjeta: $<?= number_format($pagos['tarjeta'], 2) ?></li>
          <li><strong>Total: $<?= number_format(array_sum($pagos), 2) ?></strong></li>
        </ul>
      </section>
  </div>

  <script>
    // Mostrar mensaje si viene de eliminación
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('mensaje') === 'eliminado') {
      alert('Usuario borrado exitosamente');
      window.history.replaceState(null, null, window.location.pathname);
    }

    // Botón cerrar sesión
    document.querySelector(".logout-btn").addEventListener("click", () => {
      localStorage.removeItem("id_usuario");
      localStorage.removeItem("id_vehiculo");
      localStorage.removeItem("cupoTemporal");
      localStorage.removeItem("rol");
      window.location.href = "index.html";
    });

    // Colorear cupos
    const ocupados = <?= json_encode($ocupados) ?>;
    document.querySelectorAll('.parking-spot').forEach(spot => {
      const cupo = parseInt(spot.getAttribute('data-cupo'));
      if (ocupados.includes(cupo)) {
        spot.classList.add('ocupado');
      } else {
        spot.classList.add('libre');
      }
    });

    // Mostrar modal con info de reserva
    document.querySelectorAll('.parking-spot.ocupado').forEach(spot => {
      spot.addEventListener('click', () => {
        const cupo = spot.getAttribute('data-cupo');
        fetch(`info_reserva.php?cupo=${cupo}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const modalContent = document.getElementById('modal-content');
              const fechaReserva = data.fecha_reserva.split(' ')[0];
              const horaInicio = data.fecha_reserva.split(' ')[1];
              const horaFin = data.fecha_vencimiento.split(' ')[1];

              modalContent.innerHTML = `
                  <p><strong>Usuario:</strong> ${data.nombre}</p>
                  <p><strong>Tipo de Automóvil:</strong> ${data.tipo_automovil}</p>
                  <p><strong>Método de Pago:</strong> ${data.metodo_pago}</p>
                  <p><strong>Monto:</strong> $${data.monto}</p>
                  <p><strong>Fecha de Reserva:</strong> ${fechaReserva}</p>
                  <p><strong>Hora de Inicio:</strong> ${horaInicio}</p>
                  <p><strong>Hora de Finalización:</strong> ${horaFin}</p>
                `;
              document.getElementById('modal').style.display = 'block';
            } else {
              alert('No hay información disponible para este cupo.');
            }
          })
          .catch(error => console.error('Error:', error));
      });
    });
  </script>

</body>
</html>

