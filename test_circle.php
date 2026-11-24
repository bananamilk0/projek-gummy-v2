<?php
require_once __DIR__ . '/config/database.php';
$stmt = $pdo->query("SELECT id, name, category, preview_css FROM shop_items WHERE name LIKE '%Circle%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
