<?php
session_start();
// 清除所有 Session 變數
$_SESSION = array();

// 如果有使用 Cookie 儲存 Session ID，也將其清除
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// 徹底銷毀 Session
session_destroy();

// 導向首頁或登入頁
header("Location: index");
exit();
?>
