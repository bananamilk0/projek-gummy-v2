<?php
require_once __DIR__ . '/../includes/header.php';

$game_slug = $_GET['game'] ?? '';

// Game data mapping
$games_list = [
    'neon-runner' => [
        'title' => 'Neon Runner',
        'desc' => 'Hindari rintangan merah. Kumpulkan koin untuk meningkatkan skor Anda.',
        'controls' => 'Tekan [Spasi] atau [Klik] untuk melompat.',
        'script' => ['assets/js/game.js']
    ],
    'aim-trainer' => [
        'title' => 'Aim Trainer',
        'desc' => 'Tembak blob target secepat mungkin untuk mendapatkan combo tinggi!',
        'controls' => '[Klik] atau [Sentuh] target blob yang muncul secara acak.',
        'script' => ['assets/js/arcade-engine.js', 'assets/js/games/aim-trainer.js']
    ],
    'whack-a-slime' => [
        'title' => 'Whack-a-Slime',
        'desc' => 'Pukul slime lucu sebelum mereka masuk kembali. Awas slime merah!',
        'controls' => '[Klik] atau [Sentuh] slime yang muncul dari lubang.',
        'script' => ['assets/js/arcade-engine.js', 'assets/js/games/whack-a-slime.js']
    ],
    'pixel-jumper' => [
        'title' => 'Pixel Jumper',
        'desc' => 'Melompat setinggi-tingginya dari platform ke platform. Awas platform rapuh!',
        'controls' => 'Gunakan tombol [A] / [D] atau [Panah Kiri] / [Panah Kanan] untuk bergerak.',
        'script' => ['assets/js/arcade-engine.js', 'assets/js/games/pixel-jumper.js']
    ],
    'gravity-flip' => [
        'title' => 'Gravity Flip',
        'desc' => 'Balikkan gravitasi untuk menghindari duri dan laser berbahaya!',
        'controls' => 'Tekan [Spasi], [W], [Panah Atas], atau [Klik] untuk membalik gravitasi.',
        'script' => ['assets/js/arcade-engine.js', 'assets/js/games/gravity-flip.js']
    ],
    'escape-maze' => [
        'title' => 'Escape Maze',
        'desc' => 'Cari jalan keluar labirin secepat mungkin. Kumpulkan koin & kunci!',
        'controls' => 'Gunakan tombol [WASD] atau [Tombol Panah] untuk bergerak.',
        'script' => ['assets/js/arcade-engine.js', 'assets/js/games/escape-maze.js']
    ]
];

if (!array_key_exists($game_slug, $games_list)) {
    echo "<div class='container mt-5 text-center'><h2>Game tidak ditemukan!</h2><a href='?page=games' class='btn-primary mt-3'>Kembali ke Library</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$currentGame = $games_list[$game_slug];

// Fetch High Score and Rank
$highScore = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT MAX(s.score) as max_score 
        FROM scores s
        JOIN games g ON s.game_id = g.id
        WHERE s.user_id = ? AND g.slug = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $game_slug]);
    $highScore = (int)($stmt->fetch()['max_score'] ?? 0);
}
$leaderboard = [];
try {
    $lb = $pdo->prepare("SELECT u.username, MAX(s.score) score FROM scores s JOIN games g ON g.id=s.game_id JOIN users u ON u.id=s.user_id WHERE g.slug=? GROUP BY s.user_id,u.username ORDER BY score DESC LIMIT 3");
    $lb->execute([$game_slug]); $leaderboard = $lb->fetchAll();
} catch (Exception $e) {}
?>

<div class="games-layout">
    <div class="game-info glass-panel">
        <a href="?page=games" class="btn-secondary mb-4" style="display: inline-flex; align-items: center; gap: 8px;">
            ← Library
        </a>
        <h2><?= htmlspecialchars($currentGame['title']) ?></h2>
        <p class="text-secondary"><?= htmlspecialchars($currentGame['desc']) ?></p>
        
        <div class="controls-box mt-4">
            <h4>Cara Bermain:</h4>
            <p><?= htmlspecialchars($currentGame['controls']) ?></p>
        </div>
        
        <div class="game-stats mt-4">
            <div class="stat-box">
                <h4>High Score Anda</h4>
                <p class="text-primary" id="ui-highscore"><?= number_format($highScore) ?></p>
            </div>
        </div>
        <div class="controls-box mt-4" style="margin-bottom:0;">
            <h4>🏆 Top 3 Leaderboard</h4>
            <?php if (!$leaderboard): ?><p style="margin:0;color:var(--text-muted);font-size:.85rem;">Belum ada skor. Jadilah yang pertama!</p>
            <?php else: foreach($leaderboard as $i=>$row): ?><div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.06);"><strong style="color:<?= ['#ffd700','#c8d0dc','#d48755'][$i] ?>">#<?= $i+1 ?></strong><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['username']) ?></span><strong><?= number_format($row['score']) ?></strong></div><?php endforeach; endif; ?>
        </div>
    </div>
    
    <div class="game-canvas-wrapper glass-card relative overflow-hidden" id="game-container">
        <!-- Fullscreen Button -->
        <button id="btn-fullscreen" style="position:absolute; top:15px; right:15px; z-index:20; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.2); color:#fff; border-radius:8px; padding:8px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.2s;" title="Fullscreen" onmouseenter="this.style.background='rgba(255,255,255,0.2)'" onmouseleave="this.style.background='rgba(0,0,0,0.5)'">
            <i data-lucide="maximize" style="width:18px;height:18px;"></i>
        </button>
        
        <!-- The Game Canvas -->
        <canvas id="gameCanvas" width="800" height="400"></canvas>
        
        <!-- Game UI Overlays -->
        <div id="game-start-screen" class="game-overlay">
            <h2><?= htmlspecialchars($currentGame['title']) ?></h2>
            <p><?= htmlspecialchars($currentGame['controls']) ?></p>
            <?php if ($game_slug === 'escape-maze'): ?>
                <div id="device-choice" style="display:flex;gap:10px;justify-content:center;margin-bottom:15px;">
                    <button type="button" class="btn-secondary" data-device="pc">💻 PC</button>
                    <button type="button" class="btn-secondary" data-device="mobile">📱 Handphone</button>
                </div>
            <?php endif; ?>
            <button id="btn-start-game" class="btn-primary magnetic mt-2">Mulai Bermain</button>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <p class="text-accent mt-2"><small>Bermain sebagai Tamu. Masuk untuk menyimpan skor!</small></p>
            <?php endif; ?>
        </div>
        
        <!-- On-Screen Mobile Controls Overlay -->
        <div id="mobile-controls" style="display:none; position:absolute; bottom:15px; left:50%; transform:translateX(-50%); z-index:15; gap:10px; background:rgba(0,0,0,0.6); padding:10px 16px; border-radius:30px; border:1px solid rgba(255,255,255,0.15); backdrop-filter:blur(6px);">
            <button type="button" data-move="ArrowLeft" class="btn-secondary" style="width:44px;height:44px;padding:0;font-size:1.2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;">←</button>
            <button type="button" data-move="ArrowUp" class="btn-secondary" style="width:44px;height:44px;padding:0;font-size:1.2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;">↑</button>
            <button type="button" data-move="ArrowDown" class="btn-secondary" style="width:44px;height:44px;padding:0;font-size:1.2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;">↓</button>
            <button type="button" data-move="ArrowRight" class="btn-secondary" style="width:44px;height:44px;padding:0;font-size:1.2rem;border-radius:50%;display:flex;align-items:center;justify-content:center;">→</button>
        </div>
        
        <div id="game-over-screen" class="game-overlay" style="display: none;">
            <h2>Game Over</h2>
            <p>Skor Akhir: <span id="final-score">0</span></p>
            <button id="btn-restart-game" class="btn-secondary magnetic mt-4">Main Lagi</button>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <div class="guest-warning mt-4">
                    <p>Skor yang bagus! Masuk untuk menyimpan skor ini ke papan peringkat.</p>
                    <a href="?page=login" class="btn-primary" style="margin-top:10px;">Masuk</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="hud" id="game-hud" style="display: none;">
            <span>Skor: <span id="hud-score">0</span></span>
        </div>
    </div>
</div>

<style>
.games-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 30px;
    margin-top: 30px;
    padding: 0 15px;
    align-items: start;
}
.game-info {
    padding: 30px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--glass-border);
}
.game-info h2 {
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 15px;
    color: #fff;
    text-shadow: 0 0 10px rgba(0, 229, 255, 0.2);
}
.controls-box {
    background: rgba(0,0,0,0.2);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 25px;
}
.controls-box h4 {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: var(--primary);
}
.stat-box {
    background: rgba(0,0,0,0.3);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid var(--glass-border);
    text-align: center;
}
.stat-box h4 {
    font-size: 0.95rem;
    color: #888;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.stat-box p {
    font-size: 2rem;
    font-weight: 800;
    margin: 0;
}
.game-canvas-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    background: #050505;
    min-height: 500px;
    border-radius: 16px;
    border: 1px solid var(--glass-border);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
}
canvas {
    background: linear-gradient(to bottom, #0a0a1a, #1a0b2e);
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0, 229, 255, 0.15);
    max-width: 100%;
}
.game-overlay {
    position: absolute;
    inset: 0;
    background: rgba(10, 10, 20, 0.85);
    backdrop-filter: blur(8px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    padding: 40px;
    z-index: 10;
}
.game-overlay h2 {
    font-size: 3.5rem;
    font-weight: 900;
    text-shadow: 0 0 20px var(--primary);
    margin-bottom: 15px;
}
.game-overlay p {
    font-size: 1.2rem;
    color: #ccc;
    max-width: 500px;
    margin-bottom: 25px;
    line-height: 1.6;
}
.game-overlay button {
    padding: 12px 35px;
    font-size: 1.1rem;
    font-weight: 700;
    border-radius: 10px;
    margin-top: 15px;
}
.hud {
    position: absolute;
    top: 30px;
    right: 30px;
    font-size: 1.6rem;
    font-weight: 800;
    color: white;
    text-shadow: 0 0 10px var(--primary);
    background: rgba(0,0,0,0.5);
    padding: 8px 20px;
    border-radius: 30px;
    border: 1px solid rgba(255,255,255,0.1);
    z-index: 5;
}
.guest-warning {
    background: rgba(255, 0, 127, 0.08);
    border: 1px solid rgba(255, 0, 127, 0.25);
    padding: 25px;
    border-radius: 12px;
    max-width: 380px;
    margin-top: 25px;
    box-shadow: 0 8px 20px rgba(255,0,127,0.1);
}
.guest-warning p {
    font-size: 0.95rem;
    margin-bottom: 15px;
    color: #ff80b3;
}
.text-accent {
    color: var(--accent);
}
</style>

<script>
window.submitArcadeScore = function(slug, score) {
    if(score <= 0) return;
    fetch('api/submit_score.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ game_slug: slug, score: score })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Update stats
            const curHighScore = parseInt(document.getElementById('ui-highscore').innerText.replace(/,/g, '')) || 0;
            if(score > curHighScore) {
                document.getElementById('ui-highscore').innerText = score.toLocaleString();
            }
            
            if(data.xp_gained) {
                // Show floating XP text
                const xpText = document.createElement('div');
                xpText.textContent = '+' + data.xp_gained + ' XP';
                xpText.style.position = 'absolute';
                xpText.style.color = 'var(--primary)';
                xpText.style.fontSize = '2rem';
                xpText.style.fontWeight = 'bold';
                xpText.style.pointerEvents = 'none';
                xpText.style.zIndex = '9999';
                xpText.style.left = '50%';
                xpText.style.top = '40%';
                xpText.style.transform = 'translate(-50%, -50%)';
                document.body.appendChild(xpText);
                
                if(typeof gsap !== 'undefined') {
                    gsap.to(xpText, {
                        y: -100,
                        opacity: 0,
                        duration: 2,
                        onComplete: () => xpText.remove()
                    });
                } else {
                    setTimeout(() => xpText.remove(), 2000);
                }
            }
        }
    });
}

// Prevent spacebar from scrolling the page while game is active
document.addEventListener('keydown', function(e) {
    if (e.code === 'Space' && document.body.classList.contains('game-active')) {
        // Only prevent if not focused on a text input
        if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    }
}, { passive: false });

document.addEventListener("DOMContentLoaded", () => {
    const btnFs = document.getElementById('btn-fullscreen');
    const container = document.getElementById('game-container');
    if(btnFs && container) {
        btnFs.addEventListener('click', () => {
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => alert("Fullscreen error: " + err.message));
                btnFs.innerHTML = '<i data-lucide="minimize" style="width:18px;height:18px;"></i>';
            } else {
                document.exitFullscreen();
                btnFs.innerHTML = '<i data-lucide="maximize" style="width:18px;height:18px;"></i>';
            }
            if(typeof lucide !== 'undefined') lucide.createIcons();
        });

        // Native cursor always visible
        document.addEventListener('fullscreenchange', () => {
            document.body.style.cursor = 'default';
        });
    }
});
</script>

<?php foreach ($currentGame['script'] as $script): ?>
    <script src="<?= $script ?>"></script>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
