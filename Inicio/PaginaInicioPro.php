<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Bienestar Ancud - Acceso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #2c3e50;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .card {
            background: #34495e;
            border-radius: 14px;
            padding: 30px;
            width: 400px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-lg {
            padding: 12px 30px;
            font-weight: bold;
        }

        h1,
        p {
            color: white;
        }
    </style>
</head>

<body>
    <div class="card text-center">
        <h1 class="mb-5">Bienestar Ancud</h1>
        <p class="mb-4">Selecciona el modo de acceso:</p>
        <div class="d-flex justify-content-center gap-4">
            <a href="InicioAdmin.php?tipo=admin" class="btn btn-danger btn-lg">Administrador</a>
            <a href="InicioAdmin.php?tipo=usuario" class="btn btn-primary btn-lg">Usuario</a>
        </div>
    </div>
</body>

</html>