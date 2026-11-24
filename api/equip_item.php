<?php
// api/equip_item.php
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
$action = $input['action'] ?? $_POST['action'] ?? 'equip'; // 'equip', 'unequip'
$item_id = (int)($input['item_id'] ?? $_POST['item_id'] ?? 0);
$type = $input['type'] ?? $_POST['type'] ?? '';

try {
    if ($action === 'unequip') {
        $unequip_type = $input['unequip_type'] ?? $_POST['unequip_type'] ?? $type;
        if ($unequip_type === 'border') {
            $pdo->prepare("UPDATE users SET frame = NULL, frame_style = NULL, aura = NULL WHERE id = ?")->execute([$user_id]);
        } elseif ($unequip_type === 'nameplate') {
            $pdo->prepare("UPDATE users SET nameplate = NULL, nameplate_style = NULL WHERE id = ?")->execute([$user_id]);
        } elseif ($unequip_type === 'character') {
            $pdo->prepare("UPDATE users SET equipped_character = NULL WHERE id = ?")->execute([$user_id]);
        }
        echo json_encode(['success' => true, 'message' => 'Item berhasil dilepas!']);
        exit;
    }

    if ($action === 'equip' && $item_id) {
        // Verify ownership
        $iStmt = $pdo->prepare("SELECT si.* FROM user_inventory ui JOIN shop_items si ON ui.item_id = si.id WHERE ui.user_id = ? AND ui.item_id = ?");
        $iStmt->execute([$user_id, $item_id]);
        $item = $iStmt->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan atau belum kamu miliki.']);
            exit;
        }

        if ($item['category'] === 'border') {
            $pdo->prepare("UPDATE users SET frame = ?, frame_style = ?, aura = ? WHERE id = ?")
                ->execute([$item['name'], $item['preview_css'], $item['aura_css'], $user_id]);
        } elseif ($item['category'] === 'nameplate') {
            $pdo->prepare("UPDATE users SET nameplate = ?, nameplate_style = ? WHERE id = ?")
                ->execute([$item['name'], $item['preview_css'] . ($item['aura_css'] ? ' ' . $item['aura_css'] : ''), $user_id]);
        } elseif ($item['category'] === 'character') {
            $pdo->prepare("UPDATE users SET equipped_character = ? WHERE id = ?")
                ->execute([$item['id'], $user_id]);
        }

        echo json_encode([
            'success' => true,
            'message' => '✅ ' . htmlspecialchars($item['name']) . ' berhasil dipasang!',
            'item' => $item
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
