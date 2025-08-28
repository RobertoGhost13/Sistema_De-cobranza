<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio - Sistema de Cobranza</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        /* Carrusel básico */
        .carousel {
            position: relative;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            overflow: hidden;
            border-radius: 10px;
        }

        .carousel-images {
            display: flex;
            width: 100%;
            transition: transform 0.5s ease-in-out;
        }

        .carousel-images img {
            width: 100%;
            flex-shrink: 0;
        }

        .carousel-buttons {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
        }

        .carousel-buttons button {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }

        footer {
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Cobranza</h1>

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

        <!-- Carrusel de fotos -->
        <div class="carousel">
            <div class="carousel-images" id="carousel-images">
                <img src="imagen/compuser.png" alt="Imagen 1">
                <img src="imagen/compuser2.png" alt="Imagen 2">
                <img src="imagen/compuser3.png" alt="Imagen 3">
            </div>
            <div class="carousel-buttons">
                <button onclick="prevSlide()">❮</button>
                <button onclick="nextSlide()">❯</button>
            </div>
        </div>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Compuser Valladolid.
    </footer>

    <script>
        let index = 0;
        const slides = document.querySelectorAll('#carousel-images img');
        const total = slides.length;

        function showSlide(i) {
            const carousel = document.getElementById('carousel-images');
            carousel.style.transform = 'translateX(' + (-i * 100) + '%)';
        }

        function nextSlide() {
            index = (index + 1) % total;
            showSlide(index);
        }

        function prevSlide() {
            index = (index - 1 + total) % total;
            showSlide(index);
        }

        // Auto-slide cada 5 segundos
        setInterval(nextSlide, 5000);
    </script>
</body>
</html>

<!-- Modificaciones

Añadir un boton para editar información en notas por cobrar

Opción de imprimir reporte generar pdf 
Debe contener el nombre y el monto de deuda restante del cliente

Eliminar correo

Añadir un recordatorio que 2 días antes a vencer el plazo salga una alerta

Mostrar saldo pendiente después de realizar un abono a tal nota y mostrar el restante-->

