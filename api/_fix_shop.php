<?php
require_once __DIR__ . '/../config/database.php';
$log = [];

// === BORDERS ===
// Remove Pill White (id=5) dan Square Dark (id=3)
$pdo->exec("DELETE FROM shop_items WHERE id IN (3,5) AND category='border'");
$log[] = 'Deleted Pill White & Square Dark borders';

// Replace Square Dark (id=3) with Aqua Gold Square
$pdo->exec("INSERT IGNORE INTO shop_items (id,name,description,category,price,preview_css,aura_css,emoji,rarity) VALUES
(3,'Aqua Gold','Border kotak bergaris gradasi aqua keemasan','border',350,'border:3px solid #40c9a0; background:linear-gradient(#0f111a,#0f111a) padding-box, linear-gradient(135deg,#40c9a0,#ffd700) border-box; border-radius:6px;',NULL,NULL,'common')");
$log[] = 'Added Aqua Gold Square border';

// Replace Galaxy Halo (id=10) with Rainbow Halo (lingkaran penuh warna)
$pdo->exec("UPDATE shop_items SET
    name='Rainbow Halo',
    description='Border lingkaran penuh warna pelangi yang meriah',
    preview_css='border:3px solid transparent; border-radius:50%; background:linear-gradient(#0f111a,#0f111a) padding-box, conic-gradient(from 0deg,#ff0000,#ff7700,#ffff00,#00ff00,#0000ff,#8b00ff,#ff0000) border-box;',
    aura_css=NULL,
    price=700,
    rarity='rare'
WHERE id=10 AND category='border'");
$log[] = 'Updated Galaxy Halo → Rainbow Halo';

// Remove Diamond Blue (id=8)
$pdo->exec("DELETE FROM shop_items WHERE id=8 AND category='border'");
$log[] = 'Deleted Diamond Blue border';

// Replace Gold Square (id=6) with Love Pink (heart shape)
$pdo->exec("UPDATE shop_items SET
    name='Love Pink',
    description='Border berbentuk hati merah muda yang menggemaskan',
    preview_css='border:3px solid #ff6b9d; border-radius:50% 50% 0 0 / 60% 60% 0 0; transform:rotate(-45deg); background:linear-gradient(135deg,rgba(255,107,157,0.15),rgba(255,50,100,0.1)); box-shadow:0 0 10px rgba(255,107,157,0.5);',
    aura_css=NULL,
    price=500,
    rarity='rare'
WHERE id=6 AND category='border'");
$log[] = 'Updated Gold Square → Love Pink heart border';

// Legendary: Inferno Ring — orbs orbiting with aura
$pdo->exec("UPDATE shop_items SET
    name='Inferno Orbit',
    description='Border api dengan bola merah berputar mengelilingi dan ekor aura membara',
    preview_css='border:3px solid #ff4400; border-radius:50%; box-shadow:0 0 8px rgba(255,68,0,0.7);',
    aura_css='box-shadow:0 0 0 5px rgba(255,80,0,0.2),0 0 25px rgba(255,68,0,0.7),0 0 50px rgba(200,30,0,0.3); animation:inferno-orbit 1.4s ease-in-out infinite;',
    price=2200,
    rarity='legendary'
WHERE id=16 AND category='border'");
$log[] = 'Updated Inferno Ring → Inferno Orbit';

// Legendary: Divine Crown — crown on top
$pdo->exec("UPDATE shop_items SET
    name='Divine Crown',
    description='Border kotak emas dengan mahkota di atasnya — simbol keagungan sejati',
    preview_css='border:3px solid #ffd700; border-radius:8px; box-shadow:0 0 8px rgba(255,215,0,0.5);',
    aura_css='box-shadow:0 0 0 6px rgba(255,215,0,0.3),0 0 30px rgba(255,215,0,0.7),0 0 60px rgba(255,200,0,0.25); animation:aura-pulse 1s ease-in-out infinite;',
    price=2800,
    rarity='legendary'
WHERE id=17 AND category='border'");
$log[] = 'Updated Divine Crown';

// Legendary: Void Hex → Boom Square
$pdo->exec("UPDATE shop_items SET
    name='Boom Square',
    description='Border kotak hitam dengan ledakan yang muncul setiap 3 detik!',
    preview_css='border:3px solid #ff2200; border-radius:4px; background:rgba(20,5,5,0.6); box-shadow:0 0 8px rgba(255,34,0,0.5);',
    aura_css='box-shadow:0 0 0 4px rgba(255,50,0,0.2),0 0 20px rgba(255,50,0,0.6),0 0 40px rgba(200,0,0,0.3); animation:boom-pulse 3s ease-in-out infinite;',
    price=3200,
    rarity='legendary'
WHERE id=18 AND category='border'");
$log[] = 'Updated Void Hex → Boom Square';

// === NAMEPLATES ===
// Lunar Crown (id=33) → Divine Crown with crown styling
$pdo->exec("UPDATE shop_items SET
    name='Divine Crown',
    description='Nameplate mahkota emas legendaris — tampil seperti raja/ratu di profil',
    preview_css='background:linear-gradient(135deg,rgba(255,215,0,0.25),rgba(200,160,0,0.15)); border:2px solid rgba(255,215,0,0.7); color:#ffd700; border-radius:20px;',
    aura_css='box-shadow:0 0 16px rgba(255,215,0,0.6),0 0 35px rgba(255,200,0,0.28); animation:aura-pulse 1.5s ease-in-out infinite; text-shadow:0 0 10px rgba(255,215,0,0.85);',
    price=2500
WHERE id=33 AND category='nameplate'");
$log[] = 'Updated Lunar Crown → Divine Crown nameplate';

foreach($log as $l) echo "✅ $l\n";

// Show current borders
echo "\nCurrent borders:\n";
$r = $pdo->query("SELECT id,name,rarity FROM shop_items WHERE category='border' ORDER BY FIELD(rarity,'common','rare','epic','legendary'), id");
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $row) echo $row['id'].' ['.$row['rarity'].'] '.$row['name']."\n";
