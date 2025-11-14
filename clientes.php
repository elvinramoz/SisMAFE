<?php
require_once 'config_core.php';

// === AGREGAR CLIENTE ===
if (isset($_POST['agregar_cliente'])) {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    if ($nombre) {
        $clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
        $clientes[] = [
            'id' => uniqid(),
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono
        ];
        file_put_contents('clientes.json', json_encode($clientes, JSON_PRETTY_PRINT));
    }
    header("Location: clientes.php"); exit;
}

// === GUARDAR EDICIÓN ===
if (isset($_POST['guardar_cliente'])) {
    $id = $_POST['edit_id'];
    $nombre = trim($_POST['edit_nombre']);
    $email = trim($_POST['edit_email']);
    $telefono = trim($_POST['edit_telefono']);
    if ($nombre) {
        $clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
        foreach ($clientes as &$c) {
            if ($c['id'] === $id) {
                $c['nombre'] = $nombre;
                $c['email'] = $email;
                $c['telefono'] = $telefono;
                break;
            }
        }
        file_put_contents('clientes.json', json_encode($clientes, JSON_PRETTY_PRINT));
    }
    header("Location: clientes.php"); exit;
}

// === ELIMINAR CLIENTE ===
if (isset($_POST['eliminar_cliente'])) {
    $id = $_POST['edit_id'];
    $clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
    $clientes = array_filter($clientes, fn($c) => $c['id'] !== $id);
    $clientes = array_values($clientes);
    file_put_contents('clientes.json', json_encode($clientes, JSON_PRETTY_PRINT));
    header("Location: clientes.php"); exit;
}

$clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Clientes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?= NOMBRE_NEGOCIO ?></h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="productos.php">Productos</a>
            <a href="servicios.php">Servicios</a>
            <a href="clientes.php" class="active">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="reportes.php">Reportes</a>
            <a href="gastos.php">Gastos</a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <h2>Agregar Cliente</h2>
            <form method="post">
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="email" name="email" placeholder="Email (opcional)">
                <input type="text" name="telefono" placeholder="Teléfono (opcional)">
                <button type="submit" name="agregar_cliente" class="btn btn-add">Agregar</button>
            </form>
        </div>

        <div class="card">
            <h2>Lista de Clientes</h2>
            <table>
                <thead>
                    <tr>
                        <th>NOMBRE</th>
                        <th>EMAIL</th>
                        <th>TELÉFONO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= $c['id'] ?>">
                                <input type="text" name="edit_nombre" value="<?= htmlspecialchars($c['nombre']) ?>" required>
                        </td>
                        <td>
                                <input type="email" name="edit_email" value="<?= htmlspecialchars($c['email']) ?>">
                        </td>
                        <td>
                                <input type="text" name="edit_telefono" value="<?= htmlspecialchars($c['telefono']) ?>">
                        </td>
                        <td>
                                <button type="submit" name="guardar_cliente" class="btn btn-save">Guardar</button>
                                <button type="submit" name="eliminar_cliente" class="btn btn-delete" onclick="return confirm('¿Eliminar <?= htmlspecialchars($c['nombre']) ?>?');">Eliminar</button>
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