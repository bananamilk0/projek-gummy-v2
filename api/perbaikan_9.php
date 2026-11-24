<?php
/** Jalankan sekali setelah deploy untuk memperbarui data lama. */
require_once __DIR__ . '/../config/database.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
function hasColumn(PDO $pdo, string $table, string $column): bool {
    $column = str_replace("'", "''", $column);
    return (bool)$pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
}
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (id INT AUTO_INCREMENT PRIMARY KEY,title VARCHAR(255) NOT NULL,content TEXT NOT NULL,image_url VARCHAR(255) NULL,published_by INT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_images (
        id INT AUTO_INCREMENT PRIMARY KEY, image_url VARCHAR(255) NOT NULL,
        caption VARCHAR(160) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    if (!hasColumn($pdo,'news','image_url')) $pdo->exec("ALTER TABLE news ADD COLUMN image_url VARCHAR(255) NULL");
    if (!hasColumn($pdo,'news','published_by')) $pdo->exec("ALTER TABLE news ADD COLUMN published_by INT NULL");
    if ($pdo->query("SHOW TABLES LIKE 'user_quests'")->fetch() && !hasColumn($pdo,'user_quests','claimed')) $pdo->exec("ALTER TABLE user_quests ADD COLUMN claimed TINYINT(1) NOT NULL DEFAULT 0");
    // Old installations stored CSS in VARCHAR(100), truncating Rare/Epic/Legendary effects.
    foreach (['aura','frame_style','nameplate_style'] as $styleColumn) {
        if (hasColumn($pdo,'users',$styleColumn)) $pdo->exec("ALTER TABLE users MODIFY `$styleColumn` TEXT NULL");
    }

    // Keep the first record only for each duplicated achievement.
    $pdo->exec("DELETE a1 FROM achievements a1 JOIN achievements a2 ON a1.name=a2.name AND a1.id>a2.id");
    $pdo->prepare("UPDATE achievements SET icon='🌙' WHERE name LIKE 'Lunarian%'")->execute();
    $special = [
        ['Novice I', 'Mencapai Level 50', 50, 150, 75],
        ['Veteran II', 'Mencapai Level 250', 250, 500, 250],
        ['Legend III', 'Mencapai Level 1000', 1000, 1500, 1000],
    ];
    foreach ($special as [$name,$desc,$need,$xp,$coin]) {
        $q=$pdo->prepare("INSERT INTO achievements (name,description,icon,xp_reward,coin_reward,requirement_type,requirement_value,category) SELECT ?,?,'🏆',?,?, 'level',?,'special' WHERE NOT EXISTS (SELECT 1 FROM achievements x WHERE x.name=?)");
        $q->execute([$name,$desc,$xp,$coin,$need,$name]);
    }
    $pdo->prepare("INSERT INTO daily_quests (title,description,xp_reward,coin_reward,requirement_type,requirement_value) SELECT 'Daily Login','Login hari ini untuk menjaga progress Lunarian.',20,10,'login',1 WHERE NOT EXISTS (SELECT 1 FROM daily_quests WHERE requirement_type='login')")->execute();

    // Shop replacements preserve ownership because their IDs stay the same.
    $pdo->exec("DELETE FROM shop_items WHERE category='border' AND name IN ('Pill White','Border Pill White','Square Dark','Border Square Dark','Diamond Blue')");
    $updates = [
        [3,'Border Square Gold','Border persegi emas yang elegan','border:3px solid #ffd700;border-radius:6px;box-shadow:0 0 12px rgba(255,215,0,.65);',null,'common',350],
        [6,'Love Border','Border berbentuk hati warna pink','border:3px solid #ff6b9d;border-radius:50%;box-shadow:0 0 13px rgba(255,107,157,.75);',null,'rare',500],
        [10,'Rainbow Halo','Lingkaran penuh gradasi pelangi dengan cahaya hidup','border:4px solid transparent;border-radius:50%;background:linear-gradient(#151526,#151526) padding-box,conic-gradient(#ff1744,#ff9100,#ffeb3b,#00e676,#00b0ff,#651fff,#d500f9,#ff1744) border-box;','box-shadow:0 0 14px #ff3d81,0 0 28px rgba(0,190,255,.8),0 0 48px rgba(160,0,255,.45);animation:aura-pulse 1.8s ease-in-out infinite;','rare',700],
        [16,'Inferno Orbit','Border api dengan bola merah kecil yang mengorbit dan meninggalkan cahaya','border:3px solid #ff4400;border-radius:50%;','box-shadow:0 0 0 4px rgba(255,80,0,.2),0 0 20px rgba(255,68,0,.75);','legendary',2200],
        [17,'Divine Crown','Border emas dengan mahkota sungguhan di atasnya','border:3px solid #ffd700;border-radius:8px;','box-shadow:0 0 0 6px rgba(255,215,0,.35),0 0 34px rgba(255,215,0,.75);animation:aura-pulse 1s ease-in-out infinite;','legendary',2800],
        [18,'Boom Square','Border persegi dengan ledakan dramatis setiap 3 detik','border:3px solid #ff2200;border-radius:4px;background:rgba(20,5,5,.6);','box-shadow:0 0 15px #ff4400;animation:boomPulse 3s infinite;','legendary',3200],
    ];
    $st=$pdo->prepare("UPDATE shop_items SET name=?,description=?,preview_css=?,aura_css=?,rarity=?,price=? WHERE id=? AND category='border'");
    foreach($updates as [$id,$name,$desc,$css,$aura,$rarity,$price]) $st->execute([$name,$desc,$css,$aura,$rarity,$price,$id]);
    // ID 3 was deliberately removed above, then recreated as the requested gold square.
    $pdo->exec("INSERT INTO shop_items (id,name,description,category,price,preview_css,aura_css,rarity) VALUES (3,'Border Square Gold','Border persegi emas yang elegan','border',350,'border:3px solid #ffd700;border-radius:6px;box-shadow:0 0 12px rgba(255,215,0,.65);',NULL,'common') ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),preview_css=VALUES(preview_css),aura_css=VALUES(aura_css),rarity=VALUES(rarity),price=VALUES(price)");
    $pdo->prepare("UPDATE shop_items SET name='Divine Crown', description='Border mahkota emas untuk menghiasi nama pengguna' WHERE category='nameplate' AND name='Lunar Crown'")->execute();
    $pdo->prepare("UPDATE shop_items SET aura_css='box-shadow:0 0 0 6px rgba(0,229,255,.42),0 0 28px rgba(0,229,255,.9),0 0 54px rgba(30,90,255,.5);animation:aura-pulse 1.6s ease-in-out infinite;' WHERE category='border' AND name='Diamond Aura'")->execute();
    // Refresh saved cosmetics after changing their definitions, including items equipped before this fix.
    $pdo->exec("UPDATE users u JOIN shop_items s ON s.category='border' AND s.name=u.frame SET u.frame_style=s.preview_css, u.aura=s.aura_css");
    $pdo->exec("UPDATE users u JOIN shop_items s ON s.category='nameplate' AND s.name=u.nameplate SET u.nameplate_style=CONCAT(s.preview_css, IFNULL(CONCAT(' ',s.aura_css),''))");
    echo 'Perbaikan 9 berhasil diterapkan.';
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); http_response_code(500); echo 'Gagal: '.$e->getMessage(); }
