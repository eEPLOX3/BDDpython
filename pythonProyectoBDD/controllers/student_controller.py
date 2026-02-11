from database import Database

class StudentController:
    def __init__(self):
        self.db = Database()

    def get_all_students(self):
        """Obtiene la lista de todos los alumnos."""
        try:
            # Intentar usar SP, si no existe, usar SELECT
            # En PHP usa: EXEC sp_alumno_listar
            sql = "EXEC sp_alumno_listar"
            cursor = self.db.execute_query(sql)
            return self.db.fetch_all(cursor)
        except Exception as e:
            print(f"Error obteniendo alumnos: {e}")
            return []
        finally:
            self.db.disconnect()

    def create_student(self, data):
        """
        Inserta un nuevo alumno usando SP.
        data: diccionario con claves que coinciden con los params del SP.
        """
        try:
            sql = """
                EXEC sp_alumno_insertar 
                @codigo_alu = ?, @cedula_alu = ?, @nombre_alu = ?,
                @direccion_alu = ?, @telefono_alu = ?, @genero_alu = ?,
                @email_alu = ?, @fecha_nac = ?, @observaciones = ?,
                @estado_civil_alu = ?
            """
            params = (
                data['codigo'], data['cedula'], data['nombre'],
                data.get('direccion', ''), data.get('telefono', ''), data['genero'],
                data.get('email', ''), data.get('fecha_nac'), data.get('observaciones', ''),
                data['estado_civil']
            )
            self.db.execute_query(sql, params)
            self.db.commit() # Importante guardar cambios
            return True, "Alumno creado exitosamente"
        except Exception as e:
            return False, f"Error al crear alumno: {e}"
        finally:
            self.db.disconnect()

    def update_student(self, data):
        """Actualiza un alumno existente usando SP."""
        try:
            sql = """
                EXEC sp_alumno_actualizar 
                @codigo_alu = ?, @cedula_alu = ?, @nombre_alu = ?,
                @direccion_alu = ?, @telefono_alu = ?, @genero_alu = ?,
                @email_alu = ?, @fecha_nac = ?, @observaciones = ?,
                @estado_civil_alu = ?
            """
            params = (
                data['codigo'], data['cedula'], data['nombre'],
                data.get('direccion', ''), data.get('telefono', ''), data['genero'],
                data.get('email', ''), data.get('fecha_nac'), data.get('observaciones', ''),
                data['estado_civil']
            )
            self.db.execute_query(sql, params)
            self.db.commit()
            return True, "Alumno actualizado exitosamente"
        except Exception as e:
            return False, f"Error al actualizar alumno: {e}"
        finally:
            self.db.disconnect()

    def delete_student(self, codigo_alu):
        """Elimina un alumno por su código."""
        try:
            sql = "EXEC sp_alumno_eliminar @codigo_alu = ?"
            self.db.execute_query(sql, (codigo_alu,))
            self.db.commit()
            return True, "Alumno eliminado exitosamente"
        except Exception as e:
            return False, f"Error al eliminar alumno: {e}"
        finally:
            self.db.disconnect()
