<?php
// dashboard/dashboard-usuario.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Obtener información del usuario
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_type = $_SESSION['user_type'] ?? 'usuario';
$user_code = $_SESSION['user_code'] ?? '';

// Incluir conexión
require_once '../config/database.php';

// Obtener permisos del usuario
$permisos_activos = [];

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Obtener permisos concedidos
    $sql_permisos = "SELECT codigo_permiso 
                    FROM usuario_permisos 
                    WHERE codigo_usuario = ? AND tipo_usuario = ? AND concedido = 1";
    
    $stmt_permisos = $db->executeQuery($sql_permisos, [$user_code, $user_type]);
    $permisos_db = $db->fetchAll($stmt_permisos);
    
    foreach ($permisos_db as $permiso) {
        $permisos_activos[] = $permiso['codigo_permiso'];
    }
    
    $db->disconnect();
    
} catch (Exception $e) {
    // Si hay error, el usuario no tendrá permisos
    error_log("Error obteniendo permisos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Usuario</title>
    <style>
        /* Estilos básicos para el dashboard */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .menu {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .menu ul {
            list-style: none;
            padding: 0;
        }
        
        .menu li {
            margin-bottom: 10px;
        }
        
        .menu a {
            display: block;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: background 0.3s;
        }
        
        .menu a:hover {
            background: #e9ecef;
        }
        
        .menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .disabled:hover {
            background: #f8f9fa !important;
        }
        /* Agregar al CSS de dashboard-usuario.php */
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            }

        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- En el header de dashboard-usuario.php -->
<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Bienvenido, <?php echo htmlspecialchars($user_name); ?></h1>
            <p>Tipo de usuario: <?php echo htmlspecialchars($user_type); ?></p>
        </div>
        <div style="display: flex; align-items: center; gap: 15px;">
            <span style="font-size: 0.9rem;">
                <i class="fas fa-user-circle"></i> 
                <?php echo htmlspecialchars($user_name); ?>
            </span>
            <a href="../logout.php" class="logout-btn" onclick="return confirm('¿Seguro que quieres cerrar sesión?');">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</div>
    
    <div class="menu">
        <h2>Menú Principal</h2>
        
        <ul>
            <li><a href="perfil.php"><i class="fas fa-user"></i> Mi Perfil</a></li>
            
            <!-- Opciones controladas por permisos -->
            <?php if (in_array('ALUMNO_INSERTAR', $permisos_activos) || 
                      in_array('ALUMNO_ACTUALIZAR', $permisos_activos) || 
                      in_array('ALUMNO_ELIMINAR', $permisos_activos)): ?>
                <li><a href="alumnos.php"><i class="fas fa-user-graduate"></i> Gestionar Alumnos</a></li>
            <?php else: ?>
                <li><a class="disabled"><i class="fas fa-user-graduate"></i> Gestionar Alumnos (sin permiso)</a></li>
            <?php endif; ?>
            
            <?php if (in_array('PROFESOR_INSERTAR', $permisos_activos) || 
                      in_array('PROFESOR_ACTUALIZAR', $permisos_activos) || 
                      in_array('PROFESOR_ELIMINAR', $permisos_activos)): ?>
                <li><a href="profesores.php"><i class="fas fa-chalkboard-teacher"></i> Gestionar Profesores</a></li>
            <?php else: ?>
                <li><a class="disabled"><i class="fas fa-chalkboard-teacher"></i> Gestionar Profesores (sin permiso)</a></li>
            <?php endif; ?>
            
            <?php if (in_array('NOTA_INSERTAR', $permisos_activos) || 
                      in_array('NOTA_ACTUALIZAR', $permisos_activos) || 
                      in_array('NOTA_ELIMINAR', $permisos_activos)): ?>
                <li><a href="notas.php"><i class="fas fa-clipboard-list"></i> Gestionar Notas</a></li>
            <?php else: ?>
                <li><a class="disabled"><i class="fas fa-clipboard-list"></i> Gestionar Notas (sin permiso)</a></li>
            <?php endif; ?>
            
            <?php if (in_array('REPORTE_CURSO', $permisos_activos) || 
                      in_array('REPORTE_NOTAS', $permisos_activos) || 
                      in_array('REPORTE_PERSONAL', $permisos_activos)): ?>
                <li><a href="reportes.php"><i class="fas fa-chart-bar"></i> Ver Reportes</a></li>
            <?php else: ?>
                <li><a class="disabled"><i class="fas fa-chart-bar"></i> Ver Reportes (sin permiso)</a></li>
            <?php endif; ?>
        </ul>
        
        <p style="margin-top: 20px; color: #666; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> Tienes <?php echo count($permisos_activos); ?> permisos activos.
        </p>
    </div>
</body>
</html>