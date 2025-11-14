<?php
require_once 'config_core.php';

// === AGREGAR PRODUCTO ===
if (isset($_POST['agregar_producto'])) {
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock'] ?? 0);
    if ($nombre && $precio >= 0) {
        $productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
        $productos[] = [
            'id' => uniqid(),
            'nombre' => $nombre,
            'precio' => $precio,
            'stock' => $stock  // ← STOCK GUARDADO
        ];
        file_put_contents('productos.json', json_encode($productos, JSON_PRETTY_PRINT));
    }
    header("Location: productos.php"); exit;
}

// === GUARDAR EDICIÓN ===
if (isset($_POST['guardar_producto'])) {
    $id = $_POST['edit_id'];
    $nombre = trim($_POST['edit_nombre']);
    $precio = floatval($_POST['edit_precio']);
    $stock = intval($_POST['edit_stock'] ?? 0);
    if ($nombre && $precio >= 0) {
        $productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
        foreach ($productos as &$p) {
            if ($p['id'] === $id) {
                $p['nombre'] = $nombre;
                $p['precio'] = $precio;
                $p['stock'] = $stock;  // ← STOCK ACTUALIZADO
                break;
            }
        }
        file_put_contents('productos.json', json_encode($productos, JSON_PRETTY_PRINT));
    }
    header("Location: productos.php"); exit;
}

// === ELIMINAR PRODUCTO ===
if (isset($_POST['eliminar_producto'])) {
    $id = $_POST['edit_id'];
    $productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
    $productos = array_filter($productos, fn($p) => $p['id'] !== $id);
    $productos = array_values($productos);
    file_put_contents('productos.json', json_encode($productos, JSON_PRETTY_PRINT));
    header("Location: productos.php"); exit;
}

$productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Productos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><?= NOMBRE_NEGOCIO ?></h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="productos.php" class="active">Productos</a>
            <a href="servicios.php">Servicios</a>
            <a href="clientes.php">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="reportes.php">Reportes</a>
            <a href="gastos.php">Gastos</a>
        </nav>
    </header>

    <div class="container">
        <div class="card">
            <h2>Agregar Producto</h2>
            <form method="post">
                <input type="text" name="nombre" placeholder="Nombre del producto" required>
                <input type="number" name="precio" placeholder="Precio" step="0.01" min="0" required>
                <input type="number" name="stock" placeholder="Stock inicial" min="0" value="0" required>
                <button type="submit" name="agregar_producto" class="btn btn-add">Agregar</button>
            </form>
        </div>

        <div class="card">
            <h2>Lista de Productos</h2>
            <table>
                <thead>
                    <tr>
                        <th>NOMBRE</th>
                        <th>PRECIO</th>
                        <th>STOCK</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="edit_id" value="<?= $p['id'] ?>">
                                <input type="text" name="edit_nombre" value="<?= htmlspecialchars($p['nombre']) ?>" required>
                        </td>
                        <td>
                                <input type="number" name="edit_precio" value="<?= $p['precio'] ?>" step="0.01" min="0" required>
                        </td>
                        <td>
                                <input type="number" name="edit_stock" value="<?= $p['stock'] ?? 0 ?>" min="0" required style="width:60px;">
                        </td>
                        <td>
                                <button type="submit" name="guardar_producto" class="btn btn-save">Guardar</button>
                                <button type="submit" name="eliminar_producto" class="btn btn-delete" onclick="return confirm('¿Eliminar?');">Eliminar</button>
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