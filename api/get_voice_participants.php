<?php
// api/get_voice_participants.php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'participants' => []]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$channel = trim($_GET['channel'] ?? 'lounge');
$action  = trim($_GET['action'] ?? 'list'); // list | join | leave | ping

// Ensure voice_sessions table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS voice_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        channel VARCHAR(50) NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_voice (user_id)
    )");
} catch (Exception $e) {}

// Purge stale sessions (no ping > 35s)
try {
    $pdo->exec("DELETE FROM voice_sessions WHERE last_ping < DATE_SUB(NOW(), INTERVAL 35 SECOND)");
} catch (Exception $e) {}

// ── JOIN ──────────────────────────────────────────────────────────────────
if ($action === 'join') {
    try {
        $pdo->prepare("INSERT INTO voice_sessions (user_id, channel) VALUES (?,?)
            ON DUPLICATE KEY UPDATE channel=VALUES(channel), last_ping=NOW()")
            ->execute([$user_id, $channel]);
    } catch (Exception $e) {}
    echo json_encode(['success' => true]);
    exit;
}

// ── LEAVE ─────────────────────────────────────────────────────────────────
if ($action === 'leave') {
    try {
        // Calculate time spent this session (joined_at → now)
        $sessRow = $pdo->prepare("SELECT joined_at FROM voice_sessions WHERE user_id=?");
        $sessRow->execute([$user_id]);
        $sess = $sessRow->fetch();
        if ($sess) {
            $minutes = max(0, (int)round((time() - strtotime($sess['joined_at'])) / 60));
            if ($minutes > 0) {
                $pdo->prepare("UPDATE users SET voice_minutes = voice_minutes + ? WHERE id=?")
                    ->execute([$minutes, $user_id]);

                // Check "Paling Betah" achievement: 100 jam = 6000 menit
                $vmRow = $pdo->prepare("SELECT voice_minutes FROM users WHERE id=?");
                $vmRow->execute([$user_id]);
                $vm = (int)($vmRow->fetchColumn() ?? 0);
                if ($vm >= 6000) {
                    award_ach_voice($pdo, $user_id, 'Paling Betah');
                }

                // Update quest progress (voice_minutes type)
                $today = date('Y-m-d');
                $pdo->prepare("
                    UPDATE user_quests uq
                    JOIN daily_quests dq ON uq.quest_id=dq.id
                    SET uq.progress  = uq.progress + ?,
                        uq.completed = IF(uq.progress + ? >= dq.requirement_value, 1, uq.completed)
                    WHERE uq.user_id=? AND uq.assigned_date=?
                      AND dq.requirement_type='voice_minutes' AND uq.completed=0
                ")->execute([$minutes, $minutes, $user_id, $today]);
            }
        }
        $pdo->prepare("DELETE FROM voice_sessions WHERE user_id=?")->execute([$user_id]);
    } catch (Exception $e) {}
    echo json_encode(['success' => true]);
    exit;
}

// ── PING (keep-alive every 10s) ───────────────────────────────────────────
if ($action === 'ping') {
    try {
        $pdo->prepare("UPDATE voice_sessions SET last_ping=NOW() WHERE user_id=?")->execute([$user_id]);
    } catch (Exception $e) {}
    echo json_encode(['success' => true]);
    exit;
}

// ── LIST participants ─────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.avatar, u.level
        FROM voice_sessions vs
        JOIN users u ON u.id = vs.user_id
        WHERE vs.channel = ?
        ORDER BY vs.joined_at ASC
    ");
    $stmt->execute([$channel]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'participants' => $participants, 'count' => count($participants)]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'participants' => [], 'count' => 0]);
}

// ── Helper ────────────────────────────────────────────────────────────────
function award_ach_voice($pdo, $user_id, $name) {
    try {
        $s = $pdo->prepare("SELECT id, xp_reward, coin_reward FROM achievements WHERE name=?");
        $s->execute([$name]);
        $a = $s->fetch();
        if (!$a) return;
        $c = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
        $c->execute([$user_id, $a['id']]);
        if ($c->fetch()) return;
        $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?,?)")->execute([$user_id, $a['id']]);
        $pdo->prepare("UPDATE users SET xp=xp+?, coins=coins+? WHERE id=?")->execute([$a['xp_reward'], $a['coin_reward'], $user_id]);
    } catch (Exception $e) {}
}
