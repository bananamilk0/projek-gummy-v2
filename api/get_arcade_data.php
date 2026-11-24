<?php
// api/get_arcade_data.php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = ['success' => true];

try {
    // 1. Get high scores
    $stmt = $pdo->prepare("
        SELECT g.slug, MAX(s.score) as high_score
        FROM games g
        LEFT JOIN scores s ON g.id = s.game_id AND s.user_id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$user_id]);
    $data['high_scores'] = $stmt->fetchAll();

    // 2. Get unlocked achievements
    $stmt = $pdo->prepare("
        SELECT a.id, a.name, a.description, a.icon, a.game_id 
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $data['achievements'] = $stmt->fetchAll();

    // 3. Get daily quests
    $today = date('Y-m-d');
    
    // Assign quests if none exist for today
    $checkQuest = $pdo->prepare("SELECT COUNT(*) as c FROM user_quests WHERE user_id = ? AND assigned_date = ?");
    $checkQuest->execute([$user_id, $today]);
    if ($checkQuest->fetch()['c'] == 0) {
        // Pick 3 random quests
        $dqStmt = $pdo->query("SELECT id FROM daily_quests ORDER BY RAND() LIMIT 3");
        $questsToAssign = $dqStmt->fetchAll();
        $assignStmt = $pdo->prepare("INSERT INTO user_quests (user_id, quest_id, assigned_date) VALUES (?, ?, ?)");
        foreach($questsToAssign as $q) {
            $assignStmt->execute([$user_id, $q['id'], $today]);
        }
    }

    $stmt = $pdo->prepare("
        SELECT dq.title, dq.description, dq.xp_reward, dq.requirement_value, uq.progress, uq.completed 
        FROM user_quests uq
        JOIN daily_quests dq ON uq.quest_id = dq.id
        WHERE uq.user_id = ? AND uq.assigned_date = ?
    ");
    $stmt->execute([$user_id, $today]);
    $data['quests'] = $stmt->fetchAll();

    echo json_encode($data);

} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
