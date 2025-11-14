<?php
require_once 'config_core.php';

$gastos = json_decode(@file_get_contents('gastos.json'), true) ?: [];
$categorias = ['Energía', 'Internet', 'Proveedores', 'Sueldos', 'Otros'];

// === GUARDAR GASTO ===
if ($_POST['accion'] ?? '' === 'guardar') {
    $nuevo = [
        'id' => uniqid(),
        'fecha' => $_POST['fecha'],
        'categoria' => $_POST['categoria'],
        'descripcion' => $_POST['descripcion'],
        'monto' => (float)$_POST['monto']
    ];
    $gastos[] = $nuevo;
    file_put_contents('gastos.json', json_encode($gastos, JSON_PRETTY_PRINT));
    $mensaje = "Gasto registrado.";
}

// === ELIMINAR GASTO ===
if ($_POST['eliminar'] ?? '') {
    $id = $_POST['eliminar'];
    $gastos = array_filter($gastos, fn($g) => $g['id'] !== $id);
    $gastos = array_values($gastos);
    file_put_contents('gastos.json', json_encode($gastos, JSON_PRETTY_PRINT));
    $mensaje = "Gasto eliminado.";
}

// === RESUMEN MENSUAL ===
$mes_actual = date('Y-m');
$total_gastos_mes = 0;
$gastos_mes = [];
foreach ($gastos as $g) {
    if (substr($g['fecha'], 0, 7) === $mes_actual) {
        $total_gastos_mes += $g['monto'];
        $gastos_mes[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Gastos</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1>
            <?php if (defined('LOGO_NEGOCIO') && file_exists(LOGO_NEGOCIO)): ?>
                <img src="<?= LOGO_NEGOCIO ?>" alt="Logo" style="height: 36px; margin-right: 8px; vertical-align: middle;">
            <?php endif; ?>
            <?= NOMBRE_NEGOCIO ?>
        </h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="ventas.php">Ventas</a>
            <a href="reportes.php">Reportes</a>
            <a href="gastos.php" class="active">Gastos</a>
        </nav>
    </header>

    <div class="container">

        <?php if ($mensaje ?? ''): ?>
        <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:20px; text-align:center;">
            <?= $mensaje ?>
        </div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div class="card">
            <h2>Registrar Gasto</h2>
            <form method="post">
                <input type="hidden" name="accion" value="guardar">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <label>Fecha</label>
                        <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div>
                        <label>Categoría</label>
                        <select name="categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                            <option><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" placeholder="Ej: Pago a CLARO" required>
                </div>
                <div style="margin-top:12px;">
                    <label>Monto</label>
                    <input type="number" step="0.01" name="monto" placeholder="0.00" required>
                </div>
                <button type="submit" class="btn btn-add" style="margin-top:16px;">Guardar Gasto</button>
            </form>
        </div>

        <!-- RESUMEN -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= count($gastos_mes) ?></h3>
                <p>Gastos este mes</p>
            </div>
            <div class="stat-card">
                <h3><?=MONEDA?> <?= number_format($total_gastos_mes, 2) ?></h3>
                <p>Total gastado</p>
            </div>
        </div>

        <!-- LISTA DE GASTOS -->
        <div class="card">
            <h3>Gastos del Mes</h3>
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Categoría</th><th>Descripción</th><th>Monto</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($gastos_mes) as $g): ?>
                    <tr>
                        <td><?= date('d/m', strtotime($g['fecha'])) ?></td>
                        <td><span style="background:#e6f7ff; color:#2b6cb0; padding:4px 8px; border-radius:4px; font-size:11px;"><?= $g['categoria'] ?></span></td>
                        <td><?= htmlspecialchars($g['descripcion']) ?></td>
                        <td style="color:#c53030; font-weight:600;">- <?=MONEDA?> <?= number_format($g['monto'], 2) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este gasto?');">
                                <input type="hidden" name="eliminar" value="<?= $g['id'] ?>">
                                <button type="submit" class="btn btn-delete" style="padding:4px 8px; font-size:11px;">X</button>
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