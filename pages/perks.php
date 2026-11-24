<?php
require_once __DIR__ . '/../includes/header.php';

$user_id   = $_SESSION['user_id'] ?? null;
$coins     = 0;
$owned_ids = [];

if ($user_id) {
    try {
        $cs = $pdo->prepare("SELECT coins FROM users WHERE id=?");
        $cs->execute([$user_id]);
        $coins = (int)($cs->fetchColumn() ?? 0);
        $os = $pdo->prepare("SELECT item_id FROM user_inventory WHERE user_id=?");
        $os->execute([$user_id]);
        $owned_ids = array_map('intval', $os->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {}
}

// Purchase handler
$msg = ''; $msg_type = '';
if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_item'])) {
    $item_id = (int)$_POST['buy_item'];
    try {
        $is = $pdo->prepare("SELECT * FROM shop_items WHERE id=?");
        $is->execute([$item_id]);
        $item = $is->fetch();
        if (!$item) {
            $msg = "Item tidak ditemukan."; $msg_type = 'error';
        } elseif (in_array($item_id, $owned_ids)) {
            $msg = "Kamu sudah memiliki item ini!"; $msg_type = 'warn';
        } elseif ($coins < $item['price']) {
            $msg = "Koin tidak cukup! Butuh " . number_format($item['price']) . " koin."; $msg_type = 'error';
        } else {
            $pdo->prepare("UPDATE users SET coins=coins-? WHERE id=?")->execute([$item['price'], $user_id]);
            $pdo->prepare("INSERT IGNORE INTO user_inventory (user_id,item_id) VALUES (?,?)")->execute([$user_id, $item_id]);
            $_SESSION['coins'] = $coins - $item['price'];
            $coins -= $item['price'];
            $owned_ids[] = $item_id;
            $msg = "✅ <strong>" . htmlspecialchars($item['name']) . "</strong> berhasil dibeli! Equip di Profil → Inventory.";
            $msg_type = 'success';
        }
    } catch (Exception $e) {
        $msg = "Terjadi kesalahan."; $msg_type = 'error';
    }
}

// Fetch ONLY border and nameplate — no character
$borders = [];
$nameplates = [];
try {
    $rows = $pdo->query("SELECT * FROM shop_items WHERE category IN ('border','nameplate') ORDER BY FIELD(rarity,'common','rare','epic','legendary'), price ASC")->fetchAll();
    foreach ($rows as $r) {
        if ($r['category'] === 'border') $borders[] = $r;
        else $nameplates[] = $r;
    }
} catch (Exception $e) {}

$rc  = ['common'=>'#a0a5b5','rare'=>'#00e5ff','epic'=>'#8a2be2','legendary'=>'#ffd700'];
$rl  = ['common'=>'Common','rare'=>'Rare','epic'=>'Epic','legendary'=>'Legendary'];
$rbg = ['common'=>'rgba(255,255,255,0.03)','rare'=>'rgba(0,229,255,0.05)','epic'=>'rgba(138,43,226,0.07)','legendary'=>'rgba(255,215,0,0.07)'];
?>
<style>
@keyframes aura-pulse{0%,100%{opacity:1}50%{opacity:0.6}}
@keyframes rainbow-glow{
    0%{filter:drop-shadow(0 0 5px rgba(255,0,100,0.6)) drop-shadow(0 0 10px rgba(0,200,255,0.5))}
    33%{filter:drop-shadow(0 0 5px rgba(100,255,0,0.6)) drop-shadow(0 0 10px rgba(255,200,0,0.5))}
    66%{filter:drop-shadow(0 0 5px rgba(100,0,255,0.6)) drop-shadow(0 0 10px rgba(255,100,0,0.5))}
    100%{filter:drop-shadow(0 0 5px rgba(255,0,100,0.6)) drop-shadow(0 0 10px rgba(0,200,255,0.5))}
}
@keyframes boom-pulse{
    0%,80%,100%{box-shadow:0 0 0 3px rgba(255,50,0,0.18),0 0 14px rgba(255,50,0,0.45)}
    86%{box-shadow:0 0 0 8px rgba(255,150,0,0.5),0 0 28px rgba(255,100,0,0.7);transform:scale(1.06)}
}
.rainbow-halo-preview{animation:rainbow-glow 2.5s linear infinite}
.shop-tab-btn{padding:10px 22px;border-radius:30px;cursor:pointer;font-size:0.88rem;font-weight:600;transition:all 0.25s;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.04);color:var(--text-muted)}
.shop-tab-btn.active{background:linear-gradient(135deg,rgba(125,196,146,0.3),rgba(180,221,192,0.15));border-color:rgba(125,196,146,0.5);color:#fff;box-shadow:0 0 14px rgba(125,196,146,0.25)}
.shop-tab-btn:hover:not(.active){background:rgba(255,255,255,0.09);color:#fff}
.item-card{position:relative;overflow:hidden;border-radius:18px;display:flex;flex-direction:column;height:300px;transition:transform 0.22s,box-shadow 0.22s}
.item-card:hover{transform:translateY(-5px)}
.card-info{padding:14px 16px 16px;display:flex;flex-direction:column;flex-grow:1;overflow:hidden}
.card-desc{color:var(--text-muted);font-size:0.76rem;line-height:1.5;height:42px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;margin-bottom:10px;flex-shrink:0}
.rarity-legendary .item-card:hover{box-shadow:0 16px 40px rgba(255,215,0,0.18)!important}
.rarity-epic      .item-card:hover{box-shadow:0 16px 40px rgba(138,43,226,0.18)!important}
.rarity-rare      .item-card:hover{box-shadow:0 16px 40px rgba(0,229,255,0.15)!important}
.rarity-common    .item-card:hover{box-shadow:0 12px 32px rgba(0,0,0,0.4)!important}
.preview-area{height:120px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;background:linear-gradient(135deg,rgba(0,0,0,0.55),rgba(10,20,12,0.85));border-bottom:1px solid rgba(255,255,255,0.05);flex-shrink:0}
.divine-crown-wrap{position:relative;display:inline-block}
.divine-crown-wrap::before{content:'👑';position:absolute;top:-22px;left:50%;transform:translateX(-50%);font-size:1.3rem;line-height:1;filter:drop-shadow(0 0 5px rgba(255,215,0,0.7));animation:aura-pulse 1.8s ease-in-out infinite;z-index:2}
.aura-bg{position:absolute;inset:0;opacity:0;transition:opacity 0.3s}
.item-card:hover .aura-bg{opacity:1}
.owned-badge{position:absolute;top:10px;right:10px;z-index:3;background:rgba(0,255,136,0.2);color:#00ff88;font-size:0.8rem;font-weight:800;width:24px;height:24px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid rgba(0,255,136,0.4)}
</style>

<div style="max-width:1100px;margin:0 auto;padding:0 0 80px;">

    <!-- Header -->
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
        <div>
            <h1 style="font-size:2.1rem;font-weight:800;color:#fff;margin:0 0 5px;">Shop</h1>
            <p style="color:var(--text-muted);font-size:0.88rem;margin:0;">Personalisasi foto profil dan nama pengguna dengan item eksklusif</p>
        </div>
        <?php if ($user_id): ?>
        <div style="background:rgba(255,215,0,0.08);border:1.5px solid rgba(255,215,0,0.28);padding:11px 20px;border-radius:30px;color:#ffd700;font-weight:700;font-size:0.95rem;display:flex;align-items:center;gap:9px;">
            <i data-lucide="coins" style="width:18px;height:18px;"></i> <?= number_format($coins) ?> Koin
        </div>
        <?php else: ?>
        <a href="?page=login" class="btn-primary" style="padding:11px 22px;display:inline-flex;align-items:center;gap:8px;font-size:0.88rem;">
            <i data-lucide="log-in" style="width:15px;height:15px;"></i> Login untuk Membeli
        </a>
        <?php endif; ?>
    </div>

    <!-- Flash message -->
    <?php if ($msg): ?>
    <div id="shop-msg" style="background:<?= $msg_type==='success'?'rgba(0,255,136,0.08)':($msg_type==='error'?'rgba(255,59,48,0.08)':'rgba(255,200,0,0.08)') ?>;border:1px solid <?= $msg_type==='success'?'rgba(0,255,136,0.3)':($msg_type==='error'?'rgba(255,59,48,0.3)':'rgba(255,200,0,0.3)') ?>;color:<?= $msg_type==='success'?'#00ff88':($msg_type==='error'?'#ff8080':'#ffcc00') ?>;padding:12px 18px;border-radius:12px;margin-bottom:22px;font-size:0.88rem;">
        <?= $msg ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div style="display:flex;gap:10px;margin-bottom:28px;flex-wrap:wrap;">
        <button class="shop-tab-btn active" id="tab-border" onclick="showShopTab('border')">
            🔲 Avatar Border <span style="background:rgba(255,255,255,0.1);padding:2px 7px;border-radius:10px;font-size:0.72rem;margin-left:5px;"><?= count($borders) ?></span>
        </button>
        <button class="shop-tab-btn" id="tab-nameplate" onclick="showShopTab('nameplate')">
            🏷️ Nameplate <span style="background:rgba(255,255,255,0.1);padding:2px 7px;border-radius:10px;font-size:0.72rem;margin-left:5px;"><?= count($nameplates) ?></span>
        </button>
    </div>

<?php
function shopRenderCard($item, $owned_ids, $coins, $user_id, $rc, $rl, $rbg) {
    $owned  = in_array((int)$item['id'], $owned_ids);
    $rar    = $item['rarity'] ?? 'common';
    $rc_col = $rc[$rar] ?? '#fff';
    $rl_txt = $rl[$rar] ?? 'Common';
    $rbgcol = $rbg[$rar] ?? 'rgba(255,255,255,0.03)';
    $isBdr  = ($item['category'] === 'border');
    $isNp   = ($item['category'] === 'nameplate');
    $prevCss = htmlspecialchars($item['preview_css'] ?? '');
    $auraCss = !empty($item['aura_css']) ? htmlspecialchars($item['aura_css']) : '';
    $hasAura = !empty($item['aura_css']);
    $bdrColor = $owned ? 'rgba(0,255,136,0.35)' : ($hasAura && $rar==='legendary' ? 'rgba(255,215,0,0.22)' : ($hasAura && $rar==='epic' ? 'rgba(138,43,226,0.2)' : 'rgba(255,255,255,0.07)'));

    echo '<div class="rarity-'.$rar.'">';
    echo '<div class="item-card" style="border:1px solid '.$bdrColor.';background:'.$rbgcol.';">';

    // Badges
    echo '<div style="position:absolute;top:10px;left:10px;z-index:3;font-size:0.6rem;font-weight:800;color:'.$rc_col.';background:rgba(0,0,0,0.65);padding:3px 8px;border-radius:20px;border:1px solid '.$rc_col.'44;text-transform:uppercase;letter-spacing:0.5px;">'.$rl_txt.'</div>';
    if ($owned) echo '<div class="owned-badge" title="Dimiliki">✓</div>';
    elseif ($hasAura) echo '<div style="position:absolute;top:10px;right:10px;z-index:3;background:rgba(255,215,0,0.12);color:#ffd700;font-size:0.58rem;font-weight:800;padding:3px 8px;border-radius:20px;border:1px solid rgba(255,215,0,0.3);">✨ AURA</div>';

    // Preview
    echo '<div class="preview-area">';
    if ($hasAura) {
        $gc = $rar==='legendary'?'rgba(255,215,0,0.15)':($rar==='epic'?'rgba(138,43,226,0.15)':'rgba(0,229,255,0.12)');
        echo '<div class="aura-bg" style="background:radial-gradient(circle,'.$gc.' 0%,transparent 70%);"></div>';
    }
    if ($isBdr) {
        $pImg = htmlspecialchars($item['aura_css'] ?? '');
        echo '<div style="position:relative; width:72px; height:72px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">';
        echo '<div class="border-preview-wrap" style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#141e15,#202e22);overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:1.8rem;position:relative;z-index:1;">👤</div>';
        if ($pImg && strpos($pImg, 'assets/images/borders/') !== false) {
            echo '<div class="png-border-overlay" style="inset:-10px; background-image: url(\''.$pImg.'\');"></div>';
        }
        echo '</div>';
    } elseif ($isNp) {
        $crown = stripos($item['name'],'Divine Crown')!==false ? ' divine-crown-wrap' : '';
        $pCss = htmlspecialchars($item['preview_css'] ?? '');
        $aCss = htmlspecialchars($item['aura_css'] ?? '');
        echo '<div class="nameplate-preview'.$crown.'" style="padding:7px 18px;border-radius:28px;font-size:0.88rem;font-weight:700;max-width:88%;position:relative;z-index:1;'.$pCss.' '.$aCss.'">Username</div>';
    }
    echo '</div>';

    // Info
    echo '<div class="card-info">';
    echo '<h4 style="margin:0 0 4px;color:#fff;font-size:0.9rem;font-weight:700;line-height:1.3;">'.htmlspecialchars($item['name']).'</h4>';
    echo '<div class="card-desc">'.htmlspecialchars($item['description']).'</div>';
    echo '<div style="display:flex;align-items:center;gap:5px;color:#ffd700;font-weight:700;font-size:0.88rem;margin-bottom:10px;"><i data-lucide="coins" style="width:13px;height:13px;"></i> '.number_format($item['price']).'</div>';

    // Action
    if ($owned) {
        echo '<div style="background:rgba(0,255,136,0.08);color:#00ff88;padding:8px;border-radius:10px;font-size:0.95rem;font-weight:700;text-align:center;border:1px solid rgba(0,255,136,0.22);display:flex;align-items:center;justify-content:center;" title="Dimiliki">✓</div>';
    } elseif ($user_id) {
        $noCoin = $coins < $item['price'];
        $dis = $noCoin ? ' disabled' : '';
        $dSty = $noCoin ? ' opacity:0.38;cursor:not-allowed;' : '';
        echo '<form method="POST" style="margin:0;"><input type="hidden" name="buy_item" value="'.$item['id'].'"><button type="submit" class="btn-primary"'.$dis.' style="width:100%;padding:8px;font-size:0.8rem;display:flex;align-items:center;justify-content:center;gap:5px;'.$dSty.'"><i data-lucide="shopping-cart" style="width:12px;height:12px;"></i> Beli</button></form>';
    } else {
        echo '<a href="?page=login" style="display:block;background:rgba(255,255,255,0.04);border:1px solid var(--glass-border);color:var(--text-muted);padding:8px;border-radius:10px;font-size:0.78rem;text-align:center;text-decoration:none;">Login untuk Beli</a>';
    }
    echo '</div></div></div>';
}
?>

    <!-- BORDERS -->
    <div id="section-border">
        <div style="margin-bottom:20px;">
            <h2 style="margin:0 0 4px;color:#fff;font-size:1.1rem;font-weight:700;">🔲 Avatar Border</h2>
            <p style="margin:0;color:var(--text-muted);font-size:0.82rem;">Bingkai foto profil yang tampil di halaman profil dan members.</p>
        </div>
        <?php if (empty($borders)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:40px;">Belum ada border tersedia.</p>
        <?php else: ?>
        <div class="shop-items-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px;">
            <?php foreach ($borders as $item) shopRenderCard($item,$owned_ids,$coins,$user_id,$rc,$rl,$rbg); ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- NAMEPLATES -->
    <div id="section-nameplate" style="display:none;">
        <div style="margin-bottom:20px;">
            <h2 style="margin:0 0 4px;color:#fff;font-size:1.1rem;font-weight:700;">🏷️ Nameplate</h2>
            <p style="margin:0;color:var(--text-muted);font-size:0.82rem;">Hiasan nama yang tampil di profil — bukan tulisan nama itemnya, tapi efek bordernya.</p>
        </div>
        <?php if (empty($nameplates)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:40px;">Belum ada nameplate tersedia.</p>
        <?php else: ?>
        <div class="shop-items-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:16px;">
            <?php foreach ($nameplates as $item) shopRenderCard($item,$owned_ids,$coins,$user_id,$rc,$rl,$rbg); ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
function showShopTab(tab) {
    document.getElementById('section-border').style.display = (tab === 'border') ? 'block' : 'none';
    document.getElementById('section-nameplate').style.display = (tab === 'nameplate') ? 'block' : 'none';
    document.getElementById('tab-border').classList.toggle('active', tab === 'border');
    document.getElementById('tab-nameplate').classList.toggle('active', tab === 'nameplate');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
    // Scroll ke posisi setelah form submit (beli item)
    const flashMsg = document.getElementById('shop-msg');
    if (flashMsg) {
        flashMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
            flashMsg.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            flashMsg.style.opacity = '0';
            flashMsg.style.transform = 'translateY(-10px)';
            setTimeout(() => flashMsg.remove(), 400);
        }, 4000);
    }
    // Restore active tab from URL hash
    const hash = window.location.hash;
    if (hash === '#nameplate') showShopTab('nameplate');
});
// Preserve scroll position on form submit
document.querySelectorAll('form').forEach(f => {
    f.addEventListener('submit', () => {
        sessionStorage.setItem('shopScrollY', window.scrollY);
        sessionStorage.setItem('shopTab', document.querySelector('.shop-tab-btn.active')?.id?.replace('tab-','') || 'border');
    });
});
window.addEventListener('load', () => {
    const sy = sessionStorage.getItem('shopScrollY');
    const tab = sessionStorage.getItem('shopTab');
    if (tab) showShopTab(tab);
    if (sy) window.scrollTo({ top: parseInt(sy), behavior: 'instant' });
    sessionStorage.removeItem('shopScrollY');
    sessionStorage.removeItem('shopTab');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
