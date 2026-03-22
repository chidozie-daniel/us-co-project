<?php
require_once 'includes/functions.php';

// Redirect if not logged in (before any HTML output)
if (!isLoggedIn()) {
    redirect('login.php');
}

$page_title = "Private Letters";
require_once 'includes/header.php';

$user = getCurrentUser();
$selectedUserId = $_GET['user'] ?? null;
$action = $_GET['action'] ?? null;
$conn = getDBConnection();

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'];
    $messageContent = sanitize($_POST['message']);
    
    if ($messageContent) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiverId, $messageContent]);
        
        // Notify receiver
        createNotification($receiverId, $_SESSION['user_id'], 'message');
        
        if (!isset($_GET['user'])) {
            header("Location: messages.php?user=" . $receiverId);
            exit();
        }
    }
}

// Get message threads
$stmt = $conn->prepare("
    SELECT 
        u.id, u.username, u.profile_pic,
        MAX(m.sent_at) as last_activity,
        (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY sent_at DESC LIMIT 1) as last_msg,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
    GROUP BY u.id
    ORDER BY last_activity DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
$otherUser = null;
if ($selectedUserId) {
    // Mark as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$selectedUserId, $_SESSION['user_id']]);

    // Get messages
    $stmt = $conn->prepare("
        SELECT m.*, u.username as sender_name, u.profile_pic as sender_pic
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $selectedUserId, $selectedUserId, $_SESSION['user_id']]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT id, username, profile_pic FROM users WHERE id = ?");
    $stmt->execute([$selectedUserId]);
    $otherUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="h-[calc(100vh-8rem)] flex overflow-hidden bg-white/50 dark:bg-background-dark/50 backdrop-blur-xl rounded-2xl border border-primary/10 shadow-2xl">
    <!-- Threads Sidebar -->
    <div class="w-full md:w-80 lg:w-96 border-r border-primary/5 flex flex-col <?php echo $selectedUserId ? 'hidden md:flex' : 'flex'; ?>">
        <div class="p-6 border-b border-primary/5">
            <h1 class="text-3xl font-display text-primary mb-6" style="font-family: 'Great Vibes', cursive;">Private Letters</h1>
            <div class="relative">
                <span class="material-icons-round absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" placeholder="Search conversations..." class="w-full pl-12 pr-4 py-3 bg-slate-100 dark:bg-white/5 border-none rounded-full focus:ring-2 focus:ring-primary text-sm">
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-2">
            <?php if (empty($threads)): ?>
                <div class="text-center p-12 text-slate-400 leading-relaxed italic">
                    <span class="material-icons-round text-5xl opacity-20 mb-4">mail_outline</span>
                    <p>Your mailbox is quiet. Start a new conversation through exploration.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($threads as $t): ?>
                <a href="messages.php?user=<?php echo $t['id']; ?>" 
                   class="flex items-center gap-4 p-4 rounded-xl transition-all <?php echo $selectedUserId == $t['id'] ? 'bg-primary shadow-lg shadow-primary/20 text-white' : 'hover:bg-primary/5 text-slate-600 dark:text-slate-300'; ?>">
                    <div class="relative flex-shrink-0">
                        <img src="<?php echo getProfilePic($t['profile_pic']); ?>" class="w-14 h-14 rounded-full border-2 border-white/50 object-cover">
                        <?php if ($t['unread'] > 0): ?>
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-primary border-2 border-white rounded-full"></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-baseline mb-1">
                            <h3 class="font-bold truncate"><?php echo htmlspecialchars($t['username']); ?></h3>
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-60"><?php echo date('M d', strtotime($t['last_activity'])); ?></span>
                        </div>
                        <p class="text-xs truncate opacity-70 <?php echo $t['unread'] > 0 ? 'font-bold underline decoration-primary' : ''; ?>">
                            <?php echo htmlspecialchars($t['last_msg'] ?? 'A new letter awaits...'); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Active Chat Area -->
    <div class="flex-1 flex flex-col relative <?php echo !$selectedUserId ? 'hidden md:flex' : 'flex'; ?>">
        <?php if ($otherUser): ?>
            <!-- Chat Header -->
            <div class="p-4 md:p-6 border-b border-primary/5 flex items-center justify-between bg-white/30 dark:bg-black/10">
                <div class="flex items-center gap-4">
                    <a href="messages.php" class="md:hidden text-slate-400 hover:text-primary transition-colors">
                        <span class="material-icons-round">arrow_back</span>
                    </a>
                    <div class="relative">
                        <img src="<?php echo getProfilePic($otherUser); ?>" class="w-12 h-12 rounded-full border-2 border-white shadow-sm object-cover">
                        <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div>
                        <h2 class="font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($otherUser['username']); ?></h2>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-green-500">Soulfully Connected</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button class="p-2 text-slate-400 hover:text-primary transition-colors rounded-full hover:bg-primary/5">
                        <span class="material-icons-round">videocam</span>
                    </button>
                    <button class="p-2 text-slate-400 hover:text-primary transition-colors rounded-full hover:bg-primary/5">
                        <span class="material-icons-round">info</span>
                    </button>
                </div>
            </div>

            <!-- Messages List -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar" id="chatContainer">
                <?php foreach ($messages as $m): ?>
                    <?php $isMe = $m['sender_id'] == $_SESSION['user_id']; ?>
                    <div class="flex <?php echo $isMe ? 'justify-end' : 'justify-start'; ?> group message-item" data-id="<?php echo $m['id']; ?>">
                        <div class="max-w-[85%] md:max-w-[70%] space-y-1">
                            <div class="flex items-end gap-2 <?php echo $isMe ? 'flex-row-reverse' : ''; ?>">
                                <?php if (!$isMe): ?>
                                    <img src="<?php echo getProfilePic($m['sender_pic']); ?>" class="w-8 h-8 rounded-full border border-white shadow-sm flex-shrink-0 object-cover">
                                <?php endif; ?>
                                
                                <div class="px-5 py-3 rounded-2xl shadow-sm <?php echo $isMe ? 'bg-primary text-white rounded-br-none' : 'bg-white dark:bg-white/10 text-slate-800 dark:text-slate-200 border border-primary/5 rounded-bl-none'; ?>">
                                    <p class="text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 <?php echo $isMe ? 'justify-end' : 'justify-start'; ?> px-1">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                                    <?php echo date('g:i A', strtotime($m['sent_at'])); ?>
                                </span>
                                <?php if ($isMe): ?>
                                    <span class="material-icons-round text-xs <?php echo $m['is_read'] ? 'text-primary' : 'text-slate-300'; ?>">
                                        <?php echo $m['is_read'] ? 'done_all' : 'done'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Input Area -->
            <div class="p-6 bg-white/30 dark:bg-black/10 border-t border-primary/5 backdrop-blur-md">
                <form id="messageForm" class="flex items-end gap-3">
                    <input type="hidden" name="receiver_id" id="receiver_id" value="<?php echo $selectedUserId; ?>">
                    
                    <button type="button" class="p-3 text-slate-400 hover:text-primary transition-colors rounded-full hover:bg-primary/5 flex-shrink-0">
                        <span class="material-icons-round">add_circle_outline</span>
                    </button>

                    <div class="flex-1 relative group">
                        <textarea name="message" id="messageInput" rows="1" placeholder="Craft a memory..." 
                                  class="w-full bg-white dark:bg-white/5 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-primary text-sm resize-none custom-scrollbar transition-all"
                                  style="min-height: 52px; max-height: 150px;"
                                  oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        <button type="button" class="absolute right-4 bottom-3.5 text-slate-400 hover:text-primary transition-colors">
                            <span class="material-icons-round">sentiment_satisfied_alt</span>
                        </button>
                    </div>

                    <button type="submit" class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 transition-all flex-shrink-0">
                        <span class="material-icons-round">send</span>
                    </button>
                </form>
            </div>

        <?php else: ?>
            <div class="flex-1 flex flex-col items-center justify-center p-12 text-center space-y-6">
                <div class="w-64 h-64 bg-primary/5 rounded-full flex items-center justify-center relative">
                    <span class="material-icons-round text-9xl text-primary/10 animate-pulse">edit_note</span>
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-primary/10 rounded-full blur-3xl"></div>
                </div>
                <div class="max-w-md">
                    <h2 class="text-5xl font-display text-primary mb-4" style="font-family: 'Great Vibes', cursive;">My Letters</h2>
                    <p class="text-slate-500 leading-relaxed font-medium">Select a shared thread from the left or wander into exploration to begin a new exchange of moments.</p>
                </div>
                <a href="explore.php" class="bg-primary text-white px-10 py-4 rounded-full font-bold shadow-xl shadow-primary/30 hover:scale-105 transition-all flex items-center gap-3">
                    <span class="material-icons-round">explore</span>
                    Explore Hearts
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(238, 43, 140, 0.2); border-radius: 10px; }
.message-item { animation: slideUp 0.3s ease-out forwards; }
@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
let lastMsgId = 0;
const receiverId = "<?php echo $selectedUserId; ?>";
const currentUserId = "<?php echo $_SESSION['user_id']; ?>";
const chatContainer = document.getElementById('chatContainer');

window.onload = function() {
    scrollToBottom();
    const lastMsg = document.querySelector('.message-item:last-child');
    if (lastMsg) lastMsgId = lastMsg.dataset.id;
    if (receiverId) setInterval(fetchMessages, 3000);
};

function scrollToBottom() {
    if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;
}

const messageForm = document.getElementById('messageForm');
if (messageForm) {
    messageForm.onsubmit = function(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        if (!message) return;

        const formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', message);
        formData.append('send_message', '1');

        input.value = '';
        input.style.height = 'auto';

        fetch('ajax/send_message.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            if (data.success) fetchMessages();
        });
    };
    
    document.getElementById('messageInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit'));
        }
    });
}

function fetchMessages() {
    if (!receiverId) return;
    const msgs = document.querySelectorAll('.message-item');
    if (msgs.length > 0) lastMsgId = msgs[msgs.length - 1].dataset.id;

    fetch(`ajax/get_messages.php?user_id=${receiverId}&last_id=${lastMsgId}`)
    .then(r => r.json()).then(data => {
        if (data.success && data.messages.length > 0) {
            data.messages.forEach(msg => appendMessage(msg));
            scrollToBottom();
        }
    });
}

function appendMessage(msg) {
    const isMe = msg.is_me;
    const div = document.createElement('div');
    div.className = `flex ${isMe ? 'justify-end' : 'justify-start'} group message-item`;
    div.dataset.id = msg.id;
    
    let html = `
        <div class="max-w-[85%] md:max-w-[70%] space-y-1">
            <div class="flex items-end gap-2 ${isMe ? 'flex-row-reverse' : ''}">
    `;
    
    if (!isMe) {
        html += `<img src="uploads/profile_pics/${msg.sender_pic}" class="w-8 h-8 rounded-full border border-white shadow-sm flex-shrink-0 object-cover">`;
    }
    
    html += `
                <div class="px-5 py-3 rounded-2xl shadow-sm ${isMe ? 'bg-primary text-white rounded-br-none' : 'bg-white dark:bg-white/10 text-slate-800 dark:text-slate-200 border border-primary/5 rounded-bl-none'}">
                    <p class="text-sm leading-relaxed">${msg.message}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 ${isMe ? 'justify-end' : 'justify-start'} px-1">
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">${msg.sent_at}</span>
                ${isMe ? `<span class="material-icons-round text-xs text-slate-300">done</span>` : ''}
            </div>
        </div>
    `;
    
    div.innerHTML = html;
    chatContainer.appendChild(div);
}
</script>

<?php require_once 'includes/footer.php'; ?>