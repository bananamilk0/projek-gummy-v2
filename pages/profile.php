<?php
// pages/profile.php
if (session_status() === PHP_SESSION_NONE) session_start();
global $pdo;

$my_user_id = $_SESSION['user_id'] ?? null;
$profile_user_id = isset($_GET['uid']) ? (int)$_GET['uid'] : (int)$my_user_id;
if (!$profile_user_id) { header("Location: ?page=login"); exit; }
$is_owner = ((int)$profile_user_id === (int)$my_user_id);

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$profile_user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        echo "<div class='container py-5 text-center'><h2>User tidak ditemukan!</h2><a href='?page=home' class='btn-primary mt-3'>Kembali</a></div>";
        exit;
    }
} catch (Exception $e) { $user = null; }

// Handle POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_owner) {
    // Apply inventory item (equip)
    if (isset($_POST['equip_item'])) {
        $equip_id = (int)$_POST['equip_item'];
        try {
            $iStmt = $pdo->prepare("SELECT si.* FROM user_inventory ui JOIN shop_items si ON ui.item_id = si.id WHERE ui.user_id = ? AND ui.item_id = ?");
            $iStmt->execute([$my_user_id, $equip_id]);
            $equip_item = $iStmt->fetch();
            if ($equip_item) {
                if ($equip_item['category'] === 'border') {
                    $pdo->prepare("UPDATE users SET frame = ?, frame_style = ?, aura = ? WHERE id = ?")
                        ->execute([$equip_item['name'], $equip_item['preview_css'], $equip_item['aura_css'], $my_user_id]);
                } elseif ($equip_item['category'] === 'nameplate') {
                    $pdo->prepare("UPDATE users SET nameplate = ?, nameplate_style = ? WHERE id = ?")
                        ->execute([$equip_item['name'], $equip_item['preview_css'] . ($equip_item['aura_css'] ? ' ' . $equip_item['aura_css'] : ''), $my_user_id]);
                }
            }
        } catch (Exception $e) {}
        header("Location: ?page=profile"); exit;
    }

    // Unequip item
    if (isset($_POST['unequip_type'])) {
        $utype = $_POST['unequip_type'];
        try {
            if ($utype === 'border') {
                $pdo->prepare("UPDATE users SET frame = NULL, frame_style = NULL, aura = NULL WHERE id = ?")->execute([$my_user_id]);
            } elseif ($utype === 'nameplate') {
                $pdo->prepare("UPDATE users SET nameplate = NULL, nameplate_style = NULL WHERE id = ?")->execute([$my_user_id]);
            }
        } catch (Exception $e) {}
        header("Location: ?page=profile"); exit;
    }

    $bio = trim($_POST['bio'] ?? '');
    $cropped_avatar = $_POST['cropped_avatar'] ?? '';
    $cropped_bg = $_POST['cropped_bg'] ?? '';

    try {
        $new_username = trim($_POST['new_username'] ?? '');

        // Update bio
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
        $stmt->execute([$bio, $my_user_id]);

        // Update username if changed and valid
        if ($new_username && strlen($new_username) >= 3 && strlen($new_username) <= 30) {
            // Check uniqueness
            $uniqChk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $uniqChk->execute([$new_username, $my_user_id]);
            if ((int)$uniqChk->fetchColumn() === 0) {
                $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$new_username, $my_user_id]);
                $_SESSION['username'] = $new_username;
            }
        }

        if (!empty($cropped_avatar) && strpos($cropped_avatar, 'data:image') === 0) {
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $cropped_avatar));
            $ext = (strpos($cropped_avatar, 'data:image/jpeg') !== false) ? 'jpg' : 'png';
            $newFileName = md5(time() . $my_user_id . 'av') . '.' . $ext;
            file_put_contents($uploadDir . $newFileName, $imgData);
            $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$newFileName, $my_user_id]);
            $_SESSION['avatar'] = $newFileName;
        }

        if (!empty($cropped_bg) && strpos($cropped_bg, 'data:image') === 0) {
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $cropped_bg));
            $ext = (strpos($cropped_bg, 'data:image/jpeg') !== false) ? 'jpg' : 'png';
            $bgFileName = 'bg_' . md5(time() . $my_user_id) . '.' . $ext;
            file_put_contents($uploadDir . $bgFileName, $imgData);
            $pdo->prepare("UPDATE users SET bg_image = ? WHERE id = ?")->execute(['uploads/avatars/' . $bgFileName, $my_user_id]);
        }

        header("Location: ?page=profile"); exit;
    } catch (Exception $e) { $error_msg = "Gagal memperbarui profil."; }
}

// Fetch ALL unlocked achievements for this user
try {
    $stmt = $pdo->prepare("SELECT a.id, a.name, a.description, a.icon, ua.unlocked_at FROM achievements a JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ? ORDER BY ua.unlocked_at DESC");
    $stmt->execute([$profile_user_id]);
    $achievements = $stmt->fetchAll();
    $total_achievements = (int)$pdo->query("SELECT COUNT(*) FROM achievements")->fetchColumn();
} catch (Exception $e) { $achievements = []; $total_achievements = 0; }

// Fetch user inventory (if owner) — HANYA border dan nameplate
$inventory_borders    = [];
$inventory_nameplates = [];
if ($is_owner) {
    try {
        $invStmt = $pdo->prepare("SELECT si.* FROM user_inventory ui JOIN shop_items si ON ui.item_id = si.id WHERE ui.user_id = ? AND si.category IN ('border','nameplate') ORDER BY FIELD(si.category,'border','nameplate'), FIELD(si.rarity,'common','rare','epic','legendary'), si.price ASC");
        $invStmt->execute([$my_user_id]);
        foreach ($invStmt->fetchAll() as $inv) {
            if ($inv['category'] === 'border')      $inventory_borders[]    = $inv;
            elseif ($inv['category'] === 'nameplate') $inventory_nameplates[] = $inv;
        }
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/level_system.php';
$xp = $user['xp'] ?? 0;
// Always recalculate level dynamically for accuracy
$level = calculate_level($xp);
$coins = $user['coins'] ?? 0;
$current_level_xp = get_xp_for_level($level);
$next_level_xp = get_xp_for_level($level + 1);
$xp_progress = $xp - $current_level_xp;
$xp_needed = $next_level_xp - $current_level_xp;
$percent = min(100, max(0, ($xp_progress / ($xp_needed ?: 1)) * 100));

// Determine active border and nameplate
$active_border_css = $user['frame_style'] ?? null;
$active_np_css     = $user['nameplate_style'] ?? null;

// Refresh session level/xp/coins from DB (penting agar profile selalu sinkron)
if ($is_owner) {
    $_SESSION['level']  = $level;
    $_SESSION['xp']     = $xp;
    $_SESSION['coins']  = $coins;
}

require_once __DIR__ . '/../includes/header.php';
?>


<!-- Cropper.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<div class="profile-page-wrapper" style="max-width:920px; margin:0 auto; padding:0 0 60px;">

    <!-- Profile Card -->
    <div class="profile-card-wrap glass-panel" style="overflow:hidden; position:relative; border:1px solid var(--glass-border);">
        <!-- Banner (Taller Space) -->
        <?php
            $bgStyle = '';
            $bgVal = $user['bg_image'] ?? '';
            if ($bgVal && !str_starts_with($bgVal, 'rgba') && !str_starts_with($bgVal, 'linear-gradient')) {
                $bgStyle = "background-image:url('" . htmlspecialchars($bgVal) . "'); background-size:cover; background-position:center;";
            } else {
                $bgStyle = "background:" . ($bgVal ?: 'linear-gradient(135deg, #122416 0%, #0a130b 100%)') . ";";
            }
        ?>
        <div class="profile-banner" style="height:340px; width:100%; position:relative; <?= $bgStyle ?>">
        </div>

        <div style="padding:0 30px 30px; position:relative; z-index:1;">
            <!-- Avatar row -->
            <div style="display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:15px; margin-top:-60px; padding-bottom:8px;">
                <?php
                    $pFrameName  = $user['frame'] ?? '';
                    $pAuraImage  = $user['aura'] ?? '';
                ?>
                <div class="avatar-cosmetic-effect" style="position:relative; display:inline-block; width:110px; height:110px;">
                    <div class="equipped-border" style="width:110px; height:110px; border-radius:50%; background:#0f111a; overflow:hidden; display:flex; align-items:center; justify-content:center; position:relative; <?= $active_border_css ? htmlspecialchars($active_border_css) : 'border:3px solid var(--primary); box-shadow:0 0 10px var(--primary-glow);' ?>">
                        <img id="profile-avatar-display" src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?: 'default.png') ?>" style="width:100%; height:100%; object-fit:cover; border-radius:inherit; display:block;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&size=110&background=random'">
                    </div>
                    <?php if ($pAuraImage && strpos($pAuraImage, 'assets/images/borders/') !== false): ?>
                        <div class="png-border-overlay" style="background-image: url('<?= htmlspecialchars($pAuraImage) ?>');"></div>
                    <?php endif; ?>
                    <div style="position:absolute; bottom:6px; right:3px; width:18px; height:18px; border-radius:50%; background:#00ff88; border:3px solid #0f111a; box-shadow:0 0 8px #00ff88; z-index:4;"></div>
                    <?php if($is_owner): ?>
                        <button onclick="openCropper('avatar')" style="position:absolute; inset:0; border-radius:50%; background:rgba(0,0,0,0.5); border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s; z-index:5;" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=0" title="Ganti Foto">
                            <i data-lucide="camera" style="width:22px;height:22px;"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div style="padding-bottom:10px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:35px;">
                    <?php if($is_owner): ?>
                        <button id="btn-edit-profile" class="btn-primary" style="padding:9px 20px; font-size:0.875rem; display:inline-flex; align-items:center; gap:8px;">
                            <i data-lucide="edit-3" style="width:16px;height:16px;"></i> Edit Profil
                        </button>
                    <?php else: ?>
                        <span style="background:rgba(255,255,255,0.05); border:1px solid var(--glass-border); padding:8px 18px; border-radius:30px; font-size:0.8rem; color:#ccc;">Melihat Profil</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Name with extra spacing to prevent overlapping -->
            <div style="margin-top:38px;">
                <h1 style="font-size:1.4rem; color:#fff; margin:0 0 4px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <?php if($user['nameplate'] && $active_np_css): ?>
                        <span class="nameplate-preview <?= stripos($user['nameplate'], 'Divine Crown') !== false ? 'divine-crown-wrap' : '' ?>" style="display:inline-block; padding:3px 12px; font-size:1rem; text-shadow:none; <?= htmlspecialchars($active_np_css) ?>"><?= htmlspecialchars($user['username']) ?></span>
                    <?php else: ?>
                        <?= htmlspecialchars($user['username']) ?>
                    <?php endif; ?>
                </h1>
                <p style="color:var(--text-muted); font-size:0.83rem; margin:0; display:flex; align-items:center; gap:6px;">
                    <i data-lucide="calendar" style="width:13px;height:13px;"></i>
                    Bergabung <?= date('d M Y', strtotime($user['created_at'])) ?>
                </p>
            </div>

            <!-- Bio -->
            <div style="margin-top:20px; background:rgba(0,0,0,0.25); border:1px solid rgba(255,255,255,0.04); border-radius:12px; padding:16px 20px;">
                <p style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); margin:0 0 8px; font-weight:700;">Tentang Saya</p>
                <p style="color:#ddd; font-size:0.88rem; line-height:1.65; margin:0;"><?= nl2br(htmlspecialchars($user['bio'] ?: 'Tidak ada bio.')) ?></p>
            </div>

            <!-- Stats -->
            <div class="profile-stats-grid" style="margin-top:16px; display:grid; grid-template-columns:auto 1fr auto; gap:20px; align-items:center; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:16px 20px;">
                <div>
                    <p style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); margin:0 0 3px; letter-spacing:1px;">Level</p>
                    <strong style="color:var(--primary); font-size:1.5rem;">Lv. <?= $level ?></strong>
                </div>
                <div>
                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-muted); margin-bottom:6px;">
                        <span>XP Progress</span>
                        <span style="color:#fff;"><?= number_format($xp_progress) ?> / <?= number_format($xp_needed) ?></span>
                    </div>
                    <div style="height:8px; background:rgba(0,0,0,0.5); border-radius:4px; overflow:hidden;">
                        <div style="width:<?= $percent ?>%; height:100%; background:linear-gradient(90deg, var(--secondary), var(--primary)); border-radius:4px; transition:width 1s ease;"></div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <p style="font-size:0.7rem; text-transform:uppercase; color:var(--text-muted); margin:0 0 3px; letter-spacing:1px;">Koin</p>
                    <strong style="color:#ffd700; font-size:1.4rem; display:flex; align-items:center; gap:5px; justify-content:flex-end;">
                        <i data-lucide="coins" style="width:18px;height:18px;"></i> <?= number_format($coins) ?>
                    </strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Achievements -->
    <div class="glass-panel" style="padding:28px; margin-top:20px; border:1px solid var(--glass-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h3 style="margin:0 0 3px; color:#fff; font-size:1.15rem;">🏆 Pencapaian</h3>
                <p style="margin:0; color:var(--text-muted); font-size:0.78rem;"><?= count($achievements) ?> dari <?= $total_achievements ?> pencapaian telah dicapai</p>
            </div>
            <a href="?page=achievements" class="btn-secondary" style="padding:8px 16px; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px; white-space:nowrap;">
                Lihat Semua <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
            </a>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:12px;">
            <?php if (empty($achievements)): ?>
                <div style="color:var(--text-muted); font-size:0.85rem; padding:20px 0; grid-column:1/-1; text-align:center;">Belum ada pencapaian yang diraih.</div>
            <?php else: foreach($achievements as $ach): ?>
                <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; background:rgba(0,229,255,0.04); border:1px solid rgba(0,229,255,0.15); border-radius:12px;">
                    <div style="font-size:1.8rem;"><?= $ach['icon'] ?></div>
                    <div style="flex:1; min-width:0;">
                        <p style="margin:0; color:#fff; font-weight:600; font-size:0.875rem;"><?= htmlspecialchars($ach['name']) ?></p>
                        <p style="margin:4px 0 0; color:var(--text-muted); font-size:0.76rem; line-height:1.4;"><?= htmlspecialchars($ach['description']) ?></p>
                    </div>
                    <div style="font-size:0.68rem; color:rgba(0,229,255,0.6); white-space:nowrap; flex-shrink:0;"><?= date('d M Y', strtotime($ach['unlocked_at'])) ?></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <?php if ($is_owner && (!empty($inventory_borders) || !empty($inventory_nameplates))): ?>
    <!-- Inventory Section -->
    <div class="glass-panel" style="padding:28px; margin-top:20px; border:1px solid var(--glass-border);">
        <h3 style="margin:0 0 20px; color:#fff; font-size:1.15rem;">🎒 Inventory — Item Kamu</h3>

        <!-- BORDERS -->
        <?php if (!empty($inventory_borders)): ?>
        <div style="margin-bottom:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <p style="margin:0; font-size:0.78rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); font-weight:700;">🔲 Border Avatar</p>
                <?php if ($user['frame']): ?>
                    <button type="button" onclick="ajaxEquip({action:'unequip', type:'border'})" style="background:transparent; border:1px solid rgba(255,59,48,0.3); color:#ff8080; font-size:0.75rem; padding:4px 10px; border-radius:6px; cursor:pointer;">Lepas Border</button>
                <?php endif; ?>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                <?php foreach ($inventory_borders as $inv):
                    $isEquipped = ($user['frame'] === $inv['name']);
                    $invImg     = $inv['aura_css'] ?? '';
                ?>
                <div style="padding:14px; background:rgba(255,255,255,0.03); border:1px solid <?= $isEquipped ? 'rgba(0,255,136,0.4)' : 'var(--glass-border)' ?>; border-radius:12px; text-align:center; position:relative;">
                    <div style="width:52px; height:52px; border-radius:50%; margin:0 auto 10px; background:rgba(20,20,40,0.8); <?= htmlspecialchars($inv['preview_css'] ?? '') ?>; display:flex; align-items:center; justify-content:center; font-size:1.2rem; overflow:visible; position:relative;">
                        👤
                        <?php if ($invImg && strpos($invImg, 'assets/images/borders/') !== false): ?>
                            <div class="png-border-overlay" style="inset:-8px; background-image: url('<?= htmlspecialchars($invImg) ?>');"></div>
                        <?php endif; ?>
                    </div>
                    <p style="margin:0 0 6px; color:#fff; font-size:0.78rem; font-weight:600; line-height:1.3;"><?= htmlspecialchars($inv['name']) ?></p>
                    
                    <?php if ($isEquipped): ?>
                        <span style="color:#00ff88; font-size:0.72rem; font-weight:700; display:block;">✓ Dipakai</span>
                    <?php else: ?>
                        <button type="button" onclick="ajaxEquip({action:'equip', item_id:<?= $inv['id'] ?>})" style="background:rgba(138,43,226,0.2); border:1px solid rgba(138,43,226,0.4); color:#c080ff; font-size:0.74rem; padding:5px 10px; border-radius:8px; cursor:pointer; width:100%;">Pakai</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- NAMEPLATES -->
        <?php if (!empty($inventory_nameplates)): ?>
        <div style="margin-bottom:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                <p style="margin:0; font-size:0.78rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); font-weight:700;">🏷️ Nameplate</p>
                <?php if ($user['nameplate']): ?>
                    <button type="button" onclick="ajaxEquip({action:'unequip', type:'nameplate'})" style="background:transparent; border:1px solid rgba(255,59,48,0.3); color:#ff8080; font-size:0.75rem; padding:4px 10px; border-radius:6px; cursor:pointer;">Lepas Nameplate</button>
                <?php endif; ?>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:12px;">
                <?php foreach ($inventory_nameplates as $inv):
                    $isEquipped = ($user['nameplate'] === $inv['name']);
                ?>
                <div style="padding:14px; background:rgba(255,255,255,0.03); border:1px solid <?= $isEquipped ? 'rgba(0,255,136,0.4)' : 'var(--glass-border)' ?>; border-radius:12px; text-align:center;">
                    <div style="display:inline-block; padding:5px 14px; border-radius:30px; font-size:0.78rem; font-weight:700; margin-bottom:10px; <?= htmlspecialchars($inv['preview_css'] ?? '') ?>"><?= htmlspecialchars($inv['name']) ?></div>
                    <p style="margin:0 0 8px; color:#fff; font-size:0.78rem; font-weight:600;"><?= htmlspecialchars($inv['name']) ?></p>
                    <?php if ($isEquipped): ?>
                        <span style="color:#00ff88; font-size:0.72rem; font-weight:700; display:block;">✓ Dipakai</span>
                    <?php else: ?>
                        <button type="button" onclick="ajaxEquip({action:'equip', item_id:<?= $inv['id'] ?>})" style="background:rgba(138,43,226,0.2); border:1px solid rgba(138,43,226,0.4); color:#c080ff; font-size:0.74rem; padding:5px 10px; border-radius:8px; cursor:pointer; width:100%;">Pakai</button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>


    <!-- JS script for AJAX equip/unequip & custom color (Item 16) -->
    <script>
    function ajaxEquip(payload) {
        fetch('api/equip_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Reload smoothly or update page without scroll jump
                window.location.reload();
            } else {
                alert(data.message || 'Gagal mengubah item.');
            }
        })
        .catch(err => console.error(err));
    }


    </script>
</div>

<?php if ($is_owner): ?>
<div id="cropper-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:3000; align-items:center; justify-content:center; backdrop-filter:blur(10px);">
    <div style="background:#13151f; border:1px solid var(--glass-border); border-radius:20px; padding:28px; width:100%; max-width:520px; margin:15px; box-shadow:0 20px 60px rgba(0,0,0,0.9);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 id="cropper-modal-title" style="margin:0; color:#fff; font-size:1.1rem;">Atur Foto Profil</h3>
            <button onclick="closeCropper()" style="background:rgba(255,255,255,0.08); border:none; color:#fff; width:34px; height:34px; border-radius:50%; cursor:pointer; font-size:1rem;">✕</button>
        </div>
        <div style="position:relative; width:100%; background:#000; border-radius:12px; overflow:hidden;" id="cropper-container">
            <img id="cropper-image" src="" style="display:block; max-width:100%;">
        </div>
        <div style="display:flex; gap:10px; margin-top:16px;">
            <label style="flex:1; display:flex; align-items:center; justify-content:center; gap:8px; padding:11px; background:rgba(255,255,255,0.06); border:1px solid var(--glass-border); border-radius:10px; cursor:pointer; font-size:0.85rem; color:#fff;">
                <i data-lucide="upload" style="width:15px;height:15px;"></i> Pilih Gambar
                <input type="file" id="cropper-file-input" accept="image/*" style="display:none;" onchange="loadImageToCropper(this)">
            </label>
            <button onclick="applyCrop()" class="btn-primary" style="flex:1; padding:11px; font-size:0.875rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i data-lucide="check" style="width:15px;height:15px;"></i> Terapkan
            </button>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="edit-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:2500; align-items:center; justify-content:center; backdrop-filter:blur(8px);">
    <div style="background:#13151f; border:1px solid var(--glass-border); border-radius:20px; width:100%; max-width:500px; margin:15px; box-shadow:0 20px 60px rgba(0,0,0,0.9); overflow:hidden;">
        <!-- Modal Header -->
        <div style="padding:24px 28px 0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h3 style="margin:0; color:#fff; font-size:1.15rem; font-weight:700;">Edit Profil</h3>
                <button id="btn-close-modal" style="background:rgba(255,255,255,0.08); border:none; color:#fff; width:34px; height:34px; border-radius:50%; cursor:pointer; font-size:1rem;">✕</button>
            </div>
        </div>

        <!-- Preview current avatar + bg -->
        <div style="position:relative; margin:0 0 0;">
            <div onclick="openCropper('bg')" style="height:90px; cursor:pointer; <?= (!empty($bgVal) && !str_starts_with($bgVal, 'rgba') && !str_starts_with($bgVal, 'linear')) ? "background-image:url('".htmlspecialchars($bgVal)."'); background-size:cover; background-position:center;" : "background:linear-gradient(135deg,#1a0a2e,#0a1a35);" ?>; position:relative;">
                <div style="position:absolute; inset:0; background:rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; opacity:0; transition:opacity 0.2s;" onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=0">
                </div>
            </div>
            <div style="position:absolute; bottom:-35px; left:24px; width:70px; height:70px; border-radius:50%; border:3px solid #13151f; overflow:hidden; box-shadow:0 0 0 2px var(--primary);">
                <img id="modal-avatar-preview" src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?: 'default.png') ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>&size=70&background=random'">
            </div>
        </div>

        <form id="edit-profile-form" method="POST" style="padding:50px 28px 28px; display:flex; flex-direction:column; gap:18px;">
            <input type="hidden" name="cropped_avatar" id="input-cropped-avatar">
            <input type="hidden" name="cropped_bg" id="input-cropped-bg">

            <!-- Avatar & BG buttons -->
            <div style="display:flex; gap:10px;">
                <button type="button" onclick="openCropper('avatar')" style="flex:1; background:rgba(255,255,255,0.06); border:1px solid var(--glass-border); color:#fff; padding:10px; border-radius:10px; cursor:pointer; font-size:0.82rem; display:flex; align-items:center; justify-content:center; gap:7px;">
                    <i data-lucide="user-circle" style="width:15px;height:15px;"></i> Ganti Foto Profil
                </button>
                <button type="button" onclick="openCropper('bg')" style="flex:1; background:rgba(255,255,255,0.06); border:1px solid var(--glass-border); color:#fff; padding:10px; border-radius:10px; cursor:pointer; font-size:0.82rem; display:flex; align-items:center; justify-content:center; gap:7px;">
                    <i data-lucide="image" style="width:15px;height:15px;"></i> Ganti Background
                </button>
            </div>

            <div>
                <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:8px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase;">Bio / Tentang Saya</label>
                <textarea name="bio" rows="4" style="width:100%; padding:13px 14px; background:rgba(0,0,0,0.4); border:1px solid var(--glass-border); border-radius:10px; color:#fff; resize:none; font-family:var(--font-body); font-size:0.88rem; line-height:1.6; outline:none; transition:border-color 0.2s;" placeholder="Ceritakan sedikit tentang dirimu..." onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--glass-border)'"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>

            <!-- Username -->
            <div>
                <label style="display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:8px; font-weight:600; letter-spacing:0.5px; text-transform:uppercase;">
                    Username <span style="font-weight:400; text-transform:none; letter-spacing:0; color:rgba(160,165,180,0.6);">(kosongkan jika tidak ingin mengubah)</span>
                </label>
                <input type="text" name="new_username" value="" placeholder="<?= htmlspecialchars($user['username']) ?>"
                    style="width:100%; padding:13px 14px; background:rgba(0,0,0,0.4); border:1px solid var(--glass-border); border-radius:10px; color:#fff; outline:none; font-size:0.88rem; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--glass-border)'" minlength="3" maxlength="30">
            </div>

            <!-- Crop preview notif -->
            <div id="crop-applied-notice" style="display:none; background:rgba(0,229,255,0.1); border:1px solid rgba(0,229,255,0.2); border-radius:8px; padding:10px 14px; font-size:0.82rem; color:var(--secondary); display:flex; align-items:center; gap:8px;">
                <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                <span id="crop-applied-text">Foto berhasil di-crop!</span>
            </div>

            <button type="submit" class="btn-primary" style="padding:14px; font-size:0.9rem; width:100%; display:flex; align-items:center; justify-content:center; gap:8px; margin-top:4px;">
                <i data-lucide="save" style="width:16px;height:16px;"></i> Simpan Perubahan
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const editBtn = document.getElementById("btn-edit-profile");
    const closeBtn = document.getElementById("btn-close-modal");
    const modal = document.getElementById("edit-modal");

    if (editBtn && modal) {
        editBtn.addEventListener("click", () => { modal.style.display = "flex"; });
        if(closeBtn) closeBtn.addEventListener("click", () => { modal.style.display = "none"; });
        modal.addEventListener("click", (e) => { if (e.target === modal) modal.style.display = "none"; });
    }
});

// ===== CROPPER =====
let cropperInstance = null;
let cropMode = 'avatar'; // 'avatar' or 'bg'

window.openCropper = function(mode) {
    cropMode = mode;
    const title = mode === 'avatar' ? 'Atur Foto Profil' : 'Atur Background Profil';
    document.getElementById('cropper-modal-title').textContent = title;
    document.getElementById('cropper-modal').style.display = 'flex';
    // Trigger file input
    document.getElementById('cropper-file-input').click();
    if (typeof lucide !== 'undefined') lucide.createIcons();
};

window.closeCropper = function() {
    document.getElementById('cropper-modal').style.display = 'none';
    if (cropperInstance) { cropperInstance.destroy(); cropperInstance = null; }
};

window.loadImageToCropper = function(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        const img = document.getElementById('cropper-image');
        img.src = e.target.result;
        img.style.height = cropMode === 'bg' ? '200px' : '300px';

        if (cropperInstance) { cropperInstance.destroy(); }
        const aspectRatio = cropMode === 'bg' ? 16/5 : 1;
        cropperInstance = new Cropper(img, {
            aspectRatio: aspectRatio,
            viewMode: 1,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            rotatable: false,
        });
    };
    reader.readAsDataURL(input.files[0]);
};

window.applyCrop = function() {
    if (!cropperInstance) {
        alert("Pilih gambar terlebih dahulu.");
        return;
    }
    const outputSize = cropMode === 'bg' ? { width: 900, height: 281 } : { width: 300, height: 300 };
    const canvas = cropperInstance.getCroppedCanvas(outputSize);
    const dataUrl = canvas.toDataURL('image/png');

    if (cropMode === 'avatar') {
        document.getElementById('input-cropped-avatar').value = dataUrl;
        document.getElementById('profile-avatar-display').src = dataUrl;
        const modalPrev = document.getElementById('modal-avatar-preview');
        if (modalPrev) modalPrev.src = dataUrl;
        showCropNotice('Foto profil berhasil di-crop!');
    } else {
        document.getElementById('input-cropped-bg').value = dataUrl;
        showCropNotice('Background berhasil di-crop!');
    }

    closeCropper();
    // Open edit modal after cropping
    const editModal = document.getElementById('edit-modal');
    if (editModal) editModal.style.display = 'flex';
};

function showCropNotice(msg) {
    const notice = document.getElementById('crop-applied-notice');
    const text = document.getElementById('crop-applied-text');
    if (notice && text) {
        text.textContent = msg;
        notice.style.display = 'flex';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        setTimeout(() => { notice.style.display = 'none'; }, 4000);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
