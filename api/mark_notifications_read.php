<?php
// api/mark_notifications_read.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    // Fetch active notifications
    $stmt = $pdo->query("SELECT id FROM site_notifications WHERE is_active = 1");
    $activeNotifs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($activeNotifs)) {
        $insStmt = $pdo->prepare("INSERT IGNORE INTO user_read_notifications (user_id, notification_id) VALUES (?, ?)");
        foreach ($activeNotifs as $nid) {
            $insStmt->execute([$user_id, $nid]);
        }
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
