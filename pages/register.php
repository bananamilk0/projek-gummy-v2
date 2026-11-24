<?php
if(isset($_SESSION['user_id'])) { header("Location: ?page=home"); exit; }

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$username || !$password) {
        $error = "Username dan password wajib diisi!";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = "Username harus antara 3-30 karakter.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $confirm) {
        $error = "Password tidak cocok!";
    } else {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()['c'] > 0) {
            $error = "Username sudah digunakan, coba yang lain.";
        } else {
            $avatar_filename = 'default.png';
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                    $uploadDir = __DIR__ . '/../uploads/avatars/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $newName = md5(time() . $username) . '.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newName)) {
                        $avatar_filename = $newName;
                    }
                }
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                // email column is still in DB, fill with placeholder to avoid constraint issues
                $email_placeholder = strtolower(preg_replace('/[^a-z0-9]/i', '', $username)) . '_' . time() . '@gummy.local';
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, avatar) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email_placeholder, $hash, $avatar_filename]);
                $success = "Akun berhasil dibuat! Silakan masuk.";
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan saat pendaftaran. Coba lagi.";
            }
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-container">
    <div class="auth-card glass-card" style="max-width:430px; padding:36px;">
        <div style="text-align:center; margin-bottom:28px;">
            <h2 class="gradient-text" style="font-size:1.8rem; margin-bottom:6px;">Buat Akun</h2>
            <p style="color:var(--text-muted); font-size:0.88rem;">Bergabung dengan komunitas Gummy dan mulai bermain!</p>
        </div>

        <?php if($error): ?>
            <div style="background:rgba(255,59,48,0.12); border:1px solid rgba(255,59,48,0.3); color:#ff8080; padding:12px 14px; border-radius:10px; margin-bottom:20px; font-size:0.875rem;">
                <i data-lucide="alert-circle" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if($success): ?>
            <div style="background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.3); color:#00ff88; padding:12px 14px; border-radius:10px; margin-bottom:20px; font-size:0.875rem;">
                <i data-lucide="check-circle" style="width:14px;height:14px;vertical-align:middle;margin-right:6px;"></i><?= htmlspecialchars($success) ?>
                <a href="?page=login" style="color:#00ff88; font-weight:700; margin-left:6px;">Masuk sekarang →</a>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:7px; font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Username</label>
                <input type="text" name="username" required autocomplete="off" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="Masukkan username unikmu"
                    style="width:100%; padding:12px 14px; background:rgba(0,0,0,0.35); border:1px solid var(--glass-border); border-radius:10px; color:white; outline:none; font-size:0.9rem; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--glass-border)'">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:7px; font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Password</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter"
                    style="width:100%; padding:12px 14px; background:rgba(0,0,0,0.35); border:1px solid var(--glass-border); border-radius:10px; color:white; outline:none; font-size:0.9rem; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--glass-border)'">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:7px; font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Konfirmasi Password</label>
                <input type="password" name="confirm_password" required placeholder="Ulangi password"
                    style="width:100%; padding:12px 14px; background:rgba(0,0,0,0.35); border:1px solid var(--glass-border); border-radius:10px; color:white; outline:none; font-size:0.9rem; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--glass-border)'">
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:7px; font-size:0.82rem; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Foto Profil <span style="font-weight:400; text-transform:none; letter-spacing:0;">(Opsional)</span></label>
                <label id="avatar-label" style="display:flex; align-items:center; gap:10px; padding:12px 14px; background:rgba(0,0,0,0.35); border:1px dashed var(--glass-border); border-radius:10px; cursor:pointer; transition:border-color 0.2s;" onmouseenter="this.style.borderColor='var(--primary)'" onmouseleave="this.style.borderColor='var(--glass-border)'">
                    <img id="avatar-preview" src="" alt="" style="display:none; width:36px; height:36px; border-radius:50%; object-fit:cover; border:2px solid var(--primary);">
                    <i data-lucide="image" id="avatar-icon" style="width:18px;height:18px;color:var(--text-muted);"></i>
                    <span id="avatar-text" style="color:var(--text-muted); font-size:0.875rem;">Pilih foto profil</span>
                    <input type="file" name="avatar" id="avatar-input" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                </label>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; padding:13px; font-size:0.95rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i data-lucide="user-plus" style="width:16px;height:16px;"></i> Buat Akun
            </button>
        </form>

        <p style="text-align:center; margin-top:22px; font-size:0.875rem; color:var(--text-muted);">
            Sudah punya akun? <a href="?page=login" style="color:var(--primary); font-weight:700;">Masuk disini</a>
        </p>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const prev = document.getElementById('avatar-preview');
        const icon = document.getElementById('avatar-icon');
        const text = document.getElementById('avatar-text');
        prev.src = e.target.result;
        prev.style.display = 'block';
        icon.style.display = 'none';
        text.textContent = input.files[0].name;
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
