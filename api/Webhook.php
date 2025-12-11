<?php
require 'pay.php';
$config = getBotSettings($pdo);

$input = file_get_contents('php://input');
$update = json_decode($input, true);

$token = $config['token'];
$url = "https://api.telegram.org/bot$token/";


if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];


    if (isset($update['message']['text'])) {
        $text = $update['message']['text'];

        if ($text === '/start') {
            $firstName = $update['message']['from']['first_name'] ?? '用户';
            $username = $update['message']['from']['username'] ?? '未知用户名';
            $userId   = $update['message']['from']['id'];

            $reply = "🎉 您好，{$firstName}!\n\n用户名：@{$username}\n个人ID：{$userId}\n\n请点击下方按钮操作。";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '获取套餐', 'callback_data' => 'get_package']
                    ]
                ]
            ];

            $postData = [
                'chat_id' => $chatId,
                'text' => $reply,
                'reply_markup' => json_encode($keyboard)
            ];

            file_get_contents($url . "sendMessage?" . http_build_query($postData));
        }
    }

    if (isset($update['message']['new_chat_members'])) {
        foreach ($update['message']['new_chat_members'] as $newMember) {
    $firstName = $newMember['first_name'] ?? '用户';
    $username  = isset($newMember['username']) ? '@'.$newMember['username'] : '未知用户名';
    $userId    = $newMember['id'];

    $chatTitle = $update['message']['chat']['title'] ?? '本群';
    $groupId   = $update['message']['chat']['id'];

    $welcome = "🎉 欢迎新成员加入 {$chatTitle}！\n\n"
             . "👤 姓名：{$firstName}\n"
             . "🔹 用户名：{$username}\n"
             . "🆔 用户ID：`{$userId}`\n"
             . "💬 群ID：`{$groupId}`";

    file_get_contents($url . "sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $welcome,
        'parse_mode' => 'Markdown'
    ]));
}

    }
}


if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chatId = $callback['message']['chat']['id'];
    $data   = $callback['data']; 
    
    $sql = "SELECT * FROM taocan";
    $stmt =$pdo->prepare($sql);
    $stmt->execute();
    $taocans =$stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($data === 'get_package') {
    $reply = "📦 套餐信息如下：\n\n";
    $inlineKeyboard = [];

    foreach ($taocans as $row) {
        $reply .= "🔹 套餐名称：{$row['name']}\n";
        $reply .= "💰 价格：{$row['price']} 元\n";
        $reply .= "⏳ 时长：{$row['shijian']} 天\n";
        $reply .= "-----------------------------\n";
        
        $inlineKeyboard[] = [
            ['text' => "购买 {$row['name']}", 'callback_data' => "buy_{$row['id']}"]
        ];
    }
    
    $keyboard = [
        'inline_keyboard' => $inlineKeyboard
    ];

    file_get_contents($url . "sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $reply,
        'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE)
    ]));

    }

}


if (strpos($data, 'buy_') === 0) {
    $packageId = str_replace('buy_', '', $data);


    $stmt = $pdo->prepare("SELECT * FROM taocan WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($package) {
        $reply = "✅ 您选择的套餐：\n\n" .
                 "🔹 名称：{$package['name']}\n" .
                 "💰 价格：{$package['price']} 元\n" .
                 "⏳ 时长：{$package['shijian']} 天\n\n" .
                 "请选择支付方式：";


        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '微信支付', 'callback_data' => "pay_wechat_{$package['id']}"],
                    ['text' => '支付宝支付', 'callback_data' => "pay_alipay_{$package['id']}"]
                ],
                [
                    ['text' => '取消 ❌', 'callback_data' => "cancel"]
                ]
            ]
        ];


        file_get_contents($url . "sendMessage?" . http_build_query([
            'chat_id' => $chatId,
            'text' => $reply,
            'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE)
        ]));
    }
}

if (strpos($data, 'pay_wechat_') === 0) {
    $packageId = str_replace('pay_wechat_', '', $data);


    $stmt = $pdo->prepare("SELECT * FROM taocan WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($package) {
    $userId    = $callback['from']['id']; 
    $username  = $callback['from']['username'] ?? '未知用户名';
    $firstName = $callback['from']['first_name'] ?? '用户';

    $createTime = time();
    $expireTime = $createTime + ($package['shijian'] * 86400);
    $orderNo = $createTime . mt_rand(1000, 9999);

    $insert = $pdo->prepare("INSERT INTO orders (uid, user, name, ddh, createtime, dqtime, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $userId,
        $username,
        $firstName,
        $orderNo,
        $createTime,
        $expireTime,
        0
    ]);


    $reply = "🟢 您选择了微信支付\n\n"
           . "🔹 套餐名称：{$package['name']}\n"
           . "💰 价格：{$package['price']} 元\n"
           . "⏳ 时长：{$package['shijian']} 天\n"
           . "📄 订单号：`{$orderNo}`\n\n"
           . "请使用微信扫描下方二维码完成支付👇";


    $paymentLink = getpay("wxpay", $orderNo, $package['name'], $package['price'], $pay);

    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($paymentLink);


    file_get_contents($url . "sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $reply,
        'parse_mode' => 'Markdown'
    ]));


    $postData = [
        'chat_id' => $chatId,
        'photo' => $qrUrl,
        'caption' => "📷 微信支付二维码\n请长按识别或扫码完成支付"
    ];

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($postData),
        ]
    ]);
    file_get_contents($url . "sendPhoto", false, $context);
}

}


if (strpos($data, 'pay_alipay_') === 0) {
    $packageId = str_replace('pay_alipay_', '', $data);


    $stmt = $pdo->prepare("SELECT * FROM taocan WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($package) {

        $userId    = $callback['from']['id']; 
        $username  = $callback['from']['username'] ?? '未知用户名';
        $firstName = $callback['from']['first_name'] ?? '用户';


        $createTime = time();

        $expireTime = $createTime + ($package['shijian'] * 86400);


        $orderNo = $createTime . mt_rand(1000, 9999);


        $insert = $pdo->prepare("INSERT INTO orders (uid, user, name, ddh, createtime, dqtime, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([
            $userId,
            $username,
            $firstName,
            $orderNo,
            $createTime,
            $expireTime,
            0 // 初始状态未支付
        ]);


        $reply = "🟡 您选择了支付宝支付。\n\n" .
                 "🔹 套餐名称：{$package['name']}\n" .
                 "💰 价格：{$package['price']} 元\n" .
                 "⏳ 时长：{$package['shijian']} 天\n" .
                 "📄 订单号：{$orderNo}\n\n" .
                 "请点击下方链接完成支付宝支付。";


        $paymentLink = getpay("alipay",$orderNo, $package['name'],$package['price'], $pay); 

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '去支付 🛒', 'url' => $paymentLink]
                ]
            ]
        ];

        file_get_contents($url . "sendMessage?" . http_build_query([
            'chat_id' => $chatId,
            'text' => $reply,
            'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE)
        ]));
    }
}

if ($data === 'cancel') {

    $messageId = $callback['message']['message_id'];


    $reply = "❌ 您已取消购买操作。";


    file_get_contents($url . "sendMessage?" . http_build_query([
        'chat_id' => $chatId,
        'text' => $reply
    ]));


    file_get_contents($url . "deleteMessage?" . http_build_query([
        'chat_id' => $chatId,
        'message_id' => $messageId
    ]));
}

?>
