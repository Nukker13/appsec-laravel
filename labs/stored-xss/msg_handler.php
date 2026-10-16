<?php

require __DIR__ . '/db.php';
use Doctrine\DBAL\DriverManager;


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// trim the message
$message = trim((string)($_POST['message'] ?? ''));

$maxLen = 100;
if (mb_strlen($message) > $maxLen) {
    $message = mb_substr($message, 0, $maxLen);
}

if ($message === '') {
    // redirect on empty message
    header('Location: index.php');
    exit;
}

try {
    // insert data
    $db->insert('messages', [
        'user_id'    => LAB_USER_ID,
        'email'      => LAB_USER_EMAIL,
        'content'    => $message,
        'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
} catch (\Throwable $e) {
    // Лёгкая отладка в dev: покажи ошибку (в production логируй)
    error_log('Insert message error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Server error';
    exit;
}

// После успешной вставки — редирект обратно на страницу (или в любое другое место)
header('Location: index.php');
exit;