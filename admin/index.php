<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台首页</title>
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
            max-width: 1200px;
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
            <h2>系统信息</h2>
            <p>当前登录用户: <?php echo $user['username']; ?></p>
            <p>联系邮箱: <?php echo $user['email']; ?></p>
            <p>账号创建时间: <?php echo $user['created_at']; ?></p>
        </div>
    </div>
</body>
</html>
