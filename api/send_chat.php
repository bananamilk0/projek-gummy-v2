<?php
// api/send_chat.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/level_system.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$message   = trim($_POST['message'] ?? '');
$channel   = trim($_POST['channel'] ?? 'ngobrol');
$media_url = trim($_POST['media_url'] ?? '');
$user_id   = (int)$_SESSION['user_id'];
$today     = date('Y-m-d');

// Whitelist channels
$allowed = ['ngobrol', 'media'];
if (!in_array($channel, $allowed)) $channel = 'ngobrol';

if ($message === '' && $media_url === '') {
    echo json_encode(['success' => false, 'error' => 'Message or media required']);
    exit;
}

$clean_msg   = $message !== '' ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';
$clean_media = $media_url !== '' ? $media_url : null;

try {
    // ── 1. Insert message ────────────────────────────────────────────────
    $pdo->prepare("INSERT INTO chat_messages (user_id, message, channel, media_url) VALUES (?,?,?,?)")
        ->execute([$user_id, $clean_msg, $channel, $clean_media]);

    // ── 2. XP — hanya 1x per hari (anti-spam XP) ────────────────────────
    $xp_gained = 0;
    try {
        $chatRow = $pdo->prepare("SELECT last_chat_date, chat_streak FROM users WHERE id=?");
        $chatRow->execute([$user_id]);
        $cr = $chatRow->fetch();

        $last_chat  = $cr['last_chat_date'] ?? null;
        $chat_streak = (int)($cr['chat_streak'] ?? 0);
        $yesterday  = date('Y-m-d', strtotime('-1 day'));

        // Hitung streak
        if ($last_chat === null) {
            // Pertama kali chat
            $chat_streak = 1;
        } elseif ($last_chat === $today) {
            // Sudah chat hari ini — jangan tambah XP, jangan ubah streak
        } elseif ($last_chat === $yesterday) {
            // Chat kemarin → streak naik
            $chat_streak++;
        } else {
            // Lewat lebih dari 1 hari → streak reset ke 1
            $chat_streak = 1;
        }

        // Hanya beri XP jika belum chat hari ini
        if ($last_chat !== $today) {
            $xp_gained = 5;
            add_xp($user_id, $xp_gained);
            $pdo->prepare("UPDATE users SET last_chat_date=?, chat_streak=? WHERE id=?")
                ->execute([$today, $chat_streak, $user_id]);

            // Cek achievement "Paling Rajin" (7 hari berturut chat)
            if ($chat_streak >= 7) {
                award_ach($pdo, $user_id, 'Paling Rajin');
            }
        }

        // Update quest progress chat_messages (tetap berjalan meski no XP)
        $pdo->prepare("
            UPDATE user_quests uq
            JOIN daily_quests dq ON uq.quest_id = dq.id
            SET uq.progress   = uq.progress + 1,
                uq.completed  = IF(uq.progress + 1 >= dq.requirement_value, 1, uq.completed)
            WHERE uq.user_id = ? AND uq.assigned_date = ?
              AND dq.requirement_type = 'chat_messages'
              AND uq.completed = 0
        ")->execute([$user_id, $today]);

    } catch (Exception $e2) {}

    echo json_encode(['success' => true, 'xp_gained' => $xp_gained]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

// ── Helper: award achievement safely (idempotent) ────────────────────────
function award_ach($pdo, $user_id, $name) {
    try {
        $s = $pdo->prepare("SELECT id, xp_reward, coin_reward FROM achievements WHERE name=?");
        $s->execute([$name]);
        $a = $s->fetch();
        if (!$a) return;

        $c = $pdo->prepare("SELECT id FROM user_achievements WHERE user_id=? AND achievement_id=?");
        $c->execute([$user_id, $a['id']]);
        if ($c->fetch()) return; // already unlocked

        $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?,?)")
            ->execute([$user_id, $a['id']]);
        $pdo->prepare("UPDATE users SET xp=xp+?, coins=coins+? WHERE id=?")
            ->execute([$a['xp_reward'], $a['coin_reward'], $user_id]);
    } catch (Exception $e) {}
}
