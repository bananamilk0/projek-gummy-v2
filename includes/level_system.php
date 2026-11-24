<?php
// includes/level_system.php

function calculate_level($xp) {
    // Formula: Level = floor(sqrt(XP / 100)) + 1
    return floor(sqrt($xp / 100)) + 1;
}

function get_xp_for_level($level) {
    // Inverse formula: XP = (Level - 1)^2 * 100
    return pow($level - 1, 2) * 100;
}

function add_xp($user_id, $amount) {
    global $pdo;
    
    // Fetch current user xp
    $stmt = $pdo->prepare("SELECT xp, level, coins FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) return false;
    
    $new_xp = $user['xp'] + $amount;
    $new_level = calculate_level($new_xp);
    $coins_gained = 0;
    
    if ($new_level > $user['level']) {
        // Level up bonus (e.g. 50 coins per level)
        $coins_gained = ($new_level - $user['level']) * 50;
    }
    
    $new_coins = $user['coins'] + $coins_gained;
    
    // Update DB
    $updateStmt = $pdo->prepare("UPDATE users SET xp = ?, level = ?, coins = ? WHERE id = ?");
    $updateStmt->execute([$new_xp, $new_level, $new_coins, $user_id]);

    // Special level milestones.  INSERT IGNORE keeps this safe when XP is granted repeatedly.
    try {
        foreach ([50 => 'Novice I', 250 => 'Veteran II', 1000 => 'Legend III'] as $needed => $achievement) {
            if ($new_level >= $needed) {
                $unlock = $pdo->prepare("INSERT IGNORE INTO user_achievements (user_id,achievement_id) SELECT ?,id FROM achievements WHERE name=? LIMIT 1");
                $unlock->execute([$user_id, $achievement]);
            }
        }
    } catch (Exception $e) { /* Achievements may not be installed yet. */ }
    
    // Update Session if it's the current user
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        $_SESSION['xp'] = $new_xp;
        $_SESSION['level'] = $new_level;
        $_SESSION['coins'] = $new_coins;
    }
    
    return [
        'xp' => $new_xp,
        'level' => $new_level,
        'coins' => $new_coins,
        'leveled_up' => $new_level > $user['level']
    ];
}
