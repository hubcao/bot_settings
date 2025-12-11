<?php
require '../config.php';
$config = getBotSettings($pdo);
if($_GET['trade_status'] == 'TRADE_SUCCESS') {
header('Location: ' . $config['jqr']);
exit();
	}
?>