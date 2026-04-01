<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get summary stats
$totalCustomers = $conn->query('SELECT COUNT(*) AS c FROM customers')->fetch_assoc()['c'] ?? 0;
$totalOrders = $conn->query('SELECT COUNT(*) AS c FROM orders')->fetch_assoc()['c'] ?? 0;
$pendingOrders = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'")->fetch_assoc()['c'] ?? 0;
$totalRevenueRow = $conn->query('SELECT SUM(total_amount) AS s FROM orders WHERE status IN ("Pending","Completed")')->fetch_assoc();
$totalRevenue = $totalRevenueRow && $totalRevenueRow['s'] ? $totalRevenueRow['s'] : 0;

// Latest orders
$latestOrders = [];
$result = $conn->query('SELECT o.id, c.name AS customer_name, o.total_amount, o.status, o.created_at 
                        FROM orders o 
                        JOIN customers c ON c.id = o.customer_id 
                        ORDER BY o.created_at DESC 
                        LIMIT 8');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestOrders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Foodie</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a href="/foodie/index.php" class="logo">FOODIE<span>.io</span></a>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <span style="font-size:0.9rem;color:#9ca3af;">
                Admin: <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </span>
            <a href="logout.php" class="btn outline">Logout</a>
        </nav>
    </div>
</header>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Overview</h2>
            <p>Quick stats of your online food ordering system.</p>
        </div>

        <div class="feature-grid" style="margin-bottom:1.5rem;">
            <div class="feature-card">
                <h3>Total Customers</h3>
                <p style="font-size:1.4rem;"><?php echo (int) $totalCustomers; ?></p>
            </div>
            <div class="feature-card">
                <h3>Total Orders</h3>
                <p style="font-size:1.4rem;"><?php echo (int) $totalOrders; ?></p>
            </div>
            <div class="feature-card">
                <h3>Pending Orders</h3>
                <p style="font-size:1.4rem;"><?php echo (int) $pendingOrders; ?></p>
            </div>
            <div class="feature-card">
                <h3>Total Revenue</h3>
                <p style="font-size:1.4rem;">$<?php echo number_format($totalRevenue, 2); ?></p>
            </div>
        </div>

        <h3 style="margin-bottom:0.5rem;">Latest Orders</h3>
        <?php if (empty($latestOrders)): ?>
            <p>No orders yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latestOrders as $order): ?>
                    <tr>
                        <td>#<?php echo (int) $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $order['status'] === 'Pending' ? 'pending' : 'completed'; ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>


