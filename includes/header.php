<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$customerName = $isCustomerLoggedIn ? $_SESSION['customer_name'] : null;
?>
<header class="site-header">
    <div class="container nav">
        <a href="/foodie/index.php" class="logo">FOODIE<span>.io</span></a>
        <nav class="nav-links">
            <a href="/foodie/index.php">Home</a>
            <a href="/foodie/menu.php">Menu</a>
            <?php if ($isCustomerLoggedIn): ?>
                <a href="/foodie/customer/cart.php">My Cart</a>
                <span style="font-size:0.9rem;color:#9ca3af;">
                    Hi, <?php echo htmlspecialchars($customerName); ?>
                </span>
                <a href="/foodie/customer/logout.php" class="btn outline">Logout</a>
            <?php else: ?>
                <a href="/foodie/customer/login.php">Login</a>
                <a href="/foodie/customer/register.php" class="btn primary">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>


