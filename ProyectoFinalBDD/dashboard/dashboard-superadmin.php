<?php
// dashboard-superadmin.php
session_start();

// Activar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar que el usuario es superadmin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if ($_SESSION['user_type'] !== 'superadmin') {
    header('Location: dashboard-usuario.php');
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Super Admin';

// Incluir conexión a base de datos
require_once '../config/database.php';

// Variables iniciales
$busqueda = '';
$resultados = [];
$mensaje_error = '';
$mensaje_exito = '';
$usuario_gestion = null;
$permisos_usuario = [];

// Definir permisos básicos del sistema
$permisos_sistema = [
    'ALUMNO_INSERTAR' => [
        'nombre' => 'Insertar Alumnos',
        'descripcion' => 'Agregar nuevos alumnos al sistema',
        'categoria' => 'Formulario Alumnos'
    ],
    'ALUMNO_ACTUALIZAR' => [
        'nombre' => 'Actualizar Alumnos',
        'descripcion' => 'Modificar información de alumnos existentes',
        'categoria' => 'Formulario Alumnos'
    ],
    'ALUMNO_ELIMINAR' => [
        'nombre' => 'Eliminar Alumnos',
        'descripcion' => 'Eliminar alumnos del sistema',
        'categoria' => 'Formulario Alumnos'
    ],
    'PROFESOR_INSERTAR' => [
        'nombre' => 'Insertar Profesores',
        'descripcion' => 'Agregar nuevos profesores al sistema',
        'categoria' => 'Formulario Profesores'
    ],
    'PROFESOR_ACTUALIZAR' => [
        'nombre' => 'Actualizar Profesores',
        'descripcion' => 'Modificar información de profesores existentes',
        'categoria' => 'Formulario Profesores'
    ],
    'PROFESOR_ELIMINAR' => [
        'nombre' => 'Eliminar Profesores',
        'descripcion' => 'Eliminar profesores del sistema',
        'categoria' => 'Formulario Profesores'
    ],
    'NOTA_INSERTAR' => [
        'nombre' => 'Insertar Notas',
        'descripcion' => 'Registrar nuevas calificaciones',
        'categoria' => 'Formulario Notas'
    ],
    'NOTA_ACTUALIZAR' => [
        'nombre' => 'Actualizar Notas',
        'descripcion' => 'Modificar calificaciones existentes',
        'categoria' => 'Formulario Notas'
    ],
    'NOTA_ELIMINAR' => [
        'nombre' => 'Eliminar Notas',
        'descripcion' => 'Eliminar calificaciones del sistema',
        'categoria' => 'Formulario Notas'
    ],
    'REPORTE_CURSO' => [
        'nombre' => 'Ver Reporte del Curso',
        'descripcion' => 'Ver reportes completos del curso',
        'categoria' => 'Reportes'
    ],
    'REPORTE_NOTAS' => [
        'nombre' => 'Ver Reporte de Notas',
        'descripcion' => 'Ver reportes detallados de notas',
        'categoria' => 'Reportes'
    ],
    'REPORTE_PERSONAL' => [
        'nombre' => 'Ver Reporte Personal',
        'descripcion' => 'Ver reportes personales de cada usuario',
        'categoria' => 'Reportes'
    ]
];

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Procesar búsqueda
    if (isset($_POST['buscar']) && !empty($_POST['busqueda'])) {
        $busqueda = trim($_POST['busqueda']);
        
        try {
            $db = new Database();
            $conn = $db->connect();
            
            // Buscar en alumnos usando procedimiento almacenado
            $sql_alumnos = "EXEC sp_alumno_buscar_general ?";
            $stmt_alumnos = $db->executeQuery($sql_alumnos, [$busqueda]);
            $alumnos = $db->fetchAll($stmt_alumnos);
            
            // Buscar en profesores usando procedimiento almacenado
            $sql_profesores = "EXEC sp_profesor_buscar_general ?";
            $stmt_profesores = $db->executeQuery($sql_profesores, [$busqueda]);
            $profesores = $db->fetchAll($stmt_profesores);
            
            // Combinar resultados
            $resultados = array_merge($alumnos, $profesores);
            
            if (empty($resultados)) {
                $mensaje_error = "No se encontraron usuarios con: '" . htmlspecialchars($busqueda) . "'";
            }
            
            $db->disconnect();
            
        } catch (Exception $e) {
            $mensaje_error = "Error en la búsqueda: " . $e->getMessage();
        }
    }
    
    // 2. Procesar ver permisos
    elseif (isset($_POST['ver_permisos'])) {
        $codigo_usuario = $_POST['codigo_usuario'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';
        
        if (!empty($codigo_usuario) && !empty($tipo_usuario)) {
            try {
                $db = new Database();
                $conn = $db->connect();
                
                // Obtener información del usuario usando procedimiento almacenado
                $sql_usuario = "EXEC sp_usuario_obtener_info ?, ?";
                $stmt_usuario = $db->executeQuery($sql_usuario, [$codigo_usuario, $tipo_usuario]);
                $usuario_gestion = $db->fetchOne($stmt_usuario);
                
                if ($usuario_gestion) {
                    // Verificar si la tabla existe, si no, crearla con SP simple
                    try {
                        $sql_check_table = "SELECT COUNT(*) as existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'usuario_permisos'";
                        $stmt_check = $db->executeQuery($sql_check_table, []);
                        $check_result = $db->fetchOne($stmt_check);
                        
                        if ($check_result['existe'] == 0) {
                            // Crear tabla si no existe
                            $sql_create = "CREATE TABLE usuario_permisos (
                                id_asignacion INT IDENTITY(1,1) PRIMARY KEY,
                                codigo_usuario VARCHAR(50) NOT NULL,
                                tipo_usuario VARCHAR(20) NOT NULL,
                                codigo_permiso VARCHAR(50) NOT NULL,
                                concedido BIT DEFAULT 0,
                                fecha_asignacion DATETIME DEFAULT GETDATE(),
                                CONSTRAINT unique_usuario_permiso UNIQUE (codigo_usuario, tipo_usuario, codigo_permiso)
                            )";
                            $db->executeQuery($sql_create, []);
                        }
                    } catch (Exception $e) {
                        // Intentar crear tabla si hay error
                        $sql_create = "IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'usuario_permisos')
                            CREATE TABLE usuario_permisos (
                                id_asignacion INT IDENTITY(1,1) PRIMARY KEY,
                                codigo_usuario VARCHAR(50) NOT NULL,
                                tipo_usuario VARCHAR(20) NOT NULL,
                                codigo_permiso VARCHAR(50) NOT NULL,
                                concedido BIT DEFAULT 0,
                                fecha_asignacion DATETIME DEFAULT GETDATE(),
                                CONSTRAINT unique_usuario_permiso UNIQUE (codigo_usuario, tipo_usuario, codigo_permiso)
                            )";
                        $db->executeQuery($sql_create, []);
                    }
                    
                    // Obtener permisos actuales usando procedimiento almacenado
                    try {
                        $sql_permisos = "EXEC sp_usuario_permisos_obtener ?, ?";
                        $stmt_permisos = $db->executeQuery($sql_permisos, [$codigo_usuario, $tipo_usuario]);
                        $permisos_db = $db->fetchAll($stmt_permisos);
                    } catch (Exception $e) {
                        // Si falla el SP, usar consulta directa
                        $sql_permisos = "SELECT codigo_permiso, concedido FROM usuario_permisos 
                                       WHERE codigo_usuario = ? AND tipo_usuario = ?";
                        $stmt_permisos = $db->executeQuery($sql_permisos, [$codigo_usuario, $tipo_usuario]);
                        $permisos_db = $db->fetchAll($stmt_permisos);
                    }
                    
                    // Convertir a array asociativo
                    $permisos_asignados = [];
                    foreach ($permisos_db as $permiso) {
                        $permisos_asignados[$permiso['codigo_permiso']] = (bool)$permiso['concedido'];
                    }
                    
                    // Crear array de permisos para mostrar
                    foreach ($permisos_sistema as $codigo => $info) {
                        $permisos_usuario[] = [
                            'codigo_permiso' => $codigo,
                            'nombre_permiso' => $info['nombre'],
                            'descripcion' => $info['descripcion'],
                            'categoria' => $info['categoria'],
                            'concedido' => isset($permisos_asignados[$codigo]) ? $permisos_asignados[$codigo] : false
                        ];
                    }
                } else {
                    $mensaje_error = "Usuario no encontrado";
                }
                
                $db->disconnect();
                
            } catch (Exception $e) {
                $mensaje_error = "Error: " . $e->getMessage();
            }
        }
    }
    
    // 3. Procesar guardar permisos
    elseif (isset($_POST['guardar_permisos'])) {
        $codigo_usuario = $_POST['codigo_usuario'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';
        
        if (!empty($codigo_usuario) && !empty($tipo_usuario)) {
            try {
                $db = new Database();
                $conn = $db->connect();
                
                // Construir JSON de permisos para el procedimiento almacenado
                $permisos_json = [];
                foreach ($permisos_sistema as $codigo => $info) {
                    $concedido = isset($_POST['permiso_' . $codigo]) ? 1 : 0;
                    $permisos_json[] = ['codigo' => $codigo, 'concedido' => $concedido];
                }
                
                $json_string = json_encode($permisos_json);
                
                // Intentar usar el procedimiento almacenado primero
                try {
                    $sql_guardar = "EXEC sp_usuario_permisos_actualizar_lote ?, ?, ?";
                    $stmt_guardar = $db->executeQuery($sql_guardar, [
                        $codigo_usuario, 
                        $tipo_usuario, 
                        $json_string
                    ]);
                    
                    // Si llegamos aquí, el SP se ejecutó correctamente
                    $mensaje_exito = "Permisos actualizados correctamente (usando SP)";
                    
                } catch (Exception $sp_error) {
                    // Si falla el SP, usar método tradicional
                    $conn->beginTransaction();
                    
                    foreach ($permisos_sistema as $codigo => $info) {
                        $concedido = isset($_POST['permiso_' . $codigo]) ? 1 : 0;
                        
                        // Verificar si existe
                        $sql_check = "SELECT id_asignacion FROM usuario_permisos 
                                     WHERE codigo_usuario = ? AND tipo_usuario = ? AND codigo_permiso = ?";
                        $stmt_check = $db->executeQuery($sql_check, [$codigo_usuario, $tipo_usuario, $codigo]);
                        $existe = $db->fetchOne($stmt_check);
                        
                        if ($existe) {
                            // Actualizar
                            $sql_update = "UPDATE usuario_permisos SET concedido = ?, fecha_asignacion = GETDATE()
                                         WHERE codigo_usuario = ? AND tipo_usuario = ? AND codigo_permiso = ?";
                            $db->executeQuery($sql_update, [$concedido, $codigo_usuario, $tipo_usuario, $codigo]);
                        } else {
                            // Insertar
                            $sql_insert = "INSERT INTO usuario_permisos (codigo_usuario, tipo_usuario, codigo_permiso, concedido) 
                                         VALUES (?, ?, ?, ?)";
                            $db->executeQuery($sql_insert, [$codigo_usuario, $tipo_usuario, $codigo, $concedido]);
                        }
                    }
                    
                    $conn->commit();
                    $mensaje_exito = "Permisos actualizados correctamente (método tradicional)";
                }
                
                // Recargar datos del usuario
                $sql_usuario = "EXEC sp_usuario_obtener_info ?, ?";
                $stmt_usuario = $db->executeQuery($sql_usuario, [$codigo_usuario, $tipo_usuario]);
                $usuario_gestion = $db->fetchOne($stmt_usuario);
                
                // Recargar permisos
                try {
                    $sql_permisos = "EXEC sp_usuario_permisos_obtener ?, ?";
                    $stmt_permisos = $db->executeQuery($sql_permisos, [$codigo_usuario, $tipo_usuario]);
                    $permisos_db = $db->fetchAll($stmt_permisos);
                } catch (Exception $e) {
                    $sql_permisos = "SELECT codigo_permiso, concedido FROM usuario_permisos 
                                   WHERE codigo_usuario = ? AND tipo_usuario = ?";
                    $stmt_permisos = $db->executeQuery($sql_permisos, [$codigo_usuario, $tipo_usuario]);
                    $permisos_db = $db->fetchAll($stmt_permisos);
                }
                
                $permisos_asignados = [];
                foreach ($permisos_db as $permiso) {
                    $permisos_asignados[$permiso['codigo_permiso']] = (bool)$permiso['concedido'];
                }
                
                $permisos_usuario = [];
                foreach ($permisos_sistema as $codigo => $info) {
                    $permisos_usuario[] = [
                        'codigo_permiso' => $codigo,
                        'nombre_permiso' => $info['nombre'],
                        'descripcion' => $info['descripcion'],
                        'categoria' => $info['categoria'],
                        'concedido' => isset($permisos_asignados[$codigo]) ? $permisos_asignados[$codigo] : false
                    ];
                }
                
                $db->disconnect();
                
            } catch (Exception $e) {
                $mensaje_error = "Error al guardar: " . $e->getMessage();
                
                // Intentar cargar usuario de todos modos
                try {
                    $db = new Database();
                    $conn = $db->connect();
                    
                    $sql_usuario = "EXEC sp_usuario_obtener_info ?, ?";
                    $stmt_usuario = $db->executeQuery($sql_usuario, [$codigo_usuario, $tipo_usuario]);
                    $usuario_gestion = $db->fetchOne($stmt_usuario);
                    
                    $db->disconnect();
                } catch (Exception $ex) {
                    // Ignorar error secundario
                }
            }
        }
    }
    
    // 4. Procesar reseteo de permisos
    elseif (isset($_POST['resetear_permisos'])) {
        $codigo_usuario = $_POST['codigo_usuario'] ?? '';
        $tipo_usuario = $_POST['tipo_usuario'] ?? '';
        
        if (!empty($codigo_usuario) && !empty($tipo_usuario)) {
            try {
                $db = new Database();
                $conn = $db->connect();
                
                // Intentar usar SP primero
                try {
                    $sql_resetear = "EXEC sp_usuario_permisos_resetear ?, ?";
                    $stmt_resetear = $db->executeQuery($sql_resetear, [$codigo_usuario, $tipo_usuario]);
                    $mensaje_exito = "Permisos reseteados correctamente (usando SP)";
                } catch (Exception $sp_error) {
                    // Si falla el SP, usar método tradicional
                    $sql_resetear = "UPDATE usuario_permisos SET concedido = 0, fecha_asignacion = GETDATE()
                                   WHERE codigo_usuario = ? AND tipo_usuario = ?";
                    $stmt_resetear = $db->executeQuery($sql_resetear, [$codigo_usuario, $tipo_usuario]);
                    $mensaje_exito = "Permisos reseteados correctamente";
                }
                
                // Recargar datos
                $sql_usuario = "EXEC sp_usuario_obtener_info ?, ?";
                $stmt_usuario = $db->executeQuery($sql_usuario, [$codigo_usuario, $tipo_usuario]);
                $usuario_gestion = $db->fetchOne($stmt_usuario);
                
                // Los permisos ahora estarán todos en falso
                $permisos_usuario = [];
                foreach ($permisos_sistema as $codigo => $info) {
                    $permisos_usuario[] = [
                        'codigo_permiso' => $codigo,
                        'nombre_permiso' => $info['nombre'],
                        'descripcion' => $info['descripcion'],
                        'categoria' => $info['categoria'],
                        'concedido' => false
                    ];
                }
                
                $db->disconnect();
                
            } catch (Exception $e) {
                $mensaje_error = "Error al resetear permisos: " . $e->getMessage();
            }
        }
    }
}

// Obtener resumen del sistema simplificado (sin múltiples resultados)
$total_alumnos = 0;
$total_profesores = 0;
$usuarios_con_permisos = 0;
$permisos_activos_total = 0;

if ($_SESSION['user_type'] == 'superadmin' && empty($usuario_gestion)) {
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Obtener estadísticas simples
        $sql_total_alumnos = "SELECT COUNT(*) as total FROM alumno";
        $stmt_total_alumnos = $db->executeQuery($sql_total_alumnos, []);
        $result_alumnos = $db->fetchOne($stmt_total_alumnos);
        $total_alumnos = $result_alumnos['total'] ?? 0;
        
        $sql_total_profesores = "SELECT COUNT(*) as total FROM profesor";
        $stmt_total_profesores = $db->executeQuery($sql_total_profesores, []);
        $result_profesores = $db->fetchOne($stmt_total_profesores);
        $total_profesores = $result_profesores['total'] ?? 0;
        
        // Verificar si existe la tabla de permisos
        $sql_check_permisos = "SELECT COUNT(*) as existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'usuario_permisos'";
        $stmt_check = $db->executeQuery($sql_check_permisos, []);
        $check_result = $db->fetchOne($stmt_check);
        
        if ($check_result['existe'] > 0) {
            $sql_usuarios_permisos = "SELECT COUNT(DISTINCT codigo_usuario) as total FROM usuario_permisos";
            $stmt_usuarios = $db->executeQuery($sql_usuarios_permisos, []);
            $result_usuarios = $db->fetchOne($stmt_usuarios);
            $usuarios_con_permisos = $result_usuarios['total'] ?? 0;
            
            $sql_permisos_activos = "SELECT COUNT(*) as total FROM usuario_permisos WHERE concedido = 1";
            $stmt_permisos = $db->executeQuery($sql_permisos_activos, []);
            $result_permisos = $db->fetchOne($stmt_permisos);
            $permisos_activos_total = $result_permisos['total'] ?? 0;
        }
        
        // Obtener distribución de permisos por categoría
        $distribucion_permisos = [];
        if ($check_result['existe'] > 0) {
            $sql_distribucion = "SELECT 
                CASE 
                    WHEN codigo_permiso LIKE 'ALUMNO_%' THEN 'Alumnos'
                    WHEN codigo_permiso LIKE 'PROFESOR_%' THEN 'Profesores'
                    WHEN codigo_permiso LIKE 'NOTA_%' THEN 'Notas'
                    WHEN codigo_permiso LIKE 'REPORTE_%' THEN 'Reportes'
                    ELSE 'Otros'
                END as categoria,
                COUNT(*) as total_permisos,
                SUM(CAST(concedido AS INT)) as permisos_activos
                FROM usuario_permisos
                GROUP BY 
                    CASE 
                        WHEN codigo_permiso LIKE 'ALUMNO_%' THEN 'Alumnos'
                        WHEN codigo_permiso LIKE 'PROFESOR_%' THEN 'Profesores'
                        WHEN codigo_permiso LIKE 'NOTA_%' THEN 'Notas'
                        WHEN codigo_permiso LIKE 'REPORTE_%' THEN 'Reportes'
                        ELSE 'Otros'
                    END
                ORDER BY total_permisos DESC";
            
            $stmt_distribucion = $db->executeQuery($sql_distribucion, []);
            $distribucion_permisos = $db->fetchAll($stmt_distribucion);
        }
        
        $db->disconnect();
        
    } catch (Exception $e) {
        // Silenciar error de resumen, no es crítico
        error_log("Error al obtener resumen del sistema: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Gestión de Permisos</title>
    <link rel="stylesheet" href="../assets/css/superadmin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .system-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .system-summary h3 {
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .summary-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .summary-card i {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .summary-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
        }
        
        .reset-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .reset-btn:hover {
            background: #ff3838;
        }
        
        .permisos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-primary, .btn-secondary, .reset-btn {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        @media (max-width: 768px) {
            .permisos-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <i class="fas fa-shield-alt"></i>
            <h1>Super Admin - Gestión de Permisos</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </header>

    <div class="main-container">
        <!-- Mensajes -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje_exito); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje_error); ?>
            </div>
        <?php endif; ?>

        <!-- Resumen del Sistema -->
        <?php if (empty($usuario_gestion)): ?>
            <section class="system-summary">
                <h3><i class="fas fa-chart-bar"></i> Resumen del Sistema</h3>
                <div class="summary-grid">
                    <div class="summary-card">
                        <i class="fas fa-user-graduate"></i>
                        <div class="summary-value"><?php echo $total_alumnos; ?></div>
                        <div class="summary-label">Alumnos</div>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <div class="summary-value"><?php echo $total_profesores; ?></div>
                        <div class="summary-label">Profesores</div>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-users"></i>
                        <div class="summary-value"><?php echo $usuarios_con_permisos; ?></div>
                        <div class="summary-label">Usuarios con Permisos</div>
                    </div>
                    <div class="summary-card">
                        <i class="fas fa-key"></i>
                        <div class="summary-value"><?php echo $permisos_activos_total; ?></div>
                        <div class="summary-label">Permisos Activos</div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Sección de búsqueda -->
        <section class="search-section">
            <h2><i class="fas fa-search"></i> Buscar Usuario</h2>
            <form method="POST" class="search-form">
                <input type="text" name="busqueda" class="search-input" 
                       placeholder="Buscar por código o nombre..." 
                       value="<?php echo htmlspecialchars($busqueda); ?>"
                       required>
                <button type="submit" name="buscar" class="search-btn">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>
            <p><i class="fas fa-info-circle"></i> Ejemplo: ALU-001, Juan, PROF-001, María</p>
        </section>

        <!-- Resultados de búsqueda -->
        <?php if (!empty($resultados)): ?>
            <section class="results-section">
                <h3><i class="fas fa-users"></i> Resultados (<?php echo count($resultados); ?> encontrados)</h3>
                
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $usuario): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['codigo']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong><br>
                                    <small>CI: <?php echo htmlspecialchars($usuario['cedula'] ?? 'N/A'); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($usuario['tipo']); ?>">
                                        <?php echo htmlspecialchars($usuario['tipo_display']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $usuario['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $usuario['estado'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="codigo_usuario" value="<?php echo htmlspecialchars($usuario['codigo']); ?>">
                                        <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($usuario['tipo']); ?>">
                                        <button type="submit" name="ver_permisos" class="action-btn btn-permisos">
                                            <i class="fas fa-key"></i> Gestionar Permisos
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <!-- Sección de permisos -->
        <?php if ($usuario_gestion && !empty($permisos_usuario)): ?>
            <section class="permissions-section">
                <div class="permisos-header">
                    <h3><i class="fas fa-user-lock"></i> Gestión de Permisos</h3>
                    <div>
                        <span class="badge badge-<?php echo htmlspecialchars($usuario_gestion['tipo']); ?>" style="margin-right: 10px;">
                            <?php echo htmlspecialchars($usuario_gestion['tipo_display']); ?>
                        </span>
                        <span class="badge <?php echo $usuario_gestion['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $usuario_gestion['estado'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="permissions-summary">
                    <h4><i class="fas fa-user"></i> Usuario: <?php echo htmlspecialchars($usuario_gestion['nombre']); ?></h4>
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($usuario_gestion['codigo']); ?> | 
                       <strong>Email:</strong> <?php echo htmlspecialchars($usuario_gestion['email']); ?></p>
                    <p>
                        <strong>Permisos activos:</strong> 
                        <?php
                        $activos = 0;
                        foreach ($permisos_usuario as $p) {
                            if ($p['concedido']) $activos++;
                        }
                        echo $activos . ' de ' . count($permisos_usuario);
                        ?>
                    </p>
                </div>
                
                <form method="POST" id="permisosForm">
                    <input type="hidden" name="codigo_usuario" value="<?php echo htmlspecialchars($usuario_gestion['codigo']); ?>">
                    <input type="hidden" name="tipo_usuario" value="<?php echo htmlspecialchars($usuario_gestion['tipo']); ?>">
                    
                    <?php
                    // Agrupar por categoría
                    $grupos = [];
                    foreach ($permisos_usuario as $permiso) {
                        $cat = $permiso['categoria'];
                        if (!isset($grupos[$cat])) $grupos[$cat] = [];
                        $grupos[$cat][] = $permiso;
                    }
                    
                    foreach ($grupos as $categoria => $permisos_cat):
                        $activos_cat = 0;
                        foreach ($permisos_cat as $p) {
                            if ($p['concedido']) $activos_cat++;
                        }
                    ?>
                        <div class="permission-group">
                            <h4>
                                <?php echo htmlspecialchars($categoria); ?>
                                <small style="float: right; color: #666;">
                                    <?php echo $activos_cat . '/' . count($permisos_cat); ?>
                                </small>
                            </h4>
                            
                            <?php foreach ($permisos_cat as $permiso): ?>
                                <div class="permission-item">
                                    <div class="permission-info">
                                        <h5><?php echo htmlspecialchars($permiso['nombre_permiso']); ?></h5>
                                        <p><?php echo htmlspecialchars($permiso['descripcion']); ?></p>
                                    </div>
                                    <div class="permission-action">
                                        <span class="permission-status <?php echo $permiso['concedido'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $permiso['concedido'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                        </span>
                                        <div class="simple-toggle">
                                            <span class="toggle-label">Inactivo</span>
                                            <input type="checkbox" 
                                                   class="toggle-checkbox"
                                                   name="permiso_<?php echo htmlspecialchars($permiso['codigo_permiso']); ?>"
                                                   value="1"
                                                   id="permiso_<?php echo htmlspecialchars($permiso['codigo_permiso']); ?>"
                                                   <?php echo $permiso['concedido'] ? 'checked' : ''; ?>>
                                            <span class="toggle-label">Activo</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="action-buttons">
                        <button type="submit" name="guardar_permisos" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <button type="submit" name="resetear_permisos" class="reset-btn" onclick="return confirm('¿Estás seguro de resetear todos los permisos a INACTIVO?')">
                            <i class="fas fa-undo"></i> Resetear Permisos
                        </button>
                        <button type="submit" name="buscar" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Buscar
                        </button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <!-- Estado inicial -->
        <?php if (empty($busqueda) && empty($resultados) && !$usuario_gestion): ?>
            <div class="empty-state">
                <i class="fas fa-user-cog"></i>
                <h3>Gestión de Permisos</h3>
                <p>Busca un usuario para gestionar los permisos de su cuenta.</p>
                
                <?php if (!empty($distribucion_permisos)): ?>
                    <div style="margin-top: 20px; background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: left;">
                        <h4 style="margin-top: 0; color: #495057;">
                            <i class="fas fa-chart-pie"></i> Distribución de Permisos por Categoría
                        </h4>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #e9ecef;">
                                    <th style="padding: 8px; text-align: left;">Categoría</th>
                                    <th style="padding: 8px; text-align: center;">Total Permisos</th>
                                    <th style="padding: 8px; text-align: center;">Activos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distribucion_permisos as $categoria): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px;"><?php echo htmlspecialchars($categoria['categoria']); ?></td>
                                        <td style="padding: 8px; text-align: center;"><?php echo $categoria['total_permisos']; ?></td>
                                        <td style="padding: 8px; text-align: center;">
                                            <span class="badge <?php echo $categoria['permisos_activos'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $categoria['permisos_activos']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Confirmación antes de cerrar sesión
        document.querySelector('.logout-btn')?.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                e.preventDefault();
            }
        });

        // Auto-focus en el campo de búsqueda
        const searchInput = document.querySelector('.search-input');
        if (searchInput) searchInput.focus();
        
        // Actualizar estado visual de los toggles
        document.querySelectorAll('.toggle-checkbox').forEach(toggle => {
            toggle.addEventListener('change', function() {
                const statusElement = this.closest('.permission-action').querySelector('.permission-status');
                if (this.checked) {
                    statusElement.textContent = 'ACTIVO';
                    statusElement.className = 'permission-status status-active';
                } else {
                    statusElement.textContent = 'INACTIVO';
                    statusElement.className = 'permission-status status-inactive';
                }
            });
        });
        
        // Confirmación al guardar
        const form = document.getElementById('permisosForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (e.submitter && e.submitter.name === 'guardar_permisos') {
                    const checkboxes = form.querySelectorAll('.toggle-checkbox:checked');
                    if (checkboxes.length === 0) {
                        if (!confirm('No hay permisos seleccionados. ¿Deseas guardar todos los permisos como INACTIVOS?')) {
                            e.preventDefault();
                        }
                    } else {
                        if (!confirm('¿Guardar cambios en los permisos?\nSe actualizarán ' + checkboxes.length + ' permisos.')) {
                            e.preventDefault();
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>