<?php
require_once 'config.php';
redirect_if_not_logged_in();

// Get current user ID
$current_user_id = $_SESSION['user_id'];

// First get all users except current user
$stmt = $pdo->prepare("
    SELECT id, username, profile_picture, bio 
    FROM users 
    WHERE id != ?
    ORDER BY username
");
$stmt->execute([$current_user_id]);
$users = $stmt->fetchAll();

// Then get last message for each conversation
foreach ($users as &$user) {
    $other_user_id = $user['id'];
    
    // Generate both possible room_id formats
    $room_id1 = min($current_user_id, $other_user_id) . '-' . max($current_user_id, $other_user_id);
    $room_id2 = max($current_user_id, $other_user_id) . '-' . min($current_user_id, $other_user_id);
    
    $stmt = $pdo->prepare("
        SELECT content, timestamp, seen 
        FROM messages 
        WHERE room_id = ? OR room_id = ?
        ORDER BY timestamp DESC 
        LIMIT 1
    ");
    $stmt->execute([$room_id1, $room_id2]);
    $last_message = $stmt->fetch();
    
    if ($last_message) {
        $user['last_message'] = $last_message['content'];
        $user['last_message_time'] = $last_message['timestamp'];
        $user['last_message_seen'] = $last_message['seen'];
    }
}
unset($user); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkUp - Users</title>
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
            --border-radius: 8px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .user-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }
        
        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        .search-container {
            margin-bottom: 25px;
            position: relative;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: all 0.3s;
            background-color: var(--white);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }
        
        .users-list {
            background-color: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .user-avatar-list {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--light-gray);
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-name-text {
            flex: 1;
        }
        
        .last-message-time {
            font-size: 11px;
            color: var(--gray);
            margin-left: 10px;
        }
        
        .user-bio {
            font-size: 13px;
            color: var(--gray);
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            margin-bottom: 3px;
        }
        
        .last-message {
            font-size: 13px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
        }
        
        .last-message.unseen {
            font-weight: bold;
            color: var(--dark);
        }
        
        .chat-icon {
            color: var(--primary);
            font-size: 18px;
            opacity: 0;
            transition: all 0.3s;
        }
        
        .user-item:hover .chat-icon {
            opacity: 1;
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .no-users i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .user-controls {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 480px) {
            .user-item {
                padding: 12px 15px;
            }
            
            .user-avatar-list {
                width: 45px;
                height: 45px;
                margin-right: 12px;
            }
            
            .user-name {
                font-size: 15px;
            }
            
            .user-bio, .last-message {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-comments"></i>
                <span>LinkUp</span>
            </div>
            <div class="user-controls">
                <?php 
                // Get current user's profile picture
                $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $current_user = $stmt->fetch();
                ?>
                <img src="<?php echo !empty($current_user['profile_picture']) ? htmlspecialchars($current_user['profile_picture']) : './unknown.jpg'; ?>" 
                     alt="Your profile" 
                     class="user-avatar">
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </form>
            </div>
        </header>
        
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" placeholder="Search users by name..." id="searchInput">
        </div>
        
        <div class="users-list" id="usersList">
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $user): ?>
                    <a href="chat.php?user_id=<?php echo $user['id']; ?>" class="user-item">
                        <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : './unknown.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>" 
                             class="user-avatar-list">
                        <div class="user-info">
                            <div class="user-name">
                                <span class="user-name-text"><?php echo htmlspecialchars($user['username']); ?></span>
                                <?php if (!empty($user['last_message_time'])): ?>
                                    <span class="last-message-time">
                                        <?php echo date('h:i A', strtotime($user['last_message_time'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($user['bio'])): ?>
                                <div class="user-bio"><?php echo htmlspecialchars($user['bio']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($user['last_message'])): ?>
                                <div class="last-message <?php echo empty($user['last_message_seen']) || $user['last_message_seen'] == 0 ? 'unseen' : ''; ?>">
                                    <?php echo htmlspecialchars($user['last_message']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-arrow-right chat-icon"></i>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-users">
                    <i class="fas fa-user-friends"></i>
                    <h3>No other users found</h3>
                    <p>Invite others to join LinkUp!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');
            let hasResults = false;
            
            userItems.forEach(item => {
                const userName = item.querySelector('.user-name-text').textContent.toLowerCase();
                const userBio = item.querySelector('.user-bio') ? item.querySelector('.user-bio').textContent.toLowerCase() : '';
                const lastMessage = item.querySelector('.last-message') ? item.querySelector('.last-message').textContent.toLowerCase() : '';
                
                if (userName.includes(searchTerm) || userBio.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                    item.style.display = 'flex';
                    hasResults = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show "no results" message if needed
            const noUsersDiv = document.querySelector('.no-users');
            if (!hasResults && userItems.length > 0) {
                if (!noUsersDiv) {
                    const usersList = document.getElementById('usersList');
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-users';
                    noResultsDiv.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h3>No matching users found</h3>
                        <p>Try a different search term</p>
                    `;
                    usersList.appendChild(noResultsDiv);
                }
            } else if (noUsersDiv && userItems.length > 0) {
                noUsersDiv.remove();
            }
        });
        
        // Debounce the search input for better performance
        function debounce(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }
        
        // Apply debouncing to the search input
        const searchInput = document.getElementById('searchInput');
        const debouncedSearch = debounce(() => {
            const event = new Event('input');
            searchInput.dispatchEvent(event);
        });
        
        searchInput.addEventListener('keyup', debouncedSearch);
    </script>
</body>
</html>