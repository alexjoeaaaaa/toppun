# 商品多圖 + JSON 區塊描述功能開發計畫

目標：前台商品內頁顯示多張輪播圖（上方大圖 + 左右箭頭 + 下方縮圖列），商品描述改為 JSON 區塊式編輯（文字 + 圖片可上下排列）。

---

## 階段一：資料庫與後端 API ✅ 完成

- [x] 建立 `product_images` 資料表（`id`, `product_id`, `image_url`, `sort_order`, `created_at`）
- [x] `ProductService::getProductById()` 同步撈取 `gallery_images` 陣列
- [x] `ProductService` 新增 `addGalleryImage` / `deleteGalleryImage` / `getGalleryImages` / `deleteAllGalleryImages`
- [x] `ProductController` 新增 `upload_gallery_image` action（AJAX 即時上傳單張）
- [x] `ProductController` 新增 `delete_gallery_image` action（刪除 DB + 實體檔）
- [x] `ProductController::deleteProduct()` 連同所有附加圖一起清除
- [x] `saveProduct()` 驗證 `description` 須為合法 JSON
- [x] **修正 Bug**：`admin_product_api.php` 補上缺少的 `require_once 'services/ImageService.php'`
- [x] 建立 `assets/images/products/` 目錄
- [x] 生成 `assets/images/placeholder.webp`

---

## 階段二：後台 UI 改造 (`admin_products.php`) ✅ 完成

- [x] Modal 改為 Bootstrap 三分頁：基本資訊 / 圖片管理 / 商品描述
- [x] 圖集管理：多選上傳、AJAX 即時上傳、縮圖預覽、✕ 刪除
- [x] JSON 區塊建構器：新增文字/圖片區塊、↑↓ 移動、✕ 刪除
- [x] **Description JSON 升級為三節點物件格式**（2026-05-07）：
  - `description` 欄位從平面陣列 `[...]` 改為分類物件：
    ```json
    {
      "intro":  "純文字簡介（商品頁右側顯示）",
      "detail": [ { "type": "text", "content": "..." }, ... ],
      "qa":     [ { "type": "text", "content": "..." }, ... ],
      "specs":  [ { "type": "text", "content": "..." }, ... ]
    }
    ```
  - 後台 Modal：Tab 1（基本資訊）加入「簡介」textarea；Tab 3 起為 block builder（共六個 Tab）
  - 每個分頁各自獨立的 block builder
  - 讀取舊資料時向後相容：舊的 flat array 自動移入 `detail`
  - 儲存時統一序列化為新格式物件

---

## 階段三：前台頁面渲染 (商品內頁) ⏳ 待進行

- [ ] 上方主圖 + 左右箭頭切換 + 下方縮圖列（Swiper.js）
- [ ] 主圖與附加圖集合併為輪播圖陣列
- [ ] 點擊縮圖切換大圖，選中縮圖顯示高亮邊框
- [ ] 下方商品描述渲染（三個 Tab）：
  - **商品描述** (`detail`)：預設顯示，渲染 `description.detail` 區塊陣列
  - **Q&A** (`qa`)：渲染 `description.qa` 區塊陣列
  - **規格** (`specs`)：渲染 `description.specs` 區塊陣列
  - 區塊規則：`type=text` → HTML 段落，`type=image` → `<img>` 標籤
  - PHP：`json_decode($description, true)` 後分別取出三個 key 渲染
