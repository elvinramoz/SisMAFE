<?php require_once 'config_core.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?= htmlspecialchars(NOMBRE_NEGOCIO) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #1e40af; color: white; padding: 15px 0; }
        header h1 { margin: 0; font-size: 1.5em; }
        header a { color: white; text-decoration: none; }
        nav a { margin: 0 10px; color: #bfdbfe; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 15px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1em; }
        .logo-preview { margin-top: 10px; text-align: center; }
        .logo-preview img { max-height: 80px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .btn-guardar { background: #1e40af; color: white; padding: 14px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 1.1em; }
        .btn-guardar:hover { background: #1e3a8a; }
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 8px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <header>
        <div style="max-width:1200px;margin:auto;display:flex;justify-content:space-between;align-items:center;padding:0 15px;">
            <h1><a href="index.php">Configuración</a></h1>
            <nav>
                <a href="index.php">Dashboard</a>
                <a href="ventas.php">Ventas</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Configuración guardada correctamente!</div>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-top:0; color:#1e40af;">Configuración del Negocio</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nombre del Negocio</label>
                    <input type="text" name="nombre_negocio" value="<?= htmlspecialchars(NOMBRE_NEGOCIO) ?>" required>
                </div>

                <div class="form-group">
                    <label>Moneda (ej: $, Bs, €)</label>
                    <input type="text" name="moneda" value="<?= htmlspecialchars(MONEDA) ?>" required>
                </div>

                <div class="form-group">
                    <label>Ubicación</label>
                    <textarea name="ubicacion" rows="2"><?= htmlspecialchars(UBICACION_NEGOCIO) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Logo (PNG, JPG, SVG)</label>
                    <input type="file" name="logo" accept="image/*">
                    <?php if (!empty(LOGO_NEGOCIO) && file_exists(LOGO_NEGOCIO)): ?>
                    <div class="logo-preview">
                        <p><strong>Logo actual:</strong></p>
                        <img src="<?= LOGO_NEGOCIO ?>" alt="Logo actual">
                    </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="guardar_config" class="btn-guardar">
                    Guardar Configuración
                </button>
            </form>
        </div>
    </div>
</body>
</html>