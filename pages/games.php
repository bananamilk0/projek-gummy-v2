<?php
// Handle quest claim POST BEFORE any HTML output (header.php)
// This prevents "headers already sent" error when doing redirect
global $pdo;
$user_id = $_SESSION['user_id'] ?? null;
$today   = date('Y-m-d');

if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_quest'])) {
    try {
        $claim = $pdo->prepare("SELECT uq.id,dq.xp_reward,dq.coin_reward FROM user_quests uq JOIN daily_quests dq ON dq.id=uq.quest_id WHERE uq.id=? AND uq.user_id=? AND uq.assigned_date=? AND uq.completed=1 AND COALESCE(uq.claimed,0)=0");
        $claim->execute([(int)$_POST['claim_quest'],$user_id,$today]); $quest=$claim->fetch();
        if ($quest) { $pdo->prepare("UPDATE user_quests SET claimed=1 WHERE id=?")->execute([$quest['id']]); $pdo->prepare("UPDATE users SET coins=coins+? WHERE id=?")->execute([$quest['coin_reward'],$user_id]); require_once __DIR__.'/../includes/level_system.php'; add_xp($user_id,(int)$quest['xp_reward']); }
        header('Location: ?page=games'); exit;
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';

// ── Quest reset & assign (auto setiap hari baru) ──────────────────────────
if ($user_id) {
    try {
        $checkQ = $pdo->prepare("SELECT COUNT(*) FROM user_quests WHERE user_id=? AND assigned_date=?");
        $checkQ->execute([$user_id, $today]);
        if ((int)$checkQ->fetchColumn() === 0) {
            $dqRows = $pdo->query("SELECT id FROM daily_quests ORDER BY RAND() LIMIT 3")->fetchAll();
            $ins = $pdo->prepare("INSERT IGNORE INTO user_quests (user_id,quest_id,assigned_date) VALUES (?,?,?)");
            foreach ($dqRows as $q) $ins->execute([$user_id, $q['id'], $today]);
        }
        // Auto-complete login quest
        $pdo->prepare("
            UPDATE user_quests uq
            JOIN daily_quests dq ON uq.quest_id=dq.id
            SET uq.progress=1, uq.completed=1
            WHERE uq.user_id=? AND uq.assigned_date=? AND dq.requirement_type='login' AND uq.completed=0
        ")->execute([$user_id, $today]);
    } catch (Exception $e) {}
}

// ── Games ─────────────────────────────────────────────────────────────────
try {
    $games = $pdo->query("SELECT * FROM games ORDER BY id ASC")->fetchAll();
} catch (Exception $e) { $games = []; }

// ── High scores ───────────────────────────────────────────────────────────
$highScores = [];
if ($user_id) {
    try {
        $sc = $pdo->prepare("SELECT g.slug, MAX(s.score) as hs FROM games g LEFT JOIN scores s ON g.id=s.game_id AND s.user_id=? GROUP BY g.id");
        $sc->execute([$user_id]);
        while ($r = $sc->fetch()) $highScores[$r['slug']] = $r['hs'];
    } catch (Exception $e) {}
}

// ── Daily quests ──────────────────────────────────────────────────────────
$quests = [];
if ($user_id) {
    try {
        $qst = $pdo->prepare("
            SELECT dq.title,dq.description,dq.xp_reward,dq.coin_reward,dq.requirement_value,
                   uq.id as user_quest_id,uq.progress,uq.completed,COALESCE(uq.claimed,0) as claimed
            FROM user_quests uq
            JOIN daily_quests dq ON uq.quest_id=dq.id
            WHERE uq.user_id=? AND uq.assigned_date=?
            ORDER BY uq.completed ASC, dq.id ASC
        ");
        $qst->execute([$user_id, $today]);
        $quests = $qst->fetchAll();
    } catch (Exception $e) {}
}

// ── Achievements ──────────────────────────────────────────────────────────
$achievements = [];
if ($user_id) {
    try {
        $ach = $pdo->prepare("
            SELECT a.name,a.description,a.icon,(ua.id IS NOT NULL) as unlocked
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id=ua.achievement_id AND ua.user_id=?
            ORDER BY unlocked DESC, a.id ASC
        ");
        $ach->execute([$user_id]);
        $achievements = $ach->fetchAll();
    } catch (Exception $e) {}
}

// ── Leaderboard top 3 per game ────────────────────────────────────────────
$leaderboards = [];
try {
    $lb = $pdo->query("
        SELECT g.slug, u.username, u.avatar, MAX(s.score) as best_score
        FROM scores s
        JOIN games g ON g.id = s.game_id
        JOIN users u ON u.id = s.user_id
        GROUP BY g.slug, s.user_id
        ORDER BY g.slug, best_score DESC
    ")->fetchAll();
    foreach ($lb as $row) {
        if (!isset($leaderboards[$row['slug']])) $leaderboards[$row['slug']] = [];
        if (count($leaderboards[$row['slug']]) < 3) $leaderboards[$row['slug']][] = $row;
    }
} catch (Exception $e) {}

$gameMeta = [
    'neon-runner'  => ['difficulty'=>'Mudah',  'color'=>'#00e5ff','icon'=>'🏃'],
    'aim-trainer'  => ['difficulty'=>'Sedang', 'color'=>'#ffeb3b','icon'=>'🎯'],
    'whack-a-slime'=> ['difficulty'=>'Mudah',  'color'=>'#00ff88','icon'=>'🔨'],
    'pixel-jumper' => ['difficulty'=>'Sedang', 'color'=>'#ff007f','icon'=>'🚀'],
    'gravity-flip' => ['difficulty'=>'Sulit',  'color'=>'#a020f0','icon'=>'🔄'],
    'escape-maze'  => ['difficulty'=>'Sedang', 'color'=>'#ff5722','icon'=>'🧩'],
];
?>

<div style="max-width:1200px; margin:0 auto; padding:0 0 60px;">

    <!-- Header -->
    <div style="text-align:center; margin-bottom:40px;">
        <h1 style="font-size:2.4rem; font-weight:800; color:#fff; margin-bottom:6px; text-shadow:0 0 20px var(--primary);">Arcade Library</h1>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Pilih mini-game Canvas HTML5 dan menangkan XP, Koin, serta Achievement!</p>
    </div>

    <!-- ── Game Cards (horizontal scroll pada mobile, grid pada desktop) ── -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px; margin-bottom:48px;">
        <?php foreach ($games as $game):
            $meta = $gameMeta[$game['slug']] ?? ['difficulty'=>'Sedang','color'=>'#fff','icon'=>'🎮'];
            $hs   = $highScores[$game['slug']] ?? 0;
        ?>
        <div style="border-radius:16px; border:1px solid var(--glass-border); background:rgba(10,10,20,0.45); overflow:hidden; display:flex; flex-direction:column; transition:transform 0.25s,box-shadow 0.25s; --game-c:<?= $meta['color'] ?>;"
             onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 12px 28px rgba(0,0,0,0.45),0 0 18px var(--game-c)'; this.style.borderColor='var(--game-c)'"
             onmouseleave="this.style.transform='none'; this.style.boxShadow=''; this.style.borderColor='var(--glass-border)'">

            <!-- Visual -->
            <div style="height:130px; display:flex; align-items:center; justify-content:center; position:relative; background:linear-gradient(135deg,rgba(0,0,0,0.5),rgba(20,20,40,0.7)); border-bottom:1px solid rgba(255,255,255,0.05);">
                <span style="font-size:3.8rem; z-index:2; transition:transform 0.3s ease;"><?= $meta['icon'] ?></span>
                <div style="position:absolute; inset:0; background:radial-gradient(circle at center,<?= $meta['color'] ?> 0%,transparent 65%); opacity:0.12;"></div>
            </div>

            <!-- Body -->
            <div style="padding:18px 20px 20px; flex-grow:1; display:flex; flex-direction:column;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <h3 style="margin:0; color:#fff; font-size:1.1rem; font-weight:700;"><?= htmlspecialchars($game['name']) ?></h3>
                    <span style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); color:#ccc; border-radius:6px; padding:3px 8px; font-size:0.7rem; white-space:nowrap;"><?= $meta['difficulty'] ?></span>
                </div>
                <p style="color:var(--text-muted); font-size:0.83rem; line-height:1.55; flex-grow:1; margin-bottom:16px;"><?= htmlspecialchars($game['description'] ?? 'Mainkan game seru ini!') ?></p>
                <div style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px solid rgba(255,255,255,0.05);">
                    <div>
                        <p style="margin:0; font-size:0.68rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.8px;">Skor Tertinggi</p>
                        <strong style="color:var(--primary); font-size:1.1rem;"><?= number_format($hs) ?></strong>
                    </div>
                    <a href="?page=play&game=<?= $game['slug'] ?>" style="background:<?= $meta['color'] ?>; color:#000; text-decoration:none; border-radius:8px; padding:9px 18px; font-weight:700; font-size:0.85rem; transition:opacity 0.2s;">
                        Mainkan →
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Quest Harian ── -->
    <div class="glass-panel" style="padding:28px; margin-bottom:24px; border:1px solid var(--glass-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0; font-size:1.3rem; color:#fff;">🎯 Quest Harian</h2>
                <p style="margin:4px 0 0; font-size:0.78rem; color:var(--text-muted);">Reset otomatis setiap hari pukul 00:00. Selesaikan sebelum tengah malam!</p>
            </div>
            <?php if ($user_id): ?>
            <div style="background:rgba(0,229,255,0.08); border:1px solid rgba(0,229,255,0.2); padding:7px 14px; border-radius:20px; font-size:0.8rem; color:var(--secondary);">
                <i data-lucide="clock" style="width:13px;height:13px;vertical-align:middle;margin-right:5px;"></i> Hari ini: <?= date('d M Y') ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$user_id): ?>
            <div style="text-align:center; padding:30px 0; color:var(--text-muted);">
                <i data-lucide="lock" style="width:32px;height:32px; opacity:0.4; display:block; margin:0 auto 12px;"></i>
                <p style="margin:0 0 14px; font-size:0.9rem;">Login untuk mendapatkan quest harian dan hadiah!</p>
                <a href="?page=login" class="btn-primary" style="padding:10px 24px; font-size:0.875rem; display:inline-flex; align-items:center; gap:8px;">
                    <i data-lucide="log-in" style="width:15px;height:15px;"></i> Login Sekarang
                </a>
            </div>
        <?php elseif (empty($quests)): ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px 0; margin:0;">Tidak ada quest aktif hari ini.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px;">
            <?php foreach ($quests as $q):
                $pct = min(100, $q['requirement_value'] > 0 ? round($q['progress'] / $q['requirement_value'] * 100) : 100);
            ?>
                <div style="padding:18px 20px; border-radius:12px; border:1px solid <?= $q['completed'] ? 'rgba(0,255,136,0.3)' : 'rgba(255,255,255,0.07)' ?>; background:<?= $q['completed'] ? 'rgba(0,255,136,0.04)' : 'rgba(255,255,255,0.02)' ?>;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; gap:10px;">
                        <h4 style="margin:0; color:#fff; font-size:0.92rem; font-weight:700; line-height:1.3;"><?= htmlspecialchars($q['title']) ?></h4>
                        <span style="flex-shrink:0; font-size:0.75rem; font-weight:700; color:var(--primary); white-space:nowrap;">+<?= $q['xp_reward'] ?> XP</span>
                    </div>
                    <p style="margin:0 0 12px; color:var(--text-muted); font-size:0.8rem; line-height:1.45;"><?= htmlspecialchars($q['description']) ?></p>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="flex-grow:1; height:6px; background:rgba(255,255,255,0.07); border-radius:3px; overflow:hidden;">
                            <div style="width:<?= $pct ?>%; height:100%; background:<?= $q['completed'] ? 'linear-gradient(90deg,#00ff88,#00cc66)' : 'linear-gradient(90deg,var(--secondary),var(--primary))' ?>; border-radius:3px; transition:width 0.5s ease;"></div>
                        </div>
                        <span style="font-size:0.72rem; color:var(--text-muted); white-space:nowrap;"><?= $q['progress'] ?>/<?= $q['requirement_value'] ?></span>
                        <?php if ($q['completed'] && !$q['claimed']): ?>
                            <form method="POST"><button name="claim_quest" value="<?= $q['user_quest_id'] ?>" style="border:0;border-radius:7px;background:#00d978;color:#06150d;font-weight:800;padding:5px 8px;cursor:pointer;font-size:.7rem;">Klaim</button></form>
                        <?php elseif ($q['completed']): ?>
                            <span style="font-size:0.72rem; color:#00ff88; font-weight:700;">✓</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Achievements ── -->
    <div class="glass-panel" style="padding:28px; border:1px solid var(--glass-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; flex-wrap:wrap; gap:10px;">
            <h2 style="margin:0; font-size:1.3rem; color:#fff;">🏆 Pencapaian</h2>
            <a href="?page=achievements" class="btn-secondary" style="padding:8px 16px; font-size:0.8rem; display:inline-flex; align-items:center; gap:6px;">
                Lihat Semua <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
            </a>
        </div>

        <?php if (!$user_id): ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px 0; margin:0; font-size:0.88rem;">
                <a href="?page=login" style="color:var(--primary); font-weight:700;">Masuk</a> untuk melihat achievement kamu.
            </p>
        <?php elseif (empty($achievements)): ?>
            <p style="color:var(--text-muted); text-align:center; padding:20px 0; margin:0;">Belum ada achievement.</p>
        <?php else: ?>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px;">
            <?php foreach (array_slice($achievements, 0, 6) as $a): ?>
                <div style="display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:12px; border:1px solid <?= $a['unlocked'] ? 'rgba(0,229,255,0.15)' : 'rgba(255,255,255,0.05)' ?>; background:<?= $a['unlocked'] ? 'rgba(0,229,255,0.03)' : 'rgba(0,0,0,0.15)' ?>; <?= $a['unlocked'] ? '' : 'opacity:0.5;' ?>">
                    <div style="font-size:1.9rem; flex-shrink:0; filter:<?= $a['unlocked'] ? 'none' : 'grayscale(100%) opacity(40%)' ?>;"><?= $a['icon'] ?></div>
                    <div>
                        <p style="margin:0; color:#fff; font-size:0.85rem; font-weight:<?= $a['unlocked'] ? '700' : '500' ?>;"><?= htmlspecialchars($a['name']) ?></p>
                        <p style="margin:3px 0 0; color:var(--text-muted); font-size:0.76rem; line-height:1.35;"><?= htmlspecialchars($a['description']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
