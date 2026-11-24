<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="shop-header" style="text-align: center; margin: 40px 0;">
    <h1 class="gradient-text" style="font-size: 3rem;">Shop</h1>
    <p class="text-muted">Spend your hard-earned coins on profile customizations.</p>
    
    <div style="display: inline-flex; align-items: center; gap: 10px; background: rgba(255,215,0,0.1); border: 1px solid gold; padding: 10px 20px; border-radius: 20px; margin-top: 20px; color: gold; font-weight: bold;">
        <i data-lucide="coins"></i> Balance: 1,250 Coins
    </div>
</div>

<div class="shop-grid bento-grid" style="grid-template-columns: repeat(4, 1fr);">
    <!-- Item 1 -->
    <div class="bento-item glass-card" style="align-items: center; text-align: center;">
        <div style="width: 80px; height: 80px; border-radius: 50%; border: 4px solid var(--primary); margin-bottom: 20px; box-shadow: 0 0 15px var(--primary-glow);"></div>
        <h3>Neon Frame</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">A glowing purple frame.</p>
        <button class="btn-secondary magnetic" style="width: 100%;"><i data-lucide="coins" style="width: 14px; height:14px; display:inline-block; vertical-align:middle;"></i> 500</button>
    </div>
    
    <!-- Item 2 -->
    <div class="bento-item glass-card" style="align-items: center; text-align: center;">
        <div style="width: 80px; height: 80px; border-radius: 50%; border: 4px solid var(--secondary); margin-bottom: 20px; box-shadow: 0 0 15px rgba(0,229,255,0.5);"></div>
        <h3>Cyan Frame</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">A sleek blue frame.</p>
        <button class="btn-secondary magnetic" style="width: 100%;"><i data-lucide="coins" style="width: 14px; height:14px; display:inline-block; vertical-align:middle;"></i> 500</button>
    </div>
    
    <!-- Item 3 -->
    <div class="bento-item glass-card" style="align-items: center; text-align: center;">
        <div style="width: 80px; height: 80px; background: linear-gradient(45deg, var(--primary), var(--accent)); margin-bottom: 20px; border-radius: 8px;"></div>
        <h3>Sunset Theme</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">Profile background.</p>
        <button class="btn-primary magnetic" style="width: 100%;"><i data-lucide="coins" style="width: 14px; height:14px; display:inline-block; vertical-align:middle;"></i> 1,000</button>
    </div>
    
    <!-- Item 4 -->
    <div class="bento-item glass-card" style="align-items: center; text-align: center; opacity: 0.5;">
        <div style="width: 80px; height: 80px; border-radius: 50%; border: 4px dashed gold; margin-bottom: 20px;"></div>
        <h3>Golden Crown</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">Exclusive admin item.</p>
        <button class="btn-secondary" style="width: 100%; cursor: not-allowed;" disabled>Locked</button>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
