<?php
// api/buy_item.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
global $pdo;

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$item_id = (int)($input['item_id'] ?? $_POST['buy_item'] ?? 0);

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Item ID tidak valid.']);
    exit;
}

try {
    // Fetch item
    $stmt = $pdo->prepare("SELECT * FROM shop_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan.']);
        exit;
    }

    // Check ownership
    $chk = $pdo->prepare("SELECT id FROM user_inventory WHERE user_id = ? AND item_id = ?");
    $chk->execute([$user_id, $item_id]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kamu sudah memiliki item ini!']);
        exit;
    }

    // Fetch user coins
    $uStmt = $pdo->prepare("SELECT coins FROM users WHERE id = ?");
    $uStmt->execute([$user_id]);
    $userCoins = (int)$uStmt->fetchColumn();

    if ($userCoins < (int)$item['price']) {
        echo json_encode(['success' => false, 'message' => 'Koin tidak cukup! Butuh ' . number_format($item['price']) . ' koin.']);
        exit;
    }

    // Deduct coins & insert into inventory
    $newCoins = $userCoins - (int)$item['price'];
    $pdo->prepare("UPDATE users SET coins = ? WHERE id = ?")->execute([$newCoins, $user_id]);
    $pdo->prepare("INSERT IGNORE INTO user_inventory (user_id, item_id) VALUES (?, ?)")->execute([$user_id, $item_id]);

    $_SESSION['coins'] = $newCoins;

    echo json_encode([
        'success' => true,
        'message' => '✅ ' . htmlspecialchars($item['name']) . ' berhasil dibeli!',
        'coins' => $newCoins,
        'item_id' => $item_id,
        'category' => $item['category']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
