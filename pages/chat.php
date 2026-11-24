<?php
// pages/chat.php
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user';
$username = $_SESSION['username'] ?? 'Guest';
?>

<!-- Voice Preview Modal (tampil sebelum join) -->
<div id="voice-preview-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.75); z-index:2000; align-items:center; justify-content:center; backdrop-filter:blur(8px);">
    <div class="glass-panel" style="width:100%; max-width:440px; margin:15px; padding:30px; border:1px solid var(--glass-border); box-shadow:0 20px 60px rgba(0,0,0,0.8);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
            <div style="width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--secondary)); display:flex; align-items:center; justify-content:center;">
                <i data-lucide="mic" style="width:20px;height:20px;color:#fff;"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size:1.1rem; color:#fff;" id="voice-preview-title">Lounge (Voice)</h3>
                <p style="margin:0; font-size:0.8rem; color:var(--text-muted);">Saluran Suara</p>
            </div>
        </div>
        <div style="margin-bottom:20px;">
            <p style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Pengguna di dalam</p>
            <div id="voice-preview-participants" style="display:flex; flex-direction:column; gap:8px; min-height:50px;">
                <div style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:15px 0;">Kosong — belum ada yang bergabung</div>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="confirmJoinVoice()" class="btn-primary" style="flex:1; padding:12px; font-size:0.9rem; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i data-lucide="phone" style="width:16px;height:16px;"></i> Bergabung
            </button>
            <button onclick="closeVoicePreview()" class="btn-secondary" style="padding:12px 18px; font-size:0.9rem;">Batal</button>
        </div>
    </div>
</div>

<div class="chat-dashboard-wrapper" style="display:flex; height:calc(100vh - 130px); margin-top:20px; border-radius:16px; border:1px solid var(--glass-border); overflow:hidden; background:rgba(10,10,20,0.4); backdrop-filter:blur(10px);">
    <!-- Sidebar Kiri: Daftar Saluran -->
    <div class="chat-sidebar" style="width:220px; border-right:1px solid var(--glass-border); display:flex; flex-direction:column; background:rgba(0,0,0,0.3); flex-shrink:0;">
        <div style="padding:16px; border-bottom:1px solid rgba(255,255,255,0.05); font-weight:800; color:#fff; font-size:0.95rem; letter-spacing:0.5px;">
            💬 GUMMY LOUNGE
        </div>
        <div style="flex-grow:1; overflow-y:auto; padding:15px 10px; display:flex; flex-direction:column; gap:20px;">
            <div>
                <small style="color:var(--text-muted); text-transform:uppercase; font-weight:bold; font-size:0.7rem; letter-spacing:1px; display:block; margin-bottom:8px;">Saluran Teks</small>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <button class="channel-btn active" id="chan-ngobrol" onclick="switchChannel('text','ngobrol','Ngobrol')">
                        <i data-lucide="message-square" style="width:16px;height:16px;"></i> Ngobrol
                    </button>
                    <button class="channel-btn" id="chan-media" onclick="switchChannel('text','media','Media')">
                        <i data-lucide="camera" style="width:16px;height:16px;"></i> Media
                    </button>
                </div>
            </div>
            <div>
                <small style="color:var(--text-muted); text-transform:uppercase; font-weight:bold; font-size:0.7rem; letter-spacing:1px; display:block; margin-bottom:8px;">Saluran Suara</small>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <button class="channel-btn" id="chan-voice-lounge" onclick="previewVoiceChannel('lounge','Lounge (Voice)')">
                        <i data-lucide="mic" style="width:16px;height:16px;"></i> Lounge
                    </button>
                    <button class="channel-btn" id="chan-voice-gaming" onclick="previewVoiceChannel('gaming','Gaming (Voice)')">
                        <i data-lucide="mic" style="width:16px;height:16px;"></i> Gaming
                    </button>
                    <button class="channel-btn" id="chan-voice-music" onclick="previewVoiceChannel('music','Music (Voice)')">
                        <i data-lucide="mic" style="width:16px;height:16px;"></i> Music
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Area Kerja Tengah -->
    <div class="chat-workspace" style="flex-grow:1; display:flex; flex-direction:column; background:transparent; position:relative;">
        <!-- Header Workspace -->
        <div class="chat-workspace-header" style="height:60px; border-bottom:1px solid var(--glass-border); display:flex; align-items:center; justify-content:space-between; padding:0 20px; background:rgba(0,0,0,0.15);">
            <div style="display:flex; align-items:center; gap:8px;">
                <button type="button" class="chat-channel-toggle-btn mobile-only-inline" onclick="toggleChatChannels()" aria-label="Ganti Saluran">
                    <i data-lucide="hash"></i> Saluran
                </button>
                <span style="color:var(--text-muted); font-size:1.1rem; font-weight:500;">#</span>
                <h3 class="text-white m-0" id="active-channel-title" style="font-size:1.1rem; font-weight:bold;">Ngobrol</h3>
            </div>
            <div style="display:flex; align-items:center; gap:16px; color:var(--text-muted);">
                <i data-lucide="pin" id="chat-pin-trigger" style="width:18px;height:18px;cursor:pointer;" title="Pesan Disematkan"></i>
            </div>
        </div>

        <!-- Konten 1: Text Chat Area -->
        <div id="text-chat-area" style="flex:1; min-height:0; display:flex; flex-direction:column; overflow:hidden; position:relative;">
            <div id="chat-box" style="flex:1; min-height:0; overflow-y:auto; overflow-x:hidden; padding:20px; display:flex; flex-direction:column; gap:15px;">
                <div style="text-align:center; color:var(--text-muted); padding-top:50px;">Memuat obrolan...</div>
            </div>
            
            <!-- Floating New Message Indicator -->
            <div id="new-msg-pill" class="chat-new-msg-pill" onclick="scrollToBottom(true)">
                <i data-lucide="arrow-down" style="width:14px;height:14px;"></i> <span>Pesan Baru</span>
            </div>

            <div class="chat-input-container" style="flex-shrink:0; padding:15px 20px; border-top:1px solid var(--glass-border); background:rgba(0,0,0,0.25); position:relative; z-index:5;">
                <?php if($user_id): ?>
                    <!-- Image preview before send -->
                    <div id="file-preview-bar" style="display:none; margin-bottom:10px; padding:10px 12px; background:rgba(255,255,255,0.04); border-radius:10px; border:1px solid var(--glass-border); align-items:center; gap:12px;">
                        <img id="file-preview-img" src="" style="height:60px; border-radius:8px; object-fit:cover; max-width:120px;">
                        <div style="flex-grow:1;">
                            <p id="file-preview-name" style="margin:0; font-size:0.8rem; color:#fff;"></p>
                            <p style="margin:0; font-size:0.75rem; color:var(--text-muted);">Siap dikirim</p>
                        </div>
                        <button onclick="cancelFileAttach()" style="background:transparent; border:none; color:#ff3b30; cursor:pointer; padding:4px;" title="Batal">
                            <i data-lucide="x" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                    <form id="chat-form" style="display:flex; gap:12px; align-items:center;">
                        <button type="button" id="chat-attachment-btn" style="background:transparent; border:none; color:var(--text-muted); cursor:pointer; padding:4px;" title="Lampirkan Gambar/Video">
                            <i data-lucide="paperclip" style="width:20px;height:20px;"></i>
                        </button>
                        <input type="file" id="chat-file-input" accept="image/*,video/*" style="display:none;">
                        <input type="text" id="chat-input" placeholder="Ketik pesan..." style="flex:1; padding:12px 16px; background:rgba(0,0,0,0.4); border:1px solid var(--glass-border); border-radius:10px; color:white; outline:none; font-size:0.9rem;" autocomplete="off">
                        <button type="submit" class="btn-primary" style="padding:12px 20px; border-radius:10px; font-size:0.95rem; display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                            Kirim <i data-lucide="send" style="width:14px;height:14px;"></i>
                        </button>
                    </form>
                    <div id="upload-status" style="display:none; font-size:0.75rem; color:var(--primary); margin-top:6px; font-weight:500;">
                        <i data-lucide="loader" style="width:12px;height:12px;"></i> Mengunggah file...
                    </div>
                    <!-- @Mention Popup -->
                    <div id="mention-popup" style="display:none; position:absolute; bottom:100%; left:0; right:0; margin-bottom:6px; background:#13151f; border:1px solid var(--glass-border); border-radius:12px; padding:6px; z-index:100; max-height:220px; overflow-y:auto; box-shadow:0 -8px 24px rgba(0,0,0,0.6);"></div>
                <?php else: ?>
                    <div style="text-align:center; color:var(--text-muted); font-size:0.9rem; padding:10px 0;">
                        <a href="?page=login" style="color:var(--primary); font-weight:bold;">Masuk</a> untuk mengirim pesan.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Konten 2: Voice Call Area -->
        <div id="voice-call-area" style="flex-grow:1; display:none; flex-direction:column; overflow:hidden; padding:30px; align-items:center; justify-content:flex-start; background:radial-gradient(circle at center, #1b0b2e 0%, #050510 100%); gap:25px; overflow-y:auto;">
            <div style="text-align:center; margin-top:10px;">
                <span class="badge" style="background:rgba(0,255,136,0.15); color:#00ff88; border:1px solid rgba(0,255,136,0.3); padding:6px 18px; border-radius:30px; font-size:0.8rem; font-weight:bold; letter-spacing:0.5px;">
                    ● TERHUBUNG KE SUARA
                </span>
                <p style="color:var(--text-muted); font-size:0.8rem; margin-top:8px;" id="voice-channel-label">Lounge</p>
            </div>

            <!-- Camera Video Area -->
            <div id="camera-area" style="display:none; width:100%; max-width:320px;">
                <div style="position:relative; border-radius:16px; overflow:hidden; background:#000; aspect-ratio:4/3; border:2px solid var(--primary); box-shadow:0 8px 24px rgba(0,0,0,0.6);">
                    <video id="local-video" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover;"></video>
                    
                    <!-- Overlay Avatar User (Bottom Left Inside Video Box) -->
                    <div style="position:absolute; bottom:8px; left:8px; z-index:10; display:flex; align-items:center; gap:6px; background:rgba(0,0,0,0.65); padding:4px 10px 4px 4px; border-radius:20px; backdrop-filter:blur(6px); border:1px solid rgba(255,255,255,0.18);">
                        <div style="width:26px; height:26px; border-radius:50%; overflow:hidden; border:1.5px solid var(--primary); flex-shrink:0;">
                            <img src="uploads/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? 'default.png') ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&size=26&background=2d5a38&color=fff'">
                        </div>
                        <span style="font-size:0.75rem; color:#fff; font-weight:600; white-space:nowrap;">
                            <?= htmlspecialchars($username) ?> (Anda)
                        </span>
                    </div>

                    <button id="btn-flip-cam" onclick="flipCamera()" style="position:absolute; top:8px; right:8px; z-index:10; background:rgba(0,0,0,0.6); border:none; color:#fff; border-radius:50%; width:32px; height:32px; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Putar Kamera">
                        <i data-lucide="refresh-cw" style="width:14px;height:14px;"></i>
                    </button>
                </div>
            </div>

            <!-- Participants grid -->
            <div id="voice-participants-grid" style="display:flex; flex-wrap:wrap; gap:20px; justify-content:center; max-width:600px;">
                <div class="voice-user-box text-center">
                    <div class="voice-avatar-ring" id="voice-ring-me" style="width:90px; height:90px; border-radius:50%; padding:3px; border:3px solid rgba(255,255,255,0.05); margin:0 auto; position:relative; transition:all 0.3s ease;">
                        <img src="uploads/avatars/<?= htmlspecialchars($_SESSION['avatar'] ?? 'default.png') ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($username) ?>'">
                        <div id="mic-off-badge-me" style="display:none; position:absolute; bottom:0; right:0; background:#ff3b30; border-radius:50%; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border:2px solid #0f111a;">
                            <i data-lucide="mic-off" style="width:10px;height:10px;color:#fff;"></i>
                        </div>
                    </div>
                    <p style="color:#fff; margin-top:8px; font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($username) ?> (Anda)</p>
                </div>
            </div>

            <!-- Controls bar -->
            <div class="voice-controls-bar" style="display:flex; gap:12px; padding:14px 28px; border-radius:50px; background:rgba(0,0,0,0.7); border:1px solid rgba(255,255,255,0.08); box-shadow:0 10px 30px rgba(0,0,0,0.6); position:sticky; bottom:0;">
                <button class="voice-ctrl-btn" id="btn-voice-mute" onclick="toggleMic()" title="Mute/Unmute Mikrofon">
                    <i data-lucide="mic" id="voice-icon-mute" style="width:18px;height:18px;"></i>
                </button>
                <button class="voice-ctrl-btn" id="btn-voice-deafen" onclick="toggleDeafen()" title="Bisu/Dengarkan">
                    <i data-lucide="volume-2" id="voice-icon-deafen" style="width:18px;height:18px;"></i>
                </button>
                <button class="voice-ctrl-btn" id="btn-voice-camera" onclick="toggleCamera()" title="Kamera On/Off">
                    <i data-lucide="video" id="voice-icon-camera" style="width:18px;height:18px;"></i>
                </button>
                <button class="voice-ctrl-btn" style="background:#ff3b30; color:#fff;" onclick="disconnectVoice()" title="Putuskan Sambungan">
                    <i data-lucide="phone-off" style="width:18px;height:18px;"></i>
                </button>
            </div>
        </div>
    </div>

    </div>
</div>

<!-- Pinned Messages Modal -->
<div class="modal-overlay" id="pins-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:1000; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
    <div class="glass-panel p-4" style="width:100%; max-width:480px; margin:15px; box-shadow:0 15px 35px rgba(0,0,0,0.8); border:1px solid var(--glass-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; color:#fff; font-weight:bold; font-size:1.1rem;">📌 Pesan Disematkan</h3>
            <button class="btn-secondary" id="btn-close-pins" style="padding:6px 12px; font-size:0.85rem;">Tutup</button>
        </div>
        <div style="display:flex; flex-direction:column; gap:10px; max-height:300px; overflow-y:auto;">
            <div style="padding:12px; background:rgba(255,255,255,0.03); border-radius:8px; border:1px solid rgba(255,255,255,0.05);">
                <p style="color:var(--primary); margin:0; font-size:0.8rem; font-weight:bold;">AdminGummy [Lv. 15]</p>
                <p style="color:#fff; margin:6px 0 0; font-size:0.85rem;">Selamat datang di komunitas Gummy! Tetap tertib saat mengobrol.</p>
            </div>
        </div>
    </div>
</div>

<style>
.channel-btn {
    width:100%; background:transparent; border:none; color:var(--text-muted);
    padding:10px 12px; border-radius:8px; font-size:0.85rem; font-weight:500;
    display:flex; align-items:center; gap:8px; cursor:pointer; text-align:left; transition:all 0.2s ease;
}
.channel-btn:hover, .channel-btn.active { background:rgba(255,255,255,0.07); color:#fff; }
.voice-ctrl-btn {
    width:46px; height:46px; border-radius:50%; background:rgba(255,255,255,0.1);
    border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease;
}
.voice-ctrl-btn:hover { background:rgba(255,255,255,0.2); transform:scale(1.08); }
.voice-ctrl-btn.active-mute { background:#ff3b30 !important; }
.active-talking-glow {
    border-color:#00ff88 !important;
    box-shadow:0 0 18px rgba(0,255,136,0.5);
    animation:voice-pulse 1.5s infinite ease-in-out;
}
@keyframes voice-pulse {
    0% { transform:scale(1); } 50% { transform:scale(1.03); } 100% { transform:scale(1); }
}
.chat-message-item:hover .message-delete-btn { opacity:1; }
.message-delete-btn { opacity:0; transition:opacity 0.2s ease; }
#file-preview-bar { display:none; }
#file-preview-bar.show { display:flex !important; }

/* Scrollbar tipis, modern, rounded match Dark Matcha Gummy */
#chat-box {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    scrollbar-width: thin !important;
    scrollbar-color: rgba(93, 219, 133, 0.45) rgba(0, 0, 0, 0.2) !important;
    scroll-behavior: smooth;
}
#chat-box::-webkit-scrollbar {
    width: 6px !important;
    display: block !important;
}
#chat-box::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}
#chat-box::-webkit-scrollbar-thumb {
    background: rgba(93, 219, 133, 0.45);
    border-radius: 10px;
    transition: background 0.2s ease;
}
#chat-box::-webkit-scrollbar-thumb:hover {
    background: rgba(93, 219, 133, 0.8);
}

/* Floating "Pesan Baru" indicator badge */
.chat-new-msg-pill {
    position: absolute;
    bottom: 80px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, var(--primary), #234c2b);
    color: #fff;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(0,0,0,0.6), 0 0 12px var(--primary-glow);
    display: none;
    align-items: center;
    gap: 8px;
    z-index: 50;
    border: 1.5px solid rgba(255,255,255,0.25);
    animation: pill-bounce 1.5s infinite ease-in-out;
    user-select: none;
}
.chat-new-msg-pill:hover {
    background: linear-gradient(135deg, #74e598, #2e6138);
    transform: translateX(-50%) scale(1.05);
}
@keyframes pill-bounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-4px); }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const attachmentBtn = document.getElementById('chat-attachment-btn');
    const fileInput = document.getElementById('chat-file-input');
    const uploadStatus = document.getElementById('upload-status');
    const filePreviewBar = document.getElementById('file-preview-bar');
    const filePreviewImg = document.getElementById('file-preview-img');
    const filePreviewName = document.getElementById('file-preview-name');

    // Modal toggles
    document.getElementById("chat-pin-trigger").addEventListener("click", () => {
        document.getElementById("pins-modal").style.display = "flex";
    });
    document.getElementById("btn-close-pins").addEventListener("click", () => {
        document.getElementById("pins-modal").style.display = "none";
    });

    let currentMode = 'text';
    let currentChannel = 'ngobrol';
    let lastMsgId = 0;
    let pendingFile = null;

    // ── Restore voice session if user was in voice before navigating away ──
    const savedVoiceChannel = sessionStorage.getItem('gummy_voice_channel');
    const savedVoiceTitle   = sessionStorage.getItem('gummy_voice_title');
    if (savedVoiceChannel) {
        // Mark the voice button as active
        const vBtn = document.getElementById('chan-voice-' + savedVoiceChannel);
        if (vBtn) vBtn.classList.add('active');
        document.querySelectorAll('.channel-btn:not(#chan-voice-' + savedVoiceChannel + ')').forEach(b => b.classList.remove('active'));
        // Auto-enter voice area
        setTimeout(() => {
            currentMode = 'voice';
            currentChannel = savedVoiceChannel;
            document.getElementById('active-channel-title').innerText = savedVoiceTitle || savedVoiceChannel;
            document.getElementById('text-chat-area').style.display = 'none';
            document.getElementById('voice-call-area').style.display = 'flex';
            document.getElementById('voice-channel-label').textContent = savedVoiceTitle || savedVoiceChannel;
            window._voiceCurrentChannel = savedVoiceChannel;
            // Re-init mic without re-joining DB (already joined)
            navigator.mediaDevices.getUserMedia({ audio: true, video: false })
                .then(stream => { localStream = stream; setupTalkDetection(); })
                .catch(() => {});
            // Restart ping
            if (window._voicePingInterval) clearInterval(window._voicePingInterval);
            window._voicePingInterval = setInterval(() => {
                if (window._voiceCurrentChannel) {
                    fetch('api/get_voice_participants.php?channel=' + encodeURIComponent(window._voiceCurrentChannel) + '&action=ping');
                }
            }, 10000);
        }, 200);
    }

    // --- File Attachment ---
    if (attachmentBtn && fileInput) {
        attachmentBtn.addEventListener("click", () => fileInput.click());

        fileInput.addEventListener("change", () => {
            if (!fileInput.files.length) return;
            const file = fileInput.files[0];
            pendingFile = file;

            // Show preview
            const isImage = file.type.startsWith('image/');
            if (isImage) {
                const reader = new FileReader();
                reader.onload = (e) => { filePreviewImg.src = e.target.result; };
                reader.readAsDataURL(file);
                filePreviewImg.style.display = 'block';
            } else {
                filePreviewImg.style.display = 'none';
            }
            filePreviewName.textContent = file.name;
            filePreviewBar.classList.add('show');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    }

    window.cancelFileAttach = function() {
        pendingFile = null;
        fileInput.value = '';
        filePreviewBar.classList.remove('show');
    };

    // --- Channel Switching ---
    window.toggleChatChannels = function() {
        const sb = document.querySelector('.chat-sidebar');
        if (sb) {
            sb.classList.toggle('mobile-open');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    window.switchChannel = function(mode, slug, title) {
        document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.chat-sidebar')?.classList.remove('mobile-open');
        currentMode = mode;
        currentChannel = slug;

        const btnId = mode === 'text' ? 'chan-' + slug : 'chan-voice-' + slug;
        const btn = document.getElementById(btnId);
        if (btn) btn.classList.add('active');

        document.getElementById('active-channel-title').innerText = title;

        if (mode === 'text') {
            document.getElementById('text-chat-area').style.display = 'flex';
            document.getElementById('voice-call-area').style.display = 'none';
            lastMsgId = 0;
            chatBox.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding-top:50px;">Memuat obrolan...</div>';
            fetchMessages();
        } else {
            document.getElementById('text-chat-area').style.display = 'none';
            const vca = document.getElementById('voice-call-area');
            vca.style.display = 'flex';
            document.getElementById('voice-channel-label').textContent = title;
            initVoice(slug);
        }
    };

    // --- Voice Preview Modal ---
    let pendingVoiceSlug = '';
    let pendingVoiceTitle = '';

    window.previewVoiceChannel = function(slug, title) {
        <?php if(!$user_id): ?>
        alert("Kamu harus login terlebih dahulu untuk join voice!");
        window.location.href = "?page=login";
        return;
        <?php endif; ?>
        
        // Jika user sudah aktif di voice channel ini → langsung masuk tanpa modal
        if (window._voiceCurrentChannel === slug) {
            // Pastikan tampilan voice sudah aktif
            if (currentMode !== 'voice' || document.getElementById('voice-call-area').style.display === 'none') {
                document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('active'));
                const btn = document.getElementById('chan-voice-' + slug);
                if (btn) btn.classList.add('active');
                currentMode = 'voice';
                currentChannel = slug;
                document.getElementById('active-channel-title').innerText = title;
                document.getElementById('text-chat-area').style.display = 'none';
                document.getElementById('voice-call-area').style.display = 'flex';
                document.getElementById('voice-channel-label').textContent = title;
            }
            return; // sudah di dalam, tidak perlu modal
        }

        // Jika sudah di voice lain → tanya konfirmasi
        if (window._voiceCurrentChannel && window._voiceCurrentChannel !== slug) {
            if (!confirm('Kamu sedang di voice "' + window._voiceCurrentChannel + '". Pindah ke "' + title + '"?')) return;
            stopVoiceStreams();
        }

        pendingVoiceSlug = slug;
        pendingVoiceTitle = title;
        document.getElementById('voice-preview-title').textContent = title;

        const pp = document.getElementById('voice-preview-participants');
        pp.innerHTML = '<div style="color:var(--text-muted); font-size:0.82rem; text-align:center; padding:12px;">Memuat...</div>';
        document.getElementById('voice-preview-modal').style.display = 'flex';

        // Fetch real participants
        fetch('api/get_voice_participants.php?channel=' + encodeURIComponent(slug))
            .then(r => r.json())
            .then(data => {
                if (data.participants && data.participants.length > 0) {
                    pp.innerHTML = data.participants.map(p => `
                        <div style="display:flex; align-items:center; gap:10px; padding:8px 12px; background:rgba(255,255,255,0.04); border-radius:10px;">
                            <div style="position:relative; flex-shrink:0;">
                                <img src="uploads/avatars/${p.avatar || 'default.png'}" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--primary);" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.username)}&size=34&background=random'">
                                <span style="position:absolute; bottom:0; right:0; width:9px; height:9px; border-radius:50%; background:#00ff88; border:2px solid #13151f;"></span>
                            </div>
                            <div>
                                <p style="margin:0; color:#fff; font-size:0.85rem; font-weight:600;">${p.username}</p>
                                <p style="margin:0; color:var(--text-muted); font-size:0.74rem;">Lv. ${p.level}</p>
                            </div>
                        </div>
                    `).join('');
                } else {
                    pp.innerHTML = '<div style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:15px 0; display:flex; align-items:center; justify-content:center; gap:8px;"><i data-lucide="mic-off" style="width:16px;height:16px;"></i> Kosong — belum ada yang bergabung</div>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            })
            .catch(() => {
                pp.innerHTML = '<div style="color:var(--text-muted); font-size:0.85rem; text-align:center; padding:15px 0;">Tidak dapat memuat peserta.</div>';
            });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    window.closeVoicePreview = function() {
        document.getElementById('voice-preview-modal').style.display = 'none';
    };

    window.confirmJoinVoice = function() {
        document.getElementById('voice-preview-modal').style.display = 'none';
        switchChannel('voice', pendingVoiceSlug, pendingVoiceTitle);
    };

    // --- Scroll & New Message Logic ---
    let userScrolledUp = false;

    // Detect if user scrolled up
    chatBox.addEventListener('scroll', () => {
        const threshold = 80; // px tolerance
        const isNearBottom = (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) <= threshold;
        userScrolledUp = !isNearBottom;
        if (isNearBottom) {
            hideNewMsgPill();
        }
    });

    window.scrollToBottom = function(smooth = true) {
        if (smooth) {
            chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
        } else {
            chatBox.scrollTop = chatBox.scrollHeight;
        }
        userScrolledUp = false;
        hideNewMsgPill();
    };

    function showNewMsgPill() {
        const pill = document.getElementById('new-msg-pill');
        if (pill) {
            pill.style.display = 'inline-flex';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function hideNewMsgPill() {
        const pill = document.getElementById('new-msg-pill');
        if (pill) {
            pill.style.display = 'none';
        }
    }

    // --- Messages ---
    function fetchMessages() {
        if (currentMode !== 'text') return;
        const isInitialLoad = (lastMsgId === 0);
        
        fetch('api/get_chat.php?channel=' + currentChannel + '&last_id=' + lastMsgId)
            .then(r => r.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    if (isInitialLoad) chatBox.innerHTML = '';
                    
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'chat-message-item';
                        div.style.cssText = 'background:rgba(255,255,255,0.03); padding:12px 16px; border-radius:12px; display:flex; gap:12px; align-items:flex-start; border:1px solid rgba(255,255,255,0.02);';
                        const color = msg.name_color || '#ffffff';
                        const levelBadge = `<span style="background:rgba(0,229,255,0.12); color:var(--primary); font-size:0.7rem; padding:2px 6px; border-radius:4px;">Lvl.${msg.level}</span>`;
                        let mediaContent = '';
                        if (msg.media_url) {
                            const isVideo = /\.(mp4|webm|mov)$/i.test(msg.media_url);
                            mediaContent = isVideo
                                ? `<video src="${msg.media_url}" controls style="max-width:100%; max-height:250px; border-radius:8px; margin-top:10px; display:block;"></video>`
                                : `<img src="${msg.media_url}" style="max-width:100%; max-height:250px; border-radius:8px; margin-top:10px; object-fit:contain; display:block; cursor:pointer;" onclick="window.open(this.src,'_blank')">`;
                        }
                        const myUserId = <?= $user_id ? (int)$user_id : 'null' ?>;
                        let deleteBtn = '';
                        if ('<?= $role ?>' === 'admin' || '<?= $role ?>' === 'moderator') {
                            deleteBtn = `<button class="message-delete-btn" onclick="deleteMessage(${msg.id})" style="background:transparent;border:none;color:#ff3b30;cursor:pointer;padding:2px;margin-left:auto;" title="Hapus"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>`;
                        } else if (myUserId && msg.user_id == myUserId) {
                            deleteBtn = `<button class="message-delete-btn" onclick="deleteOwnMessage(${msg.id})" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;padding:2px;margin-left:auto;" title="Batalkan pesan"><i data-lucide="x-circle" style="width:14px;height:14px;"></i></button>`;
                        }
                        div.innerHTML = `
                            <img src="uploads/avatars/${msg.avatar||'default.png'}" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(msg.username)}'">
                            <div style="flex-grow:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px; flex-wrap:wrap;">
                                    <strong style="color:${color}; font-size:0.9rem;">${msg.username}</strong>
                                    ${levelBadge}
                                    <span style="font-size:0.75rem; color:var(--text-muted);">${msg.time}</span>
                                </div>
                                <div style="color:var(--text-main); font-size:0.88rem; line-height:1.5; word-break:break-word;">${msg.message||''}</div>
                                ${mediaContent}
                            </div>
                            ${deleteBtn}`;
                        chatBox.appendChild(div);
                        lastMsgId = msg.id;
                    });
                    if (typeof lucide !== 'undefined') lucide.createIcons();

                    if (isInitialLoad) {
                        scrollToBottom(false);
                    } else {
                        if (!userScrolledUp) {
                            scrollToBottom(true);
                        } else {
                            showNewMsgPill();
                        }
                    }
                } else if (lastMsgId === 0 && chatBox.innerHTML.includes('Memuat')) {
                    chatBox.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding-top:50px; font-size:0.85rem;">Belum ada pesan. Mulai obrolan pertama!</div>';
                }
            });
    }

    function sendChatMessage(text, media) {
        userScrolledUp = false;
        fetch('api/send_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `message=${encodeURIComponent(text)}&channel=${currentChannel}&media_url=${encodeURIComponent(media||'')}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                fetchMessages();
                setTimeout(() => scrollToBottom(true), 100);
                if (data.xp_gained) {
                    const xpEl = document.createElement('div');
                    xpEl.textContent = '+' + data.xp_gained + ' XP';
                    xpEl.style.cssText = 'position:fixed; color:var(--primary); font-weight:bold; font-size:1rem; pointer-events:none; z-index:9999; left:50%; top:80%; transform:translateX(-50%);';
                    document.body.appendChild(xpEl);
                    if (typeof gsap !== 'undefined') {
                        gsap.to(xpEl, { y: -60, opacity: 0, duration: 1.5, onComplete: () => xpEl.remove() });
                    } else { setTimeout(() => xpEl.remove(), 1500); }
                }
            } else { alert(data.error || "Gagal mengirim."); }
        });
    }

    if (chatForm) {
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const text = chatInput.value.trim();

            if (pendingFile) {
                // Upload file first, then send
                const formData = new FormData();
                formData.append("file", pendingFile);
                uploadStatus.style.display = "block";
                fetch("api/upload_chat_media.php", { method: "POST", body: formData })
                    .then(r => r.json())
                    .then(data => {
                        uploadStatus.style.display = "none";
                        if (data.success) {
                            sendChatMessage(text, data.url);
                            chatInput.value = '';
                            cancelFileAttach();
                        } else {
                            alert(data.error || "Gagal mengunggah media.");
                        }
                    })
                    .catch(() => { uploadStatus.style.display = "none"; alert("Kesalahan saat mengunggah."); });
            } else {
                if (!text) return;
                chatInput.value = '';
                sendChatMessage(text, "");
            }
        });
    }

    window.deleteMessage = function(id) {
        if (!confirm("Hapus pesan ini?")) return;
        fetch('api/delete_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        }).then(r => r.json()).then(data => {
            if (data.success) { lastMsgId = 0; chatBox.innerHTML = ''; fetchMessages(); }
            else { alert(data.error || "Gagal menghapus."); }
        });
    };

    window.deleteOwnMessage = function(id) {
        if (!confirm("Batalkan dan hapus pesanmu?")) return;
        fetch('api/delete_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id + '&own=1'
        }).then(r => r.json()).then(data => {
            if (data.success) { lastMsgId = 0; chatBox.innerHTML = ''; fetchMessages(); }
            else { alert(data.error || "Gagal menghapus."); }
        });
    };

    // ── @Mention Autocomplete ──────────────────────────────────────────
    const mentionPopup = document.getElementById('mention-popup');
    let mentionUsers = [];
    let mentionFilter = '';
    let mentionStart = -1;

    // Fetch active member list periodically
    function fetchMemberList() {
        fetch('api/get_chat.php?action=members')
            .then(r => r.json())
            .then(data => {
                if (data.members) mentionUsers = data.members;
            }).catch(() => {});
    }
    fetchMemberList();
    setInterval(fetchMemberList, 30000);

    if (chatInput) {
        chatInput.addEventListener('input', () => {
            const val = chatInput.value;
            const cursor = chatInput.selectionStart;
            const textBefore = val.substring(0, cursor);
            const atIdx = textBefore.lastIndexOf('@');

            if (atIdx !== -1 && (atIdx === 0 || /\s/.test(textBefore[atIdx - 1]))) {
                mentionFilter = textBefore.substring(atIdx + 1).toLowerCase();
                mentionStart = atIdx;
                const filtered = mentionUsers.filter(u => u.username.toLowerCase().startsWith(mentionFilter));
                if (filtered.length > 0 && mentionFilter.length >= 0) {
                    showMentionPopup(filtered);
                } else {
                    hideMentionPopup();
                }
            } else {
                hideMentionPopup();
                mentionStart = -1;
            }
        });

        chatInput.addEventListener('keydown', (e) => {
            if (mentionPopup.style.display !== 'none') {
                const items = mentionPopup.querySelectorAll('.mention-item');
                let active = mentionPopup.querySelector('.mention-item.focused');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = active ? active.nextElementSibling || items[0] : items[0];
                    if (active) active.classList.remove('focused');
                    if (next) next.classList.add('focused');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = active ? active.previousElementSibling || items[items.length-1] : items[items.length-1];
                    if (active) active.classList.remove('focused');
                    if (prev) prev.classList.add('focused');
                } else if (e.key === 'Enter' || e.key === 'Tab') {
                    const focused = mentionPopup.querySelector('.mention-item.focused') || items[0];
                    if (focused) { e.preventDefault(); insertMention(focused.dataset.username); }
                } else if (e.key === 'Escape') {
                    hideMentionPopup();
                }
            }
        });
    }

    function showMentionPopup(users) {
        if (!mentionPopup) return;
        mentionPopup.innerHTML = users.slice(0,8).map(u => `
            <div class="mention-item" data-username="${u.username}" onclick="insertMention('${u.username}')" style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-radius:8px;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.08)'" onmouseleave="this.style.background=''">
                <img src="uploads/avatars/${u.avatar||'default.png'}" style="width:26px;height:26px;border-radius:50%;object-fit:cover;flex-shrink:0;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(u.username)}&size=26'">
                <span style="color:#fff;font-size:0.85rem;font-weight:600;">@${u.username}</span>
                <span style="color:var(--text-muted);font-size:0.74rem;margin-left:auto;">Lv.${u.level}</span>
            </div>
        `).join('');
        mentionPopup.style.display = 'block';
    }

    function hideMentionPopup() {
        if (mentionPopup) mentionPopup.style.display = 'none';
    }

    window.insertMention = function(username) {
        if (!chatInput || mentionStart === -1) return;
        const val = chatInput.value;
        const before = val.substring(0, mentionStart);
        const after = val.substring(chatInput.selectionStart);
        chatInput.value = before + '@' + username + ' ' + after;
        chatInput.focus();
        hideMentionPopup();
        mentionStart = -1;
    };

    document.addEventListener('click', (e) => {
        if (mentionPopup && !mentionPopup.contains(e.target) && e.target !== chatInput) {
            hideMentionPopup();
        }
    });

    setInterval(fetchMessages, 2500);
    fetchMessages();
});
</script>

<script>
// ===== VOICE SYSTEM (Real Device MediaStream) =====
let localStream = null;
let isMicMuted = false;
let isDeafened = false;
let isCameraOn = false;
let facingMode = 'user'; // 'user' = front, 'environment' = back
let audioCtx = null;
let analyserNode = null;
let talkDetectInterval = null;
let remoteAudios = {};

async function initVoice(slug) {
    isMicMuted = false;
    isDeafened = false;
    isCameraOn = false;
    updateVoiceUI();

    // Register presence in DB
    fetch('api/get_voice_participants.php?channel=' + encodeURIComponent(slug) + '&action=join');

    // Save to sessionStorage so voice persists across page navigation
    sessionStorage.setItem('gummy_voice_channel', slug);
    sessionStorage.setItem('gummy_voice_title', document.getElementById('voice-channel-label').textContent || slug);

    // Ping every 10 seconds to maintain presence
    if (window._voicePingInterval) clearInterval(window._voicePingInterval);
    window._voiceCurrentChannel = slug;
    window._voicePingInterval = setInterval(() => {
        if (window._voiceCurrentChannel) {
            fetch('api/get_voice_participants.php?channel=' + encodeURIComponent(window._voiceCurrentChannel) + '&action=ping');
        }
    }, 10000);

    try {
        localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        setupTalkDetection();
    } catch (err) {
        showVoiceError("Izin mikrofon ditolak. Aktifkan mikrofon di pengaturan browser.");
    }
}

function setupTalkDetection() {
    if (!localStream) return;
    try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const source = audioCtx.createMediaStreamSource(localStream);
        analyserNode = audioCtx.createAnalyser();
        analyserNode.fftSize = 256;
        source.connect(analyserNode);

        const bufferLength = analyserNode.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        const ring = document.getElementById('voice-ring-me');

        if (talkDetectInterval) clearInterval(talkDetectInterval);
        talkDetectInterval = setInterval(() => {
            if (!analyserNode || isMicMuted) {
                if (ring) ring.classList.remove('active-talking-glow');
                return;
            }
            analyserNode.getByteFrequencyData(dataArray);
            const avg = dataArray.reduce((a, b) => a + b, 0) / bufferLength;
            if (ring) ring.classList.toggle('active-talking-glow', avg > 20);
        }, 100);
    } catch(e) {}
}

window.toggleMic = function() {
    isMicMuted = !isMicMuted;
    if (localStream) {
        localStream.getAudioTracks().forEach(t => { t.enabled = !isMicMuted; });
    }
    updateVoiceUI();
};

window.toggleDeafen = function() {
    isDeafened = !isDeafened;
    // Mute all remote audio elements
    Object.values(remoteAudios).forEach(a => { a.muted = isDeafened; });
    updateVoiceUI();
};

window.toggleCamera = async function() {
    if (!isCameraOn) {
        try {
            const videoStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: facingMode },
                audio: false
            });
            const localVideo = document.getElementById('local-video');
            localVideo.srcObject = videoStream;
            // Add video track to localStream
            if (localStream) {
                videoStream.getVideoTracks().forEach(t => localStream.addTrack(t));
            } else {
                localStream = videoStream;
            }
            isCameraOn = true;
            document.getElementById('camera-area').style.display = 'block';
        } catch(err) {
            showVoiceError("Izin kamera ditolak. Aktifkan kamera di pengaturan browser.");
            return;
        }
    } else {
        stopCamera();
    }
    updateVoiceUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
};

function stopCamera() {
    const localVideo = document.getElementById('local-video');
    if (localVideo.srcObject) {
        localVideo.srcObject.getVideoTracks().forEach(t => t.stop());
        localVideo.srcObject = null;
    }
    if (localStream) {
        localStream.getVideoTracks().forEach(t => { t.stop(); try { localStream.removeTrack(t); } catch(e){} });
    }
    isCameraOn = false;
    document.getElementById('camera-area').style.display = 'none';
}

window.flipCamera = async function() {
    facingMode = facingMode === 'user' ? 'environment' : 'user';
    if (isCameraOn) {
        stopCamera();
        await toggleCamera();
    }
};

function updateVoiceUI() {
    const micBtn = document.getElementById('btn-voice-mute');
    const micIcon = document.getElementById('voice-icon-mute');
    const deafBtn = document.getElementById('btn-voice-deafen');
    const deafIcon = document.getElementById('voice-icon-deafen');
    const camBtn = document.getElementById('btn-voice-camera');
    const camIcon = document.getElementById('voice-icon-camera');

    if (micBtn) micBtn.classList.toggle('active-mute', isMicMuted);
    if (micIcon) micIcon.setAttribute('data-lucide', isMicMuted ? 'mic-off' : 'mic');

    if (deafBtn) deafBtn.classList.toggle('active-mute', isDeafened);
    if (deafIcon) deafIcon.setAttribute('data-lucide', isDeafened ? 'volume-x' : 'volume-2');

    if (camBtn) camBtn.classList.toggle('active-mute', !isCameraOn);
    if (camIcon) camIcon.setAttribute('data-lucide', isCameraOn ? 'video' : 'video-off');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function stopVoiceStreams() {
    if (talkDetectInterval) clearInterval(talkDetectInterval);
    if (window._voicePingInterval) clearInterval(window._voicePingInterval);
    // Leave presence
    if (window._voiceCurrentChannel) {
        fetch('api/get_voice_participants.php?channel=' + encodeURIComponent(window._voiceCurrentChannel) + '&action=leave');
        window._voiceCurrentChannel = null;
    }
    // Clear sessionStorage
    sessionStorage.removeItem('gummy_voice_channel');
    sessionStorage.removeItem('gummy_voice_title');
    if (audioCtx) { try { audioCtx.close(); } catch(e){} audioCtx = null; }
    if (localStream) {
        localStream.getTracks().forEach(t => t.stop());
        localStream = null;
    }
    const localVideo = document.getElementById('local-video');
    if (localVideo) localVideo.srcObject = null;
    document.getElementById('camera-area').style.display = 'none';
    isCameraOn = false;
    isMicMuted = false;
    isDeafened = false;
}

window.disconnectVoice = function() {
    stopVoiceStreams();
    // Switch back to text channel
    document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('chan-ngobrol');
    if (btn) btn.classList.add('active');
    document.getElementById('active-channel-title').innerText = 'Ngobrol';
    document.getElementById('voice-call-area').style.display = 'none';
    document.getElementById('text-chat-area').style.display = 'flex';
    // Reset state
    const currentModeRef = { mode: 'text', channel: 'ngobrol' };
    // Trigger fetch
    document.dispatchEvent(new CustomEvent('channelReset'));
};

function showVoiceError(msg) {
    const grid = document.getElementById('voice-participants-grid');
    if (grid) {
        const err = document.createElement('div');
        err.style.cssText = 'background:rgba(255,59,48,0.15); border:1px solid rgba(255,59,48,0.3); border-radius:10px; padding:12px 16px; color:#ff6b6b; font-size:0.85rem; text-align:center; margin-top:10px;';
        err.innerHTML = '<i data-lucide="alert-circle" style="width:16px;height:16px;vertical-align:middle;margin-right:6px;"></i>' + msg;
        grid.appendChild(err);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

// Reset channel after disconnect
document.addEventListener('channelReset', () => {
    window._chatLastMsgId = 0;
    const chatBox = document.getElementById('chat-box');
    if (chatBox) { chatBox.innerHTML = '<div style="text-align:center; color:var(--text-muted); padding-top:50px;">Memuat obrolan...</div>'; }
});

// Isolate mouse wheel & touchpad scroll inside chat box
document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.addEventListener('wheel', (e) => {
            const scrollTop = chatBox.scrollTop;
            const scrollHeight = chatBox.scrollHeight;
            const clientHeight = chatBox.clientHeight;
            const delta = e.deltaY;

            if ((delta < 0 && scrollTop <= 0) || (delta > 0 && scrollTop + clientHeight >= scrollHeight - 1)) {
                e.preventDefault();
            }
            e.stopPropagation();
        }, { passive: false });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
