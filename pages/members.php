<?php
require_once __DIR__ . '/../includes/header.php';

try {
    $stmt = $pdo->query("SELECT id, username, level, xp, coins, avatar, role, frame, frame_style, aura, name_color, frame_color FROM users ORDER BY level DESC, xp DESC");
    $members = $stmt->fetchAll();
} catch (Exception $e) { $members = []; }
?>

<style>
@keyframes aura-pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    justify-content: center;
    margin-top: 24px;
}
.member-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    min-height: 170px;
    aspect-ratio: 1 / 1;
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    background: rgba(255,255,255,0.02);
    padding: 14px 10px 10px;
    text-decoration: none;
    color: #fff;
    transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
    overflow: hidden;
    position: relative;
    box-sizing: border-box;
}
.member-card:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
    box-shadow: 0 8px 24px var(--primary-glow);
}
</style>

<div style="max-width:1200px; margin:0 auto; padding:0 0 60px;">
    <div style="text-align:center; margin-bottom:40px;">
        <h1 style="font-size:2.4rem; font-weight:800; color:#fff; text-shadow:0 0 18px var(--primary);">Members</h1>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Temui para gamer, moderator, dan administrator komunitas Gummy.</p>
    </div>

    <div class="members-grid">
        <?php foreach ($members as $m):
            $color      = $m['name_color'] ?: '#e8f5e9';
            $frameStyle = $m['frame_style'] ?? null;
            $frameName  = $m['frame'] ?? '';

            $mAuraImg  = $m['aura'] ?? '';
            // Role label + color
            $roleLabel = 'MEMBER';
            $roleColor = 'rgba(130,201,154,0.6)';
            $roleBg    = 'rgba(90,158,111,0.12)';
            $roleBdr   = 'rgba(90,158,111,0.3)';
            if ($m['role'] === 'admin') {
                $roleLabel = 'ADMIN'; $roleColor='#ff6b9d'; $roleBg='rgba(255,107,157,0.12)'; $roleBdr='rgba(255,107,157,0.3)';
            } elseif ($m['role'] === 'moderator') {
                $roleLabel = 'MOD'; $roleColor='#82c99a'; $roleBg='rgba(130,201,154,0.12)'; $roleBdr='rgba(130,201,154,0.3)';
            }
        ?>
        <a href="?page=profile&uid=<?= $m['id'] ?>" class="member-card">
            <!-- Avatar with border & spacing -->
            <div class="avatar-cosmetic-effect" style="position:relative; width:64px; height:64px; flex-shrink:0; margin-bottom:12px;">
                <div class="equipped-border" style="width:64px; height:64px; border-radius:50%; overflow:hidden; <?= $frameStyle ? htmlspecialchars($frameStyle) : 'border:2px solid rgba(90,158,111,0.3);' ?>">
                    <img src="uploads/avatars/<?= htmlspecialchars($m['avatar'] ?? 'default.png') ?>"
                         style="width:100%; height:100%; object-fit:cover; display:block;"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($m['username']) ?>&size=64&background=random'">
                </div>
                <?php if ($mAuraImg && strpos($mAuraImg, 'assets/images/borders/') !== false): ?>
                    <div class="png-border-overlay" style="inset:-9px; background-image: url('<?= htmlspecialchars($mAuraImg) ?>');"></div>
                <?php endif; ?>
                <span style="position:absolute; bottom:1px; right:1px; width:10px; height:10px; border-radius:50%; background:#00ff88; border:2px solid #0f111a; box-shadow:0 0 6px #00ff88; z-index:6;"></span>
            </div>

            <!-- Username with spacing -->
            <p style="margin:2px 0 4px; font-size:0.86rem; font-weight:700; color:<?= $color ?>; text-align:center; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; line-height:1.2;"><?= htmlspecialchars($m['username']) ?></p>

            <!-- Role + Level inline -->
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:auto;">
                <span style="background:<?= $roleBg ?>; color:<?= $roleColor ?>; border:1px solid <?= $roleBdr ?>; font-size:0.6rem; font-weight:800; padding:2px 6px; border-radius:5px; letter-spacing:0.5px;"><?= $roleLabel ?></span>
                <span style="font-size:0.68rem; color:var(--text-muted); font-weight:600; opacity:0.5;">•</span>
                <span style="font-size:0.68rem; color:var(--text-muted); font-weight:600;">Lv.<?= (int)$m['level'] ?></span>
            </div>

            <!-- Coins at bottom -->
            <div style="display:flex; align-items:center; gap:5px; margin-top:auto; padding-top:6px; border-top:1px solid rgba(255,255,255,0.06); width:100%; justify-content:center;">
                <i data-lucide="coins" style="width:12px; height:12px; color:#ffd700;"></i>
                <span style="font-size:0.75rem; color:#ffd700; font-weight:700;"><?= number_format((int)$m['coins']) ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
