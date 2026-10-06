<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\BlogService;
use Api\Core\Response;
use Exception;

class BlogController {
    private $blogService;

    public function __construct(BlogService $blogService) {
        $this->blogService = $blogService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_list':
                    $this->getList();
                    break;
                case 'update_status':
                    $this->updateStatus();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getList() {
        $filter = $_GET['filter'] ?? 'all';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $items_per_page = 10;

        $data = $this->blogService->getArticles($filter, $page, $items_per_page);
        Response::success($data, '成功取得文章列表');
    }

    private function updateStatus() {
        // 確保是 POST 請求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = $_POST['status'] ?? '';

        if ($id <= 0 || empty($status)) {
            Response::error('參數錯誤：缺少 id 或 status', 400);
        }

        $this->blogService->updateStatus($id, $status);
        
        $message = $status === 'published' ? '文章已核准' : '文章已拒絕';
        Response::success(null, $message);
    }
}
