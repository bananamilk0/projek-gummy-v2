<?php
// api/delete_chat.php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']); exit;
}

$msg_id = (int)($_POST['id'] ?? 0);
$own    = isset($_POST['own']) && $_POST['own'] == '1';
$role   = $_SESSION['role'] ?? 'user';
$uid    = (int)$_SESSION['user_id'];

if ($msg_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']); exit;
}

try {
    if ($role === 'admin' || $role === 'moderator') {
        // Admin/moderator: can delete any message
        $pdo->prepare("DELETE FROM chat_messages WHERE id=?")->execute([$msg_id]);
        echo json_encode(['success' => true]);
    } elseif ($own) {
        // User deleting own message — verify ownership
        $check = $pdo->prepare("SELECT user_id FROM chat_messages WHERE id=?");
        $check->execute([$msg_id]);
        $row = $check->fetch();
        if ($row && (int)$row['user_id'] === $uid) {
            $pdo->prepare("DELETE FROM chat_messages WHERE id=?")->execute([$msg_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Bukan pesanmu']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
