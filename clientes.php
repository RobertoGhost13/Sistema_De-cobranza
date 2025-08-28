<?php
include("conexion.php");  // Conecta con la base de datos
$mensaje = "";            // Variable para mostrar mensajes al usuario

// Guardar o actualizar cliente
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST["id"] ?? 0);                 // Obtiene el ID del formulario (0 si es nuevo cliente)
    $nombre = trim($_POST["nombre"]);                // Nombre del cliente
    $telefono = trim($_POST["telefono"]);            // Teléfono del cliente

    // Validar teléfono: debe tener 10 dígitos
    if (!preg_match('/^[0-9]{10}$/', $telefono)) {
        $mensaje = "Teléfono inválido. Debe contener 10 dígitos.";
    } elseif (empty($nombre)) {
        $mensaje = "El nombre no puede estar vacío.";
    } else {
        if ($id > 0) {
            // Actualiza cliente existente
            $stmt = $conexion->prepare("UPDATE clientes SET nombre=?, telefono=? WHERE id=?");
            $stmt->bind_param("ssi", $nombre, $telefono, $id);
            $stmt->execute();
            $mensaje = "Cliente actualizado correctamente.";
        } else {
            // Inserta nuevo cliente
            $stmt = $conexion->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?)");
            $stmt->bind_param("ss", $nombre, $telefono);
            $stmt->execute();
            $mensaje = "Cliente agregado correctamente.";
        }
    }
}

// Eliminar cliente (solo si no tiene notas)
if (isset($_GET["eliminar"])) {
    $idEliminar = intval($_GET["eliminar"]);
    $verifica = $conexion->query("SELECT COUNT(*) as total FROM notas WHERE cliente_id = $idEliminar");
    $fila = $verifica->fetch_assoc();
    if ($fila['total'] > 0) {
        $mensaje = "No se puede eliminar: el cliente tiene notas registradas.";
    } else {
        $conexion->query("DELETE FROM clientes WHERE id = $idEliminar");
        $mensaje = "Cliente eliminado correctamente.";
    }
}

// Obtener lista de clientes
$clientes = $conexion->query("SELECT * FROM clientes");

// Obtener cliente a editar si se pasa por GET
$clienteEditar = null;
if (isset($_GET["editar"])) {
    $idEditar = intval($_GET["editar"]);
    $clienteEditar = $conexion->query("SELECT * FROM clientes WHERE id = $idEditar")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes</title>
   <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
        <nav>
    <div class="logo-area">
        <img src="logo.jpg" alt="Logo">
        <span>Compuser Valladolid</span>
    </div>
    <div class="nav-links">
        <a href="index.php">Menu principal</a>
        <a href="clientes.php">Clientes</a>
        <a href="notas.php">Notas por cobrar</a>
        <a href="abonos.php">Abonos</a>
        <a href="reportes.php">Reportes</a>
         <a href="reportes2.php">Reporte de cobrado</a>
    </div>
</nav>
    <h1>🧑 Gestión de Clientes</h1>

    <?php if ($mensaje): ?>
    <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <h2><?= $clienteEditar ? "Editar Cliente" : "Agregar Cliente" ?></h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $clienteEditar["id"] ?? '' ?>">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" value="<?= htmlspecialchars($clienteEditar["nombre"] ?? '') ?>" required><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono" pattern="\d{10}" maxlength="10" value="<?= htmlspecialchars($clienteEditar["telefono"] ?? '') ?>" required><br><br>

        <input type="submit" value="<?= $clienteEditar ? "Actualizar" : "Agregar" ?>">
        <?php if ($clienteEditar): ?>
            <a href="clientes.php" style="margin-left: 10px;">Cancelar</a>
        <?php endif; ?>
    </form>

    <h2>Lista de Clientes</h2>
    <table>
        <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Acciones</th>
        </tr>
        <?php while ($fila = $clientes->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($fila["nombre"]) ?></td>
            <td><?= htmlspecialchars($fila["telefono"]) ?></td>
            <td class="acciones">
                <a href="?editar=<?= $fila["id"] ?>">✏️ Editar</a>
                <a href="?eliminar=<?= $fila["id"] ?>" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">🗑️ Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>
