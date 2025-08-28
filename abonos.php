<?php
include("conexion.php");

// Obtener clientes para selector
$clientes = $conexion->query("SELECT id, nombre FROM clientes");

// Función para obtener notas pendientes de un cliente
function obtenerNotasPendientes($conexion, $cliente_id) {
    $sqlNotas = "
    SELECT n.id, n.serie, n.importe,
        IFNULL(SUM(a.cantidad), 0) as total_abonado
    FROM notas n
    LEFT JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
    WHERE n.cliente_id = ? 
    GROUP BY n.id, n.serie, n.importe
    HAVING total_abonado < n.importe
    ";
    $stmt = $conexion->prepare($sqlNotas);
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Registrar abono
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cliente_id = (int) $_POST["cliente_id"];
    $nota_id = (int) $_POST["nota_id"];
    $serie = $_POST["serie"];
    $cantidad = (float) $_POST["cantidad"]; 
    $fecha = $_POST["fecha"];

    // Verificar importe pendiente
    $sqlPendiente = "
    SELECT n.importe, IFNULL(SUM(a.cantidad), 0) AS total_abonado
    FROM notas n
    LEFT JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
    WHERE n.id = ? AND n.serie = ?
    GROUP BY n.importe
    ";
    $stmt = $conexion->prepare($sqlPendiente);
    $stmt->bind_param("is", $nota_id, $serie);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        die("❌ Nota no encontrada.");
    }

    $restante = (float) $row['importe'] - (float) $row['total_abonado'];

    if ($cantidad > $restante) {
        die("❌ Error: El abono ($cantidad) excede el importe pendiente de la nota ($restante).");
    }

    $sql = "INSERT INTO abonos (cliente_id, nota_id, serie, cantidad, fecha) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iisis", $cliente_id, $nota_id, $serie, $cantidad, $fecha);
    $stmt->execute();

    header("Location: abonos.php");
    exit();
}

// Consulta para mostrar abonos con saldo pendiente
$sqlAbonos = "
SELECT a.id, c.nombre as cliente, a.nota_id, a.serie, a.cantidad, a.fecha,
       n.importe - IFNULL(SUM(a2.cantidad), 0) AS pendiente
FROM abonos a
JOIN clientes c ON a.cliente_id = c.id
JOIN notas n ON a.nota_id = n.id AND a.serie = n.serie
LEFT JOIN abonos a2 ON n.id = a2.nota_id AND n.serie = a2.serie
GROUP BY a.id, c.nombre, a.nota_id, a.serie, a.cantidad, a.fecha, n.importe
ORDER BY a.fecha DESC
";
$abonos = $conexion->query($sqlAbonos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Abonos</title>
    <link rel="stylesheet" href="css/estilos.css">
    <script>
        let notasPendientes = [];

        async function cargarNotas(clienteId) {
            const response = await fetch('get_notas.php?cliente_id=' + clienteId);
            notasPendientes = await response.json();

            const selectNotas = document.getElementById('nota_id');
            const selectSerie = document.getElementById('serie');
            selectNotas.innerHTML = '<option value="">-- Seleccionar Nota --</option>';
            selectSerie.value = '';

            notasPendientes.forEach(nota => {
                let option = document.createElement('option');
                option.value = nota.id;
                option.text = 'ID ' + nota.id + ' Serie ' + nota.serie + ' Importe: $' + parseFloat(nota.importe).toFixed(2);
                option.dataset.serie = nota.serie;
                option.dataset.importe = parseFloat(nota.importe);
                option.dataset.total_abonado = parseFloat(nota.total_abonado);
                selectNotas.appendChild(option);
            });

            // Mostrar saldo total pendiente del cliente
            let saldoTotal = 0;
            notasPendientes.forEach(n => {
                saldoTotal += (parseFloat(n.importe) - parseFloat(n.total_abonado));
            });
            document.getElementById('saldo-cliente').textContent = saldoTotal.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('cliente_id').addEventListener('change', function() {
                cargarNotas(this.value);
                document.getElementById('info-nota').style.display = 'none';
            });

            document.getElementById('nota_id').addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const serie = selectedOption.dataset.serie || '';
                const importe = parseFloat(selectedOption.dataset.importe || 0);
                const abonado = parseFloat(selectedOption.dataset.total_abonado || 0);
                const restante = importe - abonado;

                document.getElementById('serie').value = serie;
                document.getElementById('importe-total').textContent = importe.toFixed(2);
                document.getElementById('total-abonado').textContent = abonado.toFixed(2);
                document.getElementById('pendiente').textContent = restante.toFixed(2);
                document.getElementById('info-nota').style.display = 'block';

                const inputCantidad = document.getElementById('cantidad');
                inputCantidad.max = restante;
                inputCantidad.placeholder = "Máximo: $" + restante.toFixed(2);
            });

            document.querySelector("form").addEventListener("submit", function(e) {
                const cantidad = parseFloat(document.getElementById('cantidad').value || 0);
                const notaSelect = document.getElementById('nota_id');
                const selectedOption = notaSelect.options[notaSelect.selectedIndex];

                const importe = parseFloat(selectedOption.dataset.importe || 0);
                const abonado = parseFloat(selectedOption.dataset.total_abonado || 0);
                const restante = importe - abonado;

                if (cantidad > restante) {
                    alert("❌ La cantidad excede el importe pendiente de la nota.\nPendiente: $" + restante.toFixed(2));
                    e.preventDefault();
                }
            });
        });
    </script>
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

<h1>Registro de Abonos</h1>
<form method="POST" action="abonos.php">
    <label for="cliente_id">Cliente:</label>
    <select name="cliente_id" id="cliente_id" required>
        <option value="">-- Seleccionar Cliente --</option>
        <?php while($cliente = $clientes->fetch_assoc()): ?>
            <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre']) ?></option>
        <?php endwhile; ?>
    </select>
    <p><strong>Saldo Total Pendiente del Cliente:</strong> $<span id="saldo-cliente">0.00</span></p>

    <label for="nota_id">Nota:</label>
    <select name="nota_id" id="nota_id" required>
        <option value="">-- Seleccionar Nota --</option>
    </select>

    <div id="info-nota" style="margin-bottom: 15px; display: none;">
        <p><strong>Importe Total:</strong> $<span id="importe-total">0.00</span></p>
        <p><strong>Total Abonado:</strong> $<span id="total-abonado">0.00</span></p>
        <p><strong>Pendiente:</strong> $<span id="pendiente">0.00</span></p>
    </div>

    <input type="hidden" name="serie" id="serie" required>

    <label for="cantidad">Cantidad:</label>
    <input type="number" name="cantidad" id="cantidad" min="0" step="0.01" required> 

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" required value="<?= date('Y-m-d') ?>">

    <input type="submit" value="Registrar Abono">
</form>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Nota ID</th>
            <th>Serie</th>
            <th>Cantidad</th>
            <th>Fecha</th>
            <th>Pendiente</th>
        </tr>
    </thead>
    <tbody>
    <?php while($abono = $abonos->fetch_assoc()): ?>
        <tr>
            <td><?= $abono['id'] ?></td>
            <td><?= htmlspecialchars($abono['cliente']) ?></td>
            <td><?= $abono['nota_id'] ?></td>
            <td><?= $abono['serie'] ?></td>
            <td>$<?= number_format((float)$abono['cantidad'], 2) ?></td> 
            <td><?= $abono['fecha'] ?></td>
            <td>$<?= number_format((float)$abono['pendiente'], 2) ?></td> 
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<footer>
    &copy; <?= date("Y") ?> Compuser Valladolid.
</footer>
</body>
</html>
