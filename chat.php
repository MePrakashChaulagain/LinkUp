<?php
require_once 'config.php';
redirect_if_not_logged_in();

if (!isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user_id'];

// Update current user's last activity
$stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
$stmt->execute([$current_user_id]);

// Get other user's info and online status
$stmt = $pdo->prepare("SELECT username, profile_picture, is_logged_in, last_activity FROM users WHERE id = ?");
$stmt->execute([$other_user_id]);
$other_user = $stmt->fetch();

if (!$other_user) {
    header("Location: index.php");
    exit();
}

// Determine if user is online (logged in AND active within last 5 minutes)
$is_online = $other_user['is_logged_in'] && (strtotime($other_user['last_activity']) > (time() - 300));

// Determine room ID
$user_ids = [$current_user_id, $other_user_id];
sort($user_ids);
$room_id = implode('-', $user_ids);

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (room_id, user_id, username, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$room_id, $current_user_id, $_SESSION['username'], $message]);
        
        // Update last activity when sending a message
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$current_user_id]);
    }
}

// Get all messages for this room
$stmt = $pdo->prepare("SELECT * FROM messages WHERE room_id = ? ORDER BY timestamp ASC");
$stmt->execute([$room_id]);
$messages = $stmt->fetchAll();

// Get current user's profile picture
$stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkUp - Chat with <?php echo htmlspecialchars($other_user['username']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/png" sizes="16x16" href="logo.png">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --white: #ffffff;
            --success: #4cc9f0;
            --danger: #f72585;
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f7fa;
        }
        
        .main-container {
            display: flex;
            justify-content: center;
            height: 100vh;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
            max-width: 800px;
            background-color: var(--white);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        @media (max-width: 768px) {
            .chat-container {
                max-width: 100%;
                border-radius: 0;
            }
        }
        
        .chat-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--white);
            border-bottom: 1px solid var(--light-gray);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .back-btn {
            color: var(--primary);
            font-size: 20px;
            margin-right: 15px;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 12px;
            border: 2px solid var(--primary-light);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .chat-status {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
            display: flex;
            align-items: center;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .status-online {
            background-color: #4ade80;
            animation: pulse 1.5s infinite;
        }
        
        .status-offline {
            background-color: var(--gray);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            max-width: 75%;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .message.sent {
            align-self: flex-end;
            align-items: flex-end;
        }
        
        .message.received {
            align-self: flex-start;
            align-items: flex-start;
        }
        
        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.4;
            word-break: break-word;
            position: relative;
        }
        
        .sent .message-content {
            background-color: var(--primary);
            color: var(--white);
            border-top-right-radius: 4px;
        }
        
        .received .message-content {
            background-color: var(--white);
            color: var(--dark);
            border-top-left-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .message-info {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
            font-size: 12px;
            color: var(--gray);
        }
        
        .message-time {
            font-size: 11px;
            margin-top: 5px;
            color: var(--gray);
            opacity: 0.8;
        }
        
        .sent .message-time {
            text-align: right;
        }
        
        .received .message-time {
            text-align: left;
        }
        
        .chat-input-container {
            padding: 15px;
            background-color: var(--white);
            border-top: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            position: sticky;
            bottom: 0;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
            resize: none;
            max-height: 120px;
        }
        
        .message-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }
        
        .send-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .send-btn:hover {
            background-color: var(--secondary);
            transform: scale(1.05);
        }
        
        .no-messages {
            text-align: center;
            color: var(--gray);
            margin: auto;
            padding: 20px;
        }
        
        .no-messages i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .typing-indicator {
            display: flex;
            padding: 10px 15px;
            background-color: var(--white);
            border-radius: 18px;
            margin-bottom: 15px;
            align-self: flex-start;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: var(--gray);
            border-radius: 50%;
            margin: 0 2px;
            animation: typingAnimation 1.4s infinite ease-in-out;
        }
        
        @keyframes typingAnimation {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-5px); }
        }
        
        @media (max-width: 480px) {
            .profile-pic {
                width: 36px;
                height: 36px;
            }
            
            .user-name {
                font-size: 15px;
            }
            
            .chat-status {
                font-size: 11px;
            }
            
            .message-content {
                padding: 10px 14px;
                font-size: 14px;
            }
            
            .chat-input-container {
                padding: 12px;
            }
            
            .message-input {
                padding: 10px 14px;
                font-size: 14px;
            }
            
            .send-btn {
                width: 36px;
                height: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="chat-container">
            <div class="chat-header">
                <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                <div class="user-info">
                    <img src="<?php echo (!empty($other_user['profile_picture']) ? htmlspecialchars($other_user['profile_picture']) : './unknown.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($other_user['username']); ?>" 
                         class="profile-pic">
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($other_user['username']); ?></div>
                        <div class="chat-status">
                            <span class="status-indicator <?php echo $is_online ? 'status-online' : 'status-offline'; ?>"></span>
                            <?php 
                            if ($is_online) {
                                echo 'Online';
                            } else {
                                if (!empty($other_user['last_activity'])) {
                                    $last_seen = strtotime($other_user['last_activity']);
                                    if (date('Y-m-d') == date('Y-m-d', $last_seen)) {
                                        echo 'Last seen today at ' . date('h:i A', $last_seen);
                                    } else {
                                        echo 'Last seen ' . date('M j, Y', $last_seen);
                                    }
                                } else {
                                    echo 'Offline';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['user_id'] == $current_user_id ? 'sent' : 'received'; ?>">
                            <?php if ($message['user_id'] != $current_user_id): ?>
                                <div class="message-info">
                                    <span><?php echo htmlspecialchars($message['username']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="message-content"><?php echo htmlspecialchars($message['content']); ?></div>
                            <div class="message-time">
                                <?php echo date('h:i A', strtotime($message['timestamp'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-comment-dots"></i>
                        <h3>No messages yet</h3>
                        <p>Start the conversation with <?php echo htmlspecialchars($other_user['username']); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="typing-indicator" id="typingIndicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
            
            <form class="chat-input-container" method="POST" id="messageForm">
                <input type="text" name="message" class="message-input" placeholder="Type a message..." autocomplete="off" required>
                <button type="submit" class="send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Handle form submission
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const messageInput = form.querySelector('input[name="message"]');
            const message = messageInput.value.trim();
            
            if (message) {
                // Show typing indicator temporarily
                const typingIndicator = document.getElementById('typingIndicator');
                typingIndicator.style.display = 'flex';
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Submit the form
                form.submit();
            }
        });
        
        // Auto-focus the input field
        document.querySelector('.message-input').focus();
        
        // Keep scroll at bottom when new messages arrive
        const observer = new MutationObserver(function(mutations) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
        
        observer.observe(chatMessages, {
            childList: true,
            subtree: true
        });
        
        // Periodically check online status (every 30 seconds)
        function checkOnlineStatus() {
            fetch('check_online.php?user_id=<?php echo $other_user_id; ?>')
                .then(response => response.json())
                .then(data => {
                    const statusIndicator = document.querySelector('.status-indicator');
                    const statusText = document.querySelector('.chat-status');
                    
                    if (data.is_online) {
                        statusIndicator.classList.remove('status-offline');
                        statusIndicator.classList.add('status-online');
                        statusText.innerHTML = '<span class="status-indicator status-online"></span> Online';
                    } else {
                        statusIndicator.classList.remove('status-online');
                        statusIndicator.classList.add('status-offline');
                        if (data.last_activity) {
                            const lastSeen = new Date(data.last_activity * 1000);
                            const now = new Date();
                            let lastSeenText;
                            
                            if (lastSeen.toDateString() === now.toDateString()) {
                                lastSeenText = 'Last seen today at ' + lastSeen.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            } else {
                                lastSeenText = 'Last seen ' + lastSeen.toLocaleDateString([], {month: 'short', day: 'numeric'});
                            }
                            
                            statusText.innerHTML = '<span class="status-indicator status-offline"></span> ' + lastSeenText;
                        } else {
                            statusText.innerHTML = '<span class="status-indicator status-offline"></span> Offline';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking online status:', error);
                });
        }
        
        // Check status immediately and then every 30 seconds
        checkOnlineStatus();
        const statusCheckInterval = setInterval(checkOnlineStatus, 30000);
        
        // Clean up interval when leaving page
        window.addEventListener('beforeunload', function() {
            clearInterval(statusCheckInterval);
        });
    </script>
</body>
</html>