from database import Database

def inspect_sp():
    db = Database()
    conn = db.connect()
    if not conn:
        print("No se pudo conectar.")
        return

    try:
        # sp_helptext devuelve el texto del SP en varias filas
        cursor = db.execute_query("sp_helptext 'sp_alumno_insertar'")
        rows = db.fetch_all(cursor)
        print("--- DEFINICIÓN SP_ALUMNO_INSERTAR ---")
        for row in rows:
            # row es un diccionario o tupla dependiendo de la impl
            # pyodbc row[0] es el texto
            print(list(row.values())[0], end='') 
    except Exception as e:
        print(f"Error: {e}")
    finally:
        db.disconnect()

if __name__ == "__main__":
    inspect_sp()
