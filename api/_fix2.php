<?php
// Fix script — jalankan sekali: http://localhost/web-gummy/api/_fix2.php
require_once __DIR__ . '/../config/database.php';
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$log = [];

// 1. Hapus Love Heart, Diamond Aura dari shop
$pdo->exec("DELETE FROM shop_items WHERE name IN ('Love Heart','Love Pink','Love Border','Diamond Aura','Diamond Blue')");
$log[] = 'Deleted Love Heart + Diamond Aura';

// 2. Fix Rainbow Halo — pakai conic-gradient yang benar untuk circular avatar
$pdo->exec("UPDATE shop_items SET
    preview_css='border:4px solid transparent; border-radius:50%; background:linear-gradient(#0a130b,#0a130b) padding-box, conic-gradient(from 0deg,#ff0000 0%,#ff7700 15%,#ffff00 30%,#00ff00 45%,#0088ff 60%,#8800ff 75%,#ff0066 90%,#ff0000 100%) border-box;',
    aura_css='filter:drop-shadow(0 0 5px rgba(255,100,100,0.6)) drop-shadow(0 0 10px rgba(0,200,255,0.5)); animation:aura-pulse 2s linear infinite;'
WHERE name='Rainbow Halo'");
$log[] = 'Fixed Rainbow Halo border CSS';

// 3. Fix Inferno Orbit — radius orbit lebih kecil (14px bukan 20px)
$pdo->exec("UPDATE shop_items SET
    aura_css='box-shadow:0 -14px 0 2px #ff4400, 0 0 18px rgba(255,68,0,0.6), 0 0 35px rgba(200,30,0,0.2); animation:inferno-orbit 1.6s linear infinite;'
WHERE name='Inferno Orbit' OR name='Inferno Ring'");
$log[] = 'Reduced Inferno Orbit radius';

// 4. Ensure all border items have correct border-radius in preview_css
// Fix Circle borders to explicitly have border-radius:50%
$pdo->exec("UPDATE shop_items SET preview_css=CONCAT(preview_css, ' border-radius:50%;')
WHERE category='border' AND name LIKE 'Circle%' AND preview_css NOT LIKE '%border-radius:50%'");

// 5. Add level milestone achievements if missing
try {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM achievements")->fetchAll(), 'Field');
    if (!in_array('category', $cols)) {
        $pdo->exec("ALTER TABLE achievements ADD COLUMN category VARCHAR(50) DEFAULT 'global'");
    }
    if (!in_array('xp_reward', $cols)) {
        $pdo->exec("ALTER TABLE achievements ADD COLUMN xp_reward INT DEFAULT 50");
        $pdo->exec("ALTER TABLE achievements ADD COLUMN coin_reward INT DEFAULT 20");
        $pdo->exec("ALTER TABLE achievements ADD COLUMN icon VARCHAR(20) DEFAULT '🏆'");
    }
    $pdo->exec("INSERT IGNORE INTO achievements(name,description,icon,xp_reward,coin_reward,category) VALUES
        ('🌱 Novice I','Mencapai Level 50','🌱',200,100,'level'),
        ('⚔️ Veteran II','Mencapai Level 250','⚔️',500,250,'level'),
        ('👑 Legend III','Mencapai Level 1000','👑',2000,1000,'level')");
    $log[] = 'Added level achievements';
} catch (Exception $e) { $log[] = 'Achievement err: '.$e->getMessage(); }

// 6. Create user_achievements if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uua (user_id,achievement_id)
)");

// 7. Award level achievements to ALL users who already qualify
try {
    $users = $pdo->query("SELECT id, level FROM users")->fetchAll();
    $milestones = [50=>'🌱 Novice I', 250=>'⚔️ Veteran II', 1000=>'👑 Legend III'];
    foreach ($users as $u) {
        foreach ($milestones as $reqLvl => $achName) {
            if ((int)$u['level'] >= $reqLvl) {
                $a = $pdo->prepare("SELECT id,xp_reward,coin_reward FROM achievements WHERE name=?");
                $a->execute([$achName]);
                $ach = $a->fetch();
                if (!$ach) continue;
                $c = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
                $c->execute([$u['id'], $ach['id']]);
                if (!$c->fetch()) {
                    $pdo->prepare("INSERT IGNORE INTO user_achievements(user_id,achievement_id) VALUES(?,?)")->execute([$u['id'],$ach['id']]);
                    $log[] = "Awarded {$achName} to user #{$u['id']}";
                }
            }
        }
    }
} catch (Exception $e) { $log[] = 'Award err: '.$e->getMessage(); }

// 8. Fix profile level sync — ensure level column matches XP
try {
    $users2 = $pdo->query("SELECT id, xp, level FROM users")->fetchAll();
    foreach ($users2 as $u) {
        $correctLevel = floor(sqrt((int)$u['xp'] / 100)) + 1;
        if ($correctLevel != (int)$u['level']) {
            $pdo->prepare("UPDATE users SET level=? WHERE id=?")->execute([$correctLevel, $u['id']]);
            $log[] = "Fixed level for user #{$u['id']}: {$u['level']}→{$correctLevel}";
        }
    }
} catch (Exception $e) { $log[] = 'Level fix err: '.$e->getMessage(); }

// 9. Add equipped_character column
try {
    $uCols = array_column($pdo->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
    if (!in_array('equipped_character', $uCols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN equipped_character INT DEFAULT NULL");
        $log[] = 'Added equipped_character column';
    }
} catch (Exception $e) { $log[] = 'Col err: '.$e->getMessage(); }

// 10. Add custom_border_color column for user-customizable borders
try {
    $uCols2 = array_column($pdo->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
    if (!in_array('custom_border_color', $uCols2)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN custom_border_color VARCHAR(20) DEFAULT NULL");
        $log[] = 'Added custom_border_color column';
    }
} catch (Exception $e) { $log[] = 'Color col err: '.$e->getMessage(); }

// 11. Ensure user_quests has claimed column
try {
    $qCols = array_column($pdo->query("SHOW COLUMNS FROM user_quests")->fetchAll(), 'Field');
    if (!in_array('claimed', $qCols)) {
        $pdo->exec("ALTER TABLE user_quests ADD COLUMN claimed TINYINT(1) DEFAULT 0");
        $log[] = 'Added claimed column to user_quests';
    }
} catch (Exception $e) { $log[] = 'quest col: '.$e->getMessage(); }

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo '<pre style="background:#0a130b;color:#82c99a;padding:20px;font-family:monospace;">';
echo "✅ Fix script selesai\n\n";
foreach ($log as $l) echo "• $l\n";
echo "\n<a href='/?page=profile' style='color:#82c99a;'>→ Cek Profile</a>  ";
echo "<a href='/?page=members' style='color:#82c99a;margin-left:20px;'>→ Cek Members</a>  ";
echo "<a href='/?page=perks' style='color:#82c99a;margin-left:20px;'>→ Cek Shop</a>";
echo '</pre>';
