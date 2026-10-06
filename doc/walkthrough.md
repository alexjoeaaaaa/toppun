# 香氛後台系統重構：模組化與版面拆分記錄

## 整體目標
將龐大單一的 `admin.php` 拆解為多個獨立的模組頁面，並引入版型共用機制（Layout Partials），以提升程式碼可讀性、維護性與載入效能。

---

## ✅ 已完成階段

### 第六階段：版面拆分與模組化
- **建立共用版型 (Layout)**：
  - 新增 `view/admin_layout/header.php`：包含 Session 檢查、DB 連線、CSS 載入與 Navbar。
  - 新增 `view/admin_layout/sidebar.php`：獨立左側選單，並加入動態判斷 `active` 狀態的 PHP 邏輯。
  - 新增 `view/admin_layout/footer.php`：共用的頁尾及 JavaScript 載入區塊。
- **拆分獨立模組頁面**：
  - `view/admin/admin_dashboard.php`：儀表板與統計數據。
  - `view/admin/admin_orders.php`：訂單管理與相關 JS。
  - `view/admin/admin_products.php`：商品管理、編輯 Modal 與相關 JS。
  - `view/admin/admin_members.php`：會員管理與相關 JS。
  - `view/admin/admin_promotions.php`：折扣碼、全館活動管理、編輯 Modal 與相關 JS。
  - `view/admin/admin_blog.php`：Blog 審核管理與相關 JS。
- **處理舊入口與隱藏目錄結構 (Front Controller Pattern)**：
  - 將根目錄的 `admin.php` 改寫為單一入口路由器 (Router)。
  - 所有後台請求皆透過 `admin.php?page=xxx` 處理，藉此隱藏 `view/admin/` 目錄結構。
  - 因為執行點留在根目錄，所有的 API 呼叫（如 `api/...`）與 CSS/圖片等相對路徑，都維持與未搬移前完全相同的撰寫方式。
- **清理舊 Legacy 程式碼**：
  - `admin_products.php` 內的商品新增/修改功能，現在直接對接 `api/admin_product_api.php`，我們檢視了 `ProductController.php` 確認裡面已經具備所有 CRUD (save_product, delete_product, toggle_status) 邏輯，因此完美銜接。

---

## 📂 拆分後的檔案結構
```text
/ (根目錄)
├── admin.php (後台單一入口路由器, 透過 ?page=xxx 載入視圖)
└── view/
    ├── admin/
    │   ├── admin_dashboard.php
    │   ├── admin_orders.php
    │   ├── admin_products.php
    │   ├── admin_members.php
    │   ├── admin_promotions.php
    │   └── admin_blog.php
    └── admin_layout/
        ├── header.php
        ├── sidebar.php
        └── footer.php
```

---

## Backlog

- [ ] 分頁改為後端 SQL LIMIT 控制筆數（適用於大量資料情境）。
- [ ] 測試所有的獨立管理頁面，確保 JS 與 API 串接正常（特別是 Modal 的開啟與表單送出）。

---

## 下次繼續的切入點

1. 由使用者測試拆分後的後台頁面（登入並切換選單）。
2. 開始實作後端 SQL LIMIT 的分頁機制。
