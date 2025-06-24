<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

const API_URL = 'https://heirass.github.io/index.html'; // Note: Unused in this code

$messagesFile = 'forum_messages.json';

// Dosya yoksa boş array ile oluştur
if (!file_exists($messagesFile)) {
    if (!file_put_contents($messagesFile, json_encode([]), LOCK_EX)) {
        http_response_code(500);
        echo json_encode(['error' => 'Mesaj dosyası oluşturulamadı']);
        exit();
    }
}

// Mesajları yükle
function loadMessages() {
    global $messagesFile;
    $content = file_get_contents($messagesFile);
    if ($content === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Mesaj dosyası okunamadı']);
        exit();
    }
    $messages = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        echo json_encode(['error' => 'Mesaj dosyası geçersiz JSON formatında']);
        exit();
    }
    return $messages ?: [];
}

// Mesajları kaydet
function saveMessages($messages) {
    global $messagesFile;
    $result = file_put_contents($messagesFile, json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    if ($result === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Mesaj dosyasına yazılamadı']);
        exit();
    }
    return true;
}

// Güvenlik: HTML karakterlerini temizle
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Zaman formatla
function getTimestamp() {
    date_default_timezone_set('Europe/Istanbul');
    return date('H:i');
}

// POST isteği - Yeni mesaj ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['username']) && isset($input['message'])) {
        $username = sanitizeInput($input['username']);
        $message = sanitizeInput($input['message']);
        
        // Boş değerleri kontrol et
        if (empty($username) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username ve message boş olamaz']);
            exit();
        }
        
        // Uzunluk kontrolü
        if (strlen($username) > 20 || strlen($message) > 500) {
            http_response_code(400);
            echo json_encode(['error' => 'Username max 20, message max 500 karakter olabilir']);
            exit();
        }
        
        $messages = loadMessages();
        
        // Yeni mesaj objesi
        $newMessage = [
            'id' => uniqid(),
            'username' => $username,
            'message' => $message,
            'timestamp' => getTimestamp(),
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Mesajı listenin başına ekle
        array_unshift($messages, $newMessage);
        
        // Son 100 mesajı sakla (performans için)
        if (count($messages) > 100) {
            $messages = array_slice($messages, 0, 100);
        }
        
        // Kaydet
        saveMessages($messages);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Mesaj başarıyla kaydedildi']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Gerekli alanlar eksik']);
    }
}

// GET isteği - Mesajları getir
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = loadMessages();
    
    // Son N mesajı getir (opsiyonel limit parametresi)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $messages = array_slice($messages, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'total' => count($messages)
    ]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Sadece GET ve POST metodları desteklenir']);
}
?>