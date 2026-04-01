<?php
// eSewa TEST credentials
$secretKey = "8gBm/:&EnhH.1/q"; // TEST SECRET KEY
$productCode = "EPAYTEST";

$amount = $_POST['amount'];
$taxAmount = 0;
$totalAmount = $amount + $taxAmount;

// UNIQUE transaction UUID
$transactionUUID = "TXN_" . time();

// Signature fields
$signedFieldNames = "total_amount,transaction_uuid,product_code";
$message = "total_amount=$totalAmount,transaction_uuid=$transactionUUID,product_code=$productCode";

// Generate signature
$signature = base64_encode(
    hash_hmac('sha256', $message, $secretKey, true)
);
?>

<form action="https://rc-epay.esewa.com.np/api/epay/main/v2/form" method="POST">

    <input type="hidden" name="amount" value="<?= $amount ?>">
    <input type="hidden" name="tax_amount" value="<?= $taxAmount ?>">
    <input type="hidden" name="total_amount" value="<?= $totalAmount ?>">
    <input type="hidden" name="transaction_uuid" value="<?= $transactionUUID ?>">
    <input type="hidden" name="product_code" value="<?= $productCode ?>">
    <input type="hidden" name="product_service_charge" value="0">
    <input type="hidden" name="product_delivery_charge" value="0">
    <input type="hidden" name="success_url" value="http://localhost/esewa-test/success.php">
    <input type="hidden" name="failure_url" value="http://localhost/esewa-test/failure.php">
    <input type="hidden" name="signed_field_names" value="<?= $signedFieldNames ?>">
    <input type="hidden" name="signature" value="<?= $signature ?>">

    <button type="submit">Redirecting to eSewa...</button>
</form>

<script>
    document.forms[0].submit();
</script>
