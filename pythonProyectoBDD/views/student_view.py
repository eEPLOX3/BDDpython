import customtkinter as ctk
from controllers.student_controller import StudentController

class StudentView(ctk.CTkFrame):
    def __init__(self, master):
        super().__init__(master)
        self.controller = StudentController()
        
        # Layout Principal
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(1, weight=1)

        # Header
        self.create_header()
        
        # Tabla de Alumnos
        self.create_table_area()
        
        # Cargar datos iniciales
        self.load_students()

    def create_header(self):
        header_frame = ctk.CTkFrame(self, fg_color="transparent")
        header_frame.grid(row=0, column=0, sticky="ew", padx=20, pady=20)
        
        title = ctk.CTkLabel(header_frame, text="Gestión de Alumnos", font=("Roboto", 24, "bold"))
        title.pack(side="left")
        
        btn_add = ctk.CTkButton(header_frame, text="+ Nuevo Alumno", command=self.open_student_modal)
        btn_add.pack(side="right")

    def create_table_area(self):
        # Frame scrollable para la lista
        self.table_frame = ctk.CTkScrollableFrame(self, label_text="Lista de Alumnos")
        self.table_frame.grid(row=1, column=0, sticky="nsew", padx=20, pady=(0, 20))
        self.table_frame.grid_columnconfigure(0, weight=1)

    def load_students(self):
        # Limpiar tabla
        for widget in self.table_frame.winfo_children():
            widget.destroy()
            
        students = self.controller.get_all_students()
        
        if not students:
            ctk.CTkLabel(self.table_frame, text="No hay alumnos registrados.").pack(pady=20)
            return

        for student in students:
            self.create_student_row(student)

    def create_student_row(self, student):
        row = ctk.CTkFrame(self.table_frame)
        row.pack(fill="x", padx=5, pady=5)
        
        # Info básica
        info = f"{student['codigo_alu']} - {student['nombre_alu']} (C.I: {student['cedula_alu']})"
        ctk.CTkLabel(row, text=info, font=("Roboto", 14)).pack(side="left", padx=10)
        
        # Botones de Acción
        btn_delete = ctk.CTkButton(row, text="Eliminar", fg_color="red", width=80, 
                                 command=lambda s=student: self.delete_student(s))
        btn_delete.pack(side="right", padx=5, pady=5)
        
        btn_edit = ctk.CTkButton(row, text="Editar", fg_color="orange", width=80,
                               command=lambda s=student: self.open_student_modal(s))
        btn_edit.pack(side="right", padx=5, pady=5)

    def delete_student(self, student):
        if self.controller.delete_student(student['codigo_alu']):
            self.load_students()
        else:
            print("Error al eliminar")

    def open_student_modal(self, student=None):
        # Crear ventana modal (Toplevel)
        modal = ctk.CTkToplevel(self)
        modal.title("Nuevo Alumno" if not student else "Editar Alumno")
        modal.geometry("500x700")
        modal.grab_set() # Hacer modal

        # Scrollable frame dentro del modal para el formulario
        form_frame = ctk.CTkScrollableFrame(modal)
        form_frame.pack(fill="both", expand=True, padx=20, pady=20)

        # Campos del formulario
        entries = {}
        
        fields = [
            ("Código", "codigo", student['codigo_alu'] if student else ""),
            ("Cédula", "cedula", student['cedula_alu'] if student else ""),
            ("Nombre", "nombre", student['nombre_alu'] if student else ""),
            ("Dirección", "direccion", student.get('direccion_alu', '') if student else ""),
            ("Teléfono", "telefono", student.get('telefono_alu', '') if student else ""),
            ("Email", "email", student.get('email_alu', '') if student else ""),
            ("Fecha Nac.", "fecha_nac", str(student.get('fecha_nac', '')) if student else ""), # Simplificado fecha
            ("Observaciones", "observaciones", student.get('observaciones', '') if student else "")
        ]

        for label_text, key, value in fields:
            ctk.CTkLabel(form_frame, text=label_text).pack(anchor="w", pady=(10, 0))
            entry = ctk.CTkEntry(form_frame)
            entry.insert(0, str(value) if value else "")
            entry.pack(fill="x", pady=(0, 10))
            
            # Si es edición y es el código, deshabilitar (clave primaria)
            if key == "codigo" and student:
                entry.configure(state="disabled")
                
            entries[key] = entry

        # Combos (Género y Estado Civil)
        ctk.CTkLabel(form_frame, text="Género").pack(anchor="w", pady=(10, 0))
        gender_cb = ctk.CTkComboBox(form_frame, values=["Masculino", "Femenino", "Otro"])
        if student and student.get('genero_alu'):
            gender_cb.set(student['genero_alu'])
        gender_cb.pack(fill="x", pady=(0, 10))
        entries['genero'] = gender_cb

        ctk.CTkLabel(form_frame, text="Estado Civil").pack(anchor="w", pady=(10, 0))
        civil_cb = ctk.CTkComboBox(form_frame, values=["Soltero", "Casado", "Divorciado", "Viudo"])
        if student and student.get('estado_civil_alu'):
            civil_cb.set(student['estado_civil_alu'])
        civil_cb.pack(fill="x", pady=(0, 10))
        entries['estado_civil'] = civil_cb

        # Botón Guardar
        def save():
            data = {k: v.get() for k, v in entries.items()}
            
            if student:
                success, msg = self.controller.update_student(data)
            else:
                success, msg = self.controller.create_student(data)
            
            if success:
                modal.destroy()
                self.load_students()
            else:
                print(f"Error: {msg}") # Idealmente mostrar en UI

        ctk.CTkButton(modal, text="Guardar", command=save, fg_color="green").pack(pady=20)
