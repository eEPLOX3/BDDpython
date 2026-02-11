from database import Database

class ProfessorController:
    def __init__(self):
        self.db = Database()

    def get_all_professors(self):
        """Obtiene la lista de todos los profesores."""
        try:
            # En PHP usa: SELECT * FROM profesor ORDER BY nombre_pro;
            # El usuario mencionó CREATE PROC sp_profesor_listar ...
            sql = "EXEC sp_profesor_listar"
            cursor = self.db.execute_query(sql)
            return self.db.fetch_all(cursor)
        except Exception as e:
            print(f"Error obteniendo profesores: {e}")
            return []
        finally:
            self.db.disconnect()

    def create_professor(self, data):
        """
        Inserta un nuevo profesor usando SP.
        """
        try:
            sql = """
                EXEC sp_profesor_insertar 
                @codigo_pro = ?, @cedula_pro = ?, @nombre_pro = ?,
                @direccion_pro = ?, @telefono_pro = ?, @genero_pro = ?,
                @email_pro = ?, @fecha_nac = ?, @ocupacion_pro = ?, 
                @estado_civil_pro = ?
            """
            params = (
                data['codigo'], data['cedula'], data['nombre'],
                data.get('direccion', ''), data.get('telefono', ''), data['genero'],
                data.get('email', ''), data.get('fecha_nac'), data.get('ocupacion', ''),
                data['estado_civil']
            )
            self.db.execute_query(sql, params)
            self.db.commit()
            return True, "Profesor creado exitosamente"
        except Exception as e:
            return False, f"Error al crear profesor: {e}"
        finally:
            self.db.disconnect()

    def update_professor(self, data):
        """Actualiza un profesor existente usando SP."""
        try:
            sql = """
                EXEC sp_profesor_actualizar 
                @codigo_pro = ?, @cedula_pro = ?, @nombre_pro = ?,
                @direccion_pro = ?, @telefono_pro = ?, @genero_pro = ?,
                @email_pro = ?, @fecha_nac = ?, @ocupacion_pro = ?, 
                @estado_civil_pro = ?
            """
            params = (
                data['codigo'], data['cedula'], data['nombre'],
                data.get('direccion', ''), data.get('telefono', ''), data['genero'],
                data.get('email', ''), data.get('fecha_nac'), data.get('ocupacion', ''),
                data['estado_civil']
            )
            self.db.execute_query(sql, params)
            self.db.commit()
            return True, "Profesor actualizado exitosamente"
        except Exception as e:
            return False, f"Error al actualizar profesor: {e}"
        finally:
            self.db.disconnect()

    def delete_professor(self, codigo_pro):
        """Elimina un profesor por su código."""
        try:
            sql = "EXEC sp_profesor_eliminar @codigo_pro = ?"
            self.db.execute_query(sql, (codigo_pro,))
            self.db.commit()
            return True, "Profesor eliminado exitosamente"
        except Exception as e:
            return False, f"Error al eliminar profesor: {e}"
        finally:
            self.db.disconnect()
