from flask import Flask, render_template, request, redirect, url_for, session, flash
from database import Database
import hashlib

app = Flask(__name__)
app.secret_key = '1505_proyect_secret' # Cambia esto por algo seguro
db = Database()

def encrypt_sha256(password):
    """Replica el hash('sha256', password) de PHP"""
    return hashlib.sha256(password.encode()).hexdigest()

@app.route('/')
def login():
    if 'loggedin' in session:
        return redirect(url_for('dashboard'))
    return render_template('login.html')

@app.route('/auth', methods=['POST'])
def auth():
    tipo = request.form.get('tipo_usuario')
    codigo = request.form.get('codigo').strip()
    password = request.form.get('password').strip()

    if tipo == 'superadmin':
        # Los admin no están encriptados según tu código PHP
        sql = "SELECT * FROM superadmin WHERE username = ? AND password_hash = ?"
        user = db.execute_query(sql, (codigo, password))
    else:
        # Alumnos y Profesores usan SHA-256
        pass_encriptada = encrypt_sha256(password)
        tabla = 'alumno' if tipo == 'alumno' else 'profesor'
        id_col = 'codigo_alu' if tipo == 'alumno' else 'codigo_prof'
        nom_col = 'nombre_alu' if tipo == 'alumno' else 'nombre_prof'
        
        sql = f"SELECT {id_col} as id, {nom_col} as nombre FROM {tabla} WHERE {id_col} = ? AND password_hash = ?"
        user = db.execute_query(sql, (codigo, pass_encriptada))

    if user:
        # Guardar sesión (Igual que session.php)
        session['loggedin'] = True
        session['user_id'] = user[0]['id'] if 'id' in user[0] else user[0]['username']
        session['user_name'] = user[0]['nombre'] if 'nombre' in user[0] else user[0]['username']
        session['user_type'] = tipo
        
        if tipo == 'superadmin':
            return redirect(url_for('dashboard_admin'))
        return redirect(url_for('dashboard_user'))
    else:
        return render_template('login.html', error="Credenciales incorrectas o usuario no activo")

@app.route('/dashboard-admin')
def dashboard_admin():
    if session.get('user_type') != 'superadmin':
        return redirect(url_for('login'))
    return "Bienvenido al Panel de SuperAdmin (Próximamente)"

@app.route('/dashboard-usuario')
def dashboard_user():
    if 'loggedin' not in session:
        return redirect(url_for('login'))
    return f"Hola {session['user_name']}, bienvenido a tu panel."

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

if __name__ == '__main__':
    app.run(debug=True)