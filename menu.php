<?php
require_once __DIR__ . '/includes/config.php';

$categoryImages = [
    'burger' => 'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=600&q=80',
    'pizza' => 'https://images.unsplash.com/photo-1548369937-47519962c11a?auto=format&fit=crop&w=600&q=80',
    'drink' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80',
    'salad' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=600&q=80',
    'dessert' => 'https://images.unsplash.com/photo-1505253758473-96b7015fcd40?auto=format&fit=crop&w=600&q=80',
    'taco' => 'https://images.unsplash.com/photo-1608039829576-285928fb90e6?auto=format&fit=crop&w=600&q=80',
    'ramen' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80',
    'burger_special' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=600&q=80',
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
$totalProducts = count($products);

// Build category list for filters
$categories = [];
foreach ($products as $product) {
    $cat = trim($product['category'] ?? '');
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}

$bookingMessage = '';
$bookingError = '';

// Handle table booking request (frontend acknowledgment only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_table'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $guests = (int) ($_POST['guests'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($name === '' || $phone === '' || $date === '' || $time === '' || $guests <= 0) {
        $bookingError = 'Please fill in name, phone, date, time, and number of guests.';
    } else {
        $bookingMessage = 'Thanks, ' . htmlspecialchars($name) . '! Your table request is noted. We will confirm shortly.';
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
    <title>Menu - Foodie</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="page">
    <div class="card">
        <div class="card-header">
            <h2>Our Menu</h2>
            <p>Pick your favorites and add them to your cart.</p>
        </div>
        <?php if ($totalProducts === 0): ?>
            <div class="empty-state">
                <p>No items available at the moment.</p>
                <p style="color:#9ca3af;">Please check back later or contact support.</p>
            </div>
        <?php else: ?>
            <div class="menu-toolbar">
                <div class="search-wrap">
                    <input id="menu-search" type="search" placeholder="Search dishes..." aria-label="Search menu">
                </div>
                <div class="toolbar-right">
                    <div class="menu-meta">
                        <span id="menu-count"><?php echo (int) $totalProducts; ?> items</span>
                    </div>
                    <select id="menu-sort" class="menu-sort" aria-label="Sort menu">
                        <option value="default">Sort: Featured</option>
                        <option value="name-asc">Name A → Z</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                    </select>
                </div>
                <?php if (!empty($categories)): ?>
                    <div class="category-chips" id="category-chips">
                        <button class="chip active" data-category="all">All</button>
                        <?php foreach ($categories as $cat): ?>
                            <button class="chip" data-category="<?php echo htmlspecialchars(strtolower($cat)); ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="region-grid">
                <div class="region-card">
                    <div class="region-pill">Italian</div>
                    <img src="https://images.unsplash.com/photo-1548369937-47519962c11a?auto=format&fit=crop&w=600&q=80" alt="Italian pizza">
                    <h3>Neapolitan Pizza</h3>
                    <p>Charred crusts, San Marzano tomatoes, fresh mozzarella.</p>
                </div>
                <div class="region-card">
                    <div class="region-pill">Mexican</div>
                    <img src="pic1.webp" alt="Mexican tacos">
                    <h3>Street Tacos</h3>
                    <p>Corn tortillas, lime-marinated meats, salsa fresca.</p>
                </div>
                <div class="region-card">
                    <div class="region-pill">Asian</div>
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=600&q=80" alt="Asian ramen">
                    <h3>Spicy Ramen</h3>
                    <p>Slow-simmered broth with chili oil and soft egg.</p>
                </div>
                <div class="region-card">
                    <div class="region-pill">American</div>
                    <img src="https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=600&q=80" alt="American burgers">
                    <h3>Smash Burgers</h3>
                    <p>Griddled patties, melty cheese, toasted brioche.</p>
                </div>
            </div>

            <div class="booking-section">
                <div class="card">
                    <div class="card-header">
                        <h2>Reserve a Table</h2>
                        <p>Dine-in requests for groups, celebrations, or meetings.</p>
                    </div>
                    <?php if ($bookingError): ?>
                        <div class="alert error"><?php echo $bookingError; ?></div>
                    <?php elseif ($bookingMessage): ?>
                        <div class="alert success"><?php echo $bookingMessage; ?></div>
                    <?php endif; ?>
                    <form method="post" class="form-grid booking-form">
                        <input type="hidden" name="book_table" value="1">
                        <div class="form-field">
                            <label for="name">Full name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-field">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-field">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                        <div class="form-field">
                            <label for="time">Time</label>
                            <input type="time" id="time" name="time" required>
                        </div>
                        <div class="form-field">
                            <label for="guests">Guests</label>
                            <input type="number" id="guests" name="guests" min="1" value="2" required>
                        </div>
                        <div class="form-field">
                            <label for="note">Notes (optional)</label>
                            <textarea id="note" name="note" rows="2" placeholder="Allergies, occasion, seating preference..."></textarea>
                        </div>
                        <button class="btn primary" type="submit">Send request</button>
                    </form>
                </div>

                <div class="service-grid">
                    <div class="service-card">
                        <div class="service-pill">Catering</div>
                        <h3>Events & Office</h3>
                        <p>Pre-set menus and bulk orders for meetings and parties.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-pill">Pickup</div>
                        <h3>Order & Collect</h3>
                        <p>Skip delivery fees—grab your order on the way.</p>
                    </div>
                    <div class="service-card">
                        <div class="service-pill">Specials</div>
                        <h3>Seasonal Taste</h3>
                        <p>Rotating chef specials inspired by global flavors.</p>
                    </div>
                </div>
            </div>

            <div class="menu-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $categoryKey = strtolower($product['category'] ?? '');
                    $imageUrl = $categoryImages[$categoryKey] ?? $defaultImage;
                    ?>
                    <div class="menu-card"
                         data-category="<?php echo htmlspecialchars($categoryKey ?: 'uncategorized'); ?>"
                         data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                         data-price="<?php echo htmlspecialchars($product['price']); ?>">
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Menu item image">
                        <div class="menu-card-top">
                            <span class="menu-card-pill"><?php echo htmlspecialchars($product['category'] ?: 'Special'); ?></span>
                            <span class="menu-card-price">Rs.<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        <div class="menu-card-title">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </div>
                        <div class="menu-card-desc">
                            <?php echo htmlspecialchars($product['description']); ?>
                        </div>
                        <div class="menu-card-bottom">
                            <form method="post">
                                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                <input class="qty-input" type="number" name="quantity" value="1" min="1">
                                <button class="btn outline" type="submit">Add</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Lightweight client-side filtering for menu
(function() {
    const searchInput = document.getElementById('menu-search');
    const chips = document.querySelectorAll('#category-chips .chip');
    const cards = Array.from(document.querySelectorAll('.menu-card'));
    const grid = document.querySelector('.menu-grid');
    const sortSelect = document.getElementById('menu-sort');
    const countEl = document.getElementById('menu-count');
    let activeCategory = 'all';

    function applyFilters() {
        const term = (searchInput?.value || '').toLowerCase().trim();
        let visible = 0;
        cards.forEach(card => {
            const name = card.dataset.name || '';
            const cat = card.dataset.category || 'uncategorized';
            const matchesCat = activeCategory === 'all' || cat === activeCategory;
            const matchesSearch = term === '' || name.includes(term);
            card.style.display = matchesCat && matchesSearch ? '' : 'none';
            if (card.style.display !== 'none') visible += 1;
        });
        if (countEl) countEl.textContent = `${visible} item${visible === 1 ? '' : 's'}`;
    }

    function applySort() {
        if (!grid) return;
        const value = sortSelect?.value || 'default';
        const sorted = [...cards].sort((a, b) => {
            const nameA = (a.dataset.name || '').localeCompare(b.dataset.name || '');
            const priceA = parseFloat(a.dataset.price || '0');
            const priceB = parseFloat(b.dataset.price || '0');
            switch (value) {
                case 'name-asc': return nameA;
                case 'price-asc': return priceA - priceB;
                case 'price-desc': return priceB - priceA;
                default: return 0;
            }
        });
        sorted.forEach(card => grid.appendChild(card));
    }

    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            activeCategory = chip.dataset.category || 'all';
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            applySort();
            applyFilters();
        });
    }

    // Initial state
    applyFilters();
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

