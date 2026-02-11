import hashlib
from database import Database
from models.user_model import User

class AuthController:
    def __init__(self):
        self.db = Database()
        self.current_user = None

    def hash_password(self, password):
        """Encripta la contraseña usando SHA-256."""
        return hashlib.sha256(password.encode()).hexdigest()

    def login(self, username, password):
        """
        Valida las credenciales del usuario buscando en todas las tablas.
        Retorna un objeto User si es exitoso, o None si falla.
        """
        try:
            conn = self.db.connect()
            if not conn:
                return None, "Error de conexión a la base de datos"
            
            username = username.strip()
            password = password.strip()
            
            # 1. Intentar como Superadmin (Texto plano)
            sql = "SELECT * FROM superadmin"
            cursor = self.db.execute_query(sql)
            usuarios = self.db.fetch_all(cursor)
            
            for user in usuarios:
                u_name = str(user.get('username', '')).strip()
                u_code = str(user.get('codigo_sadmin', '')).strip()
                
                if username.lower() == u_name.lower() or username.lower() == u_code.lower():
                    db_pass = str(user.get('password_hash', '')).strip()
                    if db_pass == password:
                        self.current_user = User(
                            id=user.get('id_sadmin'),
                            username=u_code,
                            full_name=user.get('nombre_sadmin'),
                            role='superadmin'
                        )
                        return self.current_user, "Login exitoso (Superadmin)"

            # 2. Intentar como Alumno (SHA-256)
            pwd_hashed = self.hash_password(password)
            sql = "SELECT * FROM alumno WHERE activo = 1"
            cursor = self.db.execute_query(sql)
            alumnos = self.db.fetch_all(cursor)
            
            for alu in alumnos:
                u_code = str(alu.get('codigo_alu')).strip()
                u_email = str(alu.get('email_alu')).strip()
                
                if username.lower() == u_code.lower() or username.lower() == u_email.lower():
                    db_pass = str(alu.get('password')).strip()
                    if db_pass == pwd_hashed:
                        self.current_user = User(
                            id=alu.get('id_alu'),
                            username=u_code,
                            full_name=alu.get('nombre_alu'),
                            role='alumno',
                            email=u_email
                        )
                        return self.current_user, "Login exitoso (Alumno)"

            # 3. Intentar como Profesor (SHA-256)
            sql = "SELECT * FROM profesor WHERE activo = 1"
            cursor = self.db.execute_query(sql)
            profesores = self.db.fetch_all(cursor)
            
            for pro in profesores:
                u_code = str(pro.get('codigo_pro')).strip()
                u_email = str(pro.get('email_pro')).strip()
                
                if username.lower() == u_code.lower() or username.lower() == u_email.lower():
                    db_pass = str(pro.get('password')).strip()
                    if db_pass == pwd_hashed:
                        self.current_user = User(
                            id=pro.get('id_pro'),
                            username=u_code,
                            full_name=pro.get('nombre_pro'),
                            role='profesor',
                            email=u_email
                        )
                        return self.current_user, "Login exitoso (Profesor)"

            return None, "Credenciales incorrectas o usuario no encontrado"

        except Exception as e:
            return None, f"Error: {str(e)}"
        finally:
            self.db.disconnect()

    def logout(self):
        self.current_user = None
