<?php
// dashboard/alumnos.php
session_start();

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Incluir conexión
require_once '../config/database.php';

$mensaje = '';
$tipo_mensaje = '';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Procesar acciones de inserción, actualización o eliminación
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $accion = $_POST['accion'] ?? '';
        
        if ($accion == 'insertar') {
            // Usar Stored Procedure para insertar
            $sql = "EXEC sp_alumno_insertar 
                    @codigo_alu = ?,
                    @cedula_alu = ?,
                    @nombre_alu = ?,
                    @direccion_alu = ?,
                    @telefono_alu = ?,
                    @genero_alu = ?,
                    @email_alu = ?,
                    @fecha_nac = ?,
                    @observaciones = ?,
                    @estado_civil_alu = ?";
            
            $params = [
                $_POST['codigo_alu'],
                $_POST['cedula_alu'],
                $_POST['nombre_alu'],
                $_POST['direccion_alu'] ?? '',
                $_POST['telefono_alu'] ?? '',
                $_POST['genero_alu'],
                $_POST['email_alu'] ?? '',
                $_POST['fecha_nac'] ?? NULL,
                $_POST['observaciones'] ?? '',
                $_POST['estado_civil_alu']
            ];
            
            $stmt = $db->executeQuery($sql, $params);
            $mensaje = "Alumno insertado exitosamente";
            $tipo_mensaje = "success";
            
        } elseif ($accion == 'actualizar') {
            // Usar Stored Procedure para actualizar
            $sql = "EXEC sp_alumno_actualizar 
                    @codigo_alu = ?,
                    @cedula_alu = ?,
                    @nombre_alu = ?,
                    @direccion_alu = ?,
                    @telefono_alu = ?,
                    @genero_alu = ?,
                    @email_alu = ?,
                    @fecha_nac = ?,
                    @observaciones = ?,
                    @estado_civil_alu = ?";
            
            $params = [
                $_POST['codigo_alu'],
                $_POST['cedula_alu'],
                $_POST['nombre_alu'],
                $_POST['direccion_alu'] ?? '',
                $_POST['telefono_alu'] ?? '',
                $_POST['genero_alu'],
                $_POST['email_alu'] ?? '',
                $_POST['fecha_nac'] ?? NULL,
                $_POST['observaciones'] ?? '',
                $_POST['estado_civil_alu']
            ];
            
            $stmt = $db->executeQuery($sql, $params);
            $mensaje = "Alumno actualizado exitosamente";
            $tipo_mensaje = "success";
            
        } elseif ($accion == 'eliminar') {
            // Usar Stored Procedure para eliminar (con eliminación en cascada)
            try {
                $sql = "EXEC sp_alumno_eliminar @codigo_alu = ?";
                $params = [$_POST['codigo_alu']];
                $stmt = $db->executeQuery($sql, $params);
                $mensaje = "Alumno y todos sus registros asociados eliminados exitosamente";
                $tipo_mensaje = "success";
            } catch (Exception $e) {
                $mensaje = "Error al eliminar: " . $e->getMessage();
                $tipo_mensaje = "error";
            }
        }
    }
    
    // Obtener todos los alumnos usando Stored Procedure
    $sql_alumnos = "EXEC sp_alumno_listar";
    $stmt_alumnos = $db->executeQuery($sql_alumnos);
    $alumnos = $db->fetchAll($stmt_alumnos);
    
    $db->disconnect();
    
} catch (Exception $e) {
    $mensaje = "Error: " . $e->getMessage();
    $tipo_mensaje = "error";
    $alumnos = [];
}

// Variables para el formulario modal
$accion_form = $_GET['accion'] ?? 'listar';
$alumno_editar = null;

if ($accion_form == 'editar' && isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->connect();
        $sql = "SELECT * FROM alumno WHERE codigo_alu = ?";
        $stmt = $db->executeQuery($sql, [$_GET['id']]);
        $alumno_editar = $db->fetchOne($stmt);
        $db->disconnect();
    } catch (Exception $e) {
        $alumno_editar = null;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Alumnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .container-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header-section h1 {
            margin: 0;
            color: #2c3e50;
        }
        .btn-nuevo {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn-nuevo:hover {
            background: #229954;
            color: white;
        }
        .alert-box {
            margin-bottom: 20px;
            border-radius: 5px;
            padding: 15px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-responsive {
            border-radius: 5px;
            overflow: hidden;
        }
        table {
            margin: 0;
        }
        table thead {
            background: #2c3e50;
            color: white;
        }
        table thead th {
            border: none;
            padding: 15px;
            font-weight: 600;
        }
        table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-color: #f0f0f0;
        }
        table tbody tr:hover {
            background: #f9f9f9;
        }
        .btn-accion {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 4px;
            margin: 0 2px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-editar {
            background: #3498db;
            color: white;
        }
        .btn-editar:hover {
            background: #2980b9;
            color: white;
        }
        .btn-eliminar {
            background: #e74c3c;
            color: white;
        }
        .btn-eliminar:hover {
            background: #c0392b;
            color: white;
        }
        .btn-volver {
            background: #95a5a6;
            color: white;
        }
        .btn-volver:hover {
            background: #7f8c8d;
            color: white;
        }
        .modal-header {
            background: #2c3e50;
            color: white;
            border: none;
        }
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        .form-control:focus, .form-select:focus {
            border-color: #27ae60;
            box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
        }
        .btn-primary {
            background: #27ae60;
            border: none;
        }
        .btn-primary:hover {
            background: #229954;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container-main">
        <!-- Header -->
        <div class="header-section">
            <div>
                <h1><i class="fas fa-user-graduate"></i> Gestionar Alumnos</h1>
            </div>
            <div>
                <a href="dashboard-usuario.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver</a>
                <button type="button" class="btn-nuevo" data-bs-toggle="modal" data-bs-target="#modalAlumno">
                    <i class="fas fa-plus"></i> Nuevo Alumno
                </button>
            </div>
        </div>

        <!-- Mensaje de éxito o error -->
        <?php if ($mensaje): ?>
            <div class="alert-box alert-<?php echo $tipo_mensaje; ?>">
                <i class="fas fa-<?php echo $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Tabla de alumnos -->
        <div class="table-responsive">
            <?php if (!empty($alumnos)): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Género</th>
                            <th>Estado Civil</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alumnos as $alumno): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($alumno['codigo_alu']); ?></td>
                                <td><?php echo htmlspecialchars($alumno['cedula_alu']); ?></td>
                                <td><?php echo htmlspecialchars($alumno['nombre_alu']); ?></td>
                                <td><?php echo htmlspecialchars($alumno['email_alu'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($alumno['telefono_alu'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($alumno['genero_alu'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($alumno['estado_civil_alu'] ?? '-'); ?></td>
                                <td>
                                    <button class="btn-accion btn-editar" data-bs-toggle="modal" data-bs-target="#modalAlumno" 
                                            onclick="cargarAlumnoEditar('<?php echo htmlspecialchars($alumno['codigo_alu']); ?>')">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form style="display:inline-block;" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este alumno?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="codigo_alu" value="<?php echo htmlspecialchars($alumno['codigo_alu']); ?>">
                                        <button type="submit" class="btn-accion btn-eliminar">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 20px;"></i>
                    <p>No hay alumnos registrados</p>
                    <button type="button" class="btn-nuevo" data-bs-toggle="modal" data-bs-target="#modalAlumno">
                        <i class="fas fa-plus"></i> Crear primer alumno
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para insertar/editar alumno -->
    <div class="modal fade" id="modalAlumno" tabindex="-1" aria-labelledby="modalAlumnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAlumnoLabel">
                        <span id="modalTitulo">Nuevo Alumno</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="formAlumno">
                    <div class="modal-body">
                        <input type="hidden" name="accion" id="accionForm" value="insertar">
                        <input type="hidden" name="codigo_alu_original" id="codigo_alu_original">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="codigo_alu" class="form-label">Código *</label>
                                    <input type="text" class="form-control" id="codigo_alu" name="codigo_alu" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="cedula_alu" class="form-label">Cédula *</label>
                                    <input type="text" class="form-control" id="cedula_alu" name="cedula_alu" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nombre_alu" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre_alu" name="nombre_alu" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="genero_alu" class="form-label">Género *</label>
                                    <select class="form-select" id="genero_alu" name="genero_alu" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email_alu" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email_alu" name="email_alu">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono_alu" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono_alu" name="telefono_alu">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="direccion_alu" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="direccion_alu" name="direccion_alu">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fecha_nac" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nac" name="fecha_nac">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="estado_civil_alu" class="form-label">Estado Civil *</label>
                                    <select class="form-select" id="estado_civil_alu" name="estado_civil_alu" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Soltero">Soltero</option>
                                        <option value="Casado">Casado</option>
                                        <option value="Divorciado">Divorciado</option>
                                        <option value="Viudo">Viudo</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Limpiar formulario al abrir modal de nuevo alumno
        document.getElementById('modalAlumno').addEventListener('show.bs.modal', function(e) {
            if (!e.relatedTarget || !e.relatedTarget.classList.contains('btn-editar')) {
                // Si es nuevo alumno
                document.getElementById('formAlumno').reset();
                document.getElementById('accionForm').value = 'insertar';
                document.getElementById('modalTitulo').textContent = 'Nuevo Alumno';
                document.getElementById('codigo_alu').disabled = false;
            }
        });

        // Cargar datos del alumno para editar
        function cargarAlumnoEditar(codigo) {
            // Hacer petición AJAX para obtener datos del alumno
            fetch('alumnos.php?accion=obtener&id=' + encodeURIComponent(codigo))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const alumno = data.alumno;
                        document.getElementById('codigo_alu').value = alumno.codigo_alu;
                        document.getElementById('cedula_alu').value = alumno.cedula_alu;
                        document.getElementById('nombre_alu').value = alumno.nombre_alu;
                        document.getElementById('genero_alu').value = alumno.genero_alu;
                        document.getElementById('email_alu').value = alumno.email_alu || '';
                        document.getElementById('telefono_alu').value = alumno.telefono_alu || '';
                        document.getElementById('direccion_alu').value = alumno.direccion_alu || '';
                        document.getElementById('fecha_nac').value = alumno.fecha_nac || '';
                        document.getElementById('estado_civil_alu').value = alumno.estado_civil_alu;
                        document.getElementById('observaciones').value = alumno.observaciones || '';
                        document.getElementById('accionForm').value = 'actualizar';
                        document.getElementById('modalTitulo').textContent = 'Editar Alumno';
                        document.getElementById('codigo_alu').disabled = true;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>

<?php
// Endpoint AJAX para obtener datos del alumno
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener' && isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->connect();
        // Usar SQL SELECT directo para obtener un alumno específico (no hay SP para esto)
        $sql = "SELECT * FROM alumno WHERE codigo_alu = ?";
        $stmt = $db->executeQuery($sql, [$_GET['id']]);
        $alumno = $db->fetchOne($stmt);
        $db->disconnect();
        
        if ($alumno) {
            echo json_encode(['success' => true, 'alumno' => $alumno]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Alumno no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>