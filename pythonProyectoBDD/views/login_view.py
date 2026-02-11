import customtkinter as ctk
from PIL import Image
import os

class LoginView(ctk.CTkFrame):
    def __init__(self, master, auth_controller, on_login_success):
        super().__init__(master)
        self.auth_controller = auth_controller
        self.on_login_success = on_login_success
        
        # Configuración del frame
        self.grid(row=0, column=0, sticky="nsew")
        self.configure(fg_color="transparent")

        # Configuración del Grid Layout
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(0, weight=1)

        # Card de Login
        self.login_frame = ctk.CTkFrame(self, width=400, corner_radius=15)
        self.login_frame.grid(row=0, column=0, padx=20, pady=20)
        self.login_frame.grid_columnconfigure(0, weight=1)

        # Título
        self.label_title = ctk.CTkLabel(
            self.login_frame, 
            text="Sistema Académico", 
            font=("Roboto Medium", 24)
        )
        self.label_title.grid(row=0, column=0, pady=(40, 10), padx=20)



        # Input Usuario
        self.entry_user = ctk.CTkEntry(
            self.login_frame, 
            placeholder_text="Código / Usuario", 
            width=300
        )
        self.entry_user.grid(row=3, column=0, pady=(0, 20), padx=20)

        # Input Password
        self.entry_pass = ctk.CTkEntry(
            self.login_frame, 
            placeholder_text="Contraseña", 
            show="*", 
            width=300
        )
        self.entry_pass.grid(row=4, column=0, pady=(0, 20), padx=20)

        # Etiqueta de Error
        self.label_error = ctk.CTkLabel(
            self.login_frame, 
            text="", 
            text_color="red",
            font=("Roboto", 12)
        )
        self.label_error.grid(row=5, column=0, pady=(0, 10), padx=20)

        # Botón Login
        self.btn_login = ctk.CTkButton(
            self.login_frame, 
            text="Iniciar Sesión", 
            width=300, 
            command=self.login_event
        )
        self.btn_login.grid(row=6, column=0, pady=(0, 40), padx=20)

    def login_event(self):
        user = self.entry_user.get()
        password = self.entry_pass.get()

        if not user or not password:
            self.label_error.configure(text="Todos los campos son obligatorios")
            return

        # Llamar al controlador
        user_obj, message = self.auth_controller.login(user, password)
        
        if user_obj:
            self.label_error.configure(text="Login Exitoso!", text_color="green")
            # Ejecutar callback de éxito (navegar al dashboard)
            if self.on_login_success:
                self.on_login_success(user_obj)
        else:
            self.label_error.configure(text=message, text_color="red")
