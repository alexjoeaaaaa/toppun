<?php
/**
 * 讀取本機 UPLOAD_SECRET。
 * 複製 config.local.php.example 為 config.local.php 後填入金鑰。
 */
$uploadSecretFile = __DIR__ . '/config.local.php';
if (!is_file($uploadSecretFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('缺少 config.local.php。請複製 config.local.php.example 並填入 UPLOAD_SECRET。');
}
require_once $uploadSecretFile;
if (!defined('UPLOAD_SECRET') || UPLOAD_SECRET === '') {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('UPLOAD_SECRET 尚未設定。');
}
