<?php
// api/submit_score.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/level_system.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $score = isset($data['score']) ? (int)$data['score'] : 0;
    $game_slug = isset($data['game_slug']) ? $data['game_slug'] : '';
    
    if ($score <= 0 || empty($game_slug)) {
        echo json_encode(['success' => false, 'error' => 'Invalid score or game']);
        exit;
    }
    
    try {
        // Find game ID
        $stmt = $pdo->prepare("SELECT id FROM games WHERE slug = ?");
        $stmt->execute([$game_slug]);
        $game = $stmt->fetch();
        
        if (!$game) {
            echo json_encode(['success' => false, 'error' => 'Game not found']);
            exit;
        }
        
        $game_id = $game['id'];
        
        // Insert score
        $stmt = $pdo->prepare("INSERT INTO scores (user_id, game_id, score) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $game_id, $score]);
        
        // Award XP based on score (e.g. 1 XP per 10 points)
        $xp_gained = max(5, floor($score / 10)); // Minimum 5 XP
        add_xp($_SESSION['user_id'], $xp_gained);
        
        // --- ACHIEVEMENTS CHECK ---
        $achievements_unlocked = [];
        $stmt = $pdo->prepare("
            SELECT a.* FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            WHERE ua.id IS NULL AND (a.game_id = ? OR a.game_id IS NULL)
        ");
        $stmt->execute([$_SESSION['user_id'], $game_id]);
        $potential_achievements = $stmt->fetchAll();
        
        foreach ($potential_achievements as $ach) {
            $unlocked = false;
            if ($ach['requirement_type'] === 'play_count') {
                $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM scores WHERE user_id = ? AND (game_id = ? OR ? IS NULL)");
                $countStmt->execute([$_SESSION['user_id'], $ach['game_id'], $ach['game_id']]);
                $count = $countStmt->fetch()['count'];
                if ($count >= $ach['requirement_value']) $unlocked = true;
            } elseif ($ach['requirement_type'] === 'high_score') {
                $highStmt = $pdo->prepare("SELECT MAX(score) as max_score FROM scores WHERE user_id = ? AND game_id = ?");
                $highStmt->execute([$_SESSION['user_id'], $ach['game_id']]);
                $max_score = $highStmt->fetch()['max_score'];
                if ($max_score >= $ach['requirement_value']) $unlocked = true;
            }
            
            if ($unlocked) {
                $insertAch = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)");
                $insertAch->execute([$_SESSION['user_id'], $ach['id']]);
                add_xp($_SESSION['user_id'], $ach['xp_reward']);
                // Note: coins would need a function too, keeping simple for now
                $achievements_unlocked[] = $ach;
            }
        }
        
        // --- DAILY QUESTS CHECK ---
        // Basic check for 'play_game' quests
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT uq.*, dq.requirement_type, dq.requirement_value, dq.game_id 
            FROM user_quests uq
            JOIN daily_quests dq ON uq.quest_id = dq.id
            WHERE uq.user_id = ? AND uq.assigned_date = ? AND uq.completed = FALSE
        ");
        $stmt->execute([$_SESSION['user_id'], $today]);
        $active_quests = $stmt->fetchAll();
        
        foreach ($active_quests as $quest) {
            if ($quest['requirement_type'] === 'play_game' && ($quest['game_id'] == $game_id || $quest['game_id'] === null)) {
                $new_prog = $quest['progress'] + 1;
                $completed = $new_prog >= $quest['requirement_value'];
                $updQuest = $pdo->prepare("UPDATE user_quests SET progress = ?, completed = ? WHERE id = ?");
                $updQuest->execute([$new_prog, $completed ? 1 : 0, $quest['id']]);
                
                if ($completed) {
                    $qInfo = $pdo->prepare("SELECT xp_reward, coin_reward FROM daily_quests WHERE id = ?");
                    $qInfo->execute([$quest['quest_id']]);
                    $qData = $qInfo->fetch();
                    add_xp($_SESSION['user_id'], $qData['xp_reward']);
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'xp_gained' => $xp_gained, 
            'achievements' => $achievements_unlocked
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
