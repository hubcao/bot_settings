<?php
require 'config.php';

// 清除会话数据
$_SESSION = array();

// 销毁会话
session_destroy();

// 跳转至登录页
header('Location: login.php');
exit();
?>
