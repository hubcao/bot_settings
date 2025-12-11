<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
$error = '';
$orders = [];
$totalOrders = 0;
$currentPage = 1;
$ordersPerPage = 10; // 每页显示10条订单

// 获取当前页码
if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
    $currentPage = (int)$_GET['page'];
}

try {
    // 先查询总记录数
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalOrders = $result['total'];
    
    // 计算总页数
    $totalPages = ceil($totalOrders / $ordersPerPage);
    
    // 确保当前页码不超过总页数
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // 计算偏移量
    $offset = ($currentPage - 1) * $ordersPerPage;
    
    // 分页查询订单
    $stmt = $pdo->prepare("SELECT * FROM orders ORDER BY createtime DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $ordersPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = '获取订单失败: ' . $e->getMessage();
}

// 状态文本映射
function getStatusText($status) {
    switch($status) {
        case 0:
            return '<span style="color: #d93025;">未支付</span>';
        case 1:
            return '<span style="color: #137333;">已支付</span>';
        default:
            return '<span style="color: #d93025;">已过期</span>';
    }
}

// 时间戳转换为北京时间
function timestampToBeijingTime($timestamp) {
    // 检查是否为有效的10位时间戳
    if (is_numeric($timestamp) && strlen((string)$timestamp) == 10) {
        // 设置时区为北京时间
        date_default_timezone_set('Asia/Shanghai');
        // 格式化为年月日 时分秒
        return date('Y-m-d H:i:s', $timestamp);
    }
    // 无效时间戳返回原始值
    return $timestamp;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>所有订单</title>
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
        .error {
            color: #d93025;
            padding: 1rem;
            background-color: #fce8e6;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .success {
            color: #137333;
            padding: 1rem;
            background-color: #e6f4ea;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .orders-table th, .orders-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .orders-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #5f6368;
        }
        .orders-table tr:hover {
            background-color: #f8f9fa;
        }
        .no-orders {
            text-align: center;
            padding: 2rem;
            color: #5f6368;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #1a73e8;
            border-radius: 4px;
            color: #1a73e8;
            text-decoration: none;
        }
        .pagination-btn:hover, .pagination-btn.active {
            background-color: #1a73e8;
            color: white;
        }
        .pagination-info {
            text-align: center;
            margin-top: 1rem;
            color: #5f6368;
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
            <h2>所有订单</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    暂无订单记录
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>用户</th>
                            <th>用户昵称</th>
                            <th>添加时间</th>
                            <th>到期时间</th>
                            <th>状态</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['ddh']); ?></td>
                                <td><?php echo htmlspecialchars($order['user']); ?></td>
                                <td><?php echo htmlspecialchars($order['name']); ?></td>
                                <!-- 将时间戳转换为北京时间 -->
                                <td><?php echo timestampToBeijingTime($order['createtime']); ?></td>
                                <td><?php echo timestampToBeijingTime($order['dqtime']); ?></td>
                                <td><?php echo getStatusText($order['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 分页导航 -->
                <div class="pagination-info">
                    共 <?php echo $totalOrders; ?> 条订单，当前第 <?php echo $currentPage; ?> / <?php echo $totalPages; ?> 页
                </div>
                
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="orders.php?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">上一页</a>
                    <?php endif; ?>
                    
                    <?php 
                    // 显示页码，最多显示10个页码
                    $startPage = max(1, $currentPage - 4);
                    $endPage = min($totalPages, $startPage + 9);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="orders.php?page=<?php echo $i; ?>" class="pagination-btn <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="orders.php?page=<?php echo $currentPage + 1; ?>" class="pagination-btn">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
    