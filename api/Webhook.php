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
            
            $sql = "SELECT * FROM taocan";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $taocans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            
            // 发送欢迎图片（优先使用配置中的图片链接，否则使用占位图）
            $photoUrl = '';
            if (!empty($config['jqr']) && filter_var($config['jqr'], FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif)$/i', $config['jqr'])) {
                $photoUrl = $config['jqr'];
            } else {
                // 使用随机占位图，管理员可在控制面板中把可用图片 URL 填入 `jqr` 字段以替换
                $photoUrl = 'https://wdnas.me/img/photo.png';
            }

			$reply = "您好 {$firstName} 「您的关联ID：{$userId}」，欢迎光临本店\n\n".
					 "⬇️⬇️ 购买付款成功后自动发送入群链接 ⬇️⬇️\n\n" .
					 "⬇️⬇️ 群组链接点击加入后机器人秒审核 ⬇️⬇️\n\n" .
					 "⬇️⬇️ 购买套餐内容请点击下方按钮操作 ⬇️⬇️\n\n";

            $inlineKeyboard = [];
            foreach ($taocans as $row) {
				$reply .= "套餐名称：{$row['name']}\n";
                $reply .= "支付价格：{$row['price']} 元\n";
                $reply .= "使用时长：{$row['shijian']} 天\n";
                $reply .= "-----------------------------\n\n";

                $inlineKeyboard[] = [
                    ['text' => "购买 {$row['name']}", 'callback_data' => "buy_{$row['id']}"]
                ];
            }

            $keyboard = [
                'inline_keyboard' => $inlineKeyboard
            ];


            $postData = [
                'chat_id' => $chatId,
                'text' => $reply,
                'parse_mode' => 'Markdown'
            ];

            // 发送图片（通过 URL）
            file_get_contents($url . "sendPhoto?" . http_build_query([
                'chat_id' => $chatId,
                'photo' => $photoUrl,
                'caption' => $postData['text'],
                'reply_markup' => json_encode($keyboard, JSON_UNESCAPED_UNICODE),
                'parse_mode' => 'Markdown'
            ]));
        }
    }

    if (isset($update['message']['new_chat_members'])) {
        foreach ($update['message']['new_chat_members'] as $newMember) {
            $firstName = $newMember['first_name'] ?? '用户';
            $username  = isset($newMember['username']) ? '@'.$newMember['username'] : '未知用户名';
            $userId    = $newMember['id'];

            $chatTitle = $update['message']['chat']['title'] ?? '本群';
            $groupId   = $update['message']['chat']['id'];

            // 检查用户是否有有效已支付订单
            $now = time();
            $check = $pdo->prepare("SELECT * FROM orders WHERE uid = ? AND status = 1 AND dqtime > ? LIMIT 1");
            $check->execute([$userId, $now]);
            $order = $check->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // 已付费：欢迎并允许留在群内
                $welcome = "🎉 欢迎新成员加入 {$chatTitle}！\n\n"
                         . "👤 姓名：{$firstName}\n"
                         . "🔹 用户名：{$username}\n"
                         . "🆔 用户ID：`{$userId}`\n";

                file_get_contents($url . "sendMessage?" . http_build_query([
                    'chat_id' => $chatId,
                    'text' => $welcome,
                    'parse_mode' => 'Markdown'
                ]));
            } else {
                // 未付费：尝试私聊提醒并移除该用户
                $payLink = $config['jqr'] ?? '';
                $privateMsg = "您好，{$firstName}。\n\n检测到您未购买服务，加入群组前需要先购买服务。\n\n请在私聊中使用 /start 进行购买，或打开：{$payLink} 进行支付。";

                // 发送私聊提醒（如果用户未与机器人私聊，此请求会失败）
                @file_get_contents($url . "sendMessage?" . http_build_query([
                    'chat_id' => $userId,
                    'text' => $privateMsg
                ]));

                // 尝试移除用户（需要 bot 为群管理员且有踢人权限）
                @file_get_contents($url . "kickChatMember?" . http_build_query([
                    'chat_id' => $groupId,
                    'user_id' => $userId
                ]));

                // 在群内发布简短说明（不暴露隐私数据）
                $notice = "⚠️ 用户 {$username} 未订阅，已移除。如需加入请先完成购买。";
                file_get_contents($url . "sendMessage?" . http_build_query([
                    'chat_id' => $chatId,
                    'text' => $notice
                ]));
            }
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
    $reply = "选当前选择的套餐内容如下：\n\n";
    $inlineKeyboard = [];

    foreach ($taocans as $row) {
		$reply .= "❓ 如遇无法支付，获取新的支付二维码 或 更换支付方式 ❓\n\n";
		$reply .= "套餐名称：{$row['name']}\n";
        $reply .= "支付价格：{$row['price']} 元\n";
        $reply .= "使用时长：{$row['shijian']} 天\n";
        $reply .= "-----------------------------\n\n";
        
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
        $reply = "❓ 如遇无法支付，获取新的支付二维码 或 更换支付方式 ❓\n\n" .
                 "选当前选择的套餐内容如下：\n\n" .
                 "套餐名称：{$package['name']}\n" .
                 "购买价格：{$package['price']} 元\n" .
                 "购买时长：{$package['shijian']} 天\n\n" .
                 "请选择支付方式，支付后成功自动发送入群链接，机器人秒审核加入：";


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

    $paymentLink = getpay("wxpay", $orderNo, $package['name'], $package['price'], $pay);
    
    $qrUrl = "https://api.2dcode.biz/v1/create-qr-code?data=?size=300x300&data=" . urlencode($paymentLink);
    
    //备用二维码生成地址：https://api.2dcode.biz/v1/create-qr-code?data=?size=300x300&data=
    
    $reply = "订单号：`{$orderNo}`\n" .
             "支付方式：微信支付\n" .
             "支付价格：{$package['price']} 元（付款金额可能上下波动）\n" .
             "套餐时长：{$package['name']}（{$package['shijian']} 天）\n" .
             "支付链接：{$paymentLink}\n\n" .
             "⬆️ 使用微信扫描上方二维码完成支付，无法支付时请更换为支付宝 ⬆️";
             
    $postData = [
        'chat_id' => $chatId,
        'photo' => $qrUrl,
        'caption' => $reply,
        'parse_mode' => 'Markdown'
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
        
        $paymentLink = getpay("alipay",$orderNo, $package['name'],$package['price'], $pay); 
    
        $qrUrl = "https://api.2dcode.biz/v1/create-qr-code?data=?size=300x300&data=" . urlencode($paymentLink);

        $reply = "订单号：`{$orderNo}`\n" .
                 "支付方式：支付宝\n" .
                 "支付价格：{$package['price']} 元（付款金额可能上下波动）\n" .
                 "套餐时长：{$package['name']}（{$package['shijian']} 天）\n" .
                 "支付链接：{$paymentLink}\n\n" .
                 "⬆️ 使用微信扫描上方二维码完成支付，无法支付时请更换微信支付 ⬆️";
                 
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '去支付 🛒', 'url' => $paymentLink]
                ]
            ]
        ];

        $postData = [
            'chat_id' => $chatId,
            'photo' => $qrUrl,
            'caption' => $reply,
            'parse_mode' => 'Markdown'
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

// 处理群组加入审核请求（chat_join_request）
if (isset($update['chat_join_request'])) {
    $joinRequest = $update['chat_join_request'];
    $userId = $joinRequest['from']['id'];
    $firstName = $joinRequest['from']['first_name'] ?? '用户';
    $username = $joinRequest['from']['username'] ?? '未知用户名';
    $chatId = $joinRequest['chat']['id'];
    $requestId = $joinRequest['id'];

    // 检查用户是否有有效已支付订单
    $now = time();
    $check = $pdo->prepare("SELECT * FROM orders WHERE uid = ? AND status = 1 AND dqtime > ? LIMIT 1");
    $check->execute([$userId, $now]);
    $order = $check->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        // 已付费：同意加入请求
        file_get_contents($url . "approveChatJoinRequest?" . http_build_query([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]));

        // 发送欢迎私聊消息
        $welcomeMsg = "🎉 恭喜！您的加入申请已通过。\n\n感谢您的支持，期待您在群组内的参与！";
        @file_get_contents($url . "sendMessage?" . http_build_query([
            'chat_id' => $userId,
            'text' => $welcomeMsg
        ]));
    } else {
        // 未付费：拒绝加入请求
        file_get_contents($url . "declineChatJoinRequest?" . http_build_query([
            'chat_id' => $chatId,
            'user_id' => $userId
        ]));

        // 发送拒绝原因的私聊消息
        $rejectMsg = "抱歉，您的加入申请被拒绝。\n\n原因：您还未购买服务。\n\n请使用 /get_package 命令购买套餐，或联系管理员。";
        @file_get_contents($url . "sendMessage?" . http_build_query([
            'chat_id' => $userId,
            'text' => $rejectMsg
        ]));
    }
}

?>
