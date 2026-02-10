<?php
class SessionManager {
    
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($userData) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_type'] = $userData['tipo'];
        $_SESSION['user_name'] = $userData['nombre'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
    }
    
    public function logout() {
        $_SESSION = array();
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
?>