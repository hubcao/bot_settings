<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
$success = '';
$error = '';
$botSettings = [];

// 获取当前机器人设置，增加jqr字段
try {
    $stmt = $pdo->query("SELECT token, chat_id, url, jqr FROM bot LIMIT 1");
    $botSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 如果没有记录，初始化空值
    if (!$botSettings) {
        $botSettings = [
            'token' => '',
            'chat_id' => '',
            'url' => '',
            'jqr' => ''  // 新增机器人链接字段
        ];
    }
} catch(PDOException $e) {
    $error = '获取机器人设置失败: ' . $e->getMessage();
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_bot'])) {
    $token = trim($_POST['token']);
    $chat_id = trim($_POST['chat_id']);
    $url = trim($_POST['url']);
    $jqr = trim($_POST['jqr']);  // 获取机器人链接
    
    // 验证字段
    if (empty($token)) {
        $error = '请输入机器人Token';
    } elseif (empty($chat_id)) {
        $error = '请输入群组ID';
    } else {
        try {
            // 检查是否有记录
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM bot");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // 更新现有记录，增加jqr字段
                $stmt = $pdo->prepare("UPDATE bot SET token = :token, chat_id = :chat_id, url = :url, jqr = :jqr WHERE 1 LIMIT 1");
            } else {
                // 插入新记录，增加jqr字段
                $stmt = $pdo->prepare("INSERT INTO bot (token, chat_id, url, jqr) VALUES (:token, :chat_id, :url, :jqr)");
            }
            
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':chat_id', $chat_id);
            $stmt->bindParam(':url', $url);
            $stmt->bindParam(':jqr', $jqr);  // 绑定机器人链接参数
            $stmt->execute();
            
            $success = '机器人设置更新成功';
            // 更新当前设置
            $botSettings['token'] = $token;
            $botSettings['chat_id'] = $chat_id;
            $botSettings['url'] = $url;
            $botSettings['jqr'] = $jqr;  // 更新机器人链接
        } catch(PDOException $e) {
            $error = '更新机器人设置失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>机器人设置</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }
        .header {
            background-color: #1a73e8;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info span {
            margin-right: 1rem;
        }
        .logout-btn {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background-color: #d93025;
            border-radius: 4px;
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        h2 {
            color: #202124;
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        .menu {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .menu-btn {
            text-decoration: none;
            color: #1a73e8;
            padding: 0.5rem 1rem;
            border: 1px solid #1a73e8;
            border-radius: 4px;
        }
        .menu-btn:hover, .menu-btn.active {
            background-color: #1a73e8;
            color: white;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #5f6368;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        .form-hint {
            font-size: 0.875rem;
            color: #5f6368;
            margin-top: 0.25rem;
        }
        button {
            padding: 0.75rem 1.5rem;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1rem;
        }
        button:hover {
            background-color: #1557b0;
        }
        .success {
            color: #137333;
            padding: 1rem;
            background-color: #e6f4ea;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error {
            color: #d93025;
            padding: 1rem;
            background-color: #fce8e6;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>后台管理系统</h1>
        <div class="user-info">
            <span>欢迎, <?php echo $user['username']; ?></span>
            <a href="logout.php" class="logout-btn">退出登录</a>
        </div>
    </div>
    
    <div class="container">
        <div class="menu">
            <a href="index.php" class="menu-btn">首页</a>
            <a href="profile.php" class="menu-btn">修改资料</a>
            <a href="orders.php" class="menu-btn">所有订单</a>
            <a href="taocan.php" class="menu-btn">套餐管理</a>
            <a href="pay.php" class="menu-btn">支付配置</a>
            <a href="robot_settings.php" class="menu-btn">机器人设置</a>
        </div>
        
        <div class="card">
            <h2>机器人设置</h2>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="token">机器人Token</label>
                    <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($botSettings['token']); ?>" required>
                    <div class="form-hint">机器人的访问令牌，用于API调用验证</div>
                </div>
                
                <div class="form-group">
                    <label for="chat_id">群组ID</label>
                    <input type="text" id="chat_id" name="chat_id" value="<?php echo htmlspecialchars($botSettings['chat_id']); ?>" required>
                    <div class="form-hint">机器人所管理的群组唯一标识ID</div>
                </div>
                
                <div class="form-group">
                    <label for="url">群组链接</label>
                    <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($botSettings['url']); ?>">
                    <div class="form-hint">群组的邀请链接</div>
                </div>
                
                <!-- 新增机器人链接字段 -->
                <div class="form-group">
                    <label for="jqr">机器人链接</label>
                    <input type="url" id="jqr" name="jqr" value="<?php echo htmlspecialchars($botSettings['jqr']); ?>">
                    <div class="form-hint">机器人自身的链接地址</div>
                </div>
                
                <button type="submit" name="update_bot">保存机器人设置</button>
            </form>
        </div>
    </div>
</body>
</html>
    