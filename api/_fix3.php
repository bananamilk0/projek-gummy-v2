<?php
require_once __DIR__ . '/../config/database.php';
$log = [];

// 1. Ensure all "Circle" borders have explicit border-radius:50% in preview_css
$borders = $pdo->query("SELECT id, name, preview_css FROM shop_items WHERE category='border'")->fetchAll();
foreach ($borders as $b) {
    $css = $b['preview_css'] ?? '';
    $isCircle = stripos($b['name'], 'circle') !== false 
             || stripos($b['name'], 'ring') !== false
             || stripos($b['name'], 'halo') !== false
             || stripos($b['name'], 'aura') !== false;
    // Has clip-path = not circle
    $hasClip = strpos($css, 'clip-path') !== false;
    $hasExplicitBR = preg_match('/border-radius:\s*[^;]+;/', $css);
    
    if ($isCircle && !$hasClip) {
        if (!$hasExplicitBR) {
            $newCss = rtrim($css) . ' border-radius:50%;';
            $pdo->prepare("UPDATE shop_items SET preview_css=? WHERE id=?")->execute([$newCss, $b['id']]);
            $log[] = "Added border-radius:50% to: {$b['name']}";
        } elseif (!preg_match('/border-radius:\s*50%/', $css)) {
            // Replace non-50% border-radius for circle borders
            $newCss = preg_replace('/border-radius:\s*[^;]+;/', 'border-radius:50%;', $css);
            $pdo->prepare("UPDATE shop_items SET preview_css=? WHERE id=?")->execute([$newCss, $b['id']]);
            $log[] = "Fixed border-radius to 50% for: {$b['name']}";
        }
    }
}

// 2. Fix Nova Aura, Ocean Aura, Sakura Aura, Inferno Ring, Rainbow Halo — ensure border-radius:50% in preview_css
$circleNames = ['Nova Aura','Ocean Aura','Sakura Aura','Inferno Ring','Inferno Orbit','Rainbow Halo'];
foreach ($circleNames as $name) {
    $row = $pdo->prepare("SELECT id, preview_css FROM shop_items WHERE name=?");
    $row->execute([$name]);
    $item = $row->fetch();
    if ($item) {
        $css = $item['preview_css'] ?? '';
        if (!preg_match('/border-radius:\s*50%/', $css)) {
            if ($hasExplicit = preg_match('/border-radius:\s*[^;]+;/', $css)) {
                $newCss = preg_replace('/border-radius:\s*[^;]+;/', 'border-radius:50%;', $css);
            } else {
                $newCss = rtrim($css) . ' border-radius:50%;';
            }
            $pdo->prepare("UPDATE shop_items SET preview_css=? WHERE id=?")->execute([$newCss, $item['id']]);
            $log[] = "Fixed circle radius for: $name";
        }
    }
}

// 3. Fix Rainbow Halo aura_css for circular display
$pdo->exec("UPDATE shop_items SET 
    aura_css='filter:drop-shadow(0 0 5px rgba(255,50,50,0.7)) drop-shadow(0 0 10px rgba(50,200,255,0.6)) drop-shadow(0 0 15px rgba(100,255,0,0.5)); animation:rainbowGlow 2.5s linear infinite;'
WHERE name='Rainbow Halo'");
$log[] = 'Updated Rainbow Halo aura';

// 4. Add total_time_spent column if not exists (for "paling betah" leaderboard)
try {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM users")->fetchAll(), 'Field');
    if (!in_array('total_time_spent', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_time_spent INT DEFAULT 0 COMMENT 'Total seconds spent on site'");
        $log[] = 'Added total_time_spent column';
    }
    if (!in_array('frame_color', $cols)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN frame_color VARCHAR(20) DEFAULT NULL");
        $log[] = 'Added frame_color column';
    }
} catch (Exception $e) { $log[] = 'Col err: '.$e->getMessage(); }

// 5. Add voice_minutes column tracking (used for "paling betah" based on voice time)
// total_time_spent = voice_minutes * 60 + login days as proxy
try {
    $pdo->exec("UPDATE users SET total_time_spent = (COALESCE(voice_minutes,0) * 60) + (COALESCE(login_streak,0) * 86400) WHERE total_time_spent = 0");
    $log[] = 'Seeded total_time_spent from voice_minutes + login_streak';
} catch (Exception $e) { $log[] = 'Seed err: '.$e->getMessage(); }

echo '<pre style="background:#0a130b;color:#82c99a;padding:20px;font-family:monospace;">';
echo "✅ Fix3 selesai\n\n";
foreach ($log as $l) echo "• $l\n";
echo '</pre>';
