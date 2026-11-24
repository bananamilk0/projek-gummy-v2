<?php
// api/get_chat.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$channel = $_GET['channel'] ?? 'ngobrol';

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.message, c.media_url, DATE_FORMAT(c.created_at, '%H:%i') as time, 
               u.id as user_id, u.username, u.level, u.name_color, u.avatar, u.role 
        FROM chat_messages c
        JOIN users u ON c.user_id = u.id
        WHERE c.id > ? AND c.channel = ?
        ORDER BY c.id ASC
        LIMIT 50
    ");
    $stmt->execute([$last_id, $channel]);
    $messages = $stmt->fetchAll();
    
    echo json_encode(['messages' => $messages]);
} catch(Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
