<?php
require '../config.php';
$pay = getPaySettings($pdo);

// 检查支付配置是否完整
if (empty($pay['pid']) || empty($pay['md5'])) {
    die(json_encode([
        'success' => false,
        'message' => '支付配置不完整，请先在后台配置商户信息'
    ]));
}

/**
 * 生成支付签名
 * @param array $params 支付参数
 * @param string $key 商户密钥
 * @return string 签名结果
 */
function generatePaymentSign($params, $key) {
    // 1. 过滤不需要参与签名的参数（sign、sign_type、空值和param）
    $filterParams = [];
    foreach ($params as $k => $v) {
        if ($k != 'sign' && $k != 'sign_type' && $k != 'param' && $v !== '' && $v !== null) {
            $filterParams[$k] = $v;
        }
    }
    

    ksort($filterParams);
    
    // 3. 拼接成URL键值对格式
    $signStr = '';
    foreach ($filterParams as $k => $v) {
        $signStr .= $k . '=' . $v . '&';
    }
    $signStr = rtrim($signStr, '&');
    

    $signStr .= $key;
    return md5($signStr);
}

function getpay($type,$orderNo, $name,$money, $payConfig) {
    $clientIp = getClientIp();
    $domain = $_SERVER['HTTP_HOST'];
    // 支付参数
    $paymentParams = [
        'pid' => $payConfig['pid'], // 从参数中获取
        'type' => $type,
        'out_trade_no' => $orderNo,
        'notify_url' => 'https://' . $domain . '/api/notify.php', 
        'return_url' => 'https://' . $domain . '/api/return.php', 
        'name' => $name,
        'money' => $money,
        'clientip' => $clientIp,
        'sign_type' => 'MD5',
        'param' => ''
    ];

    // 生成签名
    $paymentParams['sign'] = generatePaymentSign($paymentParams, $payConfig['md5']);

// 支付接口地址
$paymentUrl = 'http://juea.cn/mapi.php';

// 配置请求选项（POST方式）
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($paymentParams),
        'timeout' => 30, // 超时时间（秒）
    ],
    'ssl' => [
        'verify_peer' => false, 
        'verify_peer_name' => false,
    ]
];

$context  = stream_context_create($options);

try {
    // 发起请求并获取响应
    $response = file_get_contents($paymentUrl, false, $context);
    
    if ($response === false) {
        // 获取错误信息
        $error = error_get_last();
        throw new Exception('请求支付接口失败: ' . ($error['message'] ?? '未知错误'));
    }
    
    $data = json_decode($response, true);
    if($type == "wxpay"){
    return $data['qrcode'];
    }else{
    return $data['payurl'];
    }
    
} catch (Exception $e) {
    // 输出错误信息
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'];
}

?>