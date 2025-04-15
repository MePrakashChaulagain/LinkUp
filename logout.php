<?php
require_once 'config.php';

// Update login status before destroying session
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_logged_in = 0, last_activity = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

session_unset();
session_destroy();

header("Location: login.php");
exit();
?>