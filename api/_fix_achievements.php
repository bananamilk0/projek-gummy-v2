<?php
require_once __DIR__ . '/../config/database.php';
$log = [];

// 1. Fix Lunarian icons to moons
$pdo->exec("UPDATE achievements SET icon='🌙' WHERE name='Lunarian 𝟏'");
$pdo->exec("UPDATE achievements SET icon='🌕' WHERE name='Lunarian 𝟐'");
$pdo->exec("UPDATE achievements SET icon='🌒' WHERE name='Lunarian 𝟑'");
$log[] = 'Fixed Lunarian icons to moons';

// 2. Remove exact duplicates by name — keep lowest id
$pdo->exec("DELETE a1 FROM achievements a1
    INNER JOIN achievements a2
    WHERE a1.id > a2.id AND a1.name = a2.name");
$log[] = 'Removed duplicate achievements';

echo '<pre>'; foreach($log as $l) echo "✅ $l\n";
// Show current achievements
$r = $pdo->query("SELECT id,name,icon,category FROM achievements ORDER BY id");
echo "\nCurrent achievements:\n";
foreach($r->fetchAll(PDO::FETCH_ASSOC) as $a) {
    echo $a['id'].' '.$a['icon'].' ['.$a['category'].'] '.$a['name']."\n";
}
echo '</pre>';
