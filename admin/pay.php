<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
$error = '';
$success = '';
$payConfigs = [];
$totalConfigs = 0;
$currentPage = 1;
$configsPerPage = 10; // 每页显示10条配置

// 处理编辑配置（使用pid作为唯一标识）
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pay'])) {
    $original_pid = trim($_POST['original_pid']);
    $new_pid = trim($_POST['pid']);
    $md5 = trim($_POST['md5']);
    
    // 验证数据
    if (empty($original_pid)) {
        $error = '无效的原始商户ID';
    } elseif (empty($new_pid)) {
        $error = '请输入商户ID';
    } elseif (empty($md5)) {
        $error = '请输入MD5密钥';
    } else {
        try {
            // 如果商户ID改变了，检查新商户ID是否已存在
            if ($original_pid != $new_pid) {
                $stmt = $pdo->prepare("SELECT pid FROM pay WHERE pid = :pid");
                $stmt->bindParam(':pid', $new_pid);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = '该商户ID已存在';
                    $new_pid = $original_pid; // 恢复原始PID
                }
            }
            
            if (empty($error)) {
                // 更新配置（使用pid作为条件）
                $stmt = $pdo->prepare("UPDATE pay SET pid = :new_pid, md5 = :md5 WHERE pid = :original_pid");
                $stmt->bindParam(':original_pid', $original_pid);
                $stmt->bindParam(':new_pid', $new_pid);
                $stmt->bindParam(':md5', $md5);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $success = '支付配置更新成功';
                } else {
                    $error = '未找到要更新的配置';
                }
            }
        } catch(PDOException $e) {
            $error = '更新配置失败: ' . $e->getMessage();
        }
    }
}

// 获取当前页码
if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
    $currentPage = (int)$_GET['page'];
}

try {
    // 先查询总记录数
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pay");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalConfigs = $result['total'];
    
    // 计算总页数
    $totalPages = ceil($totalConfigs / $configsPerPage);
    
    // 确保当前页码不超过总页数
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // 计算偏移量
    $offset = ($currentPage - 1) * $configsPerPage;
    
    // 分页查询配置（只查询pid和md5字段）
    $stmt = $pdo->prepare("SELECT pid, md5 FROM pay ORDER BY pid LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $configsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $payConfigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = '获取配置失败: ' . $e->getMessage();
}

// 获取要编辑的配置信息（使用pid作为标识）
$editConfig = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT pid, md5 FROM pay WHERE pid = :pid");
        $stmt->bindParam(':pid', $_GET['edit']);
        $stmt->execute();
        $editConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = '获取配置信息失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>支付配置管理</title>
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
        .pay-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .pay-table th, .pay-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .pay-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #5f6368;
        }
        .pay-table tr:hover {
            background-color: #f8f9fa;
        }
        .no-configs {
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
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .edit-btn {
            background-color: #1a73e8;
            color: white;
        }
        .edit-btn:hover {
            background-color: #1557b0;
        }
        /* 弹窗样式 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s linear 0.25s, opacity 0.25s;
        }
        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
            transition-delay: 0s;
        }
        .modal {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .modal h3 {
            margin-top: 0;
            color: #202124;
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
        .form-hint {
            font-size: 0.875rem;
            color: #5f6368;
            margin-top: 0.25rem;
        }
        .hint-link {
            color: #1a73e8;
            text-decoration: none;
        }
        .hint-link:hover {
            text-decoration: underline;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .cancel-btn {
            padding: 0.75rem 1.5rem;
            background-color: #5f6368;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .save-btn {
            padding: 0.75rem 1.5rem;
            background-color: #1a73e8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
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
            <h2>支付配置管理</h2>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($payConfigs)): ?>
                <div class="no-configs">
                    暂无支付配置记录
                </div>
            <?php else: ?>
                <table class="pay-table">
                    <thead>
                        <tr>
                            <th>商户ID</th>
                            <th>MD5密钥</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payConfigs as $config): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($config['pid']); ?></td>
                                <td><?php echo htmlspecialchars($config['md5']); ?></td>
                                <td>
                                    <a href="pay.php?edit=<?php echo urlencode($config['pid']); ?>&page=<?php echo $currentPage; ?>" class="action-btn edit-btn">编辑</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 分页导航 -->
                <div class="pagination-info">
                    共 <?php echo $totalConfigs; ?> 条配置，当前第 <?php echo $currentPage; ?> / <?php echo $totalPages; ?> 页
                </div>
                
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="pay.php?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">上一页</a>
                    <?php endif; ?>
                    
                    <?php 
                    // 显示页码，最多显示10个页码
                    $startPage = max(1, $currentPage - 4);
                    $endPage = min($totalPages, $startPage + 9);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="pay.php?page=<?php echo $i; ?>" class="pagination-btn <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="pay.php?page=<?php echo $currentPage + 1; ?>" class="pagination-btn">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 编辑配置弹窗 -->
    <?php if ($editConfig): ?>
    <div class="modal-overlay active" id="editModal">
        <div class="modal">
            <h3>编辑支付配置</h3>
            <form method="post">
                <!-- 存储原始PID用于更新条件 -->
                <input type="hidden" name="original_pid" value="<?php echo htmlspecialchars($editConfig['pid']); ?>">
                <div class="form-group">
                    <label for="edit_pid">商户ID</label>
                    <input type="text" id="edit_pid" name="pid" value="<?php echo htmlspecialchars($editConfig['pid']); ?>" required>
                    <div class="form-hint">开户地址: <a href="http://juea.cn/" target="_blank" class="hint-link">http://juea.cn/</a></div>
                </div>
                <div class="form-group">
                    <label for="edit_md5">商户MD5密钥</label>
                    <input type="text" id="edit_md5" name="md5" value="<?php echo htmlspecialchars($editConfig['md5']); ?>" required>
                </div>
                <div class="modal-buttons">
                    <a href="pay.php?page=<?php echo $currentPage; ?>" class="cancel-btn">取消</a>
                    <button type="submit" class="save-btn" name="edit_pay">保存</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // 点击弹窗外部关闭弹窗
        window.addEventListener('click', (e) => {
            const editModal = document.getElementById('editModal');
            if (editModal && e.target === editModal) {
                window.location.href = 'pay.php?page=<?php echo $currentPage; ?>';
            }
        });
    </script>
</body>
</html>
    