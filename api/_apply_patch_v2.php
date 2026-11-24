<?php
// api/_apply_patch_v2.php
require_once __DIR__ . '/../config/database.php';
global $pdo;

echo "<pre>\n--- Applying Database Patch V2 ---\n";

// 1. Add frame_color to users table
try {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
    if (!in_array('frame_color', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN frame_color VARCHAR(30) DEFAULT NULL");
        echo "✅ Added frame_color column to users table.\n";
    } else {
        echo "ℹ️ frame_color column already exists.\n";
    }
} catch (Exception $e) {
    echo "❌ Error adding frame_color: " . $e->getMessage() . "\n";
}

// 2. Remove Love Heart & Diamond Aura from shop_items
try {
    $pdo->exec("DELETE FROM shop_items WHERE name LIKE '%Love%' OR name LIKE '%Diamond Aura%'");
    echo "✅ Removed Love Heart and Diamond Aura items from shop_items.\n";
} catch (Exception $e) {
    echo "❌ Error removing items: " . $e->getMessage() . "\n";
}

// 3. Ensure Level Milestone achievements exist in achievements table
$milestones = [
    ['name' => 'Novice I',   'desc' => 'Mencapai Level 50 dalam komunitas',   'icon' => '🌱', 'xp' => 500,  'coin' => 250,  'cat' => 'global'],
    ['name' => 'Veteran II',  'desc' => 'Mencapai Level 250 dalam komunitas',  'icon' => '⚔️', 'xp' => 1500, 'coin' => 750,  'cat' => 'global'],
    ['name' => 'Legend III',  'desc' => 'Mencapai Level 1000 dalam komunitas', 'icon' => '👑', 'xp' => 5000, 'coin' => 2500, 'cat' => 'global']
];

foreach ($milestones as $m) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM achievements WHERE name = ?");
        $stmt->execute([$m['name']]);
        if (!$stmt->fetch()) {
            $ins = $pdo->prepare("INSERT INTO achievements (name, description, icon, xp_reward, coin_reward, category) VALUES (?, ?, ?, ?, ?, ?)");
            $ins->execute([$m['name'], $m['desc'], $m['icon'], $m['xp'], $m['coin'], $m['cat']]);
            echo "✅ Inserted achievement: {$m['icon']} {$m['name']}\n";
        } else {
            echo "ℹ️ Achievement {$m['name']} already exists.\n";
        }
    } catch (Exception $e) {
        echo "❌ Error inserting achievement {$m['name']}: " . $e->getMessage() . "\n";
    }
}

// 4. Add Rainbow Halo and Custom Colorable Borders if not exist
$new_items = [
    [
        'name' => 'Rainbow Halo',
        'desc' => 'Border pelangi berkilau memancarkan spektrum warna-warni yang memukau',
        'cat' => 'border',
        'price' => 1500,
        'prev' => 'border:3px solid transparent; border-radius:50%; background:linear-gradient(#0f111a,#0f111a) padding-box, linear-gradient(135deg,#ff0000,#ff7f00,#ffff00,#00ff00,#0000ff,#4b0082,#8b00ff) border-box;',
        'aura' => 'box-shadow:0 0 15px rgba(255,0,128,0.5), 0 0 30px rgba(0,229,255,0.4); animation:aura-pulse 2s ease-in-out infinite;',
        'rarity' => 'epic'
    ],
    [
        'name' => 'Custom Circle',
        'desc' => 'Border lingkaran polos yang warna utamanya bebas kamu ganti sesuai selera!',
        'cat' => 'border',
        'price' => 300,
        'prev' => 'border:3px solid var(--custom-color, #00ff88); border-radius:50%; box-shadow:0 0 8px var(--custom-color, rgba(0,255,136,0.5));',
        'aura' => NULL,
        'rarity' => 'common'
    ],
    [
        'name' => 'Custom Square',
        'desc' => 'Border kotak polos yang warna utamanya bebas kamu ganti sesuai selera!',
        'cat' => 'border',
        'price' => 300,
        'prev' => 'border:3px solid var(--custom-color, #00ff88); border-radius:10px; box-shadow:0 0 8px var(--custom-color, rgba(0,255,136,0.5));',
        'aura' => NULL,
        'rarity' => 'common'
    ]
];

foreach ($new_items as $item) {
    try {
        $chk = $pdo->prepare("SELECT id FROM shop_items WHERE name = ?");
        $chk->execute([$item['name']]);
        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO shop_items (name, description, category, price, preview_css, aura_css, rarity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$item['name'], $item['desc'], $item['cat'], $item['price'], $item['prev'], $item['aura'], $item['rarity']]);
            echo "✅ Inserted shop item: {$item['name']}\n";
        } else {
            // Update preview_css & aura_css to be sure
            $upd = $pdo->prepare("UPDATE shop_items SET preview_css = ?, aura_css = ?, price = ? WHERE name = ?");
            $upd->execute([$item['prev'], $item['aura'], $item['price'], $item['name']]);
            echo "ℹ️ Updated shop item: {$item['name']}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error inserting shop item {$item['name']}: " . $e->getMessage() . "\n";
    }
}

// 5. Retroactively award level achievements to all users who qualify
try {
    $users = $pdo->query("SELECT id, level FROM users")->fetchAll();
    $ach_list = $pdo->query("SELECT id, name, xp_reward, coin_reward FROM achievements WHERE name IN ('Novice I', 'Veteran II', 'Legend III')")->fetchAll();
    $ach_map = [];
    foreach ($ach_list as $a) $ach_map[$a['name']] = $a;

    $awarded = 0;
    foreach ($users as $u) {
        $uid = $u['id'];
        $lvl = (int)$u['level'];

        $check_goals = [
            50 => 'Novice I',
            250 => 'Veteran II',
            1000 => 'Legend III'
        ];

        foreach ($check_goals as $req_lvl => $ach_name) {
            if ($lvl >= $req_lvl && isset($ach_map[$ach_name])) {
                $ach = $ach_map[$ach_name];
                $chk = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
                $chk->execute([$uid, $ach['id']]);
                if (!$chk->fetch()) {
                    $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)")->execute([$uid, $ach['id']]);
                    $awarded++;
                    echo "🎉 Retroactively awarded '{$ach_name}' to user ID {$uid} (Level {$lvl})\n";
                }
            }
        }
    }
    echo "✅ Level achievement check finished. Total new awards: {$awarded}\n";
} catch (Exception $e) {
    echo "❌ Error retroactively awarding achievements: " . $e->getMessage() . "\n";
}

echo "--- Patch V2 Completed ---\n</pre>";
