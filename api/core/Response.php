<?php
namespace Api\Core;

class Response {
    /**
     * 回傳成功的 JSON 格式
     *
     * @param mixed $data 回傳的資料
     * @param string $message 提示訊息
     * @param int $statusCode HTTP 狀態碼
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }

    /**
     * 回傳錯誤的 JSON 格式
     *
     * @param string $message 錯誤訊息
     * @param int $statusCode HTTP 狀態碼
     */
    public static function error($message = 'Error', $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data' => null
        ]);
        exit();
    }
}
