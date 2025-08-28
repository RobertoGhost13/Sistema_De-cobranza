<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include("conexion.php");

$sql = "
SELECT n.serie, n.id, c.nombre, c.telefono, n.fecha, 
       n.importe - IFNULL(SUM(a.cantidad), 0) AS deuda_restante
FROM notas n
JOIN clientes c ON n.cliente_id = c.id
LEFT JOIN abonos a ON n.id = a.nota_id AND n.serie = a.serie
GROUP BY n.id, n.serie, c.nombre, c.telefono, n.fecha
HAVING deuda_restante > 0
ORDER BY n.fecha DESC
";

$result = $conexion->query($sql);

$total_deuda = 0;

$html = '
<style>
    body { 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
        font-size: 13px; 
        color: #333;
        margin: 0 30px;
    }
    .header {
        display: flex;
        justify-content: space-between; /* espacio entre logo e info */
        align-items: center;
        border-bottom: 3px solid #ff6600; /* color naranja */
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .company-info {
        text-align: left;
        line-height: 1.4;
        color: #555;
        font-size: 12px;
        max-width: 300px;
    }
    .company-info strong {
        font-size: 16px;
        color: #ff6600;
        letter-spacing: 1px;
    }
    .logo {
        max-width: 120px;
    }
    .logo img {
        width: 100%;
        height: auto;
    }
    h2 {
        color: #ff6600;
        text-align: center;
        margin: 20px 0 10px 0;
        font-weight: 700;
        letter-spacing: 1.5px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    th, td {
        padding: 8px 12px;
        border: 1px solid #ddd;
    }
    th {
        background-color: #ff6600;
        color: white;
        text-transform: uppercase;
        font-weight: 600;
        font-size: 13px;
    }
    tbody tr:hover {
        background-color: #fff2e6;
    }
    .total-row td {
        background-color: #ffe6cc;
        font-weight: 700;
        font-size: 14px;
        text-align: right;
    }
    .total-row td:first-child {
        text-align: right;
        padding-right: 12px;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        margin-top: 40px;
        color: #999;
        font-style: italic;
    }
</style>

<div class="header">
    <div class="company-info">
        <strong>Compuser Valladolid</strong><br>
        Calle 49 x 40 y 42, Valladolid, Yucatán<br>
        Col. San Juan C.P 97780<br>
        Tel: (985) 111 2976<br>
        RFC: CAAM7412129G0<br>
        CURP: CAAM741212HYNRLNOO
    </div>
</div> <br>

<h2>Reporte de Deudas Pendientes</h2>

<table>
    <thead>
        <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th>Fecha de Compra</th>
            <th>Deuda Restante</th>
        </tr>
    </thead>
    <tbody>
';

while ($row = $result->fetch_assoc()) {
    $folio = $row['serie'] . $row['id'];
    $deuda = $row['deuda_restante'];
    $total_deuda += $deuda;

    $html .= "<tr>
                <td>" . htmlspecialchars($folio) . "</td>
                <td>" . htmlspecialchars($row['nombre']) . "</td>
                <td>" . htmlspecialchars($row['telefono']) . "</td>
                <td>" . htmlspecialchars($row['fecha']) . "</td>
                <td>$" . number_format($deuda, 2) . "</td>
              </tr>";
}

$html .= '
        <tr class="total-row">
            <td colspan="4">TOTAL GENERAL:</td>
            <td>$' . number_format($total_deuda, 2) . '</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    © ' . date('Y') . ' Compuser Valladolid. Todos los derechos reservados.
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_deudas.pdf", ["Attachment" => false]);
?>