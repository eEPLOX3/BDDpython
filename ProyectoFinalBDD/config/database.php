<?php
// config/database.php
class Database {
    private $conn;
    private $host = 'localhost,1433';
    private $db_name = 'ProyectoEscolastico';
    private $username = 'sa';
    private $password = '1505';
    
    public function connect() {
        $this->conn = null;
        
        try {
            // Para SQL Server con PDO
            $dsn = "sqlsrv:Server=" . $this->host . ";Database=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
        }
        
        return $this->conn;
    }
    
    public function executeQuery($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($stmt) {
        return $stmt->fetchAll();
    }
    
    public function fetchOne($stmt) {
        return $stmt->fetch();
    }
    
    public function disconnect() {
        $this->conn = null;
    }
}
?>