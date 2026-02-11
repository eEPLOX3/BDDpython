from database import Database

class UserDashboardController:
    def __init__(self):
        self.db = Database()

    def get_active_permissions(self, codigo_usuario, tipo_usuario):
        """
        Obtiene una lista de códigos de permisos activos (concedido=1)
        para el usuario y tipo especificados.
        Retorna: ['ALUMNO_INSERTAR', 'NOTA_VER', ...]
        """
        permisos_activos = []
        try:
            conn = self.db.connect()
            if not conn:
                return []

            sql = """
                SELECT codigo_permiso 
                FROM usuario_permisos 
                WHERE codigo_usuario = ? AND tipo_usuario = ? AND concedido = 1
            """
            cursor = self.db.execute_query(sql, (codigo_usuario, tipo_usuario))
            rows = self.db.fetch_all(cursor)
            
            for row in rows:
                permisos_activos.append(row['codigo_permiso'])
                
            return permisos_activos

        except Exception as e:
            print(f"Error obteniendo permisos: {e}")
            return []
        finally:
            self.db.disconnect()
