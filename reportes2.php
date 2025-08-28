<?php
include("conexion.php");

// Obtener lista clientes para filtros
$clientes = $conexion->query("SELECT id, nombre FROM clientes");

// Variables de filtro
$resumen_cliente_filtro = $_GET['resumen_cliente_id'] ?? '';
$mes_filtro = $_GET['mes_filtro'] ?? '';

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

// Filtro por cliente
if ($resumen_cliente_filtro !== '') {
    $sql_resumen .= " AND n.cliente_id = ? ";
    $types_resumen .= "i";
    $params_resumen[] = $resumen_cliente_filtro;
}

// Filtro por mes (formato YYYY-MM)
if ($mes_filtro !== '') {
    $sql_resumen .= " AND DATE_FORMAT(n.fecha, '%Y-%m') = ? ";
    $types_resumen .= "s";
    $params_resumen[] = $mes_filtro;
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
        select, input[type="date"], input[type="month"], input[type="text"] {
            padding: 5px;
            margin-right: 15px;
        }
        input[type="submit"], button {
            background: #ff6600;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
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
<h2>💰 Total cobrado por mes y cliente</h2>

<!-- Formulario de filtros -->
<form method="GET" action="reportes2.php" style="margin-bottom:15px;">
    <label>Cliente:</label>
    <select name="resumen_cliente_id">
        <option value="">-- Todos --</option>
        <?php
        $clientes->data_seek(0);
        while ($c = $clientes->fetch_assoc()):
        ?>
            <option value="<?= $c['id'] ?>" <?= ($resumen_cliente_filtro == $c['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Mes:</label>
    <input type="month" name="mes_filtro" value="<?= htmlspecialchars($mes_filtro) ?>">

    <button type="submit">Filtrar</button>
</form>

<!-- Tabla resumen -->
<table border="1" cellpadding="5">
    <thead>
        <tr>
            <th>Cliente</th>
            <th>Mes</th>
            <th>Total Cobrado</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $total_general = 0;
        $total_mes = 0;
        $mes_actual = '';

        while ($r = $resumen->fetch_assoc()):
            if ($mes_actual != '' && $mes_actual != $r['mes']) {
                echo "<tr style='font-weight:bold;background:#f0f0f0;'>
                        <td colspan='2'>Subtotal $mes_actual</td>
                        <td>$" . number_format($total_mes, 2) . "</td>
                      </tr>";
                $total_mes = 0;
            }

            $mes_actual = $r['mes'];
            $total_mes += $r['total_cobrado'];
            $total_general += $r['total_cobrado'];
        ?>
            <tr>
                <td><?= htmlspecialchars($r['cliente']) ?></td>
                <td><?= $r['mes'] ?></td>
                <td>$<?= number_format($r['total_cobrado'], 2) ?></td>
            </tr>
        <?php endwhile; ?>

        <?php if ($mes_actual != ''): ?>
            <tr style='font-weight:bold;background:#f0f0f0;'>
                <td colspan='2'>Subtotal <?= $mes_actual ?></td>
                <td>$<?= number_format($total_mes, 2) ?></td>
            </tr>
        <?php endif; ?>

        <tr style='font-weight:bold;background:#ddd;'>
            <td colspan='2'>TOTAL GENERAL</td>
            <td>$<?= number_format($total_general, 2) ?></td>
        </tr>
    </tbody>
</table>


<footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>
