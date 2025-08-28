<?php
include("conexion.php");

// Obtener lista clientes para filtros
$clientes = $conexion->query("SELECT id, nombre FROM clientes");

// Variables de filtro
$cliente_filtro = $_GET['cliente_id'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Consulta base
$sql = "
SELECT n.id, n.serie, c.nombre AS cliente, n.fecha, n.plazo, n.importe,
       IFNULL(SUM(a.cantidad),0) AS total_abonado,
       (n.importe - IFNULL(SUM(a.cantidad),0)) AS pendiente,
       CASE
         WHEN IFNULL(SUM(a.cantidad),0) >= n.importe THEN 'Pagada'
         WHEN DATE_ADD(n.fecha, INTERVAL n.plazo DAY) < CURDATE() THEN 'Vencida'
         ELSE 'Pendiente'
       END AS estado
FROM notas n
JOIN clientes c ON n.cliente_id = c.id
LEFT JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
WHERE 1=1
";

// Parámetros para bind_param
$params = [];
$types = "";

// Filtro por cliente
if ($cliente_filtro !== '') {
    $sql .= " AND n.cliente_id = ? ";
    $types .= "i";
    $params[] = $cliente_filtro;
}

// Filtro por fechas
if ($fecha_inicio !== '') {
    $sql .= " AND n.fecha >= ? ";
    $types .= "s";
    $params[] = $fecha_inicio;
}
if ($fecha_fin !== '') {
    $sql .= " AND n.fecha <= ? ";
    $types .= "s";
    $params[] = $fecha_fin;
}

$sql .= " GROUP BY n.id, n.serie, c.nombre, n.fecha, n.plazo, n.importe";

// Filtro por estado
if ($estado_filtro !== '') {
    if ($estado_filtro == "activos") {
        $sql .= " HAVING estado = 'Pendiente'";
    } elseif ($estado_filtro == "vencidos") {
        $sql .= " HAVING estado = 'Vencida'";
    } elseif ($estado_filtro == "pagadas") {
        $sql .= " HAVING estado = 'Pagada'";
    }
} else {
    // Si no se filtra, ocultar las pagadas
    $sql .= " HAVING estado != 'Pagada'";
}

$sql .= " ORDER BY n.fecha DESC";

$stmt = $conexion->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Consulta para resumen mensual
$sql_resumen = "
SELECT 
    c.nombre AS cliente,
    DATE_FORMAT(n.fecha, '%Y-%m') AS mes,
    SUM(a.cantidad) AS total_cobrado
FROM notas n
JOIN clientes c ON n.cliente_id = c.id
JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
WHERE 1=1
";

$params_resumen = [];
$types_resumen = "";

if ($cliente_filtro !== '') {
    $sql_resumen .= " AND n.cliente_id = ? ";
    $types_resumen .= "i";
    $params_resumen[] = $cliente_filtro;
}
if ($fecha_inicio !== '') {
    $sql_resumen .= " AND n.fecha >= ? ";
    $types_resumen .= "s";
    $params_resumen[] = $fecha_inicio;
}
if ($fecha_fin !== '') {
    $sql_resumen .= " AND n.fecha <= ? ";
    $types_resumen .= "s";
    $params_resumen[] = $fecha_fin;
}

$sql_resumen .= " GROUP BY c.nombre, mes ORDER BY mes DESC, c.nombre ASC";

$stmt_resumen = $conexion->prepare($sql_resumen);
if ($types_resumen !== '') {
    $stmt_resumen->bind_param($types_resumen, ...$params_resumen);
}
$stmt_resumen->execute();
$resumen = $stmt_resumen->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Sistema de Cobranza</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        form {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        label {
            margin-right: 10px;
            font-weight: bold;
        }
        select, input[type="date"], input[type="text"] {
            padding: 5px;
            margin-right: 15px;
        }
        input[type="submit"] {
            background: #ff6600;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background: #e65c00;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #ff6600;
            color: white;
        }
        .estado-Pendiente { color: #ff6600; font-weight: bold; }
        .estado-Vencida { color: red; font-weight: bold; }
        .estado-Pagada { color: green; font-weight: bold; }
    </style>
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
<h1>📊 Reportes</h1>

<form method="GET" action="reportes.php">
    <label for="buscar_cliente">Buscar cliente:</label>
    <input type="text" id="buscar_cliente" placeholder="Escribe para buscar...">

    <select name="cliente_id" id="cliente_id">
        <option value="">-- Todos --</option>
        <?php
        $clientes->data_seek(0);
        while ($cliente = $clientes->fetch_assoc()): ?>
            <option value="<?= $cliente['id'] ?>" <?= ($cliente_filtro == $cliente['id']) ? "selected" : "" ?>>
                <?= htmlspecialchars($cliente['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="estado">Estado:</label>
    <select name="estado" id="estado">
        <option value="">-- Todos --</option>
        <option value="activos" <?= ($estado_filtro == "activos") ? "selected" : "" ?>>Activos</option>
        <option value="vencidos" <?= ($estado_filtro == "vencidos") ? "selected" : "" ?>>Vencidos</option>
        <option value="pagadas" <?= ($estado_filtro == "pagadas") ? "selected" : "" ?>>Pagadas</option>
    </select>

    <label for="fecha_inicio">Desde:</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">

    <label for="fecha_fin">Hasta:</label>
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">

    <input type="submit" value="Filtrar">
</form>

<script>
document.getElementById('buscar_cliente').addEventListener('keyup', function() {
    var filtro = this.value.toLowerCase();
    var opciones = document.getElementById('cliente_id').options;
    for (var i = 0; i < opciones.length; i++) {
        var texto = opciones[i].text.toLowerCase();
        opciones[i].style.display = texto.includes(filtro) ? '' : 'none';
    }
});
</script>

<!-- Tabla principal -->
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Serie</th>
        <th>Cliente</th>
        <th>Fecha</th>
        <th>Plazo (días)</th>
        <th>Importe</th>
        <th>Total Abonado</th>
        <th>Saldo Pendiente</th>
        <th>Estado</th>
        <th>Tiempo restante</th>
    </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['serie'] ?></td>
            <td><?= htmlspecialchars($row['cliente']) ?></td>
            <td><?= $row['fecha'] ?></td>
            <td><?= $row['plazo'] ?></td>
            <td>$<?= number_format($row['importe'], 2) ?></td>
            <td>$<?= number_format($row['total_abonado'], 2) ?></td>
            <td>$<?= number_format($row['pendiente'], 2) ?></td>
            <td class="estado-<?= $row['estado'] ?>"><?= $row['estado'] ?></td>
            <td>
                <?php
                    $fecha_vencimiento = date('Y-m-d', strtotime($row['fecha'] . " +{$row['plazo']} days"));
                    $hoy = date('Y-m-d');
                    $diferencia = (strtotime($fecha_vencimiento) - strtotime($hoy)) / (60 * 60 * 24);

                    if ($row['estado'] === 'Pagada') {
                        echo "✔️ Pagada";
                    } elseif ($diferencia < 0) {
                        echo "❌ Vencida hace " . abs($diferencia) . " días";
                    } else {
                        if ($diferencia <= 2) {
                            echo "<span style='color: red; font-weight: bold;'>⚠️ ¡Quedan $diferencia días!</span>";
                        } else {
                            echo "$diferencia días restantes";
                        }
                    }
                ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<form action="generar_pdf.php" method="post" target="_blank">
    <button type="submit" style="background:#ff6600;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;">
        🖨️ Imprimir reporte PDF
    </button>
</form>

<footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>
