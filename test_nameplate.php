<?php
require_once __DIR__ . '/config/database.php';
$stmt = $pdo->query("SELECT * FROM shop_items WHERE category='nameplate'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
