<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int) $_POST['order_id'];
    $status = $_POST['status'] === 'Completed' ? 'Completed' : 'Pending';
    $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $orderId);
    $stmt->execute();
    $stmt->close();
}

// Fetch orders
$orders = [];
$result = $conn->query('SELECT o.id, c.name AS customer_name, o.total_amount, o.status, o.created_at 
                        FROM orders o 
                        JOIN customers c ON c.id = o.customer_id 
                        ORDER BY o.created_at DESC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Foodie Admin</title>
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
            <a href="logout.php" class="btn outline">Logout</a>
        </nav>
    </div>
</header>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Orders</h2>
            <p>View and manage customer orders.</p>
        </div>

        <?php if (empty($orders)): ?>
            <p>No orders placed yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
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
                        <td>
                            <form method="post" style="display:flex;gap:0.4rem;align-items:center;">
                                <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                <select name="status" style="padding:0.2rem 0.4rem;">
                                    <option value="Pending" <?php if ($order['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Completed" <?php if ($order['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                                </select>
                                <button class="btn outline" type="submit">Save</button>
                            </form>
                        </td>
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


