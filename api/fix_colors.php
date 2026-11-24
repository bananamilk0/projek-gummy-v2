<?php
require_once __DIR__ . '/../config/database.php';
try {
    $pdo->query("UPDATE shop_items SET preview_css='background:rgba(255,255,255,0.05); border:1px solid var(--custom-color, #c8ccd8); color:var(--custom-color, #c8ccd8); border-radius:30px;' WHERE name='Circle' AND category='nameplate'");
    $pdo->query("UPDATE shop_items SET preview_css='background:rgba(255,255,255,0.05); border:1px solid var(--custom-color, #a0a8ff); color:var(--custom-color, #a0a8ff); border-radius:8px;' WHERE name='Square' AND category='nameplate'");
    
    // For character 'Circle', use ⬤ which respects CSS 'color'
    $pdo->query("UPDATE shop_items SET emoji='⬤', aura_css='color:var(--custom-color, #00ff88); text-shadow:0 0 15px var(--custom-color, #00ff88);' WHERE name='Circle' AND category='character'");
    echo "Done";
} catch (Exception $e) { echo $e->getMessage(); }
