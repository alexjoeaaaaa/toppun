<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\PromotionService;
use Api\Core\Response;
use Exception;

class PromotionController {
    private $promoService;

    public function __construct(PromotionService $promoService) {
        $this->promoService = $promoService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_coupons':
                    $this->getCoupons();
                    break;
                case 'save_coupon':
                    $this->saveCoupon();
                    break;
                case 'delete_coupon':
                    $this->deleteCoupon();
                    break;
                case 'get_promotions':
                    $this->getPromotions();
                    break;
                case 'save_promotion':
                    $this->savePromotion();
                    break;
                case 'delete_promotion':
                    $this->deletePromotion();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getCoupons() {
        $data = $this->promoService->getCoupons();
        Response::success($data, '成功取得折扣碼列表');
    }

    private function saveCoupon() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $this->promoService->saveCoupon($_POST, $id);
        Response::success(null, '折扣碼已儲存');
    }

    private function deleteCoupon() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if ($id <= 0) {
            Response::error('無效的折扣碼 ID', 400);
        }
        $this->promoService->deleteCoupon($id);
        Response::success(null, '折扣碼已刪除');
    }

    private function getPromotions() {
        $data = $this->promoService->getPromotions();
        Response::success($data, '成功取得全館活動列表');
    }

    private function savePromotion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $this->promoService->savePromotion($_POST, $id);
        Response::success(null, '促銷活動已儲存');
    }

    private function deletePromotion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }
        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        if ($id <= 0) {
            Response::error('無效的活動 ID', 400);
        }
        $this->promoService->deletePromotion($id);
        Response::success(null, '活動已刪除');
    }
}
