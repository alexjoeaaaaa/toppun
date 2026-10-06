<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\DashboardService;
use Api\Core\Response;
use Exception;

class DashboardController {
    private $dashboardService;

    public function __construct(DashboardService $dashboardService) {
        $this->dashboardService = $dashboardService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_stats':
                    $this->getStats();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getStats() {
        $data = $this->dashboardService->getStats();
        Response::success($data, '成功取得儀表板統計資料');
    }
}
