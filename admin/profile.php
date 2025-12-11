<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
$success = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 修改邮箱
    if (isset($_POST['update_email'])) {
        $new_email = $_POST['new_email'];
        
        if (empty($new_email)) {
            $error = '请输入新邮箱';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = '请输入有效的邮箱地址';
        } else {
            try {
                // 检查邮箱是否已被使用
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $stmt->bindParam(':email', $new_email);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = '该邮箱已被使用';
                } else {
                    // 更新邮箱
                    $stmt = $pdo->prepare("UPDATE users SET email = :email, updated_at = NOW() WHERE id = :id");
                    $stmt->bindParam(':email', $new_email);
                    $stmt->bindParam(':id', $user['id']);
                    $stmt->execute();
                    
                    $success = '邮箱更新成功';
                    // 更新当前用户信息
                    $user['email'] = $new_email;
                }
            } catch(PDOException $e) {
                $error = '更新邮箱失败: ' . $e->getMessage();
            }
        }
    }
    
    // 修改密码
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // 验证密码
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = '请填写所有密码字段';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = '当前密码不正确';
        } elseif ($new_password != $confirm_password) {
            $error = '两次输入的新密码不一致';
        } elseif (strlen($new_password) < 6) {
            $error = '新密码长度不能少于6个字符';
        } else {
            try {
                // 哈希新密码
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 更新密码
                $stmt = $pdo->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
                
                $success = '密码更新成功';
            } catch(PDOException $e) {
                $error = '更新密码失败: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改资料</title>
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
        }
        .menu-btn {
            text-decoration: none;
            color: #1a73e8;
            padding: 0.5rem 1rem;
            border: 1px solid #1a73e8;
            border-radius: 4px;
        }
        .menu-btn:hover {
            background-color: #e8f0fe;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #5f6368;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #dadce0;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            padding: 0.75rem 1.5rem;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
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
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
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
            <h2>修改个人资料</h2>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3>修改邮箱</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="current_email">当前邮箱</label>
                        <input type="email" id="current_email" value="<?php echo $user['email']; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="new_email">新邮箱</label>
                        <input type="email" id="new_email" name="new_email" required>
                    </div>
                    <button type="submit" name="update_email">更新邮箱</button>
                </form>
            </div>
            
            <div class="form-section">
                <h3>修改密码</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="current_password">当前密码</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">新密码</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">确认新密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="update_password">更新密码</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
