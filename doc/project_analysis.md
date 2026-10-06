# 專案狀態與改進建議報告

**專案名稱**: 香氛蠟燭企業網站 (TopPun香．芬)
**開發技術**: PHP (原生/PDO), HTML, CSS, JavaScript (Fetch API), Bootstrap 5
**資料庫**: MySQL (`fxinspir_candlesite`)

## 一、 已完成功能 (Completed Features)

經過程式碼分析，本專案已實作以下核心系統與功能：

### 1. 使用者與會員系統
*   **註冊與登入**: 包含一般會員、管理員 (Admin) 與作者 (Author) 的登入機制與角色權限劃分。
*   **會員中心**: 提供會員查看個人資料、修改密碼以及檢視過往訂單的介面 (`member.php`, `user_profile.php`)。
*   **密碼重置**: 實作了忘記密碼功能。

### 2. 電子商務與購物系統
*   **商品展示**: 具備商品分類瀏覽 (`product-list.php`) 與單品詳細介紹頁面 (`product-detail.php`)。
*   **購物車機制**: 使用 AJAX (Fetch API) 實作非同步加入購物車功能，並於導覽列即時更新購物車數量。
*   **結帳流程**: `checkout.php` 支援收件人資訊填寫、計算小計與運費，並將訂單資訊 (包含明細) 寫入資料庫，結帳後會自動清空購物車。
*   **促銷折扣系統**: 
    *   支援全域滿額活動 (滿額免運、滿額折抵)。
    *   支援折扣碼輸入，具備低消門檻及首購限制等條件判斷。

### 3. 部落格與內容管理 (CMS)
*   **多作者管理**: 提供專屬的作者登入與後台 (`author-dashboard.php`)，讓多位創作者可以發布文章。
*   **文章編輯**: 內建文章編輯器 (`post-editor.php`) 並支援文章圖片上傳功能。
*   **文章審核機制**: 作者提交文章後，需經過管理員於後台審核 (`approve_blog.php`, `reject_blog.php`) 才會顯示於前台。

### 4. 商家管理後台 (`admin.php`)
*   **儀表板**: 統計總營收、訂單數、商品總數與註冊會員數。
*   **商品管理**: 可進行商品的新增、修改、刪除與上下架狀態切換。
*   **訂單管理**: 列出所有訂單並可依據訂單狀態 (未付款、已付款、已發貨、已完成) 進行篩選。
*   **會員與促銷管理**: 檢視會員清單、管理優惠券與全域促銷活動。

---

## 二、 未完成或待確認功能 (Unfinished / Missing Features)

目前專案雖已具備雛形，但在實際商轉前仍有以下明顯缺口：

1.  **缺乏真實金物流串接**: 
    *   結帳頁面中的「信用卡付款」、「銀行轉帳」目前僅是選項紀錄，並未實際對接綠界 (ECPay)、藍新 (NewebPay) 或 Stripe 等第三方支付閘道。
    *   缺乏物流選擇 (如超商取貨付款、黑貓宅配自動拋單)。
2.  **通知系統不完善**: 
    *   註冊成功、忘記密碼或訂單建立後，並未看到觸發自動寄送 Email 通知 (Order Confirmation/Password Reset Email) 的邏輯。
3.  **安全性防護較弱**:
    *   雖然資料庫操作已使用 PDO Prepared Statement 防止 SQL Injection，但表單提交缺乏 **CSRF Token** 保護。
    *   登入介面未見驗證碼 (Captcha) 機制，可能遭受暴力破解攻擊。
4.  **前端互動體驗 (UX) 細節**: 
    *   加入購物車成功或錯誤時，目前大多使用原生的 `alert()`，視覺體驗較不專業。
5.  **架構耦合度高**: 
    *   大型檔案 (如 `admin.php` 長7(Spaghetti code)」。

---

## 三、 系統加強與優化建議 (Suggestions for Improvement)

為了提升系統的可維護性、安全性與使用者體驗，提出以下建議：

### 📌 1. 架構與程式碼重構 (Architecture Refactoring)
*   **導入 MVC 架構**: 建議將系統重構為 MVC (Model-View-Controller) 架構。可考慮輕量級做法，將資料庫邏輯獨立成類別 (Classes)，並將 HTML 抽取為獨立的視圖檔 (Views)，避免邏輯與版面混雜。
*   **或者考慮使用現代框架**: 如果專案允許，遷移至 Laravel 或 CodeIgniter 等 PHP 框架，能大幅提升開發效率並內建許多安全機制。

### 📌 2. 商業功能完善 (Business Logic Enhancement)
*   **金物流 API 串接**: 優先完成第三方金流與物流 API 的對接，這是電商網站上線的必要條件。
*   **自動化 Email 通知**: 整合 PHPMailer 或串接 SendGrid / Mailgun 等服務，實作註冊信、密碼重置信及訂單狀態更新通知。
*   **訂單狀態追蹤**: 增加客戶能在前台查詢物流進度或訂單詳細處理狀態的功能。

### 📌 3. 資訊安全提升 (Security Hardening)
*   **實作 CSRF 防護**: 在所有 POST 表單 (登入、結帳、後台資料修改) 內加入隨機產生的 CSRF Token 進行驗證。
*   **防禦 XSS 攻擊**: 在輸出任何使用者填寫的內容 (如部落格文章、會員姓名、評論) 時，確保使用 `htmlspecialchars()` 進行嚴格過濾。
*   **登入保護**: 加入 Google reCAPTCHA 或是限制同一 IP 的登入失敗次數。

### 📌 4. 前端體驗優化 (Frontend UX/UI Optimization)
*   **導入 Toast 提示元件**: 將所有的 `alert()` 替換為 Bootstrap 的 Toast 元件或 SweetAlert2，讓系統提示更加美觀且不干擾操作。
*   **單頁結帳 (One-Page Checkout)**: 優化結帳流程，利用 AJAX 動態驗證表單與計算運費，減少頁面跳轉。
*   **圖片最佳化**: `upload-article-image.php` 等上傳功能應加入圖片壓縮機制 (例如轉檔為 WebP)，以減少主機容量消耗並加快網頁載入速度。
