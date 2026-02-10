<?php
// index.php - VERSIÓN CON ENCRIPTACIÓN SHA-256
session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if ($_SESSION['user_type'] === 'superadmin') {
        header('Location: dashboard/dashboard-superadmin.php');
    } else {
        header('Location: dashboard/dashboard-usuario.php');
    }
    exit();
}

require_once 'config/database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_usuario = $_POST['tipo_usuario'] ?? '';
    $codigo = trim($_POST['codigo'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // DEBUG
    error_log("=== INTENTO DE LOGIN ===");
    error_log("Tipo: $tipo_usuario, Código: '$codigo', Password: '$password'");
    
    if (empty($tipo_usuario) || empty($codigo) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        try {
            $db = new Database();
            $conn = $db->connect();
            
            if ($tipo_usuario === 'superadmin') {
                // Los superadmin NO están encriptados (password_hash es texto plano)
                $sql = "SELECT * FROM superadmin";
                $stmt = $db->executeQuery($sql, []);
                $todos = $db->fetchAll($stmt);
                
                $usuario_encontrado = null;
                foreach ($todos as $admin) {
                    $username_clean = trim($admin['username']);
                    $codigo_clean = trim($admin['codigo_sadmin']);
                    
                    if (strcasecmp($username_clean, $codigo) === 0 || 
                        strcasecmp($codigo_clean, $codigo) === 0) {
                        $usuario_encontrado = $admin;
                        break;
                    }
                }
                
                if ($usuario_encontrado) {
                    $bd_password = trim($usuario_encontrado['password_hash']);
                    
                    // Superadmin: comparación directa (sin encriptar)
                    if ($bd_password === $password) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_type'] = 'superadmin';
                        $_SESSION['user_name'] = $usuario_encontrado['nombre_sadmin'];
                        $_SESSION['user_code'] = $usuario_encontrado['codigo_sadmin'];
                        
                        header('Location: dashboard/dashboard-superadmin.php');
                        exit();
                    } else {
                        $error = 'Contraseña incorrecta para superadmin';
                    }
                } else {
                    $error = 'Superadmin no encontrado';
                }
                
            } elseif ($tipo_usuario === 'alumno') {
                // Alumno: encriptar con SHA-256
                $password_encrypted = hash('sha256', $password);
                error_log("Password alumno encriptado: $password_encrypted");
                
                $sql = "SELECT * FROM alumno WHERE activo = 1";
                $stmt = $db->executeQuery($sql, []);
                $todos_alumnos = $db->fetchAll($stmt);
                
                error_log("Total alumnos activos: " . count($todos_alumnos));
                
                $alumno_encontrado = null;
                
                foreach ($todos_alumnos as $alumno) {
                    $codigo_alu_clean = trim($alumno['codigo_alu']);
                    $email_alu_clean = trim($alumno['email_alu']);
                    
                    error_log("Comparando con: '$codigo_alu_clean' y email: '$email_alu_clean'");
                    error_log("Password BD: " . $alumno['password']);
                    error_log("Password encriptado ingresado: $password_encrypted");
                    
                    if (strcasecmp($codigo_alu_clean, $codigo) === 0 || 
                        strcasecmp($email_alu_clean, $codigo) === 0) {
                        $alumno_encontrado = $alumno;
                        error_log("✅ Alumno encontrado: " . $alumno_encontrado['nombre_alu']);
                        break;
                    }
                }
                
                if ($alumno_encontrado) {
                    $bd_password = trim($alumno_encontrado['password']);
                    
                    // Comparar SHA-256
                    if ($bd_password === $password_encrypted) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_type'] = 'alumno';
                        $_SESSION['user_name'] = $alumno_encontrado['nombre_alu'];
                        $_SESSION['user_code'] = $alumno_encontrado['codigo_alu'];
                        
                        header('Location: dashboard/dashboard-usuario.php');
                        exit();
                    } else {
                        $error = 'Contraseña incorrecta para alumno';
                        error_log("❌ SHA-256 no coincide");
                        error_log("BD: $bd_password");
                        error_log("Ingresado: $password_encrypted");
                    }
                } else {
                    $error = 'Alumno no encontrado o inactivo';
                    error_log("❌ Alumno no encontrado");
                }
                
            } elseif ($tipo_usuario === 'profesor') {
                // Profesor: encriptar con SHA-256
                $password_encrypted = hash('sha256', $password);
                error_log("Password profesor encriptado: $password_encrypted");
                
                $sql = "SELECT * FROM profesor WHERE activo = 1";
                $stmt = $db->executeQuery($sql, []);
                $todos_profesores = $db->fetchAll($stmt);
                
                error_log("Total profesores activos: " . count($todos_profesores));
                
                $profesor_encontrado = null;
                
                foreach ($todos_profesores as $profesor) {
                    $codigo_pro_clean = trim($profesor['codigo_pro']);
                    $email_pro_clean = trim($profesor['email_pro']);
                    
                    error_log("Comparando con: '$codigo_pro_clean' y email: '$email_pro_clean'");
                    error_log("Password BD: " . $profesor['password']);
                    error_log("Password encriptado ingresado: $password_encrypted");
                    
                    if (strcasecmp($codigo_pro_clean, $codigo) === 0 || 
                        strcasecmp($email_pro_clean, $codigo) === 0) {
                        $profesor_encontrado = $profesor;
                        error_log("✅ Profesor encontrado: " . $profesor_encontrado['nombre_pro']);
                        break;
                    }
                }
                
                if ($profesor_encontrado) {
                    $bd_password = trim($profesor_encontrado['password']);
                    
                    // Comparar SHA-256
                    if ($bd_password === $password_encrypted) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_type'] = 'profesor';
                        $_SESSION['user_name'] = $profesor_encontrado['nombre_pro'];
                        $_SESSION['user_code'] = $profesor_encontrado['codigo_pro'];
                        
                        header('Location: dashboard/dashboard-usuario.php');
                        exit();
                    } else {
                        $error = 'Contraseña incorrecta para profesor';
                        error_log("❌ SHA-256 no coincide");
                        error_log("BD: $bd_password");
                        error_log("Ingresado: $password_encrypted");
                    }
                } else {
                    $error = 'Profesor no encontrado o inactivo';
                    error_log("❌ Profesor no encontrado");
                }
            }
            
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
            error_log("Error BD: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Académico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .logo i {
            font-size: 2.5rem;
            color: #4a6fa5;
        }

        .logo h1 {
            font-size: 1.8rem;
            color: #333;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .alert-error {
            background: #fee;
            color: #d9534f;
            border: 2px solid #d9534f;
        }

        .alert-info {
            background: #d9edf7;
            color: #31708f;
            border: 2px solid #bce8f1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #4a6fa5;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #4a6fa5, #166088);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            background: linear-gradient(to right, #3a5a95, #0d5078);
            transform: translateY(-2px);
        }

        .auto-login-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .auto-btn {
            flex: 1;
            padding: 12px;
            background: #f8f9fa;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .auto-btn:hover {
            background: #e9ecef;
        }

        .auto-btn.alumno {
            border-color: #5cb85c;
            color: #5cb85c;
        }

        .auto-btn.profesor {
            border-color: #f0ad4e;
            color: #f0ad4e;
        }

        .auto-btn.superadmin {
            border-color: #4a6fa5;
            color: #4a6fa5;
        }

        .encryption-info {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>Sistema Académico</h1>
            </div>
            <p>Login con encriptación SHA-256</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="fas fa-lock"></i> 
            <strong>Nota:</strong> Las contraseñas de alumnos y profesores están encriptadas con SHA-256
        </div>
        
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="tipo_usuario"><i class="fas fa-user-tag"></i> Tipo de Usuario</label>
                <select name="tipo_usuario" id="tipo_usuario" class="form-control" required>
                    <option value="">Seleccione tipo...</option>
                    <option value="alumno">Alumno</option>
                    <option value="profesor">Profesor</option>
                    <option value="superadmin">Super Administrador</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="codigo"><i class="fas fa-user"></i> Código/Usuario</label>
                <input type="text" name="codigo" id="codigo" class="form-control" 
                       placeholder="Ej: ALU-001, PROF-001, admin" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" name="password" id="password" class="form-control" 
                       placeholder="Ingrese su contraseña" 
                       required>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>
        
        <div class="auto-login-buttons">
            <button type="button" class="auto-btn alumno" onclick="autoLogin('alumno', 'ALU-001', '1234')">
                <i class="fas fa-user-graduate"></i> Alumno
            </button>
            <button type="button" class="auto-btn profesor" onclick="autoLogin('profesor', 'PROF-001', '123456')">
                <i class="fas fa-chalkboard-teacher"></i> Profesor
            </button>
            <button type="button" class="auto-btn superadmin" onclick="autoLogin('superadmin', 'admin', 'admin123')">
                <i class="fas fa-user-shield"></i> Superadmin
            </button>
        </div>
        
        <div class="encryption-info">
            <p><strong>Contraseñas encriptadas en la BD:</strong></p>
            <ul style="margin-left: 20px;">
                <li><code>1234</code> → <code>03ac674216f3e15c761ee1a5e255f067953623c8b388b4459e13f978d7c846f4</code></li>
                <li><code>123456</code> → <code>8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92</code></li>
            </ul>
            <p style="margin-top: 10px; font-size: 0.8em;">
                <i class="fas fa-info-circle"></i> 
                El sistema ahora encripta la contraseña ingresada con SHA-256 antes de comparar.
            </p>
        </div>
    </div>

    <script>
        function autoLogin(tipo, usuario, password) {
            document.getElementById('tipo_usuario').value = tipo;
            document.getElementById('codigo').value = usuario;
            document.getElementById('password').value = password;
            
            // Resaltar botón activo
            document.querySelectorAll('.auto-btn').forEach(btn => {
                btn.style.background = '#f8f9fa';
                btn.style.color = btn.classList.contains(tipo) ? 
                    (tipo === 'alumno' ? '#5cb85c' : 
                     tipo === 'profesor' ? '#f0ad4e' : '#4a6fa5') : '';
            });
            event.target.style.background = tipo === 'alumno' ? '#5cb85c20' : 
                                          tipo === 'profesor' ? '#f0ad4e20' : '#4a6fa520';
            
            // Enviar formulario automáticamente
            setTimeout(() => {
                document.getElementById('loginForm').submit();
            }, 300);
        }
        
        // Auto-seleccionar tipo según lo que se escriba
        document.getElementById('codigo').addEventListener('input', function() {
            const valor = this.value.toUpperCase();
            const tipoSelect = document.getElementById('tipo_usuario');
            
            if (valor.startsWith('ALU') || valor.includes('ALUMNO')) {
                tipoSelect.value = 'alumno';
            } else if (valor.startsWith('PROF') || valor.includes('PROFESOR')) {
                tipoSelect.value = 'profesor';
            } else if (valor.includes('ADMIN') || valor.includes('SADM') || valor.includes('SUPER')) {
                tipoSelect.value = 'superadmin';
            }
        });
    </script>
</body>
</html>