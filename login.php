<?php
require_once 'config.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            // After successful login
            $stmt = $pdo->prepare("UPDATE users SET is_logged_in = 1, last_activity = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        // Signup mode
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
        
        // Validate bio length
        if (strlen($bio) > 50) {
            $error = "Bio must be less than 50 characters";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->fetch()) {
                $error = "Email or username already exists";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Handle profile picture upload
                $profile_picture = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/profile_pics/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    
                    // Validate image
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array(strtolower($file_ext), $allowed_types)) {
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $file_path)) {
                            $profile_picture = $file_path;
                        }
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO users (email, username, password, profile_picture, bio) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$email, $username, $hashed_password, $profile_picture, $bio]);
                
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header("Location: index.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkUp - <?php echo ucfirst($mode); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="logo.png">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #560bad;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .header h2 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .form-container {
            padding: 30px;
        }
        
        .error {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            text-align: center;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(72, 149, 239, 0.2);
        }
        
        .char-count {
            font-size: 0.8rem;
            color: var(--gray);
            text-align: right;
            margin-top: 5px;
        }
        
        .char-count.warning {
            color: var(--danger);
        }
        
        .profile-pic-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-pic-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #e9ecef;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .profile-pic-preview:hover {
            transform: scale(1.05);
        }
        
        .profile-pic-input {
            display: none;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-light), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .toggle-mode {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .toggle-mode a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .toggle-mode a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container {
                border-radius: 12px;
            }
            
            .header {
                padding: 20px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Welcome to LinkUp</h2>
            <p><?php echo $mode === 'login' ? 'Sign in to continue' : 'Create your account'; ?></p>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <?php if ($mode === 'signup'): ?>
                    <div class="profile-pic-container">
                        <label for="profile-pic-input">
                            <img id="profile-pic-preview" src="https://via.placeholder.com/100" alt="Profile picture" class="profile-pic-preview">
                        </label>
                        <input type="file" id="profile-pic-input" name="profile_picture" class="profile-pic-input" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required placeholder="Enter your username">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">Short Bio (max 50 chars)</label>
                        <textarea id="bio" name="bio" placeholder="Tell us something about yourself" maxlength="50"></textarea>
                        <div id="bio-counter" class="char-count">0/50</div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                
                <?php if ($mode === 'signup'): ?>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <?php echo ucfirst($mode); ?>
                </button>
            </form>
            
            <div class="toggle-mode">
                <?php if ($mode === 'login'): ?>
                    Don't have an account? <a href="?mode=signup">Sign up</a>
                <?php else: ?>
                    Already have an account? <a href="?mode=login">Log in</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Profile picture preview
        const profilePicInput = document.getElementById('profile-pic-input');
        const profilePicPreview = document.getElementById('profile-pic-preview');
        
        if (profilePicInput && profilePicPreview) {
            profilePicInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        profilePicPreview.src = event.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Bio character counter
        const bioTextarea = document.getElementById('bio');
        const bioCounter = document.getElementById('bio-counter');
        
        if (bioTextarea && bioCounter) {
            bioTextarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                bioCounter.textContent = `${currentLength}/50`;
                
                if (currentLength > 45) {
                    bioCounter.classList.add('warning');
                } else {
                    bioCounter.classList.remove('warning');
                }
            });
        }
    </script>
</body>
</html>