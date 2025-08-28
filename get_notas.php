<?php
include("conexion.php");
if (isset($_GET['cliente_id'])) {
    $cliente_id = intval($_GET['cliente_id']);
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
    $result = $stmt->get_result();
    $notas = [];
    while ($row = $result->fetch_assoc()) {
        $notas[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($notas);
}
?>