<?php
require_once 'config_core.php';

// === ELIMINAR VENTA ===
if (isset($_POST['eliminar_venta'])) {
    $venta_id = $_POST['eliminar_venta'];
    $ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
    $detalles = json_decode(@file_get_contents('ventas_detalle.json'), true) ?: [];
    $ventas = array_filter($ventas, fn($v) => $v['id'] !== $venta_id);
    $ventas = array_values($ventas);
    $detalles = array_filter($detalles, fn($d) => $d['venta_id'] !== $venta_id);
    $detalles = array_values($detalles);
    file_put_contents('ventas.json', json_encode($ventas, JSON_PRETTY_PRINT));
    file_put_contents('ventas_detalle.json', json_encode($detalles, JSON_PRETTY_PRINT));
    header("Location: index.php"); exit;
}

// Cargar datos
$productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
$servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
$clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];
$ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
$detalles = json_decode(@file_get_contents('ventas_detalle.json'), true) ?: [];
$gastos = json_decode(@file_get_contents('gastos.json'), true) ?: [];

// Cálculos
$total_items = count($productos) + count($servicios);
$total_clientes = count($clientes);
$total_ventas = count($ventas);
$ingresos_totales = array_sum(array_column($ventas, 'total'));
$total_gastos = array_sum(array_column($gastos, 'monto'));
$ganancia_neta = $ingresos_totales - $total_gastos;

// Últimos 7 días
$ultimos7dias = $ventas_por_dia = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $dia = date('d/m', strtotime($fecha));
    $ventas_dia = array_filter($ventas, fn($v) => substr($v['fecha'], 0, 10) === $fecha);
    $total_dia = array_sum(array_column($ventas_dia, 'total'));
    $ultimos7dias[] = $dia;
    $ventas_por_dia[] = $total_dia;
}

// Top 5 ítems
$items_vendidos = [];
foreach ($detalles as $d) {
    $items_vendidos[$d['nombre']] = ($items_vendidos[$d['nombre']] ?? 0) + $d['cantidad'];
}
arsort($items_vendidos);
$top_items = array_slice($items_vendidos, 0, 5, true);
$top_nombres = array_keys($top_items);
$top_cantidades = array_values($top_items);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1>
            <?php if (defined('LOGO_NEGOCIO') && file_exists(LOGO_NEGOCIO)): ?>
                <img src="<?= LOGO_NEGOCIO ?>" alt="Logo" style="height:40px;margin-right:10px;vertical-align:middle;">
            <?php endif; ?>
            <?= NOMBRE_NEGOCIO ?>
        </h1>
        <nav>
            <a href="index.php" class="active">Dashboard</a>
            <a href="productos.php">Productos</a>
            <a href="servicios.php">Servicios</a>
            <a href="clientes.php">Clientes</a>
            <a href="ventas.php">Ventas</a>
            <a href="reportes.php">Reportes</a>
            <a href="gastos.php">Gastos</a>
        </nav>
    </header>

    <div class="dashboard">

        <!-- ESTADÍSTICAS -->
        <div class="stats-grid">
            <div class="stat-card"><h3><?= $total_items ?></h3><p>Ítems</p></div>
            <div class="stat-card"><h3><?= $total_clientes ?></h3><p>Clientes</p></div>
            <div class="stat-card"><h3><?= $total_ventas ?></h3><p>Ventas</p></div>
            <div class="ganancia-oculta" onclick="toggleGanancia()">Ver Ganancia Neta</div>
        </div>

        <div id="ganancia-reveal" class="toggle-ganancia">
            Ganancia Neta: <?=MONEDA?> <?= number_format($ganancia_neta, 2) ?>
        </div>

        <!-- GRÁFICOS EN 2 COLUMNAS -->
        <div class="charts-grid">
            <div class="chart-box">
                <h3 class="chart-title">Ventas 7 Días</h3>
                <canvas id="chartVentas" height="260"></canvas>
            </div>
            <div class="chart-box">
                <h3 class="chart-title">Top 5 Ítems</h3>
                <canvas id="chartTopItems" height="260"></canvas>
            </div>
        </div>

        <!-- ÚLTIMAS VENTAS -->
        <div style="background:white;padding:16px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.06);margin-top:20px;">
            <h3 class="chart-title">Últimas Ventas</h3>
            <table>
                <thead><tr><th>ID</th><th>CLIENTE</th><th>TOTAL</th><th>FECHA</th><th></th></tr></thead>
                <tbody>
                    <?php foreach (array_slice(array_reverse($ventas), 0, 5) as $v): 
                        $cli = 'Público';
                        if ($v['cliente_id']) {
                            $match = array_filter($clientes, fn($c) => $c['id'] === $v['cliente_id']);
                            if ($match) $cli = reset($match)['nombre'];
                        }
                    ?>
                    <tr>
                        <td><small><?= substr($v['id'], -6) ?></small></td>
                        <td><?= htmlspecialchars($cli) ?></td>
                        <td style="color:#1e40af;font-weight:600;"><?=MONEDA?> <?= number_format($v['total'], 2) ?></td>
                        <td><?= date('d/m H:i', strtotime($v['fecha'])) ?></td>
                        <td style="white-space:nowrap;">
                            <a href="venta_detalle.php?id=<?= $v['id'] ?>" class="btn btn-view">Ver</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar?');">
                                <input type="hidden" name="eliminar_venta" value="<?= $v['id'] ?>">
                                <button type="submit" class="btn btn-delete" style="padding:6px 10px;font-size:12px;">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        function toggleGanancia() {
            document.getElementById('ganancia-reveal').classList.toggle('active');
        }

        // === GRÁFICO VENTAS 7 DÍAS ===
        new Chart(document.getElementById('chartVentas'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($ultimos7dias) ?>,
                datasets: [{
                    data: <?= json_encode($ventas_por_dia) ?>,
                    backgroundColor: '#60a5fa',
                    borderRadius: 6,
                    barThickness: 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.4,
                animation: { duration: 600 },
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#1e293b', titleFont: { size: 13 }, bodyFont: { size: 12 } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 } },
                        grid: { color: '#e2e8f0' }
                    },
                    x: {
                        ticks: { font: { size: 11 }, maxRotation: 0 },
                        grid: { display: false }
                    }
                },
                layout: {
                    padding: { bottom: 20 }  // ← ESPACIO PARA EJE X
                }
            }
        });

        // === GRÁFICO TOP 5 ÍTEMS ===
        new Chart(document.getElementById('chartTopItems'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($top_nombres) ?>,
                datasets: [{
                    data: <?= json_encode($top_cantidades) ?>,
                    backgroundColor: ['#60a5fa','#34d399','#fbbf24','#f87171','#a78bfa'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                aspectRatio: 1.4,
                cutout: '62%',
                animation: { duration: 600 },
                plugins: {
                    legend: {
                        position: 'bottom',
                        align: 'center',
                        labels: {
                            font: { size: 12, weight: '500' },
                            padding: 18,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => ({
                                        text: label,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].borderColor,
                                        lineWidth: data.datasets[0].borderWidth,
                                        hidden: false,
                                        index: i
                                    }));
                                }
                                return [];
                            }
                        }
                    }
                },
                layout: {
                    padding: { bottom: 30 }  // ← ESPACIO PARA TODAS LAS BARRITAS
                }
            }
        });
    </script>
</body>
</html>