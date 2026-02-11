from database import Database

def test_sp():
    print("--- Probando Stored Procedures ---")
    db = Database()
    try:
        if db.connect():
            print("✅ Conectado.")
            query = "ALU"
            print(f"Buscando: {query}")
            
            # Prueba SP Alumnos
            print("\n1. Ejecutando sp_alumno_buscar_general...")
            try:
                # Intento 1: Sintaxis EXEC simple
                cursor = db.execute_query("EXEC sp_alumno_buscar_general ?", (query,))
                
                # Debug de cursor
                print(f"   Descripción inicial del cursor: {cursor.description}")
                
                results = db.fetch_all(cursor)
                print(f"   Resultados encontrados: {len(results)}")
                for r in results:
                    print(f"   - {r}")
                    
            except Exception as e:
                print(f"   ❌ Error ejecutando SP: {e}")

            db.disconnect()
    except Exception as e:
        print(f"Error general: {e}")

if __name__ == "__main__":
    test_sp()
