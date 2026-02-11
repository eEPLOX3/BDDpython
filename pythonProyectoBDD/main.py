import customtkinter as ctk
from controllers.auth_controller import AuthController
from controllers.superadmin_controller import SuperAdminController
from views.login_view import LoginView
from views.superadmin_view import SuperAdminView
from views.user_dashboard_view import UserDashboardView
import os

# Configuración Global de CustomTkinter
ctk.set_appearance_mode("System")  # Modes: "System" (standard), "Dark", "Light"
ctk.set_default_color_theme("blue")  # Themes: "blue" (standard), "green", "dark-blue"

class App(ctk.CTk):
    def __init__(self):
        super().__init__()

        # Configuración de Ventana
        self.title("Sistema Académico - Python Migration")
        self.geometry("1000x700")
        
        # Controladores
        self.auth_controller = AuthController()
        self.superadmin_controller = SuperAdminController()

        # Configuración del Grid Principal
        self.grid_rowconfigure(0, weight=1)
        self.grid_columnconfigure(0, weight=1)

        # Iniciar en Login
        self.show_login()

    def show_login(self):
        # Limpiar frame actual si existe
        if hasattr(self, 'current_frame'):
            self.current_frame.destroy()

        self.current_frame = LoginView(self, self.auth_controller, self.on_login_success)
        self.current_frame.grid(row=0, column=0, sticky="nsew")

    def on_login_success(self, user):
        print(f"Login exitoso: {user}")
        
        if hasattr(self, 'current_frame'):
            self.current_frame.destroy()
        
        if user.role == 'superadmin':
            self.current_frame = SuperAdminView(self, self.auth_controller, self.superadmin_controller, user, self.on_logout)
        elif user.role in ['alumno', 'profesor']:
            # Construir datos para el dashboard
            user_data = {
                'nombre': user.full_name, # Ajustado al modelo User (full_name)
                'codigo': user.username,  # 'username' guarda el código (ALU-XX/PROF-XX)
                'tipo': user.role
            }
            self.current_frame = UserDashboardView(self, user_data, self.on_logout)
        else:
             # Fallback para roles desconocidos
            self.current_frame = ctk.CTkFrame(self)
            ctk.CTkLabel(self.current_frame, text=f"Rol desconocido: {user.role}").pack(pady=20)
            
        self.current_frame.grid(row=0, column=0, sticky="nsew")

    def on_logout(self):
        self.auth_controller.logout()
        self.show_login()

if __name__ == "__main__":
    app = App()
    app.mainloop()
