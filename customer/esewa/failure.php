<?php
require_once __DIR__ . '/../../includes/esewa.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

// eSewa may redirect to failure_url for FAILED *or* PENDING states.
// Sometimes it still includes the same base64 `data` payload; if present, process via success handler.
$dataB64 = $_GET['data'] ?? '';
if (is_string($dataB64) && $dataB64 !== '') {
    header('Location: success.php?data=' . urlencode($dataB64));
    exit;
}

$_SESSION['notification'] = [
    'type' => 'error',
    'message' => 'eSewa payment was not completed (or is pending). If your account was debited, please wait a moment and try again.'
];
header('Location: ../checkout.php');
exit;

