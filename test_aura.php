<?php
require_once __DIR__ . '/config/database.php';

// Check aura_css lengths
$stmt = $pdo->query("SELECT id, name, LENGTH(aura_css) as len, aura_css FROM shop_items WHERE category='border' AND aura_css != '' ORDER BY len DESC");
echo "=== AURA CSS LENGTHS ===\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID={$row['id']} name={$row['name']} len={$row['len']}\n  aura: {$row['aura_css']}\n\n";
}

// Check users aura field - what's getting stored?
echo "\n=== USERS AURA (stored values) ===\n";
$stmt2 = $pdo->query("SELECT id, username, frame, LENGTH(aura) as aura_len, aura FROM users WHERE aura IS NOT NULL AND aura != ''");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "User={$row['username']} frame={$row['frame']} aura_len={$row['aura_len']}\n  aura: {$row['aura']}\n\n";
}
