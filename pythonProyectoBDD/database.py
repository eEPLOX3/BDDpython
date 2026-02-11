import pyodbc
import os
from dotenv import load_dotenv

load_dotenv()

class Database:
    def __init__(self):
        self.host = os.getenv("DB_HOST", "EPLOX") # Changed from localhost,1433 to EPLOX
        self.db_name = os.getenv("DB_NAME", "ProyectoEscolasticoPro")
        self.username = os.getenv("DB_USER", "sa")
        self.password = os.getenv("DB_PASS", "18046d")
        self.driver = '{ODBC Driver 18 for SQL Server}'
        self.conn = None

    def connect(self):
        try:
            # Azure SQL Connection String format
            conn_str = (
                f"DRIVER={self.driver};"
                f"SERVER={self.host};"
                f"DATABASE={self.db_name};"
                f"UID={self.username};"
                f"PWD={self.password};"
                "Encrypt=yes;"
                "TrustServerCertificate=yes;"
                "Connection Timeout=30;"
            )
            self.conn = pyodbc.connect(conn_str)
            return self.conn
        except Exception as e:
            print(f"Error de conexión: {e}")
            return None

    def execute_query(self, sql, params=()):
        if not self.conn:
            self.connect()
        cursor = self.conn.cursor()
        cursor.execute(sql, params)
        return cursor

    def fetch_all(self, cursor):
        while cursor.description is None:
            if not cursor.nextset():
                return []
        
        columns = [column[0] for column in cursor.description]
        results = []
        for row in cursor.fetchall():
            results.append(dict(zip(columns, row)))
        return results

    def fetch_one(self, cursor):
        columns = [column[0] for column in cursor.description]
        row = cursor.fetchone()
        if row:
            return dict(zip(columns, row))
        return None

    def commit(self):
        if self.conn:
            self.conn.commit()

    def disconnect(self):
        if self.conn:
            self.conn.close()
            self.conn = None
