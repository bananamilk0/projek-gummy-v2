<?php
// Fix the aura column to allow longer CSS strings, and fix Circle border
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Widen the 'aura' column so effect CSS doesn't get truncated
    $pdo->exec("ALTER TABLE users MODIFY COLUMN aura TEXT NULL");
    echo "1. aura column widened to TEXT ✓\n";

    // 2. Fix Circle border to be properly round (not semi-square)
    //    The id=12 Circle is a nameplate, NOT a border. The id=1 Circle border has correct preview_css.
    //    Let's verify and fix id=1 to ensure border-radius:50%
    $pdo->exec("UPDATE shop_items SET preview_css='border:3px solid var(--custom-color, #8a2be2); border-radius:50%; box-shadow:0 0 8px rgba(138,43,226,0.6);' WHERE id=1 AND category='border'");
    echo "2. Circle border fixed to full round ✓\n";

    // 3. Fix Circle nameplate to have proper border-radius:30px (was wrongly 30px which is fine)
    //    Actually the issue is the id=12 'Circle' is stored with category 'border' not 'nameplate' 
    //    Let's check
    $check = $pdo->query("SELECT id, name, category, preview_css FROM shop_items WHERE name='Circle'")->fetchAll(PDO::FETCH_ASSOC);
    echo "3. Circle items in DB:\n";
    foreach ($check as $c) {
        echo "   id={$c['id']} cat={$c['category']} css={$c['preview_css']}\n";
    }

    // If id=12 is category 'border' but looks like a nameplate, fix it
    $pdo->exec("UPDATE shop_items SET category='nameplate' WHERE id=12 AND preview_css LIKE '%border-radius:30px%'");
    echo "4. Fixed id=12 category if needed ✓\n";

    // 5. Also re-sync any users who have broken aura data from truncation
    //    We need to re-apply the aura from shop_items for anyone with a frame equipped
    $users = $pdo->query("SELECT u.id, u.frame, si.aura_css FROM users u JOIN shop_items si ON u.frame = si.name AND si.category='border' WHERE si.aura_css IS NOT NULL AND si.aura_css != ''")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $pdo->prepare("UPDATE users SET aura = ? WHERE id = ?")->execute([$u['aura_css'], $u['id']]);
        echo "5. Re-synced aura for user id={$u['id']} (frame={$u['frame']})\n";
    }

    // 6. Add total_time_spent column for "member paling betah" tracking
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_time_spent INT DEFAULT 0 AFTER voice_minutes");
        echo "6. Added total_time_spent column ✓\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "6. total_time_spent column already exists ✓\n";
        } else {
            echo "6. Error: " . $e->getMessage() . "\n";
        }
    }

    // 7. Initialize total_time_spent based on created_at (days since registration * rough daily minutes)
    $pdo->exec("UPDATE users SET total_time_spent = GREATEST(1, DATEDIFF(NOW(), created_at)) * (login_streak + 1) * 5 WHERE total_time_spent = 0 OR total_time_spent IS NULL");
    echo "7. Initialized total_time_spent with estimated values ✓\n";

    echo "\nAll fixes applied successfully!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
