<?php
require_once __DIR__ . '/config/database.php';
$stmt = $pdo->query("SELECT id, name, preview_css, aura_css FROM shop_items WHERE category='border' AND aura_css != ''");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
