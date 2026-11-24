<?php
require_once __DIR__ . '/../includes/header.php';

// Fetch news timeframe setting
$tfHours = 0;
try {
    $tfStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'news_timeframe_hours'");
    if ($row = $tfStmt->fetch()) {
        $tfHours = (int)$row['setting_value'];
    }
} catch (Exception $e) {}

// Fetch news
try {
    if ($tfHours > 0) {
        $newsStmt = $pdo->prepare("
            SELECT n.*, u.username as author_name, u.avatar as author_avatar
            FROM news n
            LEFT JOIN users u ON n.published_by = u.id
            WHERE n.created_at >= NOW() - INTERVAL ? HOUR
            ORDER BY n.created_at DESC
            LIMIT 30
        ");
        $newsStmt->execute([$tfHours]);
    } else {
        $newsStmt = $pdo->query("
            SELECT n.*, u.username as author_name, u.avatar as author_avatar
            FROM news n
            LEFT JOIN users u ON n.published_by = u.id
            ORDER BY n.created_at DESC
            LIMIT 30
        ");
    }
    $newsList = $newsStmt->fetchAll();
} catch (Exception $e) {
    $newsList = [];
}
?>

<div style="max-width:820px; margin:0 auto; padding:0 0 60px;">
    <!-- Header -->
    <div style="margin-bottom:36px;">
        <h1 style="font-size:2.2rem; font-weight:800; color:#fff; margin-bottom:8px; display:flex; align-items:center; gap:12px;">
            <span style="font-size:2rem;">📰</span>
            <span>Berita Terkini</span>
        </h1>
        <p style="color:var(--text-muted); font-size:0.9rem; margin:0; line-height:1.6;">Update terbaru dari admin komunitas Gummy. Stay tuned!</p>
    </div>

    <?php if (empty($newsList)): ?>
        <div class="glass-panel" style="text-align:center; padding:60px 30px; border:1px solid var(--glass-border);">
            <div style="font-size:3rem; margin-bottom:16px;">📭</div>
            <h3 style="color:#fff; margin-bottom:8px;">Belum ada berita</h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Admin belum memposting berita apapun. Cek lagi nanti!</p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:24px;">
            <?php foreach ($newsList as $news): ?>
                <article class="glass-panel" style="padding:0; overflow:hidden; border:1px solid var(--glass-border); transition:transform 0.2s, box-shadow 0.2s;" onmouseenter="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.4)'" onmouseleave="this.style.transform='none'; this.style.boxShadow=''">
                    <?php if (!empty($news['image_url'])): ?>
                        <div style="width:100%; text-align:center; background:rgba(0,0,0,0.3); border-bottom:1px solid var(--glass-border); padding:12px; display:flex; align-items:center; justify-content:center;">
                            <img src="<?= htmlspecialchars($news['image_url']) ?>" alt="<?= htmlspecialchars($news['title']) ?>" style="max-height:450px; width:auto; max-width:100%; object-fit:contain; border-radius:8px; display:block;">
                        </div>
                    <?php endif; ?>
                    <div style="padding:24px 28px;">
                        <h2 style="font-size:1.3rem; color:#fff; margin:0 0 12px; font-weight:700; line-height:1.35;"><?= htmlspecialchars($news['title']) ?></h2>
                        <div style="white-space:pre-line; color:#d0d0d0; font-size:0.9rem; line-height:1.75; margin-bottom:18px;"><?= htmlspecialchars($news['content']) ?></div>

                        <?php if (!empty($news['link_url'])): ?>
                            <div style="margin-bottom:20px;">
                                <a href="<?= htmlspecialchars($news['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; font-size:0.85rem; text-decoration:none; border-radius:8px; font-weight:600;">
                                    <i data-lucide="external-link" style="width:16px;height:16px;"></i> Kunjungi Link / Selengkapnya
                                </a>
                            </div>
                        <?php endif; ?>

                        <div style="display:flex; align-items:center; gap:10px; padding-top:14px; border-top:1px solid rgba(255,255,255,0.05);">
                            <img src="uploads/avatars/<?= htmlspecialchars($news['author_avatar'] ?: 'default.png') ?>" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid var(--glass-border);" onerror="this.src='https://ui-avatars.com/api/?name=Admin&size=28&background=random'">
                            <span style="color:var(--text-muted); font-size:0.8rem;">
                                <strong style="color:#ccc;"><?= htmlspecialchars($news['author_name'] ?? 'Admin') ?></strong>
                                &nbsp;·&nbsp; <?= date('d M Y, H:i', strtotime($news['created_at'])) ?> WIB
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
