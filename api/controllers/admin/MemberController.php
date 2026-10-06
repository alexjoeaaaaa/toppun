<?php
namespace Api\Controllers\Admin;

use Api\Services\Admin\MemberService;
use Api\Core\Response;
use Exception;

class MemberController {
    private $memberService;

    public function __construct(MemberService $memberService) {
        $this->memberService = $memberService;
    }

    public function handleRequest() {
        $action = $_REQUEST['action'] ?? '';

        try {
            switch ($action) {
                case 'get_list':
                    $this->getList();
                    break;
                case 'delete_member':
                    $this->deleteMember();
                    break;
                case 'update_member':
                    $this->updateMember();
                    break;
                default:
                    Response::error('未知的操作 (action)', 400);
            }
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    private function getList() {
        $page           = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
        $search         = isset($_GET['search']) ? trim($_GET['search'])       : '';
        $items_per_page = 10;

        $data = $this->memberService->getMembers($page, $items_per_page, $search);
        Response::success($data, '成功取得會員列表');
    }

    private function deleteMember() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($id <= 0) {
            Response::error('無效的會員 ID', 400);
        }

        $this->memberService->deleteMember($id);
        Response::success(null, '會員已刪除');
    }

    private function updateMember() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('只允許 POST 請求', 405);
        }

        $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $role = isset($_POST['role']) ? trim($_POST['role']) : '';
        $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

        if ($id <= 0) {
            Response::error('無效的會員 ID', 400);
        }
        if ($username === '' || $email === '' || $role === '') {
            Response::error('請填寫必填欄位 (使用者名稱、電子郵件與身分)', 400);
        }

        if (!in_array($role, ['member', 'author', 'admin'])) {
            Response::error('無效的會員身分種類', 400);
        }

        $this->memberService->updateMember($id, $username, $email, $role, $display_name, $phone);
        Response::success(null, '會員資料更新成功');
    }
}
