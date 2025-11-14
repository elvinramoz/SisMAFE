<?php
require_once 'config_core.php';


$mensaje = '';
$productos = json_decode(@file_get_contents('productos.json'), true) ?: [];
$servicios = json_decode(@file_get_contents('servicios.json'), true) ?: [];
$clientes = json_decode(@file_get_contents('clientes.json'), true) ?: [];

// Construir lista de ítems
$items = [];
foreach ($productos as $p) {
    $items[] = [
        'id' => $p['id'],
        'nombre' => $p['nombre'],
        'precio' => $p['precio'],
        'stock' => $p['stock'] ?? 0,
        'tipo' => 'producto'
    ];
}
foreach ($servicios as $s) {
    $items[] = [
        'id' => $s['id'],
        'nombre' => $s['nombre'],
        'precio' => $s['precio'],
        'stock' => 999,
        'tipo' => 'servicio'
    ];
}
usort($items, fn($a, $b) => strcasecmp($a['nombre'], $b['nombre']));

// Procesar venta
if (isset($_POST['guardar_venta'])) {
    $detalles = json_decode($_POST['detalles'] ?? '[]', true);
    if (empty($detalles)) {
        $mensaje = '<div class="alert alert-danger">Agrega al menos un ítem</div>';
    } else {
        $total = 0;
        $venta_id = uniqid();
        $error = false;

        foreach ($detalles as $d) {
            $item = null;
            foreach ($items as $i) {
                if ($i['id'] === $d['id']) {
                    $item = $i;
                    break;
                }
            }
            if (!$item) continue;

            if ($item['tipo'] === 'producto' && $item['stock'] < $d['cantidad']) {
                $mensaje = '<div class="alert alert-danger">Stock insuficiente: ' . htmlspecialchars($d['nombre']) . '</div>';
                $error = true;
                break;
            }
            $total += $d['subtotal'];
        }

        if (!$error) {
            $abono = floatval($_POST['abono'] ?? $total);
            $deuda = max(0, $total - $abono);

            $venta = [
                'id' => $venta_id,
                'cliente_id' => $_POST['cliente_id'] ?: null,
                'total' => $total,
                'abono' => $abono,
                'deuda' => $deuda,
                'fecha' => date('Y-m-d H:i:s'),
                'metodo_pago' => $_POST['metodo_pago'] ?? 'efectivo'
            ];

            // Guardar venta
            $ventas = json_decode(@file_get_contents('ventas.json'), true) ?: [];
            $ventas[] = $venta;
            file_put_contents('ventas.json', json_encode($ventas, JSON_PRETTY_PRINT));

            // Guardar detalles
            $detalles_db = json_decode(@file_get_contents('ventas_detalle.json'), true) ?: [];
            foreach ($detalles as $d) {
                $detalles_db[] = [
                    'venta_id' => $venta_id,
                    'item_id' => $d['id'],
                    'nombre' => $d['nombre'],
                    'cantidad' => $d['cantidad'],
                    'precio' => $d['precio'],
                    'subtotal' => $d['subtotal'],
                    'tipo' => $d['tipo']
                ];
            }
            file_put_contents('ventas_detalle.json', json_encode($detalles_db, JSON_PRETTY_PRINT));

            // Actualizar stock
            foreach ($detalles as $d) {
                if ($d['tipo'] === 'producto') {
                    foreach ($productos as &$p) {
                        if ($p['id'] === $d['id']) {
                            $p['stock'] -= $d['cantidad'];
                            break;
                        }
                    }
                }
            }
            file_put_contents('productos.json', json_encode($productos, JSON_PRETTY_PRINT));

            header("Location: venta_detalle.php?id=$venta_id");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .compact-form { max-width: 800px; margin: 20px auto; }
        .form-header { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #374151; }
        .form-group select, .form-group input { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1em; }
        
        .add-item { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: end; }
        .add-item select { flex: 1; min-width: 200px; }
        .add-item input[type="number"] { width: 90px; }
        .add-item button { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }

        .items-list { max-height: 300px; overflow-y: auto; margin-bottom: 15px; }
        .item-row { display: flex; align-items: center; gap: 8px; padding: 8px; background: #f8fafc; border-radius: 8px; margin-bottom: 6px; }
        .item-name { flex: 1; font-weight: 600; }
        .item-input { width: 70px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9em; }
        .item-subtotal { font-weight: 600; }
        .btn-delete { background: #ef4444; color: white; border: none; width: 28px; height: 28px; border-radius: 6px; cursor: pointer; font-weight: bold; }

        .total-resumen { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 16px; border-radius: 12px; margin: 16px 0; font-size: 1.3em; font-weight: 700; }
        .abono-group { display: flex; align-items: center; gap: 10px; font-size: 1.1em; }
        .abono-group input { width: 130px; padding: 8px; border: 2px solid #1e40af; border-radius: 8px; font-weight: 600; }

        .btn-guardar { background: #10b981; color: white; border: none; padding: 14px; font-size: 1.1em; font-weight: 600; border-radius: 12px; width: 100%; cursor: pointer; transition: 0.2s; }
        .btn-guardar:hover { background: #059669; }

        .deuda-fila { background-color: #fef2f2 !important; color: #dc2626 !important; font-weight: 600 !important; }
        .deuda-fila td { color: #dc2626 !important; }
        .deuda-fila strong { color: #dc2626 !important; font-weight: 700; }

        @media (max-width: 600px) {
            .form-header, .add-item, .total-resumen { flex-direction: column; }
            .total-resumen { text-align: center; gap: 8px; }
        }
    </style>
    <script>
        let items = <?= json_encode($items) ?>;
        let detalles = [];

        function agregarItem() {
            const select = document.getElementById('item');
            const itemId = select.value;
            const item = items.find(i => i.id === itemId);
            if (!item) return;

            const cantidad = parseInt(document.getElementById('cantidad').value) || 1;
            if (item.tipo === 'producto' && cantidad > item.stock) {
                alert('Stock insuficiente');
                return;
            }

            detalles.push({
                id: item.id,
                nombre: item.nombre,
                precio: item.precio,
                cantidad: cantidad,
                subtotal: item.precio * cantidad,
                tipo: item.tipo
            });

            actualizarLista();
            select.value = '';
            document.getElementById('cantidad').value = 1;
        }

        function eliminarItem(index) {
            detalles.splice(index, 1);
            actualizarLista();
        }

        function actualizarCantidad(index) {
            const cant = parseInt(document.getElementById('cant_' + index).value) || 1;
            detalles[index].cantidad = cant;
            detalles[index].subtotal = detalles[index].precio * cant;
            actualizarLista();
        }

        function actualizarPrecio(index) {
            const precio = parseFloat(document.getElementById('precio_' + index).value) || 0;
            detalles[index].precio = precio;
            detalles[index].subtotal = precio * detalles[index].cantidad;
            actualizarLista();
        }

        function actualizarAbono() {
            const total = parseFloat(document.getElementById('total').textContent) || 0;
            const abonoInput = document.getElementById('abono');
            const abono = parseFloat(abonoInput.value) || 0;
            abonoInput.max = total;
            document.getElementById('deuda').textContent = (total - abono).toFixed(2);
            document.getElementById('detalles_input').value = JSON.stringify(detalles);
        }

        function actualizarLista() {
            const lista = document.getElementById('lista-items');
            lista.innerHTML = '';
            let total = 0;

            detalles.forEach((d, i) => {
                total += d.subtotal;
                lista.innerHTML += `
                    <div class="item-row">
                        <div class="item-name">${d.nombre}</div>
                        <input type="number" id="cant_${i}" class="item-input" value="${d.cantidad}" min="1" onchange="actualizarCantidad(${i})">
                        <input type="number" id="precio_${i}" class="item-input" value="${d.precio.toFixed(2)}" step="0.01" min="0" onchange="actualizarPrecio(${i})">
                        <div class="item-subtotal">$${d.subtotal.toFixed(2)}</div>
                        <button type="button" class="btn-delete" onclick="eliminarItem(${i})">X</button>
                    </div>`;
            });

            document.getElementById('total').textContent = total.toFixed(2);
            document.getElementById('abono').value = total.toFixed(2);
            actualizarAbono();
        }
    </script>
</head>
<body>
    <header>
        <div style="max-width:1200px;margin:auto;display:flex;justify-content:space-between;align-items:center;padding:15px 0;">
            <h1>Nueva Venta</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="ventas.php" class="active">Ventas</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?= $mensaje ?>

        <form method="POST" class="compact-form">
            <div class="card">
                <!-- CLIENTE Y MÉTODO -->
                <div class="form-header">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select name="cliente_id">
                            <option value="">Público General</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Método de Pago</label>
                        <select name="metodo_pago" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="tarjeta_debito">Tarjeta Débito</option>
                        </select>
                    </div>
                </div>

                <!-- AGREGAR ÍTEM -->
                <div class="add-item">
                    <div>
                        <label>Ítem</label>
                        <select id="item">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($items as $i): ?>
                                <option value="<?= $i['id'] ?>">
                                    <?= htmlspecialchars($i['nombre']) ?> (<?=MONEDA?> <?= $i['precio'] ?>) 
                                    <?= $i['tipo'] === 'producto' ? "- Stock: {$i['stock']}" : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Cant.</label>
                        <input type="number" id="cantidad" value="1" min="1">
                    </div>
                    <div>
                        <button type="button" onclick="agregarItem()">Agregar</button>
                    </div>
                </div>

                <!-- LISTA DE ÍTEMS -->
                <div id="lista-items" class="items-list"></div>

                <!-- TOTAL Y ABONO -->
                <div class="total-resumen">
                    <span>TOTAL: <?=MONEDA?> <span id="total">0.00</span></span>
                    <div class="abono-group">
                        <span>Abono:</span>
                        <input type="number" name="abono" id="abono" step="0.01" min="0" value="0" onchange="actualizarAbono()" required>
                        <span>Deuda: <?=MONEDA?> <span id="deuda">0.00</span></span>
                    </div>
                </div>

                <input type="hidden" name="detalles" id="detalles_input" value="[]">
                <button type="submit" name="guardar_venta" class="btn-guardar">
                    Guardar Venta
                </button>
            </div>
        </form>

        <!-- VENTAS RECIENTES -->
        <div class="card mt-4">
            <h3>Ventas Recientes</h3>
            <?php
            $ventas_recientes = array_slice(array_reverse(json_decode(@file_get_contents('ventas.json'), true) ?: []), 0, 5);
            if (empty($ventas_recientes)): ?>
                <p style="color:#94a3b8;">No hay ventas recientes.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CLIENTE</th>
                            <th>MÉTODO</th>
                            <th>TOTAL</th>
                            <th>ABONO</th>
                            <th>DEUDA</th>
                            <th>FECHA</th>
                            <th>VER</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas_recientes as $v): 
                            $cliente_nombre = 'Público General';
                            if (!empty($v['cliente_id'])) {
                                $match = array_filter($clientes, fn($c) => $c['id'] === $v['cliente_id']);
                                if ($match) $cliente_nombre = reset($match)['nombre'];
                            }
                            $deuda = $v['deuda'] ?? 0;
                            $fila_clase = $deuda > 0 ? ' class="deuda-fila"' : '';
                        ?>
                        <tr<?= $fila_clase ?>>
                            <td>#<?= substr($v['id'], -6) ?></td>
                            <td><?= htmlspecialchars($cliente_nombre) ?></td>
                            <td><?= ['efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta_debito'=>'Débito'][$v['metodo_pago']??'efectivo'] ?></td>
                            <td><strong><?=MONEDA?> <?= number_format($v['total'], 2) ?></strong></td>
                            <td><?=MONEDA?> <?= number_format($v['abono']??0, 2) ?></td>
                            <td><strong><?=MONEDA?> <?= number_format($deuda, 2) ?></strong></td>
                            <td><?= date('d/m H:i', strtotime($v['fecha'])) ?></td>
                            <td><a href="venta_detalle.php?id=<?= $v['id'] ?>" class="btn btn-view">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>