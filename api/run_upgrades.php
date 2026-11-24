<?php
// api/run_upgrades.php
// Jalankan SEKALI untuk apply semua perubahan database.
// Akses di browser: http://localhost/web-gummy/api/run_upgrades.php
// HAPUS file ini setelah berhasil dijalankan!

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Gummy DB Upgrade</title>
<style>
body { background:#0f111a; color:#e0e0e0; font-family:monospace; padding:24px; }
h1 { color:#8a2be2; }
.ok  { color:#00ff88; }
.err { color:#ff8080; }
.warn{ color:#ffcc00; }
pre  { background:#13151f; padding:16px; border-radius:10px; font-size:0.85rem; line-height:1.7; overflow-x:auto; }
.final { margin-top:20px; padding:14px 20px; border-radius:10px; font-size:1rem; font-weight:bold; }
.btn { display:inline-block; margin-top:16px; padding:12px 28px; background:#8a2be2; color:#fff; border-radius:10px; text-decoration:none; font-weight:700; }
</style>
</head>
<body>
<h1>🛠️ Gummy DB Upgrade Runner</h1>
<?php

$sql = file_get_contents(__DIR__ . '/../config/database_upgrades.sql');
if (!$sql) {
    echo '<p class="err">❌ File database_upgrades.sql tidak ditemukan!</p>';
    exit;
}

// Disable FK checks first, enable after
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

echo '<pre>';

// Split statements on semicolon (handle multi-line)
$raw_statements = explode(';', $sql);
$ok = 0; $err = 0; $skip = 0;

foreach ($raw_statements as $raw) {
    $stmt = trim(preg_replace('/--[^\n]*/', '', $raw)); // strip line comments
    $stmt = trim($stmt);
    if (strlen($stmt) < 4) { $skip++; continue; }

    try {
        $pdo->exec($stmt);
        $preview = htmlspecialchars(substr(preg_replace('/\s+/', ' ', $stmt), 0, 90));
        echo '<span class="ok">✅ OK</span>  ' . $preview . "\n";
        $ok++;
    } catch (PDOException $e) {
        $preview = htmlspecialchars(substr(preg_replace('/\s+/', ' ', $stmt), 0, 80));
        $emsg = htmlspecialchars($e->getMessage());
        // Common non-fatal: duplicate key, column already exists
        $isFatal = !preg_match('/Duplicate entry|already exists|Can\'t create table.*already exists/i', $emsg);
        if ($isFatal) {
            echo '<span class="err">❌ ERR</span>  ' . $preview . "\n";
            echo '        → ' . $emsg . "\n";
            $err++;
        } else {
            echo '<span class="warn">⚠️ SKIP</span> ' . $preview . "\n";
            $skip++;
        }
    }
}

echo '</pre>';

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

if ($err === 0) {
    echo '<div class="final" style="background:rgba(0,255,136,0.1); border:1px solid rgba(0,255,136,0.3); color:#00ff88;">';
    echo '✅ Selesai! ' . $ok . ' statement berhasil, ' . $skip . ' dilewati, 0 error.';
    echo '<br><small>Refresh halaman Cosmetics Shop, Achievements, atau halaman lain untuk melihat perubahan.</small>';
    echo '</div>';
} else {
    echo '<div class="final" style="background:rgba(255,59,48,0.1); border:1px solid rgba(255,59,48,0.3); color:#ff8080;">';
    echo '⚠️ Selesai dengan ' . $err . ' error, ' . $ok . ' berhasil, ' . $skip . ' dilewati.';
    echo '<br><small>Error di atas mungkin perlu diperhatikan.</small>';
    echo '</div>';
}
?>

<a href="?page=perks" class="btn">🛍️ Lihat Cosmetics Shop</a>
<a href="?page=achievements" class="btn" style="margin-left:10px; background:#00e5ff; color:#000;">🏆 Lihat Achievements</a>

<p style="margin-top:24px; color:#ff8080; font-size:0.85rem;">
    ⚠️ <strong>Hapus file ini setelah dijalankan!</strong><br>
    Path: <code>c:\laragon\www\web-gummy\api\run_upgrades.php</code>
</p>
</body>
</html>
