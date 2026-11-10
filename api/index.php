
<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// Only allow GET requests.
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    header('Allow: GET');

    respond(405, [
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
}

// Read the mobile number.
$number = $_GET['number'] ?? null;

if (!is_string($number) || trim($number) === '') {
    respond(400, [
        'success' => false,
        'error' => 'Mobile number is required.',
        'example' => '/api?number=01812345678'
    ]);
}

$number = trim($number);

// Accept local format, 880 format, or +880 format.
if (!preg_match('/^\+?[0-9]+$/', $number)) {
    respond(400, [
        'success' => false,
        'error' => 'Number must contain digits only, optionally prefixed by +.'
    ]);
}

// Normalize to Bangladesh local format.
if (str_starts_with($number, '+880')) {
    $number = '0' . substr($number, 4);
} elseif (str_starts_with($number, '880')) {
    $number = '0' . substr($number, 3);
}

// Bangladesh mobile number format:
// 11 digits, starts with 013–019.
if (!preg_match('/^01[3-9][0-9]{8}$/', $number)) {
    respond(400, [
        'success' => false,
        'error' => 'Invalid Bangladesh mobile number format.',
        'expected_format' => '01XXXXXXXXX'
    ]);
}

// Mask the number in the response.
$maskedNumber = substr($number, 0, 3)
    . '****'
    . substr($number, -4);

respond(200, [
    'success' => true,
    'country' => 'Bangladesh',
    'country_code' => '+880',
    'number' => $maskedNumber,
    'valid_format' => true,
    'message' => 'Number format is valid.',
    'note' => 'Format validation only. No SMS or OTP was sent.',
    'api_version' => '1.0.0',
    'timestamp' => gmdate('c')
]);
