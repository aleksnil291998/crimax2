<?php
// send.php — отправка заявки в Telegram + сохранение в leads.json

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Honeypot защита
if (!empty($_POST['honeypot'])) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OK']);
    exit;
}

// Данные формы
$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$message = trim($_POST['message'] ?? '');

// Валидация
$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 60) $errors[] = 'name';
if (!preg_match('/^\+7\s?\(\d{3}\)\s?\d{3}-\d{2}-\d{2}$/', $phone)) $errors[] = 'phone';
if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) $errors[] = 'message';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'validation', 'fields' => $errors]);
    exit;
}

// Защита от XSS
$name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Telegram настройки
$TG_BOT_TOKEN = '8810035339:AAFRV_SZA7rHDSu2XpI8IlRTJeMk0Ru0kmQ';
$TG_CHAT_IDS = ['5748323500', '410982202'];

// Формируем сообщение
$text = "🔥 <b>Новая заявка с сайта</b>\n\n";
$text .= "👤 <b>Имя:</b> $name\n";
$text .= "📞 <b>Телефон:</b> <code>$phone</code>\n";
$text .= "💬 <b>Сообщение:</b>\n$message\n\n";
$text .= "━━━━━━━━━━━━━━━━━━━━━\n";
$text .= "🕐 <b>Время:</b> " . date('d.m.Y H:i:s') . "\n";
$text .= "🌐 <b>IP:</b> <code>" . $_SERVER['REMOTE_ADDR'] . "</code>\n";

// Отправка в Telegram
$url = "https://api.telegram.org/bot$TG_BOT_TOKEN/sendMessage";
$all_success = true;

foreach ($TG_CHAT_IDS as $chat_id) {
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result === FALSE || $http_code !== 200) {
        $all_success = false;
        error_log("Telegram Error (chat $chat_id): " . curl_error($ch));
    } else {
        $response = json_decode($result, true);
        if (!$response['ok']) $all_success = false;
    }
}

// Сохранение в leads.json
$leadsFile = __DIR__ . '/leads.json';
$leads = [];
if (file_exists($leadsFile)) {
    $leads = json_decode(file_get_contents($leadsFile), true) ?: [];
}

$leads[] = [
    'name' => $name,
    'phone' => $phone,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'created_at' => date('Y-m-d H:i:s')
];

file_put_contents($leadsFile, json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Обновление статистики
$statsFile = __DIR__ . '/stats.json';
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    if (is_array($stats)) {
        $stats['total_forms'] = ($stats['total_forms'] ?? 0) + 1;
        if (!isset($stats['today']) || $stats['today']['date'] !== date('Y-m-d')) {
            $stats['today'] = ['date' => date('Y-m-d'), 'forms' => 0, 'views' => 0];
        }
        $stats['today']['forms'] = ($stats['today']['forms'] ?? 0) + 1;
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Ответ
http_response_code($all_success ? 200 : 500);
echo json_encode(['success' => $all_success]);
