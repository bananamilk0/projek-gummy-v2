<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="chat-wrapper">
    <!-- Chat Sidebar / Channels -->
    <div class="chat-sidebar glass-panel">
        <h3>Lounge Channels</h3>
        <ul class="channel-list">
            <li class="active"><i data-lucide="hash"></i> general</li>
            <li><i data-lucide="hash"></i> looking-for-group</li>
            <li><i data-lucide="hash"></i> memes</li>
        </ul>
        
        <h3 class="mt-4">Voice Channels (Discord)</h3>
        <ul class="voice-list">
            <li><i data-lucide="volume-2"></i> Main Lobby (3/10) <button class="btn-sm btn-secondary">Join</button></li>
        </ul>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-area glass-card">
        <div class="chat-header border-b">
            <h2><i data-lucide="hash"></i> general</h2>
            <div class="chat-actions">
                <i data-lucide="pin" title="Pinned Messages"></i>
                <i data-lucide="users" title="Member List"></i>
            </div>
        </div>
        
        <div class="chat-messages-wrapper" id="chat-messages">
            <!-- Messages will be injected here via JS -->
            <div class="message system-msg">
                <p>Welcome to the #general lounge! Be respectful and have fun.</p>
            </div>
        </div>
        
        <div class="chat-input-area border-t">
            <i data-lucide="plus-circle" class="btn-attach"></i>
            <input type="text" id="chat-input" placeholder="Message #general..." <?php echo !isset($_SESSION['user_id']) ? 'disabled' : ''; ?>>
            <i data-lucide="smile" class="btn-emoji"></i>
            <i data-lucide="send" class="btn-send text-primary" id="btn-send"></i>
        </div>
    <div class="new-msg-banner" id="new-msg-banner">⬇ Pesan Baru (<span id="new-msg-count">0</span>)</div>
    <button class="scroll-to-bottom" id="scroll-to-bottom"><i data-lucide="arrow-down"></i></button>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="login-prompt">
                <p>You must be <a href="?page=login">logged in</a> to send messages.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Mock Realtime Chat Logic (Client Side)
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('btn-send');
    const messages = document.getElementById('chat-messages');
    const banner = document.getElementById('new-msg-banner');
    const countSpan = document.getElementById('new-msg-count');
    const scrollBtn = document.getElementById('scroll-to-bottom');
    
    function addMessage(user, text, isMe = false) {
        const msg = document.createElement('div');
        msg.className = 'message gsap-reveal';
        msg.innerHTML = `
            <img src="https://ui-avatars.com/api/?name=${user}&background=random" class="msg-avatar">
            <div class="msg-content">
                <h4 style="color: ${isMe ? 'var(--secondary)' : 'var(--text-main)'}">${user} <span class="time">Just now</span></h4>
                <p>${text}</p>
            </div>
        `;
        messages.appendChild(msg);
        // Smart auto‑scroll: only auto‑scroll if user is near bottom
        const isNearBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 80;
        if (isNearBottom) {
            messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
        } else {
            // Show new‑message banner and scroll button
            const banner = document.getElementById('new-msg-banner');
            const countSpan = document.getElementById('new-msg-count');
            let count = parseInt(countSpan.textContent) || 0;
            count++;
            countSpan.textContent = count;
            banner.style.display = 'block';
            scrollBtn.style.display = 'flex';
        }
        // Trigger reveal animation for new message
        gsap.from(msg, { y: 20, opacity: 0, duration: 0.3, ease: "power2.out" });
        
        // Trigger reveal animation for new message
        gsap.from(msg, { y: 20, opacity: 0, duration: 0.3, ease: "power2.out" });
    }

    if(input) {
        input.addEventListener('keypress', (e) => {
            if(e.key === 'Enter' && input.value.trim() !== '') {
                addMessage('You', input.value.trim(), true);
                input.value = '';
                // In a real app, send Fetch request to api/chat.php here
            }
        });
        
        sendBtn.addEventListener('click', () => {
            if(input.value.trim() !== '') {
                addMessage('You', input.value.trim(), true);
                input.value = '';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
