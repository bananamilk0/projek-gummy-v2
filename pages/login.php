<?php
if(isset($_SESSION['user_id'])) {
    header("Location: /web-gummy/");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $error = '';
    
    if($email_or_username && $password) {
        // Query DB
        global $pdo; 
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email_or_username, $email_or_username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['xp'] = $user['xp'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['coins'] = $user['coins'];
            $_SESSION['avatar'] = $user['avatar'] ?? 'default.png';

            // ── Login streak & achievement tracking ──────────────────────
            try {
                $today = date('Y-m-d');
                $hour  = (int)date('G'); // 0-23
                $uid   = $user['id'];

                $last  = $user['last_login_date'] ?? null;
                $streak = (int)($user['login_streak'] ?? 0);
                $total  = (int)($user['total_login_days'] ?? 0);

                if ($last !== $today) {
                    // New day login
                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                    if ($last === $yesterday) {
                        $streak++;
                    } else {
                        $streak = 1;
                    }
                    $total++;
                    $pdo->prepare("UPDATE users SET last_login_date=?, login_streak=?, total_login_days=? WHERE id=?")
                        ->execute([$today, $streak, $total, $uid]);

                    // Ghost Mode: login at 3am
                    if ($hour === 3) {
                        award_achievement_by_name($pdo, $uid, 'Ghost Mode');
                    }
                    // Lunarian 𝟏 – 7 hari streak
                    if ($streak >= 7)   award_achievement_by_name($pdo, $uid, 'Lunarian 𝟏');
                    // Lunarian 𝟐 – 30 hari streak
                    if ($streak >= 30)  award_achievement_by_name($pdo, $uid, 'Lunarian 𝟐');
                    // Lunarian 𝟑 – 100 hari streak
                    if ($streak >= 100) award_achievement_by_name($pdo, $uid, 'Lunarian 𝟑');
                }
            } catch (Exception $loginEx) {}

            header("Location: ?page=home");
            exit;
        } else {
            // Check for admin fallback creation
            if (!$user && $email_or_username === 'gummy@gmail.com' && $password === 'admingummy') {
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
                header("Location: ?page=home");
                exit;
            }
            
            $error = "Kombinasi Username/Gmail dan Password salah.";
        }
    }
}
require_once __DIR__ . '/../includes/header.php'; 
?>

<?php
// Helper: award achievement by name (safe, idempotent)
function award_achievement_by_name($pdo, $user_id, $name) {
    try {
        $s = $pdo->prepare("SELECT id, xp_reward, coin_reward FROM achievements WHERE name=?");
        $s->execute([$name]);
        $a = $s->fetch();
        if (!$a) return;
        $c = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
        $c->execute([$user_id, $a['id']]);
        if ($c->fetch()) return; // already unlocked
        $pdo->prepare("INSERT INTO user_achievements (user_id,achievement_id) VALUES (?,?)")->execute([$user_id,$a['id']]);
        $pdo->prepare("UPDATE users SET xp=xp+?, coins=coins+? WHERE id=?")->execute([$a['xp_reward'],$a['coin_reward'],$user_id]);
    } catch (Exception $e) {}
}
?>

<div class="auth-container">
    <div class="auth-card glass-card">
        <h2 class="gradient-text">Welcome Back</h2>
        <p>Login to access the community and save your progress.</p>
        
        <?php if(!empty($error)): ?>
            <div style="background: rgba(255,0,0,0.15); color: #ff80b3; padding: 10px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--accent);"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username / Gmail</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary magnetic" style="width: 100%; margin-top: 10px;">Login</button>
        </form>
        
        <div class="auth-divider"><span>ATAU</span></div>
        <p style="margin-bottom: 0;"><small>Belum punya akun? <a href="?page=register" style="color: var(--primary); font-weight: bold;">Daftar disini</a></small></p>
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
.auth-divider {
    margin: 24px 0;
    position: relative;
    text-align: center;
}
.auth-divider::before {
    content: '';
    position: absolute;
    top: 50%; left: 0; width: 100%; height: 1px;
    background: var(--glass-border);
}
.auth-divider span {
    background: var(--bg-panel);
    padding: 0 10px;
    position: relative;
    font-size: 0.8rem;
    color: var(--text-muted);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
