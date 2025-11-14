<?php
require_once 'config_core.php';

// === AGREGAR SERVICIO ===
if (isset($_POST['agregar_servicio'])) {
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    if ($nombre && $precio >= 0) {
        $servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
        $servicios[] = [
            'id' => uniqid(),
            'nombre' => $nombre,
            'precio' => $precio
        ];
        file_put_contents('servicios.json', json_encode($servicios, JSON_PRETTY_PRINT));
    }
    header("Location: servicios.php"); exit;
}

// === GUARDAR EDICIÓN ===
if (isset($_POST['guardar_servicio'])) {
    $id = $_POST['edit_id'];
    $nombre = trim($_POST['edit_nombre']);
    $precio = floatval($_POST['edit_precio']);
    if ($nombre && $precio >= 0) {
        $servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
        foreach ($servicios as &$s) {
            if ($s['id'] === $id) {
                $s['nombre'] = $nombre;
                $s['precio'] = $precio;
                break;
            }
        }
        file_put_contents('servicios.json', json_encode($servicios, JSON_PRETTY_PRINT));
    }
    header("Location: servicios.php"); exit;
}

// === ELIMINAR SERVICIO ===
if (isset($_POST['eliminar_servicio'])) {
    $id = $_POST['edit_id'];
    $servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
    $servicios = array_filter($servicios, fn($s) => $s['id'] !== $id);
    $servicios = array_values($servicios);
    file_put_contents('servicios.json', json_encode($servicios, JSON_PRETTY_PRINT));
    header("Location: servicios.php"); exit;
}

$servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Servicios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?= NOMBRE_NEGOCIO ?></h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="productos.php">Productos</a>
            <a href="servicios.php" class="active">Servicios</a>
            <a href="clientes.php">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="reportes.php">Reportes</a>
            <a href="gastos.php">Gastos</a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <h2>Agregar Servicio</h2>
            <form method="post">
                <input type="text" name="nombre" placeholder="Nombre del servicio" required>
                <input type="number" name="precio" placeholder="Precio" step="0.01" min="0" required>
                <button type="submit" name="agregar_servicio" class="btn btn-add">Agregar</button>
            </form>
        </div>

        <div class="card">
            <h2>Lista de Servicios</h2>
            <table>
                <thead>
                    <tr>
                        <th>NOMBRE</th>
                        <th>PRECIO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servicios as $s): ?>
                    <tr>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= $s['id'] ?>">
                                <input type="text" name="edit_nombre" value="<?= htmlspecialchars($s['nombre']) ?>" required>
                        </td>
                        <td>
                                <input type="number" name="edit_precio" value="<?= $s['precio'] ?>" step="0.01" min="0" required>
                        </td>
                        <td>
                                <button type="submit" name="guardar_servicio" class="btn btn-save">Guardar</button>
                                <button type="submit" name="eliminar_servicio" class="btn btn-delete" onclick="return confirm('¿Eliminar <?= htmlspecialchars($s['nombre']) ?>?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>