<?php
require_once __DIR__ . '/includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Foodie</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Choose your login</h2>
            <p>Access your account or manage the restaurant.</p>
        </div>
        <div class="service-grid" style="margin-top:0.4rem;">
            <div class="service-card">
                <div class="service-pill">Customer</div>
                <h3>Order food</h3>
                <p>Login to view your cart, checkout, and track orders.</p>
                <a class="btn primary" href="/foodie/customer/login.php" style="margin-top:0.6rem;">Customer Login</a>
                <a class="btn outline" href="/foodie/customer/register.php" style="margin-top:0.4rem;">Create Account</a>
            </div>
            <div class="service-card">
                <div class="service-pill">Admin / Staff</div>
                <h3>Manage operations</h3>
                <p>Update products, review orders, and oversee the business.</p>
                <a class="btn primary" href="/foodie/admin/login.php" style="margin-top:0.6rem;">Admin Login</a>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>







