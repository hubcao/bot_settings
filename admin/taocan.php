<?php
require '../config.php';
checkLogin(); // 检查登录状态

$user = getCurrentUser($pdo);
$error = '';
$success = '';
$taocans = [];
$totalTaocans = 0;
$currentPage = 1;
$taocansPerPage = 10; // 每页显示10个套餐

// 处理添加套餐
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_taocan'])) {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $shijian = trim($_POST['shijian']);
    
    // 验证数据
    if (empty($name)) {
        $error = '请输入套餐名称';
    } elseif (empty($price) || !is_numeric($price) || $price <= 0) {
        $error = '请输入有效的价格';
    } elseif (empty($shijian) || !is_numeric($shijian) || $shijian <= 0) {
        $error = '请输入有效的时间天数';
    } else {
        try {
            // 插入新套餐
            $stmt = $pdo->prepare("INSERT INTO taocan (name, price, shijian, createtime) VALUES (:name, :price, :shijian, NOW())");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':shijian', $shijian);
            $stmt->execute();
            
            $success = '套餐添加成功';
        } catch(PDOException $e) {
            $error = '添加套餐失败: ' . $e->getMessage();
        }
    }
}

// 处理编辑套餐
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_taocan'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $shijian = trim($_POST['shijian']);
    
    // 验证数据
    if (empty($id) || !is_numeric($id)) {
        $error = '无效的套餐ID';
    } elseif (empty($name)) {
        $error = '请输入套餐名称';
    } elseif (empty($price) || !is_numeric($price) || $price <= 0) {
        $error = '请输入有效的价格';
    } elseif (empty($shijian) || !is_numeric($shijian) || $shijian <= 0) {
        $error = '请输入有效的时间天数';
    } else {
        try {
            // 更新套餐
            $stmt = $pdo->prepare("UPDATE taocan SET name = :name, price = :price, shijian = :shijian WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':shijian', $shijian);
            $stmt->execute();
            
            $success = '套餐更新成功';
        } catch(PDOException $e) {
            $error = '更新套餐失败: ' . $e->getMessage();
        }
    }
}

// 获取当前页码
if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
    $currentPage = (int)$_GET['page'];
}

try {
    // 先查询总记录数
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM taocan");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalTaocans = $result['total'];
    
    // 计算总页数
    $totalPages = ceil($totalTaocans / $taocansPerPage);
    
    // 确保当前页码不超过总页数
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    
    // 计算偏移量
    $offset = ($currentPage - 1) * $taocansPerPage;
    
    // 分页查询套餐
    $stmt = $pdo->prepare("SELECT * FROM taocan ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $taocansPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $taocans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = '获取套餐失败: ' . $e->getMessage();
}

// 转换时间天数为描述
function getTimeDescription($days) {
    $map = [
        30 => '1个月',
        90 => '季度',
        180 => '半年',
        365 => '一年'
    ];
    
    return isset($map[$days]) ? $map[$days] : $days . '天';
}

// 获取要编辑的套餐信息
$editTaocan = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM taocan WHERE id = :id");
        $stmt->bindParam(':id', $_GET['edit']);
        $stmt->execute();
        $editTaocan = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = '获取套餐信息失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>套餐管理</title>
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
        .taocan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .taocan-table th, .taocan-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .taocan-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #5f6368;
        }
        .taocan-table tr:hover {
            background-color: #f8f9fa;
        }
        .no-taocans {
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
        .add-btn {
            padding: 0.75rem 1.5rem;
            background-color: #137333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 1rem;
            display: inline-block;
        }
        .add-btn:hover {
            background-color: #0d5d27;
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
            <h2>套餐管理</h2>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- 添加套餐按钮 -->
            <button class="add-btn" id="addTaocanBtn">添加套餐</button>
            
            <?php if (empty($taocans)): ?>
                <div class="no-taocans">
                    暂无套餐记录
                </div>
            <?php else: ?>
                <table class="taocan-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>套餐名称</th>
                            <th>价格</th>
                            <th>时长</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($taocans as $taocan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($taocan['id']); ?></td>
                                <td><?php echo htmlspecialchars($taocan['name']); ?></td>
                                <td><?php echo htmlspecialchars($taocan['price']); ?> 元</td>
                                <td><?php echo getTimeDescription($taocan['shijian']); ?></td>
                                <td><?php echo htmlspecialchars($taocan['createtime'] ?? ''); ?></td>
                                <td>
                                    <a href="taocan.php?edit=<?php echo $taocan['id']; ?>&page=<?php echo $currentPage; ?>" class="action-btn edit-btn">编辑</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- 分页导航 -->
                <div class="pagination-info">
                    共 <?php echo $totalTaocans; ?> 个套餐，当前第 <?php echo $currentPage; ?> / <?php echo $totalPages; ?> 页
                </div>
                
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="taocan.php?page=<?php echo $currentPage - 1; ?>" class="pagination-btn">上一页</a>
                    <?php endif; ?>
                    
                    <?php 
                    // 显示页码，最多显示10个页码
                    $startPage = max(1, $currentPage - 4);
                    $endPage = min($totalPages, $startPage + 9);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): 
                    ?>
                        <a href="taocan.php?page=<?php echo $i; ?>" class="pagination-btn <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="taocan.php?page=<?php echo $currentPage + 1; ?>" class="pagination-btn">下一页</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 添加套餐弹窗 -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <h3>添加套餐</h3>
            <form method="post">
                <div class="form-group">
                    <label for="name">套餐名称</label>
                    <input type="text" id="name" name="name" required>
                    <div class="form-hint">例如：月付套餐、季度套餐、半年套餐、年度套餐</div>
                </div>
                <div class="form-group">
                    <label for="price">价格 (元)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="shijian">时长 (天)</label>
                    <input type="number" id="shijian" name="shijian" min="1" required>
                    <div class="form-hint">30 = 1个月，90 = 季度，180 = 半年，365 = 一年</div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" id="cancelAdd">取消</button>
                    <button type="submit" class="save-btn" name="add_taocan">保存</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 编辑套餐弹窗 -->
    <?php if ($editTaocan): ?>
    <div class="modal-overlay active" id="editModal">
        <div class="modal">
            <h3>编辑套餐</h3>
            <form method="post">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editTaocan['id']); ?>">
                <div class="form-group">
                    <label for="edit_name">套餐名称</label>
                    <input type="text" id="edit_name" name="name" value="<?php echo htmlspecialchars($editTaocan['name']); ?>" required>
                    <div class="form-hint">例如：月付套餐、季度套餐、半年套餐、年度套餐</div>
                </div>
                <div class="form-group">
                    <label for="edit_price">价格 (元)</label>
                    <input type="number" id="edit_price" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($editTaocan['price']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="edit_shijian">时长 (天)</label>
                    <input type="number" id="edit_shijian" name="shijian" min="1" value="<?php echo htmlspecialchars($editTaocan['shijian']); ?>" required>
                    <div class="form-hint">30 = 1个月，90 = 季度，180 = 半年，365 = 一年</div>
                </div>
                <div class="modal-buttons">
                    <a href="taocan.php?page=<?php echo $currentPage; ?>" class="cancel-btn">取消</a>
                    <button type="submit" class="save-btn" name="edit_taocan">保存</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        // 处理添加套餐弹窗
        const addModal = document.getElementById('addModal');
        const addTaocanBtn = document.getElementById('addTaocanBtn');
        const cancelAdd = document.getElementById('cancelAdd');
        
        addTaocanBtn.addEventListener('click', () => {
            addModal.classList.add('active');
        });
        
        cancelAdd.addEventListener('click', () => {
            addModal.classList.remove('active');
        });
        
        // 点击弹窗外部关闭弹窗
        window.addEventListener('click', (e) => {
            if (e.target === addModal) {
                addModal.classList.remove('active');
            }
            
            const editModal = document.getElementById('editModal');
            if (editModal && e.target === editModal) {
                window.location.href = 'taocan.php?page=<?php echo $currentPage; ?>';
            }
        });
    </script>
</body>
</html>
