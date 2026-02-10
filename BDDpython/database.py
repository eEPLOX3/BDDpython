import pyodbc

class Database:
    def __init__(self):
        # Tus credenciales exactas de SQL Server
        self.connection_string = (
            'DRIVER={ODBC Driver 17 for SQL Server};'
            'SERVER=localhost,1433;'
            'DATABASE=ProyectoEscolasticoPro;'
            'UID=sa;'
            'PWD=18046d'
        )

    def connect(self):
        try:
            return pyodbc.connect(self.connection_string)
        except Exception as e:
            print(f"Error conectando a SQL Server: {e}")
            return None

    def execute_query(self, sql, params=()):
        conn = self.connect()
        if conn:
            cursor = conn.cursor()
            cursor.execute(sql, params)
            # Si es una consulta SELECT, traemos los nombres de columnas
            if cursor.description:
                columns = [column[0] for column in cursor.description]
                results = [dict(zip(columns, row)) for row in cursor.fetchall()]
                conn.close()
                return results
            conn.commit()
            conn.close()
        return None