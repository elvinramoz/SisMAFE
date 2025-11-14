<?php
require_once 'config_core.php';

$ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
$detalles = json_decode(@file_get_contents('ventas_detalle.json'), true) ?: [];
$clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
$gastos = json_decode(@file_get_contents('gastos.json'), true) ?: [];

// === FILTROS ===
$tipo = $_GET['tipo'] ?? 'mensual';
$fecha_inicio = $_GET['inicio'] ?? '';
$fecha_fin = $_GET['fin'] ?? '';

$ventas_filtradas = $ventas;
if ($fecha_inicio && $fecha_fin) {
    $inicio = strtotime($fecha_inicio);
    $fin = strtotime($fecha_fin . ' +1 day');
    $ventas_filtradas = array_filter($ventas, fn($v) => strtotime($v['fecha']) >= $inicio && strtotime($v['fecha']) < $fin);
} elseif ($tipo === 'semanal') {
    $inicio = strtotime('monday this week');
    $fin = strtotime('sunday this week') + 86400;
    $ventas_filtradas = array_filter($ventas, fn($v) => strtotime($v['fecha']) >= $inicio && strtotime($v['fecha']) < $fin);
} elseif ($tipo === 'mensual') {
    $inicio = strtotime(date('Y-m-01'));
    $fin = strtotime(date('Y-m-t')) + 86400;
    $ventas_filtradas = array_filter($ventas, fn($v) => strtotime($v['fecha']) >= $inicio && strtotime($v['fecha']) < $fin);
} elseif ($tipo === 'anual') {
    $inicio = strtotime(date('Y-01-01'));
    $fin = strtotime(date('Y-12-31')) + 86400;
    $ventas_filtradas = array_filter($ventas, fn($v) => strtotime($v['fecha']) >= $inicio && strtotime($v['fecha']) < $fin);
}

// === RESUMEN ===
$total_ventas = count($ventas_filtradas);
$total_ingresos = array_sum(array_column($ventas_filtradas, 'total'));
$promedio_venta = $total_ventas > 0 ? $total_ingresos / $total_ventas : 0;

// === GASTOS DEL PERÍODO ===
$gastos_periodo = array_filter($gastos, fn($g) => strtotime($g['fecha']) >= $inicio && strtotime($g['fecha']) < $fin);
$total_gastos_periodo = array_sum(array_column($gastos_periodo, 'monto'));

// === INGRESOS POR DÍA ===
$ingresos_dia = [];
foreach ($ventas_filtradas as $v) {
    $dia = date('d/m', strtotime($v['fecha']));
    $ingresos_dia[$dia] = ($ingresos_dia[$dia] ?? 0) + $v['total'];
}
ksort($ingresos_dia);
$dias_labels = array_keys($ingresos_dia);
$dias_valores = array_values($ingresos_dia);

// === TOP PRODUCTOS ===
$productos_vendidos = [];
foreach ($detalles as $d) {
    if (in_array($d['venta_id'], array_column($ventas_filtradas, 'id'))) {
        $productos_vendidos[$d['nombre']] = ($productos_vendidos[$d['nombre']] ?? 0) + $d['cantidad'];
    }
}
arsort($productos_vendidos);
$top_productos = array_slice($productos_vendidos, 0, 6, true);
$top_nombres = array_keys($top_productos);
$top_cantidades = array_values($top_productos);

// === VENTAS POR CLIENTE ===
$ventas_cliente = [];
foreach ($ventas_filtradas as $v) {
    $cli_id = $v['cliente_id'] ?? 'publico';
    $ventas_cliente[$cli_id] = ($ventas_cliente[$cli_id] ?? 0) + $v['total'];
}
arsort($ventas_cliente);
$top_clientes = array_slice($ventas_cliente, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Reportes</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reportes { max-width: 1100px; margin: 15px auto; padding: 0 10px; }
        .filtros { background: white; padding: 14px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.07); margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filtros select, .filtros input { padding: 6px 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 13px; }
        .filtros button { background: #4299e1; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 18px; }
        .resumen-card { background: white; padding: 14px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.07); text-align: center; }
        .resumen-card h3 { margin: 0; font-size: 1.6rem; color: #2b6cb0; font-weight: 700; }
        .chart-small { height: 200px; background: white; padding: 14px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,0.07); margin-bottom: 16px; }
        .chart-title { margin: 0 0 8px; font-size: 1rem; color: #2d3748; font-weight: 600; }
        @media (max-width: 768px) { .filtros { flex-direction: column; } .chart-small { height: 180px; } }
    </style>
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
            <a href="reportes.php" class="active">Reportes</a>
            <a href="gastos.php">Gastos</a>
        </nav>
    </header>

    <div class="reportes">

        <!-- FILTROS -->
        <form class="filtros">
            <select name="tipo" onchange="this.form.submit()">
                <option value="semanal" <?= $tipo==='semanal'?'selected':'' ?>>Semanal</option>
                <option value="mensual" <?= $tipo==='mensual'?'selected':'' ?>>Mensual</option>
                <option value="anual" <?= $tipo==='anual'?'selected':'' ?>>Anual</option>
                <option value="rango" <?= $tipo==='rango'?'selected':'' ?>>Rango</option>
            </select>
            <?php if ($tipo === 'rango'): ?>
            <input type="date" name="inicio" value="<?= $fecha_inicio ?>" required>
            <input type="date" name="fin" value="<?= $fecha_fin ?>" required>
            <?php endif; ?>
            <button type="submit">Filtrar</button>
            <a href="reportes.php" style="margin-left:auto; color:#718096; font-size:12px;">Limpiar</a>
        </form>

        <!-- RESUMEN -->
        <div class="resumen">
            <div class="resumen-card">
                <h3><?= $total_ventas ?></h3>
                <p>Ventas</p>
            </div>
            <div class="resumen-card">
                <h3><?=MONEDA?> <?= number_format($total_ingresos, 2) ?></h3>
                <p>Ingresos</p>
            </div>
            <div class="resumen-card">
                <h3><?=MONEDA?> <?= number_format($total_gastos_periodo, 2) ?></h3>
                <p>Gastos</p>
            </div>
        </div>

        <!-- GRÁFICOS -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
            <div class="chart-small">
                <h3 class="chart-title">Ingresos Diarios</h3>
                <canvas id="chartIngresos"></canvas>
            </div>
            <div class="chart-small">
                <h3 class="chart-title">Top Productos</h3>
                <canvas id="chartProductos"></canvas>
            </div>
        </div>

        <!-- INGRESOS VS GASTOS -->
        <div class="chart-small">
            <h3 class="chart-title">Ingresos vs Gastos</h3>
            <canvas id="chartComparacion"></canvas>
        </div>

        <!-- VENTAS POR CLIENTE -->
        <div style="background:white; padding:14px; border-radius:8px; box-shadow:0 1px 6px rgba(0,0,0,0.07);">
            <h3 class="chart-title">Ventas por Cliente</h3>
            <table class="tabla-peq" style="width:100%;">
                <thead>
                    <tr><th>Cliente</th><th style="text-align:right;">Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($top_clientes as $id => $total):
                        $nombre = $id === 'publico' ? 'Público General' : 'Desconocido';
                        if ($id !== 'publico') {
                            $match = array_filter($clientes, fn($c) => $c['id'] === $id);
                            if ($match) {
                                $cliente_array = $match;
                                $nombre = reset($cliente_array)['nombre'];
                            }
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($nombre) ?></td>
                        <td style="text-align:right; color:#2b6cb0; font-weight:600;"><?=MONEDA?> <?= number_format($total, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        new Chart(document.getElementById('chartIngresos'), {
            type: 'bar',
            data: { labels: <?= json_encode($dias_labels) ?>, datasets: [{ label: 'Ingresos ($)', data: <?= json_encode($dias_valores) ?>, backgroundColor: '#4299e1' }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('chartProductos'), {
            type: 'doughnut',
            data: { labels: <?= json_encode($top_nombres) ?>, datasets: [{ data: <?= json_encode($top_cantidades) ?>, backgroundColor: ['#4299e1','#48bb78','#ed8936','#f56565','#9f7aea','#718096'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('chartComparacion'), {
            type: 'bar',
            data: {
                labels: ['Período'],
                datasets: [
                    { label: 'Ingresos', data: [<?= $total_ingresos ?>], backgroundColor: '#48bb78' },
                    { label: 'Gastos', data: [<?= $total_gastos_periodo ?>], backgroundColor: '#f56565' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</body>
</html>