<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'cs');
define('DB_USER', 'cs');
define('DB_PASS', 'z7J2B6G8hAEmxTYK');

// 连接数据库
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("数据库连接失败: " . $e->getMessage());
}

// 启动会话
session_start();

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 验证用户是否已登录，未登录则跳转至登录页
function checkLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// 获取当前登录用户信息
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getBotSettings($pdo) {
    try {
        // 只查询需要的字段，限制只获取一条记录
        $stmt = $pdo->query("SELECT token, chat_id, url, jqr FROM bot LIMIT 1");
        $settings = $stmt->fetch();
        
        // 如果没有记录，返回默认空值数组
        if (!$settings) {
            return [
                'token' => '',
                'chat_id' => '',
                'url' => '',
                'jqr' => ''
            ];
        }
        
        // 对获取的数据进行过滤和净化
        return [
            'token' => trim($settings['token'] ?? ''),
            'chat_id' => trim($settings['chat_id'] ?? ''),
            'url' => filter_var($settings['url'] ?? '', FILTER_VALIDATE_URL) ? $settings['url'] : '',
            'jqr' => filter_var($settings['jqr'] ?? '', FILTER_VALIDATE_URL) ? $settings['jqr'] : ''
        ];
    } catch(PDOException $e) {
        // 记录错误但不暴露给用户
        error_log("获取机器人设置失败: " . $e->getMessage());
        return [
            'token' => '',
            'chat_id' => '',
            'url' => ''
        ];
    }
}


function getPaySettings($pdo) {
    try {
        $stmt = $pdo->query("SELECT pid, md5 FROM pay LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            return [
                'pid' => '',
                'md5' => ''
            ];
        }

        $pid = isset($settings['pid']) ? (int)$settings['pid'] : 0;
        $md5 = isset($settings['md5']) ? trim($settings['md5']) : '';

        return [
            'pid' => $pid,
            'md5' => $md5
        ];

    } catch(PDOException $e) {
        // 记录详细的错误信息到服务器日志，方便排查问题
        error_log("获取支付设置失败: " . $e->getMessage());
        // 发生异常时，同样返回一个安全的默认数组
        return [
            'pid' => 0,
            'md5' => ''
        ];
    }
}

?>