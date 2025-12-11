<?php
require '../config.php'; 
$config = getBotSettings($pdo);
$token = $config['token'];

$domain = $_SERVER['HTTP_HOST'];
$webhookUrl = 'https://' . $domain . '/api/Webhook.php';

$response = file_get_contents("https://api.telegram.org/bot$token/setWebhook?url=" . urlencode($webhookUrl));
echo $response;
?>
