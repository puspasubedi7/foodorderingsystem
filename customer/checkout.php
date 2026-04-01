<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

 $cart = $_SESSION['cart'] ?? [];
 $items = [];
 $total = 0;

if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $result = $conn->query("SELECT id, name, price FROM products WHERE id IN ($ids)");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pid = $row['id'];
            $qty = $cart[$pid] ?? 0;
            $row['quantity'] = $qty;
            $row['line_total'] = $qty * $row['price'];
            $total += $row['line_total'];
            $items[] = $row;
        }
    }
}

 $message = '';
 $error = '';
 $orderPlaced = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    $address = trim($_POST['address'] ?? '');
    $payment = trim($_POST['payment_method'] ?? 'COD');
    if ($payment === 'eSewa') {
        $payment = 'eSewa';
    }

    if ($address === '') {
        $error = 'Please enter delivery address.';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('INSERT INTO orders (customer_id, total_amount, address, payment_method, status) VALUES (?, ?, ?, ?, "Pending")');
            $customerId = $_SESSION['customer_id'];
            $totalAmount = $total;
            $stmt->bind_param('idss', $customerId, $totalAmount, $address, $payment);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();

            $stmtItem = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            foreach ($items as $item) {
                $pid = $item['id'];
                $qty = $item['quantity'];
                $price = $item['price'];
                $stmtItem->bind_param('iiid', $orderId, $pid, $qty, $price);
                $stmtItem->execute();
            }
            $stmtItem->close();

            $conn->commit();
            $_SESSION['cart'] = [];
            $orderPlaced = true;

            if ($payment === 'eSewa') {
                // Redirect to eSewa payment gateway
                header('Location: esewa/pay.php?order_id=' . (int)$orderId);
                exit;
            }

            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Order placed successfully! Your order ID is #' . $orderId
            ];
            header('Location: checkout.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Could not place order. Please try again.';
        }
    }
}

// Check for notification in session
 $notification = null;
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    unset($_SESSION['notification']); // Clear notification after retrieving
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Foodie</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Notification Styles */
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }
        
        .notification {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out;
            transition: opacity 0.3s ease-out;
        }
        
        .notification.success {
            background-color: #2ecc71;
            color: white;
        }
        
        .notification.error {
            background-color: #e74c3c;
            color: white;
        }
        
        .notification.info {
            background-color: #3498db;
            color: white;
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .notification i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            margin-left: 10px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .notification.hiding {
            opacity: 0;
            transform: translateX(100%);
        }
        
        .order-success {
            text-align: center;
            padding: 2rem;
        }
        
        .order-success i {
            font-size: 3rem;
            color: #2ecc71;
            margin-bottom: 1rem;
        }
        
        .order-success a {
            display: inline-block;
            margin-top: 1rem;
        }
        
        .order-summary {
            margin-bottom: 1.5rem;
        }
        
        .order-summary table {
            margin-bottom: 1rem;
        }
        
        .order-summary .total {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Notification Container -->
<div class="notification-container" id="notificationContainer"></div>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Checkout</h2>
            <p>Confirm your order and delivery details.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>No items to checkout.</p>
                <a href="menu.php" class="btn primary">Go to menu</a>
            </div>
        <?php elseif ($notification && $notification['type'] === 'success'): ?>
            <div class="order-success">
                <i class="fas fa-check-circle"></i>
                <h3>Order Placed Successfully!</h3>
                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center;">
                    <a href="orders.php" class="btn primary">View My Orders</a>
                    <a href="menu.php" class="btn outline">Continue Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo (int) $item['quantity']; ?></td>
                            <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                            <td>Rs.<?php echo number_format($item['line_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="total">Total: Rs.<?php echo number_format($total, 2); ?></div>
            </div>

            <form method="post" class="form-grid">
                <div class="form-field">
                    <label for="address">Delivery address</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                <div class="form-field">
                    <label for="payment_method">Payment method</label>
                    <select id="payment_method" name="payment_method">
                        <option value="COD">Cash on Delivery</option>
                        <option value="Card">Card (on delivery)</option>
                        <option value="eSewa">eSewa (Online)</option>
                    </select>
                </div>
                <button class="btn primary" type="submit">Place Order</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
    // Notification System
    function showNotification(type, message) {
        const container = document.getElementById('notificationContainer');
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // Determine icon based on type
        let icon = '';
        if (type === 'success') {
            icon = '<i class="fas fa-check-circle"></i>';
        } else if (type === 'error') {
            icon = '<i class="fas fa-exclamation-circle"></i>';
        } else if (type === 'info') {
            icon = '<i class="fas fa-info-circle"></i>';
        }
        
        notification.innerHTML = `
            <div class="notification-content">
                ${icon}
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="hideNotification(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(notification);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            hideNotification(notification.querySelector('.notification-close'));
        }, 5000);
    }
    
    function hideNotification(button) {
        const notification = button.closest('.notification');
        notification.classList.add('hiding');
        
        // Remove from DOM after animation completes
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
    
    // Show notification from session if exists
    <?php if ($notification): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showNotification('<?php echo $notification['type']; ?>', '<?php echo $notification['message']; ?>');
    });
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>