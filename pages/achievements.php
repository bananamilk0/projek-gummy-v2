<?php
// pages/achievements.php
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;

// Fetch all achievements + unlock status
try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.name, a.description, a.icon, a.xp_reward, a.coin_reward, a.category,
               (ua.id IS NOT NULL) as unlocked, ua.unlocked_at
        FROM achievements a
        LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
        ORDER BY unlocked DESC, a.category ASC, a.id ASC
    ");
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll();
} catch (Exception $e) {
    $achievements = [];
}

// Fetch login streak & voice hours for progress display
$login_streak = 0; $voice_hours = 0; $chat_streak = 0;
if ($user_id) {
    try {
        $ps = $pdo->prepare("SELECT login_streak, voice_minutes, chat_streak FROM users WHERE id=?");
        $ps->execute([$user_id]);
        $pd = $ps->fetch();
        if ($pd) {
            $login_streak = (int)$pd['login_streak'];
            $voice_hours  = round((int)$pd['voice_minutes'] / 60, 1);
            $chat_streak  = (int)$pd['chat_streak'];
        }
    } catch (Exception $e) {}
}

$unlocked_count = 0;
foreach ($achievements as $a) { if ($a['unlocked']) $unlocked_count++; }
$total_count = count($achievements);
$progress_percent = $total_count > 0 ? floor(($unlocked_count / $total_count) * 100) : 0;

// Category grouping
$cat_labels = [
    'social'  => ['label' => '👥 Sosial',       'color' => '#00e5ff'],
    'streak'  => ['label' => '🔥 Login Streak',  'color' => '#ff6b35'],
    'special' => ['label' => '✨ Spesial',        'color' => '#ffd700'],
    'global'  => ['label' => '🌐 Umum',          'color' => '#8a2be2'],
    'game'    => ['label' => '🕹️ Games',         'color' => '#00ff88'],
];

// Progress data per achievement untuk progress bar
function ach_progress($name, $login_streak, $voice_hours, $chat_streak) {
    switch ($name) {
        case 'Paling Rajin':  return ['current'=>$chat_streak,  'max'=>7,   'unit'=>'hari'];
        case 'Paling Betah':  return ['current'=>$voice_hours,  'max'=>100, 'unit'=>'jam'];
        case 'Lunarian 𝟏':   return ['current'=>$login_streak,  'max'=>7,   'unit'=>'hari'];
        case 'Lunarian 𝟐':   return ['current'=>$login_streak,  'max'=>30,  'unit'=>'hari'];
        case 'Lunarian 𝟑':   return ['current'=>$login_streak,  'max'=>100, 'unit'=>'hari'];
        default: return null;
    }
}
?>

<style>
@keyframes unlock-glow {
    0%,100% { box-shadow:0 0 12px rgba(0,229,255,0.3); }
    50%     { box-shadow:0 0 24px rgba(0,229,255,0.6); }
}
@keyframes badge-bounce { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }
.ach-card { transition:transform 0.2s, box-shadow 0.2s; }
.ach-card:hover { transform:translateY(-3px); }
.ach-card.unlocked-card:hover { box-shadow:0 12px 32px rgba(0,229,255,0.2) !important; }
</style>

<div style="max-width:960px; margin:0 auto; padding:0 0 70px;">

    <!-- ── Header ── -->
    <div style="text-align:center; margin-bottom:44px;">
        <div style="font-size:3.5rem; margin-bottom:12px; line-height:1;">🏆</div>
        <h1 style="font-size:2.4rem; font-weight:800; color:#fff; margin:0 0 8px; text-shadow:0 0 25px rgba(138,43,226,0.6);">Pencapaian</h1>
        <p style="color:var(--text-muted); font-size:0.92rem; margin:0 0 28px;">Tunjukkan dedikasi dan kehebatanmu! Setiap pencapaian memberi XP dan Koin.</p>

        <?php if ($user_id): ?>
        <!-- Progress Overview -->
        <div style="display:inline-flex; align-items:center; gap:24px; background:rgba(255,255,255,0.03); border:1px solid var(--glass-border); padding:20px 32px; border-radius:20px; flex-wrap:wrap; justify-content:center;">
            <div style="text-align:center;">
                <p style="margin:0; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Terbuka</p>
                <strong style="color:var(--primary); font-size:1.8rem;"><?= $unlocked_count ?></strong>
                <span style="color:var(--text-muted); font-size:0.9rem;"> / <?= $total_count ?></span>
            </div>
            <div style="width:200px;">
                <div style="display:flex; justify-content:space-between; font-size:0.78rem; color:var(--text-muted); margin-bottom:8px;">
                    <span>Kemajuan Total</span>
                    <strong style="color:var(--primary);"><?= $progress_percent ?>%</strong>
                </div>
                <div style="height:10px; background:rgba(0,0,0,0.5); border-radius:5px; overflow:hidden; border:1px solid rgba(255,255,255,0.05);">
                    <div style="width:<?= $progress_percent ?>%; height:100%; background:linear-gradient(90deg,var(--secondary),var(--primary)); border-radius:5px;"></div>
                </div>
            </div>
            <div style="text-align:center;">
                <p style="margin:0; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Login Streak</p>
                <strong style="color:#ff6b35; font-size:1.6rem;"><?= $login_streak ?></strong>
                <span style="color:var(--text-muted); font-size:0.8rem;"> hari</span>
            </div>
            <div style="text-align:center;">
                <p style="margin:0; font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted);">Voice Hours</p>
                <strong style="color:#00e5ff; font-size:1.6rem;"><?= $voice_hours ?></strong>
                <span style="color:var(--text-muted); font-size:0.8rem;"> jam</span>
            </div>
        </div>
        <?php else: ?>
        <div style="background:rgba(138,43,226,0.1); border:1px solid rgba(138,43,226,0.25); padding:16px 28px; border-radius:14px; display:inline-block;">
            <p style="margin:0; color:#ccc; font-size:0.9rem;">
                <a href="?page=login" style="color:var(--primary); font-weight:700;">Masuk</a> untuk melacak kemajuan achievement kamu!
            </p>
        </div>
        <?php endif; ?>
    </div>

    <?php
    // Group by category
    $grouped = [];
    foreach ($achievements as $a) {
        $cat = $a['category'] ?? 'global';
        $grouped[$cat][] = $a;
    }

    foreach ($grouped as $cat_key => $cat_achievements):
        $cat_info = $cat_labels[$cat_key] ?? ['label'=>'🏆 Lainnya','color'=>'#fff'];
        $cat_unlocked = count(array_filter($cat_achievements, fn($a) => $a['unlocked']));
    ?>
    <div style="margin-bottom:44px;">
        <!-- Category Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.06);">
            <h2 style="margin:0; color:#fff; font-size:1.15rem; font-weight:700;"><?= $cat_info['label'] ?></h2>
            <span style="font-size:0.8rem; color:<?= $cat_info['color'] ?>; background:<?= $cat_info['color'] ?>1a; padding:4px 12px; border-radius:20px; border:1px solid <?= $cat_info['color'] ?>44;">
                <?= $cat_unlocked ?>/<?= count($cat_achievements) ?> terbuka
            </span>
        </div>

        <!-- Achievement Cards Grid -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(270px,1fr)); gap:14px;">
        <?php foreach ($cat_achievements as $ach):
            $prog = ach_progress($ach['name'], $login_streak, $voice_hours, $chat_streak);
            $pct  = 0;
            if ($prog && !$ach['unlocked']) {
                $pct = min(100, $prog['max'] > 0 ? round($prog['current'] / $prog['max'] * 100) : 0);
            }
        ?>
        <div class="ach-card <?= $ach['unlocked'] ? 'unlocked-card' : '' ?>"
             style="position:relative; overflow:hidden; border-radius:16px;
                    border:1px solid <?= $ach['unlocked'] ? 'rgba(0,229,255,0.3)' : 'rgba(255,255,255,0.06)' ?>;
                    background:<?= $ach['unlocked'] ? 'rgba(0,229,255,0.04)' : 'rgba(255,255,255,0.015)' ?>;
                    padding:20px; <?= $ach['unlocked'] ? 'animation:unlock-glow 3s ease-in-out infinite;' : 'opacity:0.7;' ?>">

            <!-- Left accent bar (unlocked only) -->
            <?php if ($ach['unlocked']): ?>
            <div style="position:absolute; top:0; left:0; width:4px; height:100%; background:linear-gradient(180deg,<?= $cat_info['color'] ?>,var(--primary));"></div>
            <?php endif; ?>

            <div style="display:flex; gap:16px; align-items:flex-start;">
                <!-- Icon -->
                <div style="flex-shrink:0; width:52px; height:52px; border-radius:14px;
                            background:<?= $ach['unlocked'] ? 'rgba(0,229,255,0.12)' : 'rgba(255,255,255,0.05)' ?>;
                            border:1px solid <?= $ach['unlocked'] ? 'rgba(0,229,255,0.25)' : 'rgba(255,255,255,0.06)' ?>;
                            display:flex; align-items:center; justify-content:center;
                            font-size:1.8rem; filter:<?= $ach['unlocked'] ? 'none' : 'grayscale(100%) opacity(45%)' ?>;
                            <?= $ach['unlocked'] ? 'animation:badge-bounce 2.5s ease-in-out infinite;' : '' ?>">
                    <?= $ach['icon'] ?>
                </div>

                <div style="flex-grow:1; min-width:0;">
                    <!-- Name + unlocked badge -->
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:5px;">
                        <h3 style="margin:0; color:<?= $ach['unlocked'] ? '#fff' : '#aaa' ?>; font-size:1rem; font-weight:700;"><?= htmlspecialchars($ach['name']) ?></h3>
                        <?php if ($ach['unlocked']): ?>
                        <span style="background:rgba(0,255,136,0.2); color:#00ff88; border:1px solid rgba(0,255,136,0.35); font-size:0.62rem; padding:2px 8px; border-radius:20px; font-weight:800;">✓ UNLOCKED</span>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <p style="margin:0 0 10px; color:var(--text-muted); font-size:0.82rem; line-height:1.55;"><?= htmlspecialchars($ach['description']) ?></p>

                    <!-- Progress bar (only for trackable, unlocked ones don't need it) -->
                    <?php if ($prog && !$ach['unlocked']): ?>
                    <div style="margin-bottom:10px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.74rem; color:var(--text-muted); margin-bottom:5px;">
                            <span>Kemajuan</span>
                            <span style="color:#fff;"><?= min($prog['current'], $prog['max']) ?> / <?= $prog['max'] ?> <?= $prog['unit'] ?></span>
                        </div>
                        <div style="height:6px; background:rgba(255,255,255,0.06); border-radius:3px; overflow:hidden;">
                            <div style="width:<?= $pct ?>%; height:100%; background:linear-gradient(90deg,<?= $cat_info['color'] ?>,var(--primary)); border-radius:3px; transition:width 1s ease;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Rewards -->
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <span style="color:var(--primary); font-weight:700; font-size:0.8rem; background:rgba(138,43,226,0.12); padding:3px 10px; border-radius:10px;">+<?= $ach['xp_reward'] ?> XP</span>
                        <span style="color:#ffd700; font-weight:700; font-size:0.8rem; background:rgba(255,215,0,0.1); padding:3px 10px; border-radius:10px;">+<?= $ach['coin_reward'] ?> Koin</span>
                        <?php if ($ach['unlocked'] && $ach['unlocked_at']): ?>
                        <span style="color:var(--text-muted); font-size:0.74rem; margin-left:auto;"><?= date('d M Y', strtotime($ach['unlocked_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($achievements)): ?>
    <div style="text-align:center; padding:80px 20px; color:var(--text-muted);">
        <div style="font-size:4rem; margin-bottom:16px;">🔒</div>
        <p style="margin:0; font-size:0.95rem;">Belum ada achievement tersedia. Coba jalankan database_upgrades.sql!</p>
    </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
