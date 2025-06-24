<?php
header('Content-Type: application/json');

$filename = 'messages.json';

// Mesajları yükle
function loadMessages($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $data = file_get_contents($filename);
    return json_decode($data, true);
}

// Mesajları kaydet
function saveMessages($filename, $messages) {
    file_put_contents($filename, json_encode($messages, JSON_PRETTY_PRINT));
}

// Mesajları al
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = loadMessages($filename);
    $response = [
        'success' => true,
        'messages' => $messages,
        'total' => count($messages)
    ];
    echo json_encode($response);
    exit;
}

// Yeni mesaj ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = isset($input['username']) ? htmlspecialchars($input['username']) : '';
    $message = isset($input['message']) ? htmlspecialchars($input['message']) : '';

    if (empty($username) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Kullanıcı adı ve mesaj boş olamaz.']);
        exit;
    }

    $messages = loadMessages($filename);
    $timestamp = date('Y-m-d H:i:s');
    $messages[] = [
        'username' => $username,
        'message' => $message,
        'timestamp' => $timestamp
    ];

    saveMessages($filename, $messages);
    echo json_encode(['success' => true]);
    exit;
}
?>
