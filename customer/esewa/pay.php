<?php
require_once __DIR__ . '/../../includes/esewa.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid order for payment.'];
    header('Location: ../checkout.php');
    exit;
}

esewa_ensure_tables($conn);

// Ensure the order belongs to the logged-in customer.
$stmt = $conn->prepare('SELECT id, total_amount, status, payment_method FROM orders WHERE id = ? AND customer_id = ? LIMIT 1');
$customerId = (int)$_SESSION['customer_id'];
$stmt->bind_param('ii', $orderId, $customerId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Order not found.'];
    header('Location: ../checkout.php');
    exit;
}

if ($order['payment_method'] !== 'eSewa') {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'This order is not set for eSewa payment.'];
    header('Location: ../checkout.php');
    exit;
}

if ($order['status'] === 'Completed') {
    $_SESSION['notification'] = ['type' => 'success', 'message' => 'This order is already completed.'];
    header('Location: ../checkout.php');
    exit;
}

$amount = esewa_normalize_amount($order['total_amount']);
$taxAmount = '0';
$totalAmount = $amount;

// transaction_uuid supports alphanumeric and hyphen only.
$transactionUuid = 'FOODIE-' . $orderId . '-' . time();

// Record INITIATED payment (or update if user retries)
$stmt = $conn->prepare(
    'INSERT INTO esewa_payments (order_id, transaction_uuid, product_code, total_amount, status)
     VALUES (?, ?, ?, ?, "INITIATED")
     ON DUPLICATE KEY UPDATE order_id = VALUES(order_id), product_code = VALUES(product_code), total_amount = VALUES(total_amount), status = "INITIATED"'
);
$productCode = ESEWA_PRODUCT_CODE;
$amountFloat = (float)$totalAmount;
$stmt->bind_param('issd', $orderId, $transactionUuid, $productCode, $amountFloat);
$stmt->execute();
$stmt->close();

$successUrl = esewa_base_url() . '/customer/esewa/success.php';
$failureUrl = esewa_base_url() . '/customer/esewa/failure.php';

$signedFieldNames = 'total_amount,transaction_uuid,product_code';
$signature = esewa_generate_signature([
    'total_amount' => $totalAmount,
    'transaction_uuid' => $transactionUuid,
    'product_code' => $productCode,
], $signedFieldNames);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to eSewa...</title>
</head>
<body>
<p>Redirecting to eSewa, please wait...</p>

<form action="<?php echo htmlspecialchars(ESEWA_FORM_URL); ?>" method="POST">
    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
    <input type="hidden" name="tax_amount" value="<?php echo htmlspecialchars($taxAmount); ?>">
    <input type="hidden" name="total_amount" value="<?php echo htmlspecialchars($totalAmount); ?>">
    <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transactionUuid); ?>">
    <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($productCode); ?>">
    <input type="hidden" name="product_service_charge" value="0">
    <input type="hidden" name="product_delivery_charge" value="0">
    <input type="hidden" name="success_url" value="<?php echo htmlspecialchars($successUrl); ?>">
    <input type="hidden" name="failure_url" value="<?php echo htmlspecialchars($failureUrl); ?>">
    <input type="hidden" name="signed_field_names" value="<?php echo htmlspecialchars($signedFieldNames); ?>">
    <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature); ?>">
    <noscript>
        <button type="submit">Continue to eSewa</button>
    </noscript>
</form>

<script>
    document.forms[0].submit();
</script>
</body>
</html>

