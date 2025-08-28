<?php
include("conexion.php");

$id = $_GET['id'] ?? null;
$serie = $_GET['serie'] ?? null;

if (!$id || !$serie) {
    header("Location: notas.php");
    exit;
}

$sql = "SELECT * FROM notas WHERE id = ? AND serie = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("is", $id, $serie);
$stmt->execute();
$nota = $stmt->get_result()->fetch_assoc();

if (!$nota) {
    echo "Nota no encontrada";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = $_POST["cliente_id"];
    $fecha = $_POST["fecha"];
    $plazo = $_POST["plazo"];
    $importe = $_POST["importe"];

    $sqlUpdate = "UPDATE notas SET cliente_id = ?, fecha = ?, plazo = ?, importe = ? WHERE id = ? AND serie = ?";
    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->bind_param("issdis", $cliente_id, $fecha, $plazo, $importe, $id, $serie);
    $stmtUpdate->execute();

    header("Location: notas.php");
    exit;
}

$clientes = $conexion->query("SELECT id, nombre FROM clientes");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Nota</title>
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
    </div>
</nav>
<h1>✏️ Editar Nota <?= $serie . "-" . $id ?></h1>
<form method="POST">
    <label for="cliente_id">Cliente:</label>
    <select name="cliente_id" required>
        <?php while($cliente = $clientes->fetch_assoc()): ?>
            <option value="<?= $cliente['id'] ?>" <?= $cliente['id'] == $nota['cliente_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cliente['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" value="<?= $nota['fecha'] ?>" required>

    <label for="plazo">Plazo (días):</label>
    <input type="number" name="plazo" value="<?= $nota['plazo'] ?>" required>

    <label for="importe">Importe:</label>
    <input type="number" name="importe" step="0.01" value="<?= $nota['importe'] ?>" required>

    <input type="submit" value="Actualizar Nota">
    <a href="notas.php">Cancelar</a>
</form>

<footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>