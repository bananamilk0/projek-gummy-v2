<?php
require_once __DIR__ . '/../config/database.php';
try {
    // Delete duplicates
    $pdo->query("DELETE FROM shop_items WHERE id IN (2, 4, 13, 18, 70, 71)");
    // Rename remaining
    $pdo->query("UPDATE shop_items SET name='Circle', description='Border bentuk lingkaran', preview_css='border:3px solid #8a2be2; border-radius:50%; box-shadow:0 0 8px rgba(138,43,226,0.6);' WHERE id=1");
    $pdo->query("UPDATE shop_items SET name='Square', description='Border bentuk persegi', preview_css='border:3px solid #ffd700; border-radius:6px; box-shadow:0 0 12px rgba(255,215,0,.65);' WHERE id=3");
    echo "Done updating borders.";
} catch (Exception $e) {
    echo $e->getMessage();
}
