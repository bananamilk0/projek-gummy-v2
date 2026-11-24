<?php
if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ?page=admin");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = '';
    
    if($email_or_username && $password) {
        global $pdo; 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND role = 'admin'");
        $stmt->execute([$email_or_username, $email_or_username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['xp'] = $user['xp'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['coins'] = $user['coins'];
            header("Location: ?page=admin");
            exit;
        } else {
            // Check for admin fallback creation (only if no admin exists yet)
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $adminCount = $stmt->fetchColumn();

            if ($adminCount == 0 && $email_or_username === 'gummy@gmail.com' && $password === 'admingummy') {
                $hash = password_hash('admingummy', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES ('AdminGummy', 'gummy@gmail.com', ?, 'admin')");
                $stmt->execute([$hash]);
                
                $new_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = 'AdminGummy';
                $_SESSION['role'] = 'admin';
                $_SESSION['xp'] = 0;
                $_SESSION['level'] = 1;
                $_SESSION['coins'] = 0;
                header("Location: ?page=admin");
                exit;
            }
            
            $error = "Invalid admin credentials.";
        }
    }
}
require_once __DIR__ . '/../includes/header.php'; 
?>

<div class="auth-container">
    <div class="auth-card glass-card" style="border: 1px solid var(--primary);">
        <h2 class="gradient-text">Admin Login</h2>
        <p>Access the control panel.</p>
        
        <?php if(!empty($error)): ?>
            <div style="background: rgba(255,0,0,0.2); padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid red;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Admin Username / Email</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary magnetic" style="width: 100%; margin-top: 10px;">Login as Admin</button>
        </form>
    </div>
</div>

<style>
.auth-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: calc(100vh - 200px);
}
.auth-card {
    width: 100%;
    max-width: 400px;
    padding: 30px;
    text-align: center;
}
.auth-card h2 { margin-bottom: 10px; }
.auth-card p { color: var(--text-muted); margin-bottom: 24px; font-size: 0.9rem; }
.form-group {
    margin-bottom: 16px;
    text-align: left;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.85rem;
    color: var(--text-muted);
}
.form-group input {
    width: 100%;
    padding: 12px;
    background: rgba(0,0,0,0.3);
    border: 1px solid var(--glass-border);
    border-radius: 8px;
    color: white;
    outline: none;
    transition: border 0.3s;
}
.form-group input:focus { border-color: var(--primary); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
