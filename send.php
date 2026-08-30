<?php
// send.php — отправка заявки в Telegram нескольким получателям

// === 1. РАЗРЕШАЕМ ТОЛЬКО POST-ЗАПРОСЫ ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// === 2. ЗАЩИТА ОТ БОТОВ: HONEYPOT ===
if (!empty($_POST['honeypot'])) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OK']);
    exit;
}

// === 3. ПОЛУЧАЕМ ДАННЫЕ ИЗ ФОРМЫ ===
$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$message = trim($_POST['message'] ?? '');

// === 4. ВАЛИДАЦИЯ НА СЕРВЕРЕ ===
$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
    $errors[] = 'name';
}
if (!preg_match('/^\+7\s?\(\d{3}\)\s?\d{3}-\d{2}-\d{2}$/', $phone)) {
    $errors[] = 'phone';
}
if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) {
    $errors[] = 'message';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => 'validation', 'fields' => $errors]);
    exit;
}

// === 5. ЗАЩИТА ОТ XSS И HTML-ИНЪЕКЦИЙ ===
$name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$phone   = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// === 6. TELEGRAM НАСТРОЙКИ ===
$TG_BOT_TOKEN = '8810035339:AAFRV_SZA7rHDSu2XpI8IlRTJeMk0Ru0kmQ'; // ⚠️ ВСТАВЬТЕ СЮДА НОВЫЙ ТОКЕН!

// СПИСОК ПОЛУЧАТЕЛЕЙ — добавляйте chat_id через запятую
// Каждый получатель должен написать боту /start, иначе сообщение не дойдёт
$TG_CHAT_IDS = [
    '5748323500',   // Вы (основной)
    '410982202', // Второй получатель (раскомментируйте и вставьте ID)
    // '222222222', // Третий получатель
    // '333333333', // Четвёртый получатель
];

// === 7. ФОРМИРУЕМ КРАСИВОЕ СООБЩЕНИЕ ===
$text = "🔥 <b>Новая заявка с сайта</b>\n\n";
$text .= "👤 <b>Имя:</b> $name\n";
$text .= "📞 <b>Телефон / Telegram:</b> <code>$phone</code>\n";
$text .= "💬 <b>Сообщение:</b>\n$message\n\n";
$text .= "━━━━━━━━━━━━━━━━━━━━━\n";
$text .= "🕐 <b>Время:</b> " . date('d.m.Y H:i:s') . "\n";
$text .= "🌐 <b>IP:</b> <code>{$_SERVER['REMOTE_ADDR']}</code>\n";
$text .= "📱 <b>Browser:</b> " . substr($_SERVER['HTTP_USER_AGENT'], 0, 100);

$url = "https://api.telegram.org/bot$TG_BOT_TOKEN/sendMessage";

// === 8. ОТПРАВЛЯЕМ КАЖДОМУ ПОЛУЧАТЕЛЮ ===
$all_success = true;
$failed_chats = [];

foreach ($TG_CHAT_IDS as $chat_id) {
    $data = [
        'chat_id'    => $chat_id,
        'text'       => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Проверяем результат для этого получателя
    if ($result === FALSE || $http_code !== 200) {
        error_log("Telegram API Error (chat $chat_id): $curl_error | Response: $result");
        $all_success = false;
        $failed_chats[] = $chat_id;
        continue; // пробуем следующего
    }

    $response = json_decode($result, true);
    if (!$response['ok']) {
        error_log("Telegram Error (chat $chat_id): " . $response['description']);
        $all_success = false;
        $failed_chats[] = $chat_id;
    }
}

// === 9. УЧЁТ ОТПРАВЛЕННОЙ ФОРМЫ В СТАТИСТИКЕ ===
$statsFile = __DIR__ . '/stats.json';
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    if (is_array($stats)) {
        $stats['total_forms'] = ($stats['total_forms'] ?? 0) + 1;
        if (isset($stats['today']) && $stats['today']['date'] === date('Y-m-d')) {
            $stats['today']['forms'] = ($stats['today']['forms'] ?? 0) + 1;
        }
        file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// === 10. ОТВЕТ КЛИЕНТУ ===
if (!$all_success) {
    http_response_code(500);
    echo json_encode([
        'error' => 'telegram_partial_fail',
        'message' => 'Не удалось отправить некоторым получателям: ' . implode(', ', $failed_chats)
    ]);
    exit;
}

// Успех — все получили заявку
http_response_code(200);
echo json_encode(['success' => true]);
