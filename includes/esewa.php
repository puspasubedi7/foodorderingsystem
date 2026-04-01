<?php
require_once __DIR__ . '/config.php';

// eSewa ePay v2 (UAT/test) defaults.
// For production replace with your real merchant credentials and endpoints.
const ESEWA_PRODUCT_CODE = 'EPAYTEST';
const ESEWA_SECRET_KEY = '8gBm/:&EnhH.1/q';
const ESEWA_FORM_URL = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
// Test env: try rc-epay first (matches form); fallback uat. Production: use epay.esewa.com.np
const ESEWA_STATUS_URLS = [
    'https://rc-epay.esewa.com.np/api/epay/transaction/status/',
    'https://uat.esewa.com.np/api/epay/transaction/status/',
];

function esewa_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Assuming the project is served as http(s)://host/foodie
    return $scheme . '://' . $host . '/foodie';
}

function esewa_normalize_amount($amount): string
{
    $n = (float) $amount;
    return number_format($n, 2, '.', '');
}

function esewa_build_message(array $fields, string $signedFieldNames): string
{
    $names = array_filter(array_map('trim', explode(',', $signedFieldNames)));
    $parts = [];
    foreach ($names as $name) {
        $value = $fields[$name] ?? '';
        $parts[] = $name . '=' . $value;
    }
    return implode(',', $parts);
}

function esewa_generate_signature(array $fields, string $signedFieldNames, string $secretKey = ESEWA_SECRET_KEY): string
{
    $message = esewa_build_message($fields, $signedFieldNames);
    return base64_encode(hash_hmac('sha256', $message, $secretKey, true));
}

function esewa_verify_signature(array $fields, string $signedFieldNames, string $signature, string $secretKey = ESEWA_SECRET_KEY): bool
{
    $expected = esewa_generate_signature($fields, $signedFieldNames, $secretKey);
    return hash_equals($expected, $signature);
}

function esewa_ensure_tables(mysqli $conn): void
{
    // Safe to run repeatedly.
    $conn->query(
        "CREATE TABLE IF NOT EXISTS esewa_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            transaction_uuid VARCHAR(80) NOT NULL,
            product_code VARCHAR(30) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('INITIATED','COMPLETE','FAILED','AMBIGUOUS','PENDING') NOT NULL DEFAULT 'INITIATED',
            transaction_code VARCHAR(50) NULL,
            raw_success_data LONGTEXT NULL,
            raw_status_data LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_esewa_txn (transaction_uuid),
            INDEX idx_esewa_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function esewa_status_check(string $productCode, string $totalAmount, string $transactionUuid): array
{
    $query = http_build_query([
        'product_code' => $productCode,
        'total_amount' => $totalAmount,
        'transaction_uuid' => $transactionUuid,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'header' => "Accept: application/json\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ]
    ]);

    foreach (ESEWA_STATUS_URLS as $baseUrl) {
        $url = $baseUrl . '?' . $query;
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false) {
            continue;
        }
        $json = json_decode($raw, true);
        if (is_array($json) && isset($json['status'])) {
            return ['ok' => true, 'data' => $json];
        }
    }

    return ['ok' => false, 'error' => 'Could not reach eSewa status API.'];
}

