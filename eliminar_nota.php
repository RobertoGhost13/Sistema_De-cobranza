<?php
include("conexion.php");

if (isset($_GET['id']) && isset($_GET['serie'])) {
    $id = $_GET['id'];
    $serie = $_GET['serie'];

    // Puedes eliminar también los abonos relacionados si deseas
    $conexion->query("DELETE FROM abonos WHERE nota_id=$id AND serie='$serie'");

    // Luego eliminar la nota
    $conexion->query("DELETE FROM notas WHERE id=$id AND serie='$serie'");

    header("Location: reportes.php");
    exit();
} else {
    echo "Parámetros inválidos.";
}
?>