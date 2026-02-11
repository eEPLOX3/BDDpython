from database import Database

def create_sps():
    db = Database()
    conn = db.connect()
    if not conn:
        print("No se pudo conectar.")
        return

    try:
        cursor = conn.cursor()

        # 1. sp_profesor_listar
        print("Creando sp_profesor_listar...")
        cursor.execute("DROP PROCEDURE IF EXISTS sp_profesor_listar")
        cursor.execute("""
            CREATE PROCEDURE sp_profesor_listar
            AS
            BEGIN
                SELECT * FROM profesor ORDER BY nombre_pro;
            END
        """)
        
        # 2. sp_profesor_eliminar
        print("Creando sp_profesor_eliminar...")
        cursor.execute("DROP PROCEDURE IF EXISTS sp_profesor_eliminar")
        cursor.execute("""
            CREATE PROCEDURE sp_profesor_eliminar
                @codigo_pro VARCHAR(20)
            AS
            BEGIN
                DELETE FROM profesor WHERE codigo_pro = @codigo_pro;
            END
        """)

        # 3. sp_profesor_insertar
        print("Creando sp_profesor_insertar...")
        cursor.execute("DROP PROCEDURE IF EXISTS sp_profesor_insertar")
        cursor.execute("""
            CREATE PROCEDURE sp_profesor_insertar
                @codigo_pro VARCHAR(20),
                @cedula_pro VARCHAR(20),
                @nombre_pro VARCHAR(100),
                @direccion_pro VARCHAR(200),
                @telefono_pro VARCHAR(20),
                @genero_pro VARCHAR(20),
                @email_pro VARCHAR(100),
                @fecha_nac DATE,
                @ocupacion_pro VARCHAR(100),
                @estado_civil_pro VARCHAR(50)
            AS
            BEGIN
                INSERT INTO profesor (
                    codigo_pro, cedula_pro, nombre_pro, direccion_pro,
                    telefono_pro, genero_pro, email_pro, fecha_nac,
                    ocupacion_pro, estado_civil_pro
                )
                VALUES (
                    @codigo_pro, @cedula_pro, @nombre_pro, @direccion_pro,
                    @telefono_pro, @genero_pro, @email_pro, @fecha_nac,
                    @ocupacion_pro, @estado_civil_pro
                );
            END
        """)

        # 4. sp_profesor_actualizar
        print("Creando sp_profesor_actualizar...")
        cursor.execute("DROP PROCEDURE IF EXISTS sp_profesor_actualizar")
        cursor.execute("""
            CREATE PROCEDURE sp_profesor_actualizar
                @codigo_pro VARCHAR(20),
                @cedula_pro VARCHAR(20),
                @nombre_pro VARCHAR(100),
                @direccion_pro VARCHAR(200),
                @telefono_pro VARCHAR(20),
                @genero_pro VARCHAR(20),
                @email_pro VARCHAR(100),
                @fecha_nac DATE,
                @ocupacion_pro VARCHAR(100),
                @estado_civil_pro VARCHAR(50)
            AS
            BEGIN
                UPDATE profesor
                SET 
                    cedula_pro = @cedula_pro,
                    nombre_pro = @nombre_pro,
                    direccion_pro = @direccion_pro,
                    telefono_pro = @telefono_pro,
                    genero_pro = @genero_pro,
                    email_pro = @email_pro,
                    fecha_nac = @fecha_nac,
                    ocupacion_pro = @ocupacion_pro,
                    estado_civil_pro = @estado_civil_pro
                WHERE codigo_pro = @codigo_pro;
            END
        """)

        conn.commit()
        print("¡Todos los SPs fueron creados exitosamente!")

    except Exception as e:
        print(f"Error: {e}")
        conn.rollback()
    finally:
        db.disconnect()

if __name__ == "__main__":
    create_sps()
