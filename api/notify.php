<?php
require_once("lib/EpayCore.class.php");
require '../config.php';
$config = getBotSettings($pdo);
$pay = getPaySettings($pdo);
$token = $config['token'];

$epay_config['apiurl'] = 'https://mapi.mvlpbsg.com/';
$epay_config['pid'] = $pay['pid'];
$epay_config['key'] = $pay['md5'];

$epay = new EpayCore($epay_config);
$verify_result =$epay->verifyNotify();

if($verify_result) {
    // 商户订单号
$out_trade_no = $_GET['out_trade_no'];

if ($_GET['trade_status'] == 'TRADE_SUCCESS') {

    $update = $pdo->prepare("UPDATE orders SET status = 1 WHERE ddh = ?");
    $update->execute([$out_trade_no]);

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE ddh = :ddh LIMIT 1");
    $stmt->bindParam(':ddh', $out_trade_no, PDO::PARAM_STR);
    $stmt->execute();
    $order = $stmt->fetch();

    $userId = $order['uid'];

    $groupUrl = $config['url'];

    // 创建消息内容
    $message = "🎉 用户ID：`{$userId} 订阅成功，您的订阅已经激活！\n\n"
             . "💳 订单号：`{$out_trade_no}`\n\n"
             . "📌 请点击以下链接加入我们的群组：\n"
             . "👉 [点击这里加入群组]({$groupUrl})";

    // Telegram API URL
    $url = "https://api.telegram.org/bot$token/sendMessage";

    // 发送消息到用户
    $postData = [
        'chat_id' => $userId,  // 发送给用户的个人 chat_id
        'text' => $message,
        'parse_mode' => 'Markdown',  // 让消息格式化显示
        'disable_web_page_preview' => true  // 禁止链接预览
    ];

    // 发送请求
    file_get_contents($url . '?' . http_build_query($postData));
}

// 验证成功返回
echo "success";
}
else {
	//验证失败
	echo "fail";
}
?>