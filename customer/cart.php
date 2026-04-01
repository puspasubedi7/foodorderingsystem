<?php
require_once __DIR__ . '/../includes/config.php';

 $cart = $_SESSION['cart'] ?? [];

// Update quantities or remove items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['qty'] as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId] = $qty;
            }
        }
        $_SESSION['cart'] = $cart;
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Cart updated successfully.'
        ];
        header('Location: cart.php');
        exit;
    }
    if (isset($_POST['clear_cart'])) {
        $cart = [];
        $_SESSION['cart'] = [];
        $_SESSION['notification'] = [
            'type' => 'info',
            'message' => 'Cart cleared successfully.'
        ];
        header('Location: cart.php');
        exit;
    }
}

// Fetch product details for items in cart
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
    <title>My Cart - Foodie</title>
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
        
        .cart-empty {
            text-align: center;
            padding: 2rem;
        }
        
        .cart-empty i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .cart-empty a {
            display: inline-block;
            margin-top: 1rem;
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
            <h2>Your Cart</h2>
            <p>Review your items before checkout.</p>
        </div>

        <?php if (empty($items)): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty.</p>
                <a href="menu.php" class="btn primary">Browse the menu</a>
            </div>
        <?php else: ?>
            <form method="post">
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th style="width:80px;">Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>
                                <input type="number" name="qty[<?php echo (int) $item['id']; ?>]"
                                       value="<?php echo (int) $item['quantity']; ?>" min="0"
                                       style="width:60px;">
                            </td>
                            <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                            <td>Rs.<?php echo number_format($item['line_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-footer" style="margin-top:1rem;">
                    <div>
                        <strong>Total: Rs.<?php echo number_format($total, 2); ?></strong>
                    </div>
                    <div style="display:flex;gap:0.5rem;">
                        <button class="btn outline" type="submit" name="clear_cart">Clear Cart</button>
                        <button class="btn outline" type="submit" name="update_cart">Update Cart</button>
                        <a href="checkout.php" class="btn primary">Checkout</a>
                    </div>
                </div>
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