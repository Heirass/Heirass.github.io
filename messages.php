<?php
// PHP KISMI (messages.php'nin yerine)
header('Content-Type: application/json');

$filename = 'messages.json';

// JSON dosyasını yükle (yoksa oluştur)
if (!file_exists($filename)) {
    file_put_contents($filename, json_encode([]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'] ?? '';
    $message = $input['message'] ?? '';

    if (empty($username) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Boş alan bırakılamaz!']);
        exit;
    }

    $messages = json_decode(file_get_contents($filename), true);
    $messages[] = [
        'username' => htmlspecialchars($username),
        'message' => htmlspecialchars($message),
        'timestamp' => date('d.m.Y H:i:s')
    ];
    file_put_contents($filename, json_encode($messages));
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messages = json_decode(file_get_contents($filename), true) ?: [];
    echo json_encode(['success' => true, 'messages' => $messages]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<!-- JavaScript kısmı aşağıda -->
<script>
async function loadMessages() {
    const response = await fetch('index.php');
    const data = await response.json();
    if (data.success && data.messages) {
        const topicList = document.getElementById('topicList');
        if (data.messages.length === 0) {
            topicList.innerHTML = '<div class="empty-state">Henüz mesaj yok!</div>';
        } else {
            topicList.innerHTML = data.messages.map(msg => `
                <div class="forum-topic">
                    <strong>${msg.username}:</strong> ${msg.message}
                    <div class="timestamp">${msg.timestamp}</div>
                </div>
            `).join('');
        }
    }
}

async function addTopic() {
    const username = document.getElementById('username').value;
    const message = document.getElementById('message').value;

    const response = await fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, message })
    });
    
    const result = await response.json();
    if (result.success) {
        alert('Mesaj gönderildi!');
        loadMessages(); // Mesajları yenile
    } else {
        alert('Hata: ' + (result.error || 'Bilinmeyen bir hata oluştu.'));
    }
}

// Sayfa yüklendiğinde mesajları çek
document.addEventListener('DOMContentLoaded', loadMessages);
</script>

<!-- HTML Kısmı -->
<head>
    <title>Retro Forum</title>
    <style>
        body { font-family: Arial; }
        .forum-topic { border: 1px solid #ccc; margin: 10px; padding: 10px; }
        .timestamp { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Retro Forum</h1>
    <div id="topicList"></div>
    <form onsubmit="event.preventDefault(); addTopic();">
        <input type="text" id="username" placeholder="Adınız" required><br>
        <textarea id="message" placeholder="Mesajınız" required></textarea><br>
        <button type="submit">Gönder</button>
    </form>
</body>
</html>
