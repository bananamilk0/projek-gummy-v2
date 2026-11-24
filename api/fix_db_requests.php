<?php
require_once __DIR__ . '/../config/database.php';
try {
    // 3. Delete Forest Leaf and Crimson
    $pdo->query("DELETE FROM shop_items WHERE name LIKE '%Forest Leaf%' OR name LIKE '%Crimson%'");

    // 4. Nameplates: Keep one Circle (Silver Tag, make it Circle), Keep one Square (Indigo Box, make it Square). Delete other commons/rares maybe?
    // Let's just update Silver Tag to Circle and Indigo Box to Square.
    $pdo->query("UPDATE shop_items SET name='Circle', description='Nameplate bentuk bulat', preview_css='background:rgba(160,165,180,0.15); border:1px solid rgba(160,165,180,0.5); color:#c8ccd8; border-radius:30px;', aura_css='' WHERE id=12"); // Assuming 12 is Silver Tag
    $pdo->query("UPDATE shop_items SET name='Square', description='Nameplate bentuk kotak', preview_css='background:rgba(80,60,200,0.18); border:1px solid rgba(100,80,220,0.5); color:#a0a8ff; border-radius:8px;', aura_css='' WHERE id=13"); // Assuming 13 is Indigo Box

    // Delete other nameplates that are basic shapes
    $pdo->query("DELETE FROM shop_items WHERE category='nameplate' AND id NOT IN (12,13) AND rarity IN ('common', 'rare')");

    // 5. Game characters: Unify basic characters into 'Circle'
    $pdo->query("UPDATE shop_items SET name='Circle', description='Karakter dasar bulat yang warnanya bisa diubah', emoji='⚪' WHERE id=22"); // Assuming 22 is Gummy Bear
    $pdo->query("DELETE FROM shop_items WHERE category='character' AND id != 22 AND rarity='common'");

    // 6. Fix borders with effects. What could be the issue? 
    // Maybe `aura_css` has double quotes and it breaks HTML?
    $stmt = $pdo->query("SELECT id, aura_css, preview_css FROM shop_items");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($items as $item) {
        $upd = false;
        $aura = $item['aura_css'];
        $prev = $item['preview_css'];
        if (strpos($aura, '"') !== false) {
            $aura = str_replace('"', "'", $aura);
            $upd = true;
        }
        if (strpos($prev, '"') !== false) {
            $prev = str_replace('"', "'", $prev);
            $upd = true;
        }
        if ($upd) {
            $u = $pdo->prepare("UPDATE shop_items SET aura_css=?, preview_css=? WHERE id=?");
            $u->execute([$aura, $prev, $item['id']]);
        }
    }
    
    // Also update all users with equipped items to ensure quotes don't break HTML
    $pdo->query("UPDATE users SET frame_style = REPLACE(frame_style, '\"', '\'') WHERE frame_style LIKE '%\"%'");
    $pdo->query("UPDATE users SET aura = REPLACE(aura, '\"', '\'') WHERE aura LIKE '%\"%'");
    $pdo->query("UPDATE users SET nameplate_style = REPLACE(nameplate_style, '\"', '\'') WHERE nameplate_style LIKE '%\"%'");
    
    echo "DB Updates complete.\n";
} catch (Exception $e) {
    echo $e->getMessage();
}
