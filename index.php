<?php
require_once __DIR__ . '/includes/config.php';

$categoryImages = [
    'burger' => 'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=600&q=80',
    'pizza' => 'https://images.unsplash.com/photo-1548369937-47519962c11a?auto=format&fit=crop&w=600&q=80',
    'drink' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80',
    'salad' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=600&q=80',
    'dessert' => 'https://images.unsplash.com/photo-1505253758473-96b7015fcd40?auto=format&fit=crop&w=600&q=80'
];
$defaultImage = 'https://images.unsplash.com/photo-1478145046317-39f10e56b5e9?auto=format&fit=crop&w=600&q=80';

// Fetch all active products
$products = [];
$result = $conn->query("SELECT id, name, description, price, category FROM products WHERE is_active = 1 ORDER BY category, name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle add to cart (simple session-based cart)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = (int) $_POST['product_id'];
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = 0;
    }
    $_SESSION['cart'][$productId] += $qty;
    header('Location: customer/cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Online Food Ordering</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="hero">
    <div class="hero-content">
        <h1>Delicious food, delivered to your door</h1>
        <p>Order from our curated menu of tasty dishes. Fresh, fast, and affordable.</p>
        <div class="hero-actions">
            <a href="/foodie/menu.php" class="btn primary">Order Now</a>
            <a href="/foodie/customer/register.php" class="btn outline">Create Account</a>
        </div>
    </div>
         <div class="hero-visual">
        <div class="hero-main">
            <img src="IMG_0002_3.webp"
                 alt="Chef crafted burger"
                 class="hero-main-img">
            <span class="hero-badge">Chef's Pick</span>
        </div>
        <div class="hero-gallery">
            <div class="hero-thumb">
                <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=500&q=80"
                     alt="Fresh salads">
                <span>Fresh Bowls</span>
            </div>
            <div class="hero-thumb">
                <img src="https://images.unsplash.com/photo-1548369937-47519962c11a?auto=format&fit=crop&w=500&q=80"
                     alt="Wood fired pizza">
                <span>Wood-Fired Pizza</span>
            </div>
        </div>
    </div>
</section>

<section class="special-offers">
    <div class="container">
        <div class="offers-header">
            <div>
                <p class="eyebrow">Fresh deals</p>
                <h2>Today’s Special Offers</h2>
                <p class="sub">Limited-time picks curated for you.</p>
            </div>
            <a href="/foodie/menu.php" class="btn outline">See full menu</a>
        </div>
    <div class="offers-container">
        <?php
        $offers = [
            [
                "title" => "8 Pcs/4 Pcs Fried Chicken ( Offer)",
                "price" => "NRS 1699",
                "image" => "images.jpg"
            ],
            [
                    "title" => "Burger Offer Deal",
                "price" => "NRS 500",
                "image" => "burger.jpg"
            ],
            [
                    "title" => "Tomato & Mozzarella Pizza",
                "price" => "NRS 1199",
                "image" => "pizza.webp"
            ],
            [
                    "title" => "Buy 1 Get 1 Free",
                "price" => "NRS 1500",
                "image" => "deep.jpg"
            ],
        ];
        ?>

        <?php foreach ($offers as $offer): ?>
        <div class="offer-card">
            <img src="<?php echo $offer['image']; ?>" alt="Offer Image">
                <div class="offer-body">
            <h3><?php echo $offer['title']; ?></h3>
            <p class="price"><?php echo $offer['price']; ?></p>
                </div>
                <a href="/foodie/menu.php" class="btn primary">View in Menu</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="about">
    <div class="container about-grid">
        <div class="about-copy">
            <p class="eyebrow">About us</p>
            <h2>Crafted by food lovers, delivered with care.</h2>
            <p class="sub">We partner with local kitchens, focus on fresh ingredients, and keep delivery times tight so your meal arrives hot and delicious.</p>
            <div class="about-points">
                <div class="point">
                    <span class="dot"></span>
                    <p>Trusted by hundreds of weekly customers.</p>
                </div>
                <div class="point">
                    <span class="dot"></span>
                    <p>Curated menu across burgers, pizza, bowls, and more.</p>
                </div>
                <div class="point">
                    <span class="dot"></span>
                    <p>Secure payments and 30-minute delivery zones in most areas.</p>
                </div>
            </div>
            <div class="about-actions">
                <a class="btn primary" href="/foodie/menu.php">View menu</a>
                <a class="btn outline" href="/foodie/customer/register.php">Create account</a>
            </div>
        </div>
        <div class="about-cards">
            <div class="about-card">
                <h3>Avg. delivery</h3>
                <p class="stat">24 min</p>
                <p class="muted">In our core delivery zones</p>
            </div>
            <div class="about-card">
                <h3>Customer rating</h3>
                <p class="stat">4.8/5</p>
                <p class="muted">Based on recent orders</p>
            </div>
            <div class="about-card">
                <h3>Menu items</h3>
                <p class="stat">45+</p>
                <p class="muted">Curated favorites and specials</p>
            </div>
        </div>
    </div>
</section>

<section class="admin-access">
    <div class="container admin-card">
        <div>
            <p class="eyebrow">Admin / staff</p>
            <h2>Manage menu, orders, and customers</h2>
            <p class="sub">Log in to update products, track orders, and keep operations running smoothly.</p>
        </div>
        <div class="admin-actions">
            <a class="btn primary" href="/foodie/admin/login.php">Admin Login</a>
            <a class="btn outline" href="/foodie/admin/login.php">Go to dashboard</a>
        </div>
    </div>
</section>

<section class="features">
    <h2>Why Choose Foodie?</h2>
    <div class="feature-grid">
        <div class="feature-card">
            <h3>Fast Delivery</h3>
            <p>Get your food delivered hot and fresh in under 30 minutes in most areas.</p>
        </div>
        <div class="feature-card">
            <h3>Curated Menu</h3>
            <p>Choose from a variety of popular dishes across cuisines.</p>
        </div>
        <div class="feature-card">
            <h3>Secure Payments</h3>
            <p>Safe and easy online payments or cash on delivery.</p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>


