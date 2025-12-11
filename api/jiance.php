<?php
require '../config.php';
$config = getBotSettings($pdo);

$token = $config['token'];
$chatId = $config['chat_id'];

$apiUrl = "https://api.telegram.org/bot$token/";
$now = time();

$sql = "SELECT * FROM orders WHERE status = 1 AND dqtime < :now";
$stmt = $pdo->prepare($sql);
$stmt->execute([':now' => $now]);
$expiredOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expiredOrders as $order) {
    $userId   = $order['uid'];   
    $userName = $order['user'];  
    $name     = $order['name'];  

    // 2. 发送提醒消息（艾特用户）
    $message = "⚠️ 用户 {$name} (ID: `{$userId}`)\n您的订阅已到期，将被移出群组。";
    
    file_get_contents($apiUrl . "sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ]));

    // 3. 踢出用户
    file_get_contents($apiUrl . "kickChatMember?" . http_build_query([
        'chat_id' => $chatId,
        'user_id' => $userId
    ]));

    $update = $pdo->prepare("UPDATE orders SET status = 2 WHERE id = ?");
    $update->execute([$order['id']]);
}

echo "过期检测完成，共处理 " . count($expiredOrders) . " 个用户。\n";
