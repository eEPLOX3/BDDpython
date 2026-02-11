class User:
    def __init__(self, id, username, full_name, role, email=None):
        self.id = id
        self.username = username
        self.full_name = full_name
        self.role = role
        self.email = email

    def __str__(self):
        return f"{self.full_name} ({self.role})"
