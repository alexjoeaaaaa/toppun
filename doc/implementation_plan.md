# 重構「TopPun香．芬」後台為 REST API 架構 (第三階段)

## 專案目標與背景

為了提升網站效能與維護性，我們正逐步將 `admin.php` 中的各項模組重構為前後端分離的 REST API 架構。在前兩階段中，我們已經成功重構了 Blog、Dashboard 以及 Product 模組。
本階段的目標是將核心的 **訂單管理 (Orders)** 模組改寫為 API 架構。這將讓所有的搜尋、篩選與分頁行為都不再需要重新載入整個網頁。

> [!IMPORTANT]
> **使用者審核需求**
> 此為第三階段的重構計畫。我們將刪除原本的 `api/update-order-status.php`，並將其邏輯整合到新的 API 架構中。請您確認以下規劃，若無問題，請回覆「同意」或「開始執行」。

## 實作步驟規劃

---

### [後端 API] 訂單模組 (Orders)

我們將建立標準的 `Service -> Controller -> Router` 結構。

#### [NEW] `api/services/admin/OrderService.php`
封裝與訂單相關的所有資料庫邏輯：
* `getOrders($page, $status, $searchQuery, $itemsPerPage)`: 處理列表查詢、搜尋與狀態過濾。
* `getOrderDetails($order_id)`: 查詢訂單的所有商品明細。
* `updateOrderStatus($order_id, $status)`: 更新訂單的付款/配送狀態。

#### [NEW] `api/controllers/admin/OrderController.php`
處理接收到的 HTTP 請求並驗證參數，再呼叫 `OrderService` 來回傳 JSON 結果。

#### [NEW] `api/admin_order_api.php`
建立訂單的專屬 API 路由入口，強制驗證管理員 Session 權限。

#### [DELETE] `api/update-order-status.php`
將此舊版單一功能的腳本刪除，將其整合入新架構。

---

### [前端改造] `admin.php` 視圖更新

#### [MODIFY] `admin.php` (移除 PHP 資料邏輯)
* **刪除** 第 220 至 262 行中透過 PHP 去查詢 `orders`、處理過濾器與分頁的邏輯。

#### [MODIFY] `admin.php` (改造 HTML 結構)
* 將原有的訂單列表 `<tbody>` 與訂單明細摺疊列改為動態渲染容器 (`<tbody id="order-table-body">`)。
* 將 PHP 產生的搜尋列、過濾器（全部、未付款、已付款等）與分頁按鈕改為透過 JavaScript 監聽點擊與提交事件。

#### [MODIFY] `admin.php` (加入 JavaScript 邏輯)
* 撰寫 `fetchOrders(status, search, page)` 函數發送 AJAX 請求至 `api/admin_order_api.php`。
* 撰寫 `renderOrderTable()` 與 `renderOrderPagination()` 動態生成畫面，包含訂單明細的摺疊效果。
* 將舊版的 `updateOrderStatus()` 修改為呼叫新的 API 端點。

## 驗證計畫 (Verification Plan)

### 手動驗證 (Manual Verification)
1. 在 `admin.php` 點擊「已付款」、「已發貨」等按鈕，確認頁面不會重新整理，且表格會正確載入對應狀態的訂單。
2. 於搜尋框輸入訂單編號或姓名，確認搜尋結果正確。
3. 展開某一筆訂單的「查看」明細，確認訂購項目、單價、小計及總金額計算皆正確。
4. 試著切換一筆訂單的狀態（例如從「未付款」改為「已付款」），確認系統成功更新並保持當前頁面狀態。
