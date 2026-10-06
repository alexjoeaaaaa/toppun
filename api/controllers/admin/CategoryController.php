<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\CategoryService;
use Api\Core\Response;
use Exception;

class CategoryController {
    private $categoryService;

    public function __construct(CategoryService $categoryService) {
        $this->categoryService = $categoryService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_list':
                    $this->getList();
                    break;
                case 'get_category':
                    $this->getCategory();
                    break;
                case 'save_category':
                    $this->saveCategory();
                    break;
                case 'delete_category':
                    $this->deleteCategory();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getList() {
        $data = $this->categoryService->getCategories();
        Response::success($data, '成功取得分類列表');
    }

    private function getCategory() {
        $id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        if ($id <= 0) Response::error('無效的分類 ID', 400);

        $data = $this->categoryService->getCategoryById($id);
        Response::success($data, '成功取得分類資料');
    }

    private function saveCategory() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'keywords'    => trim($_POST['keywords'] ?? '')
        ];

        if (empty($data['name'])) {
            Response::error('分類名稱不可為空', 400);
        }

        $categoryId = $this->categoryService->saveCategory($id, $data);
        Response::success(['category_id' => $categoryId], '分類資料已儲存');
    }

    private function deleteCategory() {
        $id = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : 0;
        if ($id <= 0) Response::error('無效的分類 ID', 400);

        $this->categoryService->deleteCategory($id);
        Response::success(null, '分類已刪除，該分類下的商品已移至「尚未分類」');
    }
}
