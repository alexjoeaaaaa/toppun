<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\ProductService;
use Api\Services\ImageService;
use Api\Core\Response;
use Exception;

class ProductController {
    private $productService;

    // 商品附加圖的存放目錄（相對於專案根目錄）
    const GALLERY_DIR = 'assets/images/products/';

    public function __construct(ProductService $productService) {
        $this->productService = $productService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_list':
                    $this->getList();
                    break;
                case 'get_categories':
                    $this->getCategories();
                    break;
                case 'get_product':
                    $this->getProduct();
                    break;
                case 'save_product':
                    $this->saveProduct();
                    break;
                case 'upload_gallery_image':
                    $this->uploadGalleryImage();
                    break;
                case 'delete_gallery_image':
                    $this->deleteGalleryImage();
                    break;
                case 'delete_product':
                    $this->deleteProduct();
                    break;
                case 'toggle_status':
                    $this->toggleStatus();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getList() {
        $page           = isset($_GET['page'])        ? max(1, (int)$_GET['page'])   : 1;
        $category_id    = isset($_GET['category_id']) ? (int)$_GET['category_id']    : 0;
        $keyword        = isset($_GET['search'])      ? trim($_GET['search'])         : '';
        $items_per_page = 10;

        $data = $this->productService->getProducts($page, $items_per_page, $category_id, $keyword);
        Response::success($data, '成功取得商品列表');
    }

    private function getCategories() {
        $data = $this->productService->getCategories();
        Response::success($data, '成功取得分類列表');
    }

    private function getProduct() {
        $id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        if ($id <= 0) Response::error('無效的商品 ID', 400);

        $data = $this->productService->getProductById($id);
        Response::success($data, '成功取得商品資料');
    }

    /**
     * 儲存商品基本資料（主圖 + description JSON）。
     * 附加圖片的上傳走獨立的 upload_gallery_image action，
     * 這樣前台可以即時預覽，不需要等整個表單送出。
     */
    private function saveProduct() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

        // 驗證 description 是否為合法 JSON（若有傳入）
        $descriptionRaw = $_POST['description'] ?? '';
        if (!empty($descriptionRaw)) {
            $decoded = json_decode($descriptionRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('description 格式不正確，必須為有效的 JSON 字串', 400);
            }
        }

        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'category_id'   => (int)($_POST['category_id'] ?? 0),
            'price'         => (float)($_POST['price'] ?? 0),
            'stock'         => (int)($_POST['stock'] ?? 0),
            'description'   => $descriptionRaw,
            'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
            'image_main'    => $_POST['existing_image'] ?? ''
        ];

        if (empty($data['name'])) {
            Response::error('商品名稱不可為空', 400);
        }

        // 處理主圖上傳
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../' . self::GALLERY_DIR;
            try {
                $newFileName = ImageService::processAndSaveImage(
                    $_FILES['image_file']['tmp_name'],
                    $_FILES['image_file']['name'],
                    $uploadDir
                );
                // 刪除舊主圖
                if (!empty($_POST['existing_image'])) {
                    $oldPath = __DIR__ . '/../../../' . $_POST['existing_image'];
                    ImageService::deleteImageWithThumb($oldPath);
                }
                $data['image_main'] = self::GALLERY_DIR . $newFileName;
            } catch (Exception $e) {
                Response::error('主圖處理失敗: ' . $e->getMessage(), 500);
            }
        }

        $productId = $this->productService->saveProduct($id, $data);
        Response::success(['product_id' => $productId], '商品資料已儲存');
    }

    /**
     * 上傳單張附加圖片（AJAX 即時上傳）。
     * POST: product_id, image_file (file)
     * 回傳：新插入的圖片 id、image_url、sort_order。
     */
    private function uploadGalleryImage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($product_id <= 0) {
            Response::error('無效的商品 ID', 400);
        }

        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('請選擇圖片檔案', 400);
        }

        $uploadDir = __DIR__ . '/../../../' . self::GALLERY_DIR;

        try {
            $newFileName = ImageService::processAndSaveImage(
                $_FILES['image_file']['tmp_name'],
                $_FILES['image_file']['name'],
                $uploadDir
            );
            $imageUrl = self::GALLERY_DIR . $newFileName;

            // 計算目前最大 sort_order，新圖排最後
            $existing = $this->productService->getGalleryImages($product_id);
            $sort_order = count($existing);

            $newId = $this->productService->addGalleryImage($product_id, $imageUrl, $sort_order);

            Response::success([
                'id'          => $newId,
                'image_url'   => $imageUrl,
                'sort_order'  => $sort_order
            ], '附加圖片已上傳');
        } catch (Exception $e) {
            Response::error('圖片上傳失敗: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 刪除單張附加圖片。
     * GET: image_id, product_id
     */
    private function deleteGalleryImage() {
        $image_id   = isset($_REQUEST['image_id'])   ? (int)$_REQUEST['image_id']   : 0;
        $product_id = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;

        if ($image_id <= 0 || $product_id <= 0) {
            Response::error('無效的參數', 400);
        }

        $imageUrl = $this->productService->deleteGalleryImage($image_id, $product_id);

        // 刪除實體檔案與縮圖
        $imagePath = __DIR__ . '/../../../' . $imageUrl;
        ImageService::deleteImageWithThumb($imagePath);

        Response::success(null, '附加圖片已刪除');
    }

    /**
     * 刪除商品，同時清除主圖、所有附加圖片（DB + 實體檔案）。
     */
    private function deleteProduct() {
        $id = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : 0;
        if ($id <= 0) Response::error('無效的商品 ID', 400);

        $product = $this->productService->getProductById($id);

        // 先刪除所有附加圖片的實體檔案
        foreach ($product['gallery_images'] as $img) {
            $imgPath = __DIR__ . '/../../../' . $img['image_url'];
            ImageService::deleteImageWithThumb($imgPath);
        }
        $this->productService->deleteAllGalleryImages($id);

        // 刪除主圖實體檔案
        if (!empty($product['image_main'])) {
            $mainPath = __DIR__ . '/../../../' . $product['image_main'];
            ImageService::deleteImageWithThumb($mainPath);
        }

        $this->productService->deleteProduct($id);
        Response::success(null, '商品已刪除');
    }

    private function toggleStatus() {
        $id = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : 0;
        if ($id <= 0) Response::error('無效的商品 ID', 400);

        $this->productService->toggleStatus($id);
        Response::success(null, '商品狀態已更新');
    }
}
