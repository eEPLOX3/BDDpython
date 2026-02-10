<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit();
}

$conn = getConexion();
if ($conn === false) {
    die("Error de conexión: " . print_r(sqlsrv_errors(), true));
}

$accion = $_GET['accion'] ?? '';
$id = $_GET['id'] ?? '';
$alumno = null;

// Si es edición, cargar datos del alumno
if ($accion == 'editar' && $id) {
    $sql = "SELECT * FROM alumno WHERE codigo_alu = ?";
    $params = array($id);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt && sqlsrv_has_rows($stmt)) {
        $alumno = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    } else {
        header("Location: alumnos.php?mensaje=Alumno no encontrado");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $accion == 'nuevo' ? 'Nuevo Alumno' : 'Editar Alumno'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-4">
        <h2><?php echo $accion == 'nuevo' ? '➕ Nuevo Alumno' : '✏️ Editar Alumno'; ?></h2>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Datos del Alumno</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="alumnos.php?accion=<?php echo $accion == 'nuevo' ? 'insertar' : 'actualizar'; ?>">
                    <?php if ($accion == 'editar'): ?>
                        <input type="hidden" name="codigo_alu" value="<?php echo htmlspecialchars($alumno['codigo_alu'] ?? ''); ?>">
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="codigo_alu" class="form-label">Código del Alumno *</label>
                            <input type="text" class="form-control" id="codigo_alu" name="codigo_alu" 
                                   value="<?php echo htmlspecialchars($alumno['codigo_alu'] ?? ''); ?>" required>
                            <div class="form-text">Ingrese un código único para el alumno</div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cedula_alu" class="form-label">Cédula *</label>
                            <input type="text" class="form-control" id="cedula_alu" name="cedula_alu" 
                                   value="<?php echo htmlspecialchars($alumno['cedula_alu'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="nombre_alu" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre_alu" name="nombre_alu" 
                                   value="<?php echo htmlspecialchars($alumno['nombre_alu'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="direccion_alu" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion_alu" name="direccion_alu" rows="2"><?php echo htmlspecialchars($alumno['direccion_alu'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono_alu" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono_alu" name="telefono_alu" 
                                   value="<?php echo htmlspecialchars($alumno['telefono_alu'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email_alu" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email_alu" name="email_alu" 
                                   value="<?php echo htmlspecialchars($alumno['email_alu'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="genero_alu" class="form-label">Género</label>
                            <select class="form-select" id="genero_alu" name="genero_alu">
                                <option value="">Seleccionar...</option>
                                <option value="M" <?php echo (isset($alumno['genero_alu']) && $alumno['genero_alu'] == 'M') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="F" <?php echo (isset($alumno['genero_alu']) && $alumno['genero_alu'] == 'F') ? 'selected' : ''; ?>>Femenino</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="fecha_nac" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nac" name="fecha_nac" 
                                   value="<?php 
                                   if (isset($alumno['fecha_nac']) && $alumno['fecha_nac'] instanceof DateTime) {
                                       echo $alumno['fecha_nac']->format('Y-m-d');
                                   } elseif (!empty($alumno['fecha_nac'])) {
                                       echo htmlspecialchars($alumno['fecha_nac']);
                                   }
                                   ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="estado_civil_alu" class="form-label">Estado Civil</label>
                            <select class="form-select" id="estado_civil_alu" name="estado_civil_alu">
                                <option value="">Seleccionar...</option>
                                <option value="Soltero/a" <?php echo (isset($alumno['estado_civil_alu']) && $alumno['estado_civil_alu'] == 'Soltero/a') ? 'selected' : ''; ?>>Soltero/a</option>
                                <option value="Casado/a" <?php echo (isset($alumno['estado_civil_alu']) && $alumno['estado_civil_alu'] == 'Casado/a') ? 'selected' : ''; ?>>Casado/a</option>
                                <option value="Divorciado/a" <?php echo (isset($alumno['estado_civil_alu']) && $alumno['estado_civil_alu'] == 'Divorciado/a') ? 'selected' : ''; ?>>Divorciado/a</option>
                                <option value="Viudo/a" <?php echo (isset($alumno['estado_civil_alu']) && $alumno['estado_civil_alu'] == 'Viudo/a') ? 'selected' : ''; ?>>Viudo/a</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo htmlspecialchars($alumno['observaciones'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="alumnos.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> 
                            <?php echo $accion == 'nuevo' ? 'Guardar Alumno' : 'Actualizar Alumno'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if ($conn) {
    sqlsrv_close($conn);
}
?>