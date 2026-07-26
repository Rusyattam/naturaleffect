<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$chatMap = [
    '1' => '-5248565134',
    '4' => '-5257561951',
    '5' => '-5122655400',
    '7' => '-5180715578',
    '8' => '-5263039363',
    '9' => '-5205054614',
    '10' => '-5007803479',
    '11' => '-5226417176',
    '12' => '-5051286860',
    '13' => '-5270846031',
    '14' => '-5120181560',
    '17' => '-5172424709',
    '18' => '-5039690929',
    '19' => '-5178944065',
];

$token = '8686784975:AAFi-nQ8k19jjZlEC8a4SV0PVmhaSFN1-GI';

$clean = static function ($value, int $limit = 160): string {
    $value = trim((string) $value);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
};

$line = preg_replace('/[^0-9]/', '', (string)($data['line'] ?? '')) ?: '1';
$chatId = $chatMap[$line] ?? $chatMap['1'];

$name = $clean($data['name'] ?? '', 120);
$phone = $clean($data['phone'] ?? '', 40);
$address = $clean($data['address'] ?? '', 180);
$age = $clean($data['age'] ?? '', 20);

if ($name === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Name and phone are required']);
    exit;
}

$text = implode("\n", array_filter([
    'Yangi buyurtma: f9-2',
    'Ism Familiya: ' . $name,
    'Telefon: ' . $phone,
    'Turar joyi: ' . ($address !== '' ? $address : '-'),
    'Yosh: ' . ($age !== '' ? $age : '-'),
    'Sana: ' . date('d/m/Y, H:i:s'),
    'Liniya: ' . $line,
]));

$payload = json_encode([
    'chat_id' => $chatId,
    'text' => $text,
    'disable_web_page_preview' => true,
], JSON_UNESCAPED_UNICODE);

$url = 'https://api.telegram.org/bot' . $token . '/sendMessage';
$status = 0;
$error = '';

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 12,
    ]);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 12,
        ],
    ]);
    $response = file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? '';
    if (preg_match('/\s(\d{3})\s/', $statusLine, $match)) {
        $status = (int) $match[1];
    }
}

if ($response === false || $status < 200 || $status >= 300) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'message' => $error ?: 'Telegram request failed']);
    exit;
}

echo json_encode(['ok' => true]);
