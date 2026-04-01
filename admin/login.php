<?php
require_once __DIR__ . '/../includes/config.php';

$error = '';
$info = '';

// Inform if no admin accounts exist (common setup issue)
$countResult = $conn->query('SELECT COUNT(*) AS c FROM admins');
if ($countResult && ($row = $countResult->fetch_assoc())) {
    if ((int) $row['c'] === 0) {
        $info = 'No admin accounts found. Use the default seed (admin@example.com / admin123) by importing sql/schema.sql or create an admin in the database.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, password FROM admins WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $name, $hash);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                $_SESSION['admin_id'] = $id;
                $_SESSION['admin_name'] = $name;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid credentials.';
            }
        } else {
            $error = 'Invalid credentials.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Foodie</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a href="/foodie/index.php" class="logo">FOODIE<span>.io</span></a>
        <nav class="nav-links">
            <span style="font-size:0.9rem;color:#9ca3af;">Admin Panel</span>
        </nav>
    </div>
</header>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Admin Login</h2>
            <p>Owner / staff access only.</p>
        </div>

        <?php if ($info): ?>
            <div class="alert success"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <div class="form-field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="admin@example.com" autofocus>
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="admin123">
            </div>
            <button class="btn primary" type="submit">Login</button>
            <div class="form-footer">
                <span>Default (seed) admin:</span>
                <span style="color:#facc15;">admin@example.com / admin123</span>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>


