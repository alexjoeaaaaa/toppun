# 香氛網站 (TopPun香．芬) REST API 重構評估與規劃

## 一、 討論背景與目標
目前香氛網站 (`mcandle.fxinspire.com`) 的架構屬於傳統的 PHP 伺服器端渲染 (Server-Side Rendering) 模式，HTML 視圖與資料庫邏輯（如 `admin.php`, `checkout.php`）高度耦合。
經參考另一專案 `SeoApi` 的架構後，確認未來香氛網站的優化方向為：**轉型為 REST API 架構 (Controller-Service 模式)**，實現前後端完全分離。

## 二、 參考架構 (以 SeoApi 為例)
在 `SeoApi` 專案中，展示了標準的分層架構：
*   **Controller (控制器)**：負責接收 HTTP 請求，驗證參數，並呼叫對應的 Service，最後將結果格式化為 JSON 回傳。
*   **Service (服務層)**：負責處理核心的商業邏輯與資料庫互動。
*   **View (視圖)**：**被捨棄**。API 不需要產出 HTML 網頁，而是單純回傳純文字的 JSON 格式資料。

這是一種非常現代且標準的 **API MVC**（或稱 Controller-Service 模式）做法。

## 三、 轉型為 REST API 的三大優勢

### 1. 解決「義大利麵條程式碼」(Spaghetti Code)
將後端邏輯徹底從 HTML 中抽離。後端開發者只需專注於寫出穩定、安全的回傳 JSON 資料的 API (Controller + Service)；前端開發者則專注於介面渲染。這大幅提升了程式碼的**可讀性與可維護性**。

### 2. 釋放現代前端框架的威力
當後端改為純 API 提供者後，前端便可以導入如 **Angular、Vue.js 或是 React** 等單頁應用程式 (SPA) 框架。
這能讓使用者的購物體驗產生質的飛躍：換頁不需重新載入、加入購物車畫面即時更新、結帳流程更流暢，體驗媲美原生手機 App。

### 3. 極高的未來擴充性 (跨平台共用)
這是最重要的商業優勢。當未來香氛品牌需要開發 **iOS 或 Android 原生手機 App** 時：
*   舊架構：需重新開發一套專給 App 用的後端邏輯。
*   **REST API 架構**：App 可以**直接呼叫同一套 API**！因為無論是網頁前端、iOS 還是 Android，都能解析 JSON 格式。實現「後端寫一次，多端共用」的目標。

## 四、 建議的初步重構步驟 (Roadmap)

如果決定開始進行重構，建議依循以下漸進式的步驟，避免一次性推翻整個專案導致系統崩潰：

1.  **建立 API 目錄與基礎建設**: 
    *   在專案中建立 `api/controllers` 與 `api/services` 目錄。
    *   設定一個統一的回傳格式機制 (例如統包成 `{"success": true, "data": {...}, "message": ""}` 的 JSON 結構)。
2.  **模組化拆解 (以商品列表為例)**:
    *   將 `product-list.php` 中讀取商品的 SQL 邏輯抽離到 `ProductService.php`。
    *   建立一個 `ProductController.php` 接收請求，呼叫 Service 並回傳 JSON。
3.  **前端逐步替換 (AJAX/Fetch)**:
    *   前台的 `product-list.php` 改為空白的 HTML 結構，透過 JavaScript 的 `fetch` 呼叫剛剛寫好的 API，並動態把商品塞進 HTML 中。
4.  **核心功能重構**:
    *   依序將最複雜的「購物車/結帳系統」與「後台管理系統」改寫為 API 提供服務。
5.  **全面導入前端框架 (可選的最後一步)**:
    *   當所有後端都變成 API 後，就可以正式使用 Angular/Vue 將前端整個打掉重練，完成徹底的前後端分離。
