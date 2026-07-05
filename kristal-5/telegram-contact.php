<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Tashkent');

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Метод не поддерживается']);
}

$configFile = __DIR__ . '/.telegram-config.php';
$config = is_file($configFile) ? require $configFile : [];
$botToken = getenv('TELEGRAM_BOT_TOKEN') ?: (string)($config['bot_token'] ?? '8686784975:AAFi-nQ8k19jjZlEC8a4SV0PVmhaSFN1-GI');
$chatId = (string)($config['chat_id'] ?? (getenv('TELEGRAM_CHAT_ID') ?: '-5120181560'));

if ($botToken === '' || $chatId === '') {
    respond(500, ['ok' => false, 'message' => 'Telegram не настроен']);
}

$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$name = trim((string)($payload['name'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));
$phoneDisplay = trim((string)($payload['phoneDisplay'] ?? $payload['phone_display'] ?? ''));
$address = trim((string)($payload['address'] ?? ''));
$age = trim((string)($payload['age'] ?? ''));
$service = trim((string)($payload['service'] ?? ''));
$source = trim((string)($payload['source'] ?? ''));
$number = trim((string)($payload['number'] ?? ''));
$message = trim((string)($payload['message'] ?? ''));
$date = trim((string)($payload['date'] ?? ''));

if ($name === '' || $phone === '') {
    respond(422, ['ok' => false, 'message' => 'Укажите имя и телефон']);
}

if (text_length($name) > 100 || text_length($phone) > 40 || text_length($phoneDisplay) > 40
    || text_length($address) > 200 || text_length($age) > 20 || text_length($service) > 150
    || text_length($source) > 80 || text_length($number) > 100 || text_length($message) > 2000
    || text_length($date) > 80) {
    respond(422, ['ok' => false, 'message' => 'Слишком длинные данные']);
}

$displayPhone = $phoneDisplay !== '' ? $phoneDisplay : $phone;
$displayDate = $date !== '' ? $date : date('d.m.Y, H:i:s');

if ($source !== '' || $address !== '' || $age !== '') {
    $title = $source !== '' ? $source : 'MIGRA';
    $text = "🆕 Yangi buyurtma: {$title}\n\n"
        . "👤 Ism Familiya: {$name}\n"
        . "📱 Telefon: {$displayPhone}"
        . ($address !== '' ? "\n📍 Turar joyi: {$address}" : '')
        . ($age !== '' ? "\n🎂 Yosh: {$age}" : '')
        . ($service !== '' && $service !== 'Выберите услугу' ? "\n📌 Xizmat: {$service}" : '')
        . ($message !== '' ? "\n💬 Izoh: {$message}" : '')
        . "\n🕐 Sana: {$displayDate}";
} else {
    $text = "Новая заявка\n"
        . "Имя: {$name}\n"
        . "Телефон: {$phone}"
        . ($service !== '' ? "\nУслуга: {$service}" : '')
        . ($number !== '' ? "\nНомер заявки: {$number}" : '')
        . ($message !== '' ? "\nВопрос: {$message}" : '');
}

$telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
$telegramBody = json_encode([
    'chat_id' => $chatId,
    'text' => $text,
    'disable_web_page_preview' => true,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$response = false;
$status = 0;
$error = '';

if (function_exists('curl_init')) {
    $request = curl_init($telegramUrl);
    curl_setopt_array($request, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $telegramBody,
    ]);

    $response = curl_exec($request);
    $status = (int)curl_getinfo($request, CURLINFO_HTTP_CODE);
    $error = curl_error($request);
    curl_close($request);
} else {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $telegramBody,
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($telegramUrl, false, $context);
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $status = (int)$matches[1];
    }
    if ($response === false) {
        $lastError = error_get_last();
        $error = (string)($lastError['message'] ?? 'HTTP request failed');
    }
}

$telegramResult = is_string($response) ? json_decode($response, true) : null;
$telegramDescription = is_array($telegramResult) ? (string)($telegramResult['description'] ?? '') : '';

if ($response === false || $status < 200 || $status >= 300 || !is_array($telegramResult) || empty($telegramResult['ok'])) {
    error_log('Telegram send failed: ' . ($error ?: $response));
    respond(502, [
        'ok' => false,
        'message' => $telegramDescription !== '' ? 'Telegram: ' . $telegramDescription : 'Не удалось отправить заявку',
    ]);
}

respond(200, ['ok' => true]);
