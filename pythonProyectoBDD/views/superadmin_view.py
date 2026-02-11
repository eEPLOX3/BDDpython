import customtkinter as ctk

class SuperAdminView(ctk.CTkFrame):
    def __init__(self, master, auth_controller, superadmin_controller, user, on_logout):
        super().__init__(master)
        self.auth_controller = auth_controller
        self.superadmin_controller = superadmin_controller
        self.user = user  # Objeto User del superadmin logueado
        self.on_logout = on_logout

        # Configuración del Grid Layout Principal
        self.grid_columnconfigure(1, weight=1) # Contenido principal se expande
        self.grid_rowconfigure(0, weight=1)

        # --- Sidebar ---
        self.sidebar_frame = ctk.CTkFrame(self, width=200, corner_radius=0)
        self.sidebar_frame.grid(row=0, column=0, sticky="nsew")
        self.sidebar_frame.grid_rowconfigure(4, weight=1)

        self.logo_label = ctk.CTkLabel(self.sidebar_frame, text="Panel SuperAdmin", font=ctk.CTkFont(size=20, weight="bold"))
        self.logo_label.grid(row=0, column=0, padx=20, pady=(20, 10))

        self.btn_permisos = ctk.CTkButton(self.sidebar_frame, text="Gestión Permisos")
        self.btn_permisos.grid(row=1, column=0, padx=20, pady=10)

        self.btn_logout = ctk.CTkButton(self.sidebar_frame, text="Cerrar Sesión", fg_color="red", hover_color="darkred", command=self.logout)
        self.btn_logout.grid(row=5, column=0, padx=20, pady=20)

        # --- Área Principal ---
        self.main_area = ctk.CTkFrame(self, corner_radius=0, fg_color="transparent")
        self.main_area.grid(row=0, column=1, sticky="nsew", padx=20, pady=20)
        self.main_area.grid_columnconfigure(0, weight=1)

        # Título Sección
        self.header_label = ctk.CTkLabel(self.main_area, text="Gestión de Permisos de Usuario", font=ctk.CTkFont(size=24, weight="bold"))
        self.header_label.grid(row=0, column=0, sticky="w", pady=(0, 20))

        # --- Buscador ---
        self.search_frame = ctk.CTkFrame(self.main_area)
        self.search_frame.grid(row=1, column=0, sticky="ew", pady=(0, 20))
        
        self.entry_search = ctk.CTkEntry(self.search_frame, placeholder_text="Buscar por código o nombre (Ej: ALU-001)...", width=400)
        self.entry_search.pack(side="left", padx=10, pady=10)
        
        self.btn_search = ctk.CTkButton(self.search_frame, text="Buscar", command=self.perform_search)
        self.btn_search.pack(side="left", padx=10, pady=10)

        # --- Resultados de Búsqueda (Scrollable Frame) ---
        self.results_frame = ctk.CTkScrollableFrame(self.main_area, label_text="Resultados", height=150)
        self.results_frame.grid(row=2, column=0, sticky="ew", pady=(0, 20))

        # --- Panel de Permisos (Oculto inicialmente) ---
        self.permissions_frame = ctk.CTkScrollableFrame(self.main_area, label_text="Asignar Permisos", height=300)
        self.permissions_frame.grid(row=3, column=0, sticky="nsew")
        self.permissions_frame.grid_columnconfigure((0, 1, 2), weight=1) # 3 columnas de permisos

        # Estado actual
        self.selected_user_code = None
        self.selected_user_type = None
        self.permission_vars = {} # { 'ALUMNO_INSERTAR': BooleanVar }

        # Lista de permisos del sistema (Hardcoded por ahora para matchear PHP)
        self.system_permissions = [
            # Alumnos
            {'code': 'ALUMNO_INSERTAR', 'name': 'Insertar Alumnos', 'category': 'Alumnos'},
            {'code': 'ALUMNO_ACTUALIZAR', 'name': 'Actualizar Alumnos', 'category': 'Alumnos'},
            {'code': 'ALUMNO_ELIMINAR', 'name': 'Eliminar Alumnos', 'category': 'Alumnos'},
            # Profesores
            {'code': 'PROFESOR_INSERTAR', 'name': 'Insertar Profesores', 'category': 'Profesores'},
            {'code': 'PROFESOR_ACTUALIZAR', 'name': 'Actualizar Profesores', 'category': 'Profesores'},
            {'code': 'PROFESOR_ELIMINAR', 'name': 'Eliminar Profesores', 'category': 'Profesores'},
            # Notas
            {'code': 'NOTA_INSERTAR', 'name': 'Insertar Notas', 'category': 'Notas'},
            {'code': 'NOTA_ACTUALIZAR', 'name': 'Actualizar Notas', 'category': 'Notas'},
            {'code': 'NOTA_ELIMINAR', 'name': 'Eliminar Notas', 'category': 'Notas'},
            # Reportes
            {'code': 'REPORTE_CURSO', 'name': 'Ver Reporte Curso', 'category': 'Reportes'},
            {'code': 'REPORTE_NOTAS', 'name': 'Ver Reporte Notas', 'category': 'Reportes'},
            {'code': 'REPORTE_PERSONAL', 'name': 'Ver Reporte Personal', 'category': 'Reportes'},
        ]

    def logout(self):
        if self.on_logout:
            self.on_logout()

    def perform_search(self):
        query = self.entry_search.get()
        if not query:
            return

        # Limpiar resultados anteriores
        for widget in self.results_frame.winfo_children():
            widget.destroy()

        results, msg = self.superadmin_controller.search_users(query)
        
        if not results:
            ctk.CTkLabel(self.results_frame, text="No se encontraron usuarios.").pack(pady=10)
            return

        for user in results:
            # Crear targeta de usuario
            card = ctk.CTkFrame(self.results_frame)
            card.pack(fill="x", padx=5, pady=5)
            
            info_text = f"{user['nombre']} ({user['codigo']}) - {user['tipo_display']}"
            ctk.CTkLabel(card, text=info_text).pack(side="left", padx=10)
            
            btn_manage = ctk.CTkButton(
                card, 
                text="Gestionar Permisos", 
                width=100,
                command=lambda u=user: self.load_permissions(u)
            )
            btn_manage.pack(side="right", padx=10, pady=5)

    def load_permissions(self, user):
        self.selected_user_code = user['codigo']
        self.selected_user_type = user['tipo']
        
        self.permissions_frame.configure(label_text=f"Permisos para: {user['nombre']}")
        
        # Limpiar checkboxes anteriores
        for widget in self.permissions_frame.winfo_children():
            widget.destroy()
        self.permission_vars = {}

        # Obtener permisos actuales
        current_perms, msg = self.superadmin_controller.get_permissions(user['codigo'], user['tipo'])

        # Renderizar Checkboxes por Categoría
        row = 0
        col = 0
        
        for perm in self.system_permissions:
            var = ctk.BooleanVar(value=current_perms.get(perm['code'], False))
            self.permission_vars[perm['code']] = var
            
            cb = ctk.CTkCheckBox(
                self.permissions_frame, 
                text=perm['name'], 
                variable=var
            )
            cb.grid(row=row, column=col, sticky="w", padx=10, pady=5)
            
            # Layout simple de grid
            col += 1
            if col > 2:
                col = 0
                row += 1

        # Botón Guardar
        btn_save = ctk.CTkButton(
            self.permissions_frame, 
            text="Guardar Cambios", 
            fg_color="green", 
            hover_color="darkgreen",
            command=self.save_permissions
        )
        btn_save.grid(row=row+1, column=0, columnspan=3, pady=20)

    def save_permissions(self):
        if not self.selected_user_code:
            return

        perms_to_save = {}
        for code, var in self.permission_vars.items():
            perms_to_save[code] = var.get()

        success, msg = self.superadmin_controller.update_permissions_batch(
            self.selected_user_code, 
            self.selected_user_type, 
            perms_to_save
        )
        
        # Feedback visual (simple print o label por ahora)
        print(f"Guardado: {msg}")
