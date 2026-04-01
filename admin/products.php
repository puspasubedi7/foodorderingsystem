<?php
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

 $error = '';
 $success = '';
 $edit_mode = false;
 $edit_product = null;

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    if ($name === '' || $price <= 0) {
        $error = 'Please enter name and valid price.';
    } else {
        $stmt = $conn->prepare('INSERT INTO products (name, description, price, category, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->bind_param('ssds', $name, $description, $price, $category);
        if ($stmt->execute()) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Product added successfully.'
            ];
            header('Location: products.php');
            exit;
        } else {
            $error = 'Could not add product.';
        }
        $stmt->close();
    }
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = (int) $_POST['product_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    if ($name === '' || $price <= 0) {
        $error = 'Please enter name and valid price.';
    } else {
        $stmt = $conn->prepare('UPDATE products SET name = ?, description = ?, price = ?, category = ? WHERE id = ?');
        $stmt->bind_param('ssdsi', $name, $description, $price, $category, $id);
        if ($stmt->execute()) {
            $_SESSION['notification'] = [
                'type' => 'success',
                'message' => 'Product updated successfully.'
            ];
            header('Location: products.php');
            exit;
        } else {
            $error = 'Could not update product.';
        }
        $stmt->close();
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Product deleted successfully.'
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Could not delete product.'
        ];
    }
    $stmt->close();
    header('Location: products.php');
    exit;
}

// Toggle product active
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $conn->prepare('UPDATE products SET is_active = IF(is_active=1,0,1) WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    
    // Get product name for notification
    $name_stmt = $conn->prepare('SELECT name, is_active FROM products WHERE id = ?');
    $name_stmt->bind_param('i', $id);
    $name_stmt->execute();
    $result = $name_stmt->get_result();
    $product = $result->fetch_assoc();
    $name_stmt->close();
    
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Product "' . htmlspecialchars($product['name']) . '" is now ' . ($product['is_active'] ? 'visible' : 'hidden') . ' on the menu.'
    ];
    
    header('Location: products.php');
    exit;
}

// Edit product - fetch product data
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT id, name, description, price, category, is_active FROM products WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_product = $result->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

// List products
 $products = [];
 $result = $conn->query('SELECT id, name, price, category, is_active FROM products ORDER BY created_at DESC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
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
    <title>Manage Products - Foodie Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn.delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn.delete:hover {
            background-color: #c0392b;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 500px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
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
    </style>
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

<!-- Notification Container -->
<div class="notification-container" id="notificationContainer"></div>

<main class="page">
    <div class="card" style="margin-bottom:1rem;">
        <div class="card-header">
            <h2><?php echo $edit_mode ? 'Edit Product' : 'Add New Product'; ?></h2>
            <p><?php echo $edit_mode ? 'Update product details.' : 'Add dishes to your online menu.'; ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" class="form-grid">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="update_product" value="1">
                <input type="hidden" name="product_id" value="<?php echo (int) $edit_product['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="add_product" value="1">
            <?php endif; ?>
            
            <div class="form-field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required value="<?php echo $edit_mode ? htmlspecialchars($edit_product['name']) : ''; ?>">
            </div>
            <div class="form-field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2"><?php echo $edit_mode ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
            </div>
            <div class="form-field">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" min="0" step="0.01" required value="<?php echo $edit_mode ? number_format($edit_product['price'], 2) : ''; ?>">
            </div>
            <div class="form-field">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="Burger, Pizza, Drink, ..." value="<?php echo $edit_mode ? htmlspecialchars($edit_product['category']) : ''; ?>">
            </div>
            <div class="form-buttons">
                <button class="btn primary" type="submit"><?php echo $edit_mode ? 'Update Product' : 'Add Product'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="products.php" class="btn outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Products</h2>
            <p>Manage items on your menu.</p>
        </div>
        <?php if (empty($products)): ?>
            <p>No products created yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo (int) $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $product['is_active'] ? 'completed' : 'pending'; ?>">
                                <?php echo $product['is_active'] ? 'Active' : 'Hidden'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a class="btn outline" href="products.php?toggle=<?php echo (int) $product['id']; ?>">
                                    <?php echo $product['is_active'] ? 'Hide' : 'Show'; ?>
                                </a>
                                <a class="btn outline" href="products.php?edit=<?php echo (int) $product['id']; ?>">
                                    Edit
                                </a>
                                <a class="btn delete" href="javascript:void(0);" onclick="confirmDelete(<?php echo (int) $product['id']; ?>)">
                                    Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this product? This action cannot be undone.</p>
        <div class="modal-buttons">
            <button class="btn outline" onclick="closeModal()">Cancel</button>
            <a id="confirmDeleteBtn" class="btn delete" href="#">Delete</a>
        </div>
    </div>
</div>

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
    
    function confirmDelete(id) {
        document.getElementById('confirmDeleteBtn').href = 'products.php?delete=' + id;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>