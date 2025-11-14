<?php
// config_core.php - LÓGICA DE CONFIGURACIÓN
define('CONFIG_FILE', 'config.json');

function loadConfig() {
    if (file_exists(CONFIG_FILE)) {
        $data = json_decode(file_get_contents(CONFIG_FILE), true);
        return is_array($data) ? $data : [];
    }
    return [
        'nombre_negocio' => 'Mi Negocio',
        'moneda' => '$',
        'ubicacion' => 'Ciudad, País',
        'logo' => ''
    ];
}

function saveConfig($data) {
    file_put_contents(CONFIG_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// GUARDAR ANTES DE CARGAR
if (isset($_POST['guardar_config']) && basename($_SERVER['PHP_SELF']) === 'config.php') {
    $nuevo = [
        'nombre_negocio' => trim($_POST['nombre_negocio'] ?? ''),
        'moneda' => trim($_POST['moneda'] ?? '$'),
        'ubicacion' => trim($_POST['ubicacion'] ?? ''),
        'logo' => ''
    ];

    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'svg'];
        if (in_array($ext, $allowed) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $nuevo_nombre = 'logo.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $nuevo_nombre)) {
                $nuevo['logo'] = $nuevo_nombre;
            }
        }
    }

    saveConfig($nuevo);
    if (headers_sent()) {
        echo '<script>window.location.href="config.php?saved=1";</script>';
        exit;
    } else {
        header("Location: config.php?saved=1");
        exit;
    }
}

// CARGAR CONFIGURACIÓN
$config = loadConfig();

// DEFINIR CONSTANTES
define('NOMBRE_NEGOCIO', $config['nombre_negocio'] ?? 'Mi Negocio');
define('MONEDA', $config['moneda'] ?? '$');
define('UBICACION_NEGOCIO', $config['ubicacion'] ?? '');
define('LOGO_NEGOCIO', $config['logo'] ?? '');
?>