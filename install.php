<?php
// install.php - Run this once to setup database
require_config();

function require_config() {
    $host = '127.0.0.1';
    $user = 'root';
    $pass = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = file_get_contents(__DIR__ . '/config/database.sql');
        $pdo->exec($sql);
        
        // Ensure admin user exists
        $adminEmail = 'gummy@gmail.com';
        $adminUser = 'AdminGummy';
        $adminPass = password_hash('admingummy', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$adminEmail]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$adminUser, $adminEmail, $adminPass]);
        }
        
        echo "Database setup successful!";
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
