<?php
// Obtener cupos ocupados desde la base de datos
$host = "localhost";
$user = "root";
$password = "";
$database = "dbparking";

$conn = new mysqli($host, $user, $password, $database);
$ocupados = [];

if (!$conn->connect_error) {
  $sql = "SELECT numero_cupo FROM reservas";
  $result = $conn->query($sql);
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $ocupados[] = (int)$row['numero_cupo'];
    }
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agenda tu cupo</title>
  <link rel="stylesheet" href="css/cupo.css" />
  <link rel="icon" href="imagenes/logo-primary.png" >
</head>
<body>
  <div class="phone-container">
    <div class="header">
      <h1>Agenda tu cupo</h1>
      <button class="logout-btn">Cerrar sesión</button>
      <a href="tarifa.html" class="tarifas-btn">Tarifas</a>

      <!-- Botón de admin oculto por defecto -->
      <a href="admin.php" class="admin-btn" id="admin-btn" style="display: none;">Admin Panel</a>
    </div>

    <div class="container">
      <h2>Zona A</h2>
      <div class="parking-lot">
        <?php
        // Mostrar 1000 cupos, insertando una imagen cada 4 cupos (como en tu ejemplo)
        for ($i = 1; $i <= 1000; $i++) {
            echo '<div class="parking-spot">' . $i . '</div>';

            // Cada 4 cupos se pone la imagen decorativa
            if ($i % 4 === 0 && $i < 1000) {
                echo '<div class="parking-image">
                        <img src="imagenes/1.1.png" alt="Imagen zona A" />
                      </div>';
            }
        }
        ?>
      </div>

      <div class="button-container">
        <a href="#" id="agendar-btn">Agendar</a>
        <a href="editar_perfil.php" class="action-button">Editar Perfil</a>
      </div>

      <div id="error"></div>
    </div>
  </div>

  <script>
    const ocupados = <?php echo json_encode($ocupados); ?>;
    let cupoSeleccionado = null;

    document.addEventListener("DOMContentLoaded", () => {
      const spots = document.querySelectorAll(".parking-spot");

      spots.forEach((spot, index) => {
        const numero = index + 1;
        if (ocupados.includes(numero)) {
          spot.classList.add("ocupado");
        } else {
          spot.addEventListener("click", () => {
            spots.forEach(s => s.classList.remove("seleccionado"));
            spot.classList.add("seleccionado");
            cupoSeleccionado = numero;
            document.getElementById("error").innerText = "";
          });
        }
      });

      // Botón Agendar
      document.getElementById("agendar-btn").addEventListener("click", (e) => {
        e.preventDefault();
        if (cupoSeleccionado) {
          localStorage.setItem("cupoTemporal", cupoSeleccionado);
          window.location.href = "confirmacion.html";
        } else {
          document.getElementById("error").innerText = "Por favor selecciona un cupo.";
        }
      });

      // Botón Cerrar sesión
      document.querySelector(".logout-btn").addEventListener("click", () => {
        localStorage.removeItem("id_usuario");
        localStorage.removeItem("id_vehiculo");
        localStorage.removeItem("cupoTemporal");
        localStorage.removeItem("rol");
        window.location.href = "index.html";
      });

      // Mostrar botón admin si el rol es administrador
      const rol = localStorage.getItem("rol");
      if (rol === "administrador") {
          window.location.href = "admin.php";
      }
    });
  </script>
</body>
</html>
