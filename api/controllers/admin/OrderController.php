<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\OrderService;
use Api\Core\Response;
use Exception;

class OrderController {
    private $orderService;

    public function __construct(OrderService $orderService) {
        $this->orderService = $orderService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_list':
                    $this->getList();
                    break;
                case 'get_details':
                    $this->getDetails();
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
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $items_per_page = 10;

        $data = $this->orderService->getOrders($page, $items_per_page, $status, $search);
        Response::success($data, '成功取得訂單列表');
    }

    private function getDetails() {
        $id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($id <= 0) {
            Response::error('無效的訂單 ID', 400);
        }
        $data = $this->orderService->getOrderDetails($id);
        Response::success($data, '成功取得訂單明細');
    }

    private function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : '';

        if ($id <= 0 || empty($status)) {
            Response::error('參數錯誤', 400);
        }

        $this->orderService->updateOrderStatus($id, $status);
        Response::success(null, '訂單狀態已更新');
    }
}
