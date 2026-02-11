import customtkinter as ctk
from controllers.professor_controller import ProfessorController

class ProfessorView(ctk.CTkFrame):
    def __init__(self, master):
        super().__init__(master)
        self.controller = ProfessorController()
        
        # Layout Principal
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(1, weight=1)

        # Header
        self.create_header()
        
        # Tabla de Profesores
        self.create_table_area()
        
        # Cargar datos iniciales
        self.load_professors()

    def create_header(self):
        header_frame = ctk.CTkFrame(self, fg_color="transparent")
        header_frame.grid(row=0, column=0, sticky="ew", padx=20, pady=20)
        
        title = ctk.CTkLabel(header_frame, text="Gestión de Profesores", font=("Roboto", 24, "bold"))
        title.pack(side="left")
        
        btn_add = ctk.CTkButton(header_frame, text="+ Nuevo Profesor", command=self.open_professor_modal)
        btn_add.pack(side="right")

    def create_table_area(self):
        # Frame scrollable para la lista
        self.table_frame = ctk.CTkScrollableFrame(self, label_text="Lista de Profesores")
        self.table_frame.grid(row=1, column=0, sticky="nsew", padx=20, pady=(0, 20))
        self.table_frame.grid_columnconfigure(0, weight=1)

    def load_professors(self):
        # Limpiar tabla
        for widget in self.table_frame.winfo_children():
            widget.destroy()
            
        professors = self.controller.get_all_professors()
        
        if not professors:
            ctk.CTkLabel(self.table_frame, text="No hay profesores registrados.").pack(pady=20)
            return

        for prof in professors:
            self.create_professor_row(prof)

    def create_professor_row(self, prof):
        row = ctk.CTkFrame(self.table_frame)
        row.pack(fill="x", padx=5, pady=5)
        
        # Info básica
        info = f"{prof['codigo_pro']} - {prof['nombre_pro']} ({prof.get('ocupacion_pro', 'Docente')})"
        ctk.CTkLabel(row, text=info, font=("Roboto", 14)).pack(side="left", padx=10)
        
        # Botones de Acción
        btn_delete = ctk.CTkButton(row, text="Eliminar", fg_color="red", width=80, 
                                 command=lambda p=prof: self.delete_professor(p))
        btn_delete.pack(side="right", padx=5, pady=5)
        
        btn_edit = ctk.CTkButton(row, text="Editar", fg_color="orange", width=80,
                               command=lambda p=prof: self.open_professor_modal(p))
        btn_edit.pack(side="right", padx=5, pady=5)

    def delete_professor(self, prof):
        if self.controller.delete_professor(prof['codigo_pro']):
            self.load_professors()
        else:
            print("Error al eliminar")

    def open_professor_modal(self, prof=None):
        # Crear ventana modal (Toplevel)
        modal = ctk.CTkToplevel(self)
        modal.title("Nuevo Profesor" if not prof else "Editar Profesor")
        modal.geometry("500x750")
        modal.grab_set()

        # Scrollable frame dentro del modal para el formulario
        form_frame = ctk.CTkScrollableFrame(modal)
        form_frame.pack(fill="both", expand=True, padx=20, pady=20)

        # Campos del formulario
        entries = {}
        
        fields = [
            ("Código", "codigo", prof['codigo_pro'] if prof else ""),
            ("Cédula", "cedula", prof['cedula_pro'] if prof else ""),
            ("Nombre", "nombre", prof['nombre_pro'] if prof else ""),
            ("Dirección", "direccion", prof.get('direccion_pro', '') if prof else ""),
            ("Teléfono", "telefono", prof.get('telefono_pro', '') if prof else ""),
            ("Email", "email", prof.get('email_pro', '') if prof else ""),
            ("Fecha Nac.", "fecha_nac", str(prof.get('fecha_nac', '')) if prof else ""),
            ("Ocupación", "ocupacion", prof.get('ocupacion_pro', '') if prof else "") # Campo específico
        ]

        for label_text, key, value in fields:
            ctk.CTkLabel(form_frame, text=label_text).pack(anchor="w", pady=(10, 0))
            entry = ctk.CTkEntry(form_frame)
            entry.insert(0, str(value) if value else "")
            entry.pack(fill="x", pady=(0, 10))
            
            # Si es edición y es el código, deshabilitar (clave primaria)
            if key == "codigo" and prof:
                entry.configure(state="disabled")
                
            entries[key] = entry

        # Combos (Género y Estado Civil)
        ctk.CTkLabel(form_frame, text="Género").pack(anchor="w", pady=(10, 0))
        gender_cb = ctk.CTkComboBox(form_frame, values=["Masculino", "Femenino", "Otro"])
        if prof and prof.get('genero_pro'):
            gender_cb.set(prof['genero_pro'])
        gender_cb.pack(fill="x", pady=(0, 10))
        entries['genero'] = gender_cb

        ctk.CTkLabel(form_frame, text="Estado Civil").pack(anchor="w", pady=(10, 0))
        civil_cb = ctk.CTkComboBox(form_frame, values=["Soltero", "Casado", "Divorciado", "Viudo"])
        if prof and prof.get('estado_civil_pro'):
            civil_cb.set(prof['estado_civil_pro'])
        civil_cb.pack(fill="x", pady=(0, 10))
        entries['estado_civil'] = civil_cb

        # Botón Guardar
        def save():
            data = {k: v.get() for k, v in entries.items()}
            
            if prof:
                success, msg = self.controller.update_professor(data)
            else:
                success, msg = self.controller.create_professor(data)
            
            if success:
                modal.destroy()
                self.load_professors()
            else:
                print(f"Error: {msg}")

        ctk.CTkButton(modal, text="Guardar", command=save, fg_color="green").pack(pady=20)
