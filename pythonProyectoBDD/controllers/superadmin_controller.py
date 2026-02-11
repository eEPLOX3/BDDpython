import json
from database import Database

class SuperAdminController:
    def __init__(self):
        self.db = Database()

    def search_users(self, query):
        """
        Busca alumnos y profesores usando los SPs:
        - sp_alumno_buscar_general
        - sp_profesor_buscar_general
        """
        results = []
        try:
            conn = self.db.connect()
            if not conn:
                return [], "Error de conexión"

            # 1. Buscar Alumnos
            cursor_alu = self.db.execute_query("EXEC sp_alumno_buscar_general ?", (query,))
            alumnos = self.db.fetch_all(cursor_alu)
            cursor_alu.close()
            
            for row in alumnos:
                # El SP retorna: codigo, nombre, cedula, telefono, email, estado, tipo, tipo_display
                results.append({
                    'codigo': row.get('codigo'),
                    'nombre': row.get('nombre'),
                    'tipo': row.get('tipo'), # 'alumno'
                    'tipo_display': row.get('tipo_display'), # 'Alumno'
                    'email': row.get('email'),
                    'estado': row.get('estado')
                })

            # 2. Buscar Profesores
            cursor_pro = self.db.execute_query("EXEC sp_profesor_buscar_general ?", (query,))
            profesores = self.db.fetch_all(cursor_pro)
            cursor_pro.close()

            for row in profesores:
                results.append({
                    'codigo': row.get('codigo'),
                    'nombre': row.get('nombre'),
                    'tipo': row.get('tipo'), # 'profesor'
                    'tipo_display': row.get('tipo_display'), # 'Profesor'
                    'email': row.get('email'),
                    'estado': row.get('estado')
                })

            return results, "Búsqueda completada"

        except Exception as e:
            return [], f"Error en búsqueda: {str(e)}"
        finally:
            self.db.disconnect()

    def ensure_permissions_table(self):
        """
        Verifica si la tabla usuario_permisos existe, si no, la crea.
        Lógica portada del PHP para auto-reparación.
        """
        try:
            # 1. Verificar si existe
            cursor = self.db.execute_query("SELECT COUNT(*) as existe FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'usuario_permisos'")
            row = self.db.fetch_one(cursor)
            
            if row and row['existe'] == 0:
                # 2. Crear tabla si no existe
                sql_create = """
                CREATE TABLE usuario_permisos (
                    id_asignacion INT IDENTITY(1,1) PRIMARY KEY,
                    codigo_usuario VARCHAR(50) NOT NULL,
                    tipo_usuario VARCHAR(20) NOT NULL,
                    codigo_permiso VARCHAR(50) NOT NULL,
                    concedido BIT DEFAULT 0,
                    fecha_asignacion DATETIME DEFAULT GETDATE(),
                    CONSTRAINT unique_usuario_permiso UNIQUE (codigo_usuario, tipo_usuario, codigo_permiso)
                )
                """
                self.db.execute_query(sql_create)
                print("Tabla 'usuario_permisos' creada correctamente.")
        except Exception as e:
            print(f"Advertencia al verificar tabla de permisos: {e}")

    def get_permissions(self, codigo_usuario, tipo_usuario):
        """
        Obtiene los permisos asignados usando sp_usuario_permisos_obtener.
        Incluye lógica de verificación de tabla.
        """
        try:
            conn = self.db.connect()
            if not conn:
                return {}, "Error de conexión"

            # Asegurar que la tabla exista (igual que en PHP)
            self.ensure_permissions_table()

            # Obtener datos del usuario (opcional según lógica PHP pero útil para validación)
            # cursor_info = self.db.execute_query("EXEC sp_usuario_obtener_info ?, ?", (codigo_usuario, tipo_usuario))
            # user_info = self.db.fetch_one(cursor_info)
            # if not user_info:
            #    return {}, "Usuario no encontrado"

            # Obtener permisos
            try:
                cursor = self.db.execute_query("EXEC sp_usuario_permisos_obtener ?, ?", (codigo_usuario, tipo_usuario))
                permisos_db = self.db.fetch_all(cursor)
            except Exception:
                # Fallback si falla el SP, consulta directa
                print("Fallback a consulta directa para permisos")
                sql_direct = "SELECT codigo_permiso, concedido FROM usuario_permisos WHERE codigo_usuario = ? AND tipo_usuario = ?"
                cursor = self.db.execute_query(sql_direct, (codigo_usuario, tipo_usuario))
                permisos_db = self.db.fetch_all(cursor)
            
            # Convertir a diccionario: {codigo_permiso: concedido (bool)}
            permisos_asignados = {}
            for p in permisos_db:
                permisos_asignados[p['codigo_permiso']] = bool(p['concedido'])
            
            return permisos_asignados, "Permisos cargados"

        except Exception as e:
            return {}, f"Error al obtener permisos: {str(e)}"
        finally:
            self.db.disconnect()

    def update_permissions_batch(self, codigo_usuario, tipo_usuario, permisos_dict):
        """
        Actualiza permisos en lote.
        Replica EXACTAMENTE la lógica de PHP:
        1. Intenta SP 'sp_usuario_permisos_actualizar_lote'.
        2. Si falla, usa fallback manual (Check -> Insert/Update) dentro de una transacción.
        """
        conn = self.db.connect()
        if not conn:
            return False, "Error de conexión"

        try:
            # 1. Intentar con SP (Optimización)
            try:
                permisos_list = []
                for codigo, concedido in permisos_dict.items():
                    permisos_list.append({
                        'codigo': codigo,
                        'concedido': 1 if concedido else 0
                    })
                json_string = json.dumps(permisos_list)

                self.db.execute_query("EXEC sp_usuario_permisos_actualizar_lote ?, ?, ?", 
                                    (codigo_usuario, tipo_usuario, json_string))
                self.db.commit()
                return True, "Permisos actualizados correctamente (SP)"

            except Exception as sp_error:
                print(f"DEBUG: Falló SP ({sp_error}). Iniciando fallback manual tipo PHP...")
                
                # 2. Fallback Manual (Igual que PHP)
                cursor = conn.cursor()
                
                for codigo, concedido in permisos_dict.items():
                    val_concedido = 1 if concedido else 0
                    
                    # A. Verificar existencia
                    # SELECT id_asignacion FROM usuario_permisos WHERE ...
                    chk_sql = "SELECT id_asignacion FROM usuario_permisos WHERE codigo_usuario = ? AND tipo_usuario = ? AND codigo_permiso = ?"
                    cursor.execute(chk_sql, (codigo_usuario, tipo_usuario, codigo))
                    row = cursor.fetchone()
                    
                    if row:
                        # B. UPDATE
                        upd_sql = """
                            UPDATE usuario_permisos 
                            SET concedido = ?, fecha_asignacion = GETDATE()
                            WHERE codigo_usuario = ? AND tipo_usuario = ? AND codigo_permiso = ?
                        """
                        cursor.execute(upd_sql, (val_concedido, codigo_usuario, tipo_usuario, codigo))
                    else:
                        # C. INSERT
                        ins_sql = """
                            INSERT INTO usuario_permisos (codigo_usuario, tipo_usuario, codigo_permiso, concedido)
                            VALUES (?, ?, ?, ?)
                        """
                        cursor.execute(ins_sql, (codigo_usuario, tipo_usuario, codigo, val_concedido))
                
                self.db.commit()
                return True, "Permisos actualizados correctamente (Manual)"

        except Exception as e:
            print(f"Error crítico al guardar permisos: {e}")
            return False, f"Error al guardar: {str(e)}"
        finally:
            self.db.disconnect()
