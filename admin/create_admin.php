<?php
// One-time script to (re)create an admin user.
require_once __DIR__ . '/../includes/config.php';

$email = 'admin@example.com';
$passwordPlain = 'admin123';
$name = 'Main Admin';

$hash = password_hash($passwordPlain, PASSWORD_DEFAULT);

// If email exists, update password; if not, insert a new row
$stmt = $conn->prepare('INSERT INTO admins (name, email, password) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password)');

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('sss', $name, $email, $hash);

if ($stmt->execute()) {
    echo 'Admin user is ready.<br>';
    echo 'Email: ' . htmlspecialchars($email) . '<br>';
    echo 'Password: ' . htmlspecialchars($passwordPlain) . '<br>';
    echo '<a href="login.php">Go to admin login</a>';
} else {
    echo 'Failed to create/update admin: ' . htmlspecialchars($stmt->error);
}

$stmt->close();


