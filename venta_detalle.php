<?php
require_once 'config_core.php';

$mensaje = '';
$venta_id = $_GET['id'] ?? '';
$ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
$detalles = json_decode(@file_get_contents('ventas_detalle.json'), true) ?: [];
$clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];

// === PAGAR DEUDA ===
if (isset($_POST['pagar_deuda'])) {
    $monto_pago = floatval($_POST['monto_pago'] ?? 0);
    if ($monto_pago > 0) {
        foreach ($ventas as &$v) {
            if ($v['id'] === $venta_id) {
                $deuda_actual = $v['deuda'] ?? 0;
                if ($monto_pago > $deuda_actual) {
                    $monto_pago = $deuda_actual;
                    $mensaje = '<div class="alert alert-warning">Se aplicó el monto total de la deuda.</div>';
                }
                $v['abono'] = ($v['abono'] ?? 0) + $monto_pago;
                $v['deuda'] = $deuda_actual - $monto_pago;
                break;
            }
        }
        file_put_contents('ventas.json', json_encode($ventas, JSON_PRETTY_PRINT));
        $mensaje = '<div class="alert alert-success">Pago registrado: $' . number_format($monto_pago, 2) . '</div>';
        $ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
    } else {
        $mensaje = '<div class="alert alert-danger">Ingresa un monto válido</div>';
    }
}

// === CARGAR VENTA ===
$venta = null;
foreach ($ventas as $v) {
    if ($v['id'] === $venta_id) {
        $venta = $v;
        break;
    }
}
if (!$venta) {
    die("Venta no encontrada.");
}

$detalles_venta = array_filter($detalles, fn($d) => $d['venta_id'] === $venta_id);

$cliente_nombre = 'Público General';
if (!empty($venta['cliente_id'])) {
    $match = array_filter($clientes, fn($c) => $c['id'] === $venta['cliente_id']);
    if ($match) $cliente_nombre = reset($match)['nombre'];
}

$metodo = $venta['metodo_pago'] ?? 'efectivo';
$metodos = ['efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_debito'=>'Tarjeta Débito'];

$abono = $venta['abono'] ?? 0;
$deuda = $venta['deuda'] ?? max(0, $venta['total'] - $abono);
$fila_deuda_clase = $deuda > 0 ? ' class="deuda-fila"' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= NOMBRE_NEGOCIO ?> - Factura #<?= substr($venta['id'], -6) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* === PANTALLA === */
        .no-print { display: block; }
        .print-only { display: none; }

        .deuda-fila {
            background: #fef2f2 !important;
            color: #dc2626 !important;
            font-weight: 700 !important;
        }
        .deuda-fila td { color: #dc2626 !important; }

        .pago-form {
            display: flex; gap: 10px; align-items: center; margin-top: 8px; flex-wrap: wrap;
        }
        .pago-form input {
            width: 120px; padding: 8px; border: 2px solid #dc2626; border-radius: 8px; font-weight: 600;
        }
        .pago-form button {
            background: #dc2626; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;
        }

        /* === FACTURA PROFESIONAL (A4 o 80mm) === */
        @media print {
            @page { size: A4; margin: 15mm; }
            body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 12pt; line-height: 1.5; color: #000; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .factura { width: 100%; max-width: 800px; margin: 0 auto; }
            .factura-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 2px solid #1e40af; padding-bottom: 15px; }
            .factura-logo img { height: 70px; }
            .factura-titulo { text-align: right; }
            .factura-titulo h1 { margin: 0; font-size: 24pt; color: #1e40af; }
            .factura-titulo p { margin: 5px 0; font-size: 11pt; }

            .factura-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
            .info-block { background: #f8fafc; padding: 12px; border-radius: 8px; }
            .info-block h3 { margin: 0 0 8px; font-size: 12pt; color: #1e40af; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px; }
            .info-block p { margin: 4px 0; font-size: 11pt; }

            .factura-items table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .factura-items th { background: #1e40af; color: white; padding: 12px; text-align: left; font-weight: 600; }
            .factura-items td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
            .factura-items tr:last-child td { border-bottom: 2px solid #1e40af; }

            .factura-totales { float: right; width: 40%; background: #f1f5f9; padding: 15px; border-radius: 8px; }
            .factura-totales table { width: 100%; }
            .factura-totales td { padding: 6px 0; font-size: 12pt; }
            .total-row { font-weight: bold; font-size: 14pt; color: #1e40af; }
            .deuda-row { color: #dc2626 !important; font-weight: bold; }

            .factura-footer { clear: both; text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #cbd5e1; font-size: 10pt; color: #64748b; }
        }
    </style>
</head>
<body>
    <!-- HEADER (SOLO PANTALLA) -->
    <header class="no-print">
        <div style="max-width:1200px;margin:auto;display:flex;justify-content:space-between;align-items:center;padding:15px 0;">
            <h1>
                <?php if (defined('LOGO_NEGOCIO') && file_exists(LOGO_NEGOCIO)): ?>
                    <img src="<?= LOGO_NEGOCIO ?>" alt="Logo" style="height:40px;margin-right:10px;vertical-align:middle;">
                <?php endif; ?>
                <?= NOMBRE_NEGOCIO ?>
            </h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="ventas.php">Ventas</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?= $mensaje ?>
        <div class="card no-print">
            <h2>Detalle de Venta #<?= substr($venta['id'], -6) ?></h2>

            <!-- RESUMEN PANTALLA -->
            <table class="mb-3">
                <tr><th>Cliente:</th><td><?= htmlspecialchars($cliente_nombre) ?></td></tr>
                <tr><th>Método:</th><td><strong><?= $metodos[$metodo] ?? '—' ?></strong></td></tr>
                <tr><th>Abono:</th><td><strong style="color:#1e40af;"><?=MONEDA?> <?= number_format($abono, 2) ?></strong></td></tr>
                <tr<?= $fila_deuda_clase ?>>
                    <th>Deuda:</th>
                    <td>
                        <strong><?= $deuda > 0 
				? 'Lps ' . number_format($deuda, 2)
				: 'PAGADO' 
				?>
			</strong>
                        <?php if ($deuda > 0): ?>
                            <form method="POST" class="pago-form">
                                <input type="number" name="monto_pago" step="0.01" min="0.01" max="<?= $deuda ?>" placeholder="Monto" required>
                                <button type="submit" name="pagar_deuda">Pagar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><th>Total:</th><td><strong><?=MONEDA?> <?= number_format($venta['total'], 2) ?></strong></td></tr>
                <tr><th>Fecha:</th><td><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></td></tr>
            </table>

            <!-- ÍTEMS PANTALLA -->
            <table>
                <thead><tr><th>ÍTEM</th><th>CANT.</th><th>PRECIO</th><th>SUBTOTAL</th></tr></thead>
                <tbody>
                    <?php foreach ($detalles_venta as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                        <td style="text-align:center;"><?= $d['cantidad'] ?></td>
                        <td><?=MONEDA?> <?= number_format($d['precio'], 2) ?></td>
                        <td style="font-weight:600;"><?=MONEDA?> <?= number_format($d['subtotal'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="text-align:right;font-weight:600;">TOTAL:</td><td><?=MONEDA?> <?= number_format($venta['total'], 2) ?></td></tr>
                </tfoot>
            </table>

            <!-- BOTONES PANTALLA -->
            <div style="margin-top:20px;text-align:center;">
                <button onclick="window.print()" class="btn btn-add" style="margin-right:10px;">Imprimir Factura</button>
                <a href="ventas.php" class="btn btn-view">Volver</a>
            </div>
        </div>

        <!-- === FACTURA PROFESIONAL (SOLO IMPRESIÓN) === -->
        <div class="print-only">
            <div class="factura">
                <!-- ENCABEZADO -->
                <div class="factura-header">
                    <div class="factura-logo">
                        <?php if (defined('LOGO_NEGOCIO') && file_exists(LOGO_NEGOCIO)): ?>
                            <img src="<?= LOGO_NEGOCIO ?>" alt="Logo">
                        <?php endif; ?>
                    </div>
                    <div class="factura-titulo">
                        <h1>FACTURA</h1>
                        <p><strong>N°:</strong> <?= substr($venta['id'], -6) ?></p>
                        <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($venta['fecha'])) ?></p>
                        <p><strong>Hora:</strong> <?= date('H:i', strtotime($venta['fecha'])) ?></p>
                    </div>
                </div>

                <!-- INFORMACIÓN -->
                <div class="factura-info">
                    <div class="info-block">
                        <h3>Cliente</h3>
                        <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente_nombre) ?></p>
                        <p><strong>Método de Pago:</strong> <?= $metodos[$metodo] ?? '—' ?></p>
                    </div>
                    <div class="info-block">
                        <h3>Negocio</h3>
                        <p><strong><?= NOMBRE_NEGOCIO ?></strong></p>
                        <p>Secretaría e Informática FE</p>
                    </div>
                </div>

                <!-- ÍTEMS -->
                <div class="factura-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles_venta as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['nombre']) ?></td>
                                <td style="text-align:center;"><?= $d['cantidad'] ?></td>
                                <td><?=MONEDA?> <?= number_format($d['precio'], 2) ?></td>
                                <td><?=MONEDA?> <?= number_format($d['subtotal'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- TOTALES -->
                <div class="factura-totales">
                    <table>
                        <tr><td>Abono:</td><td style="text-align:right;"><?=MONEDA?> <?= number_format($abono, 2) ?></td></tr>
                        <?php if ($deuda > 0): ?>
                        <tr class="deuda-row"><td>DEUDA PENDIENTE:</td><td style="text-align:right;"><?=MONEDA?> <?= number_format($deuda, 2) ?></td></tr>
                        <?php else: ?>
                        <tr><td>Estado:</td><td style="text-align:right;">PAGADO</td></tr>
                        <?php endif; ?>
                        <tr class="total-row"><td>TOTAL:</td><td style="text-align:right;"><?=MONEDA?> <?= number_format($venta['total'], 2) ?></td></tr>
                    </table>
                </div>

                <!-- PIE DE PÁGINA -->
                <div class="factura-footer">
                    <p><strong>¡Gracias por su preferencia!</strong></p>
                    <p>Este documento es una representación impresa de la venta realizada.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>