<?php
require_once __DIR__ . '/../../includes/esewa.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

esewa_ensure_tables($conn);

$dataB64 = $_GET['data'] ?? '';
if (!is_string($dataB64) || $dataB64 === '') {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid payment callback from eSewa.'];
    header('Location: ../checkout.php');
    exit;
}

$decoded = base64_decode($dataB64, true);
if ($decoded === false) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Could not decode eSewa response.'];
    header('Location: ../checkout.php');
    exit;
}

$payload = json_decode($decoded, true);
if (!is_array($payload)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Invalid eSewa response data.'];
    header('Location: ../checkout.php');
    exit;
}

$transactionUuid = (string)($payload['transaction_uuid'] ?? '');
$productCode = (string)($payload['product_code'] ?? '');
$totalAmount = isset($payload['total_amount']) ? esewa_normalize_amount($payload['total_amount']) : '';
$status = (string)($payload['status'] ?? '');
$signedFieldNames = (string)($payload['signed_field_names'] ?? '');
$signature = (string)($payload['signature'] ?? '');
$transactionCode = (string)($payload['transaction_code'] ?? '');

if ($transactionUuid === '' || $productCode === '' || $totalAmount === '' || $signedFieldNames === '' || $signature === '') {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Missing required eSewa response fields.'];
    header('Location: ../checkout.php');
    exit;
}

// Verify response signature integrity.
if (!esewa_verify_signature($payload, $signedFieldNames, $signature)) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'eSewa response signature verification failed.'];
    header('Location: ../checkout.php');
    exit;
}

// Find initiated payment record and corresponding order.
$stmt = $conn->prepare(
    'SELECT p.order_id, o.customer_id, o.total_amount, o.status AS order_status
     FROM esewa_payments p
     JOIN orders o ON o.id = p.order_id
     WHERE p.transaction_uuid = ? AND p.product_code = ? LIMIT 1'
);
$stmt->bind_param('ss', $transactionUuid, $productCode);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || (int)$row['customer_id'] !== (int)$_SESSION['customer_id']) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Payment record not found for this account.'];
    header('Location: ../checkout.php');
    exit;
}

$orderId = (int)$row['order_id'];
$orderTotal = esewa_normalize_amount($row['total_amount']);

if ($orderTotal !== $totalAmount) {
    $_SESSION['notification'] = ['type' => 'error', 'message' => 'Payment amount mismatch.'];
    header('Location: ../checkout.php');
    exit;
}

// Confirm with status check API when possible (optional in test; callback signature is the main check).
$statusCheck = esewa_status_check($productCode, $totalAmount, $transactionUuid);

$rawSuccessJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
$rawStatusJson = isset($statusCheck['data']) ? json_encode($statusCheck['data'], JSON_UNESCAPED_UNICODE) : null;

$statusFromApi = '';
if ($statusCheck['ok'] && !empty($statusCheck['data']['status'])) {
    $statusFromApi = (string)$statusCheck['data']['status'];
}

// Success: either API says COMPLETE, or callback is signed + COMPLETE (trust callback when API unreachable).
$isComplete = ($status === 'COMPLETE')
    && ($statusFromApi === 'COMPLETE' || $statusFromApi === '');

if ($isComplete) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('UPDATE esewa_payments SET status = "COMPLETE", transaction_code = ?, raw_success_data = ?, raw_status_data = ? WHERE transaction_uuid = ?');
        $stmt->bind_param('ssss', $transactionCode, $rawSuccessJson, $rawStatusJson, $transactionUuid);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE orders SET status = "Completed" WHERE id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['notification'] = ['type' => 'success', 'message' => 'Payment successful via eSewa! Your order ID is #' . $orderId];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['notification'] = ['type' => 'error', 'message' => 'Payment verified but could not update order.'];
    }

    header('Location: ../checkout.php');
    exit;
}

// Not complete -> store status and show message.
$finalStatus = in_array($statusFromApi, ['PENDING', 'AMBIGUOUS'], true) ? $statusFromApi : 'FAILED';
$stmt = $conn->prepare('UPDATE esewa_payments SET status = ?, raw_success_data = ?, raw_status_data = ? WHERE transaction_uuid = ?');
$stmt->bind_param('ssss', $finalStatus, $rawSuccessJson, $rawStatusJson, $transactionUuid);
$stmt->execute();
$stmt->close();

$_SESSION['notification'] = ['type' => 'error', 'message' => 'eSewa payment not completed (' . htmlspecialchars($statusFromApi) . ').'];
header('Location: ../checkout.php');
exit;

