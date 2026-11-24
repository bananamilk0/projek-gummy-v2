<?php 
require_once __DIR__ . '/../includes/header.php'; 

// Fetch site settings
global $pdo;
$settings = [];
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$welcome_title = $settings['welcome_title'] ?? 'Welcome to the <span class="gradient-text">Clubhouse</span>';
$welcome_message = $settings['welcome_message'] ?? 'The ultimate digital lobby for gamers. Play, chat, and level up.';
$gallery = [];
try { $gallery = $pdo->query("SELECT image_url,caption FROM gallery_images ORDER BY id DESC LIMIT 8")->fetchAll(); } catch (Exception $e) {}
?>

<div class="hero-section">
    <div class="hero-content">
        <h1 class="gsap-reveal"><?= $welcome_title ?></h1>
        <p class="gsap-reveal delay-1"><?= htmlspecialchars($welcome_message) ?></p>
        <div class="hero-actions gsap-reveal delay-2">
            <a href="?page=community" class="btn-primary magnetic">Join the Lounge</a>
            <a href="?page=games" class="btn-secondary magnetic">Play Mini Games</a>
        </div>
    </div>
</div>

<?php if ($gallery): 
    // Duplicate photos for seamless infinite marquee loop
    $marqueePhotos = array_merge($gallery, $gallery);
?>
<style>
@keyframes marqueeScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.marquee-container {
    overflow: hidden;
    position: relative;
    width: 100%;
    border-radius: 14px;
    padding: 10px 0;
}
.marquee-container::before, .marquee-container::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0; width: 40px;
    z-index: 2;
    pointer-events: none;
}
.marquee-container::before { left: 0; background: linear-gradient(to right, var(--bg-dark), transparent); }
.marquee-container::after { right: 0; background: linear-gradient(to left, var(--bg-dark), transparent); }

.marquee-track {
    display: flex;
    gap: 16px;
    width: max-content;
    animation: marqueeScroll 25s linear infinite;
}
.marquee-track:hover {
    animation-play-state: paused;
}
.marquee-item {
    flex: 0 0 auto;
    width: 220px;
    border-radius: 12px;
    overflow: hidden;
    background: #101411;
    border: 1px solid var(--glass-border);
    cursor: pointer;
    transition: transform 0.25s, border-color 0.25s;
}
.marquee-item:hover {
    transform: translateY(-4px);
    border-color: var(--primary);
}
</style>

<section class="glass-panel" style="margin:26px 0;padding:24px;border:1px solid var(--glass-border);">
    <h2 style="margin:0 0 16px;color:#fff;font-size:1.25rem;display:flex;align-items:center;gap:8px;">
        🖼️ Galeri Komunitas <span style="font-size:0.75rem;color:var(--text-muted);font-weight:400;">(Geser otomatis — Sentuh/Sorot untuk jeda)</span>
    </h2>
    <div class="marquee-container">
        <div class="marquee-track">
            <?php foreach ($marqueePhotos as $photo): ?>
                <figure class="marquee-item" onclick="openHomeLightbox('<?= htmlspecialchars($photo['image_url']) ?>', '<?= htmlspecialchars(addslashes($photo['caption'] ?? '')) ?>')">
                    <img src="<?= htmlspecialchars($photo['image_url']) ?>" alt="<?= htmlspecialchars($photo['caption'] ?? 'Gallery') ?>" style="display:block;width:100%;height:140px;object-fit:cover;">
                    <?php if (!empty($photo['caption'])): ?>
                        <figcaption style="padding:8px 10px;color:#ddd;font-size:.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($photo['caption']) ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox Modal for Home Gallery -->
<div id="home-lightbox" onclick="closeHomeLightbox()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.9); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(8px); padding:20px;">
    <div onclick="event.stopPropagation()" style="position:relative; max-width:90vw; max-height:90vh; text-align:center;">
        <button onclick="closeHomeLightbox()" style="position:absolute; top:-40px; right:0; background:rgba(255,255,255,0.2); border:none; color:#fff; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.2rem; display:flex; align-items:center; justify-content:center;">✕</button>
        <img id="home-lightbox-img" src="" style="max-width:90vw; max-height:80vh; object-fit:contain; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.8); border:1px solid var(--glass-border);">
        <p id="home-lightbox-caption" style="margin-top:12px; color:#e0e0e0; font-size:0.95rem; font-weight:600;"></p>
    </div>
</div>

<script>
function openHomeLightbox(url, caption) {
    const lb = document.getElementById('home-lightbox');
    const img = document.getElementById('home-lightbox-img');
    const cap = document.getElementById('home-lightbox-caption');
    if (lb && img) {
        img.src = url;
        if (cap) cap.textContent = caption || '';
        lb.style.display = 'flex';
    }
}
function closeHomeLightbox() {
    const lb = document.getElementById('home-lightbox');
    if (lb) lb.style.display = 'none';
}
</script>
<?php endif; ?>

<div class="bento-grid" style="margin-top: 36px;">
    <div class="bento-item glass-card tilt-card span-2">
        <div class="bento-content">
            <i data-lucide="message-square-dashed" class="bento-icon"></i>
            <h3>Real-time Community</h3>
            <p>Jump into the Lounge and chat with other players in real time.</p>
        </div>
    </div>
    <div class="bento-item glass-card tilt-card bg-gradient">
        <div class="bento-content">
            <i data-lucide="swords" class="bento-icon text-white"></i>
            <h3 class="text-white">Leaderboards</h3>
            <p class="text-white">Climb the ranks and earn badges.</p>
        </div>
    </div>
    <div class="bento-item glass-card tilt-card row-span-2 relative overflow-hidden">
        <div class="bento-content">
            <i data-lucide="gamepad-2" class="bento-icon"></i>
            <h3>Canvas Mini Games</h3>
            <p>Play exclusive HTML5 games directly in your browser. Earn XP and Coins to customize your profile.</p>
            <div class="particle-fx"></div>
        </div>
    </div>
    <div class="bento-item glass-card tilt-card">
        <div class="bento-content">
            <i data-lucide="shopping-bag" class="bento-icon"></i>
            <h3>Community Perks</h3>
            <p>Spend your coins on custom name colors and badges.</p>
        </div>
    </div>
    <div class="bento-item glass-card tilt-card">
        <div class="bento-content">
            <i data-lucide="sparkles" class="bento-icon"></i>
            <h3>Daily Missions</h3>
            <p>Complete quests to maintain your login streak.</p>
        </div>
    </div>
</div>

<!-- Top 3 Leaderboard Member Paling Betah -->
<?php
$topMembers = [];
try {
    $topMembers = $pdo->query("SELECT username, level, xp, avatar, total_time_spent, created_at, DATEDIFF(NOW(), created_at) as days_joined FROM users ORDER BY total_time_spent DESC, DATEDIFF(NOW(), created_at) DESC LIMIT 3")->fetchAll();
} catch (Exception $e) {}
?>
<?php if($topMembers): ?>
<section class="glass-panel" style="margin-top: 50px; padding: 30px; background: linear-gradient(135deg, rgba(18, 40, 22, 0.85), rgba(12, 28, 16, 0.9)); border: 1px solid rgba(107, 158, 120, 0.25);">
    <div style="text-align: center; margin-bottom: 25px;">
        <h2 style="margin:0; font-size: 1.8rem; color: #fff;">🏆 Top 3 Member Paling Betah</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-top: 5px;">Peringkat berdasarkan lama waktu di Gummy!</p>
    </div>
    <div class="row" style="justify-content: center; gap: 20px;">
        <?php foreach($topMembers as $index => $user): 
            $colors = ['#ffd700', '#c8d0dc', '#d48755'];
            $cardBgs = [
                'linear-gradient(135deg, rgba(40, 70, 45, 0.6), rgba(25, 50, 30, 0.7))',
                'linear-gradient(135deg, rgba(30, 55, 38, 0.5), rgba(20, 42, 26, 0.6))',
                'linear-gradient(135deg, rgba(35, 55, 38, 0.45), rgba(22, 40, 28, 0.55))'
            ];
            $color = $colors[$index] ?? '#fff';
            $cardBg = $cardBgs[$index] ?? $cardBgs[2];
            $days = (int)($user['days_joined'] ?? 0);
            $timeSpent = (int)($user['total_time_spent'] ?? 0);
        ?>
        <div class="col-md-3 glass-card" style="text-align: center; padding: 25px; border-top: 3px solid <?= $color ?>; display:flex; flex-direction:column; align-items:center; background: <?= $cardBg ?>; border: 1px solid rgba(107, 158, 120, 0.18);">
            <div style="font-size: 1.5rem; font-weight: 800; color: <?= $color ?>; margin-bottom: 15px;">#<?= $index + 1 ?></div>
            <img src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.png') ?>" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid <?= $color ?>; margin-bottom: 15px;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['username']) ?>'">
            <h3 style="margin: 0; font-size: 1.2rem; color: #fff;"><?= htmlspecialchars($user['username']) ?></h3>
            <div style="margin-top: 10px; font-size: 0.85rem; color: var(--secondary); font-weight: bold;">📅 <?= $days ?> hari bergabung</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">Level <?= $user['level'] ?> · <?= number_format($user['xp']) ?> XP</div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
