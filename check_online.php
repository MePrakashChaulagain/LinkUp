<?php
require_once 'config.php';

if (!isset($_GET['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['is_online' => false]);
    exit();
}

$user_id = $_GET['user_id'];

// Get user's last activity
$stmt = $pdo->prepare("SELECT last_activity FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Content-Type: application/json');
    echo json_encode(['is_online' => false]);
    exit();
}

// Determine if user is online (active within last 5 minutes)
$last_activity = strtotime($user['last_activity']);
$is_online = (time() - $last_activity) < 300; // 5 minutes in seconds

header('Content-Type: application/json');
echo json_encode(['is_online' => $is_online]);
?>