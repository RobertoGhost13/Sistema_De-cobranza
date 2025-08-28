<?php
include("conexion.php");

$error = "";
$id = "";
$serie = "";
$cliente_id = "";
$fecha = date('Y-m-d');
$plazo = "";
$importe = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_GET["editar"])) {
    $serie = $_POST["serie"];
    $cliente_id = $_POST["cliente_id"];
    $fecha = $_POST["fecha"];
    $plazo = (int) $_POST["plazo"]; 
    $importe = (int) $_POST["importe"]; 
    $id = (int) $_POST["id"];

    // Verificar si ya existe el folio (id) en esa serie
    $verifica_sql = "SELECT COUNT(*) AS total FROM notas WHERE id = ? AND serie = ?";
    $stmt_verifica = $conexion->prepare($verifica_sql);
    $stmt_verifica->bind_param("is", $id, $serie);
    $stmt_verifica->execute();
    $resultado = $stmt_verifica->get_result()->fetch_assoc();

    if ($resultado['total'] > 0) {
        $error = "⚠️ Ya existe una nota con folio $serie$id. Por favor elige otro número.";
    } else {
        $sql = "INSERT INTO notas (id, serie, cliente_id, fecha, plazo, importe, estado) VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isssii", $id, $serie, $cliente_id, $fecha, $plazo, $importe);
        $stmt->execute();
        header("Location: notas.php");
        exit();
    }
}

$clientes = $conexion->query("SELECT id, nombre FROM clientes");

$sqlNotas = "
SELECT n.id, n.serie, c.nombre, n.fecha, n.plazo, n.importe,
       IFNULL(SUM(a.cantidad), 0) as total_abonado,
       CASE
         WHEN IFNULL(SUM(a.cantidad), 0) >= n.importe THEN 'Pagada'
         WHEN DATE_ADD(n.fecha, INTERVAL n.plazo DAY) < CURDATE() THEN 'Vencida'
         ELSE 'Pendiente'
       END AS estado
FROM notas n
LEFT JOIN clientes c ON n.cliente_id = c.id
LEFT JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
GROUP BY n.id, n.serie, c.nombre, n.fecha, n.plazo, n.importe
ORDER BY n.fecha DESC
";

$notas = $conexion->query($sqlNotas);

// Preparar mensajes para alertas JavaScript
$alertas_js = [];
while ($nota = $notas->fetch_assoc()) {
    $fechaVencimiento = date_create($nota['fecha']);
    date_add($fechaVencimiento, date_interval_create_from_date_string($nota['plazo'] . ' days'));
    $diasParaVencer = (int)date_diff(date_create(), $fechaVencimiento)->format('%r%a');
    if ($diasParaVencer >= 0 && $diasParaVencer <= 2 && $nota['estado'] === 'Pendiente') {
        $msg = "La nota con folio {$nota['serie']}{$nota['id']} del cliente {$nota['nombre']} vence en $diasParaVencer día(s). ¡Haz un seguimiento!";
        $alertas_js[] = addslashes($msg);
    }
}
$notas->data_seek(0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notas por Cobrar</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<div class="container">
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
    <h1>📄 Notas por Cobrar</h1>

    <?php if ($error): ?>
        <div class="error-message" style="color: red; font-weight: bold; margin-bottom: 1em;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="notas.php">
        <label for="serie">Serie (Tipo de folio):</label>
        <select name="serie" required>
            <option value="">-- Seleccionar Folio --</option>
            <option value="F-" <?= ($serie === 'F-') ? 'selected' : '' ?>>F</option>
        </select>

        <label for="id">Número de Folio:</label>
        <input type="number" name="id" min="1" step="1" required value="<?= htmlspecialchars($id) ?>">

        <label for="cliente_id">Cliente:</label>
        <select name="cliente_id" required>
            <?php
            $clientes->data_seek(0);
            while($cliente = $clientes->fetch_assoc()):
            ?>
                <option value="<?= $cliente['id'] ?>" <?= ($cliente_id == $cliente['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cliente['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="fecha">Fecha:</label>
        <input type="date" name="fecha" required value="<?= htmlspecialchars($fecha) ?>">

        <label for="plazo">Plazo (días):</label>
        <input type="number" name="plazo" min="1" step="1" required value="<?= htmlspecialchars($plazo) ?>">

        <label for="importe">Importe:</label>
        <input type="number" name="importe" min="0" step="1" required value="<?= htmlspecialchars($importe) ?>">

        <input type="submit" value="Registrar Nota">
    </form>

    <table border="1" width="100%">
        <thead>
        <tr>
            <th>ID</th>
            <th>Serie</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th>Plazo</th>
            <th>Importe</th>
            <th>Total Abonado</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($nota = $notas->fetch_assoc()): ?>
            <tr>
                <td><?= $nota['id'] ?></td>
                <td><?= htmlspecialchars($nota['serie']) ?></td>
                <td><?= htmlspecialchars($nota['nombre']) ?></td>
                <td><?= htmlspecialchars($nota['fecha']) ?></td>
                <td><?= $nota['plazo'] ?></td>
                <td>$<?= number_format($nota['importe'], 0) ?></td>
                <td>$<?= number_format($nota['total_abonado'], 0) ?></td>
                <td class="estado-<?= htmlspecialchars($nota['estado']) ?>"><?= htmlspecialchars($nota['estado']) ?></td>
                <td>
                    <a href="editar_nota.php?id=<?= $nota['id'] ?>&serie=<?= urlencode($nota['serie']) ?>">✏️ Editar</a>
                    <?php if ($nota['estado'] === 'Pagada'): ?>
                        | <a href="eliminar_nota.php?id=<?= $nota['id'] ?>&serie=<?= urlencode($nota['serie']) ?>" onclick="return confirm('¿Seguro que deseas eliminar esta nota?')">🗑️ Eliminar</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php if (count($alertas_js) > 0): ?>
<script>
    <?php foreach ($alertas_js as $mensaje): ?>
        alert("<?= $mensaje ?>");
    <?php endforeach; ?>
</script>
<?php endif; ?>

<footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>
