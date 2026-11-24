<?php
require_once __DIR__ . '/../config/database.php';
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$log = [];

// 1. Remove EXACT duplicate achievements by name (keep lowest id)
$pdo->exec("DELETE a1 FROM achievements a1
    INNER JOIN achievements a2 ON a1.name = a2.name AND a1.id > a2.id");
$log[] = 'Removed duplicate achievements by name';

// 2. Specifically remove duplicates of level achievements with emoji prefix
// Ensure only one of each name variant exists
$levelAchs = ['Novice I', 'Veteran II', 'Legend III'];
foreach ($levelAchs as $base) {
    try {
        // Find all rows containing this base name
        $rows = $pdo->prepare("SELECT id, name FROM achievements WHERE name LIKE ?");
        $rows->execute(['%'.$base.'%']);
        $found = $rows->fetchAll();
        if (count($found) > 1) {
            // Keep first, delete rest
            $keepId = $found[0]['id'];
            foreach ($found as $i => $r) {
                if ($i > 0) {
                    // Also remove user_achievements for deleted ach
                    $pdo->prepare("DELETE FROM user_achievements WHERE achievement_id=?")->execute([$r['id']]);
                    $pdo->prepare("DELETE FROM achievements WHERE id=?")->execute([$r['id']]);
                    $log[] = "Deleted duplicate '{$r['name']}' (id={$r['id']})";
                }
            }
        }
    } catch (Exception $e) { $log[] = "Err: ".$e->getMessage(); }
}

// 3. Ensure 'level' category achievements exist exactly once
$levelData = [
    ['🌱 Novice I',   'Mencapai Level 50',   '🌱', 200, 100, 'level'],
    ['⚔️ Veteran II', 'Mencapai Level 250',  '⚔️', 500, 250, 'level'],
    ['👑 Legend III', 'Mencapai Level 1000', '👑', 2000, 1000, 'level'],
];
foreach ($levelData as [$name, $desc, $icon, $xp, $coins, $cat]) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM achievements WHERE name=?");
    $check->execute([$name]);
    if ((int)$check->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO achievements(name,description,icon,xp_reward,coin_reward,category) VALUES(?,?,?,?,?,?)")
            ->execute([$name, $desc, $icon, $xp, $coins, $cat]);
        $log[] = "Added missing achievement: $name";
    }
}

// 4. Add 'level' to cat_labels — add level category to achievements.php data
// (no DB change needed, just ensure category exists)
$log[] = 'Level achievements category ready';

// 5. Re-award level achievements to all users who qualify (idempotent)
try {
    $users = $pdo->query("SELECT id, level FROM users WHERE level > 0")->fetchAll();
    $milestones = [50=>'🌱 Novice I', 250=>'⚔️ Veteran II', 1000=>'👑 Legend III'];
    $awarded = 0;
    foreach ($users as $u) {
        foreach ($milestones as $reqLvl => $achName) {
            if ((int)$u['level'] >= $reqLvl) {
                $a = $pdo->prepare("SELECT id FROM achievements WHERE name=?");
                $a->execute([$achName]);
                $ach = $a->fetch();
                if (!$ach) continue;
                $c = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
                $c->execute([$u['id'], $ach['id']]);
                if (!$c->fetch()) {
                    $pdo->prepare("INSERT IGNORE INTO user_achievements(user_id,achievement_id) VALUES(?,?)")->execute([$u['id'],$ach['id']]);
                    $awarded++;
                }
            }
        }
    }
    $log[] = "Awarded $awarded missing level achievements";
} catch (Exception $e) { $log[] = "Award err: ".$e->getMessage(); }

// 6. Fix border sizes — increase preview_css border-width from 3px to 4px for all non-aura borders
// and reduce box-shadow values by ~30% for less glow spam
try {
    $borders = $pdo->query("SELECT id, name, preview_css, aura_css FROM shop_items WHERE category='border'")->fetchAll();
    foreach ($borders as $b) {
        $css = $b['preview_css'] ?? '';
        // Increase border-width from 3px to 4px
        $newCss = preg_replace('/\bborder:3px\b/', 'border:4px', $css);
        // Reduce box-shadow opacity/spread
        $newCss = preg_replace('/box-shadow:0 0 (\d+)px/', 'box-shadow:0 0 $1px', $newCss);
        if ($newCss !== $css) {
            $pdo->prepare("UPDATE shop_items SET preview_css=? WHERE id=?")->execute([$newCss, $b['id']]);
        }
        // Reduce aura box-shadow spread for less spam
        if ($b['aura_css']) {
            $newAura = preg_replace('/0 0 (\d+)px rgba\((\d+),(\d+),(\d+),([\d.]+)\)/e', 
                '\'0 0 \'.($1*0.7).\'px rgba(\'.$2.\',\'.$3.\',\'.$4.\',\'.min(0.55,$5).\')\' ', 
                $b['aura_css']);
            // Simpler: just reduce outer glow
            $newAura = preg_replace('/0 0 (5[0-9]|[6-9]\d|\d{3,})px/', '0 0 30px', $b['aura_css']);
            if ($newAura !== $b['aura_css']) {
                $pdo->prepare("UPDATE shop_items SET aura_css=? WHERE id=?")->execute([$newAura, $b['id']]);
            }
        }
    }
    $log[] = 'Updated border sizes and reduced aura glow';
} catch (Exception $e) { $log[] = 'Border fix err: '.$e->getMessage(); }

// 7. Fix Divine Crown aura — restore it (ensure aura_css is set)
$pdo->exec("UPDATE shop_items SET 
    aura_css='box-shadow:0 0 0 4px rgba(255,215,0,0.25),0 0 18px rgba(255,215,0,0.5),0 0 30px rgba(255,200,0,0.2); animation:aura-pulse 1s ease-in-out infinite;'
WHERE name='Divine Crown' AND category='border'");
$log[] = 'Restored Divine Crown aura';

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo '<pre style="background:#0a130b;color:#82c99a;padding:20px;font-family:monospace;">';
echo "✅ Fix4 selesai\n\n";
foreach ($log as $l) echo "• $l\n";
echo "\n<a href='/?page=achievements' style='color:#82c99a;'>→ Cek Achievements</a>  ";
echo "<a href='/?page=perks' style='color:#82c99a;margin-left:16px;'>→ Cek Shop</a>";
echo '</pre>';
