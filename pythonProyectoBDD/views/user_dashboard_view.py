import customtkinter as ctk
from controllers.user_dashboard_controller import UserDashboardController
from views.student_view import StudentView
from views.professor_view import ProfessorView

class UserDashboardView(ctk.CTkFrame):
    def __init__(self, master, user_data, on_logout):
        super().__init__(master)
        self.master = master
        self.user_data = user_data
        self.on_logout = on_logout
        self.controller = UserDashboardController()
        
        # Colores
        self.colors = {
            "sidebar": "#2c3e50",
            "bg": "#ecf0f1",
            "text": "#333333",
            "white": "#ffffff",
            "hover": "#34495e",
            "active": "#2980b9"
        }

        # Layout Principal: Sidebar (Izquierda) + Contenido (Derecha)
        self.grid_columnconfigure(1, weight=1)
        self.grid_rowconfigure(0, weight=1)

        # Cargar permisos
        self.permisos = self.controller.get_active_permissions(
            self.user_data['codigo'], 
            self.user_data['tipo']
        )

        # Sidebar
        self.sidebar_frame = ctk.CTkFrame(self, fg_color=self.colors["sidebar"], width=250, corner_radius=0)
        self.sidebar_frame.grid(row=0, column=0, sticky="nsew")
        self.sidebar_frame.grid_rowconfigure(10, weight=1) # Espaciador al final

        self.create_sidebar_header()
        self.create_sidebar_menu()

        # Área de Contenido
        self.content_frame = ctk.CTkFrame(self, fg_color=self.colors["bg"], corner_radius=0)
        self.content_frame.grid(row=0, column=1, sticky="nsew")
        
        # Mostrar mensaje de bienvenida inicial
        self.show_welcome_screen()

    def create_sidebar_header(self):
        # Título
        title = ctk.CTkLabel(
            self.sidebar_frame, 
            text="Sistema Académico", 
            font=("Roboto", 20, "bold"),
            text_color=self.colors["white"]
        )
        title.grid(row=0, column=0, padx=20, pady=(20, 10))
        
        # Info Usuario
        user_info = ctk.CTkLabel(
            self.sidebar_frame, 
            text=f"{self.user_data.get('nombre', 'Usuario')}\n({self.user_data.get('tipo', '').capitalize()})", 
            font=("Roboto", 12),
            text_color="#bdc3c7"
        )
        user_info.grid(row=1, column=0, padx=20, pady=(0, 20))

    def create_sidebar_menu(self):
        # Botón Inicio
        self.create_nav_button("Inicio", "🏠", 2, self.show_welcome_screen)

        row_idx = 3

        # -- Botón: Gestión de Alumnos --
        if any(p in self.permisos for p in ['ALUMNO_INSERTAR', 'ALUMNO_ACTUALIZAR', 'ALUMNO_ELIMINAR']):
            self.create_nav_button("Gestionar Alumnos", "👥", row_idx, lambda: self.show_module("Alumnos"))
            row_idx += 1

        # -- Botón: Gestión de Profesores --
        if any(p in self.permisos for p in ['PROFESOR_INSERTAR', 'PROFESOR_ACTUALIZAR', 'PROFESOR_ELIMINAR']):
            self.create_nav_button("Gestionar Profesores", "🎓", row_idx, lambda: self.show_module("Profesores"))
            row_idx += 1

        # -- Botón: Gestión de Notas --
        if any(p in self.permisos for p in ['NOTA_INSERTAR', 'NOTA_ACTUALIZAR', 'NOTA_ELIMINAR']):
            self.create_nav_button("Gestionar Notas", "📝", row_idx, lambda: self.show_module("Notas"))
            row_idx += 1

        # -- Botón: Reportes --
        if any(p in self.permisos for p in ['REPORTE_CURSO', 'REPORTE_NOTAS', 'REPORTE_PERSONAL']):
            self.create_nav_button("Ver Reportes", "📊", row_idx, lambda: self.show_module("Reportes"))
            row_idx += 1

        # Botón Cerrar Sesión (Al final)
        logout_btn = ctk.CTkButton(
            self.sidebar_frame,
            text="Cerrar Sesión",
            fg_color="#c0392b",
            hover_color="#e74c3c",
            anchor="w",
            command=self.on_logout
        )
        logout_btn.grid(row=11, column=0, padx=20, pady=20, sticky="ew")

    def create_nav_button(self, text, icon, row, command):
        btn = ctk.CTkButton(
            self.sidebar_frame,
            text=f"{icon}  {text}",
            fg_color="transparent",
            text_color=self.colors["white"],
            hover_color=self.colors["hover"],
            anchor="w",
            command=command,
            height=40
        )
        btn.grid(row=row, column=0, padx=10, pady=5, sticky="ew")

    def show_welcome_screen(self):
        self.clear_content()
        
        container = ctk.CTkFrame(self.content_frame, fg_color="transparent")
        container.pack(expand=True)
        
        ctk.CTkLabel(container, text="👋", font=("Arial", 60)).pack(pady=(0, 20))
        ctk.CTkLabel(
            container, 
            text=f"¡Hola de nuevo, {self.user_data.get('nombre')}!", 
            font=("Roboto", 28, "bold"),
            text_color=self.colors["text"]
        ).pack()
        
        ctk.CTkLabel(
            container, 
            text="Selecciona una opción del menú lateral para comenzar.", 
            font=("Roboto", 16),
            text_color="gray"
        ).pack(pady=10)

    def show_module(self, module_name):
        self.clear_content()
        
        if module_name == "Alumnos":
            # Instanciar y mostrar la vista de estudiantes real
            student_view = StudentView(self.content_frame)
            student_view.pack(fill="both", expand=True)
            return

        if module_name == "Profesores":
            # Instanciar y mostrar la vista de profesores real
            prof_view = ProfessorView(self.content_frame)
            prof_view.pack(fill="both", expand=True)
            return

        # Placeholder para otros módulos
        container = ctk.CTkFrame(self.content_frame, fg_color="white", corner_radius=10)
        container.pack(fill="both", expand=True, padx=40, pady=40)
        
        ctk.CTkLabel(
            container, 
            text=f"Módulo de {module_name}", 
            font=("Roboto", 24, "bold"),
            text_color=self.colors["text"]
        ).pack(pady=40)
        
        ctk.CTkLabel(
            container, 
            text="🚧 En Construcción 🚧", 
            font=("Arial", 18),
            text_color="gray"
        ).pack()

    def clear_content(self):
        for widget in self.content_frame.winfo_children():
            widget.destroy()
