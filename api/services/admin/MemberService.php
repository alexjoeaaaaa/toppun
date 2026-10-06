<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class MemberService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getMembers($page = 1, $items_per_page = 10, $search = '') {
        $offset = ($page - 1) * $items_per_page;

        $count_sql = "SELECT COUNT(*) FROM users u WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $count_sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_members = $stmt->fetchColumn() ?: 0;
        $total_pages = max(1, ceil($total_members / $items_per_page));

        $sql = "SELECT u.id, u.username, u.email, u.role, u.created_at,
                       p.display_name, p.phone, p.avatar,
                       COALESCE(SUM(o.total_amount), 0) AS total_spent,
                       COUNT(DISTINCT o.id) AS order_count
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                LEFT JOIN orders o ON u.id = o.user_id AND o.order_status != 'cancelled'
                WHERE 1=1";

        if ($search !== '') {
            $sql .= " AND (u.username LIKE :s1 OR u.email LIKE :s2)";
        }

        $sql .= " GROUP BY u.id, u.username, u.email, u.role, u.created_at, p.display_name, p.phone, p.avatar
                  ORDER BY u.id DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        if ($search !== '') {
            $stmt->bindValue(':s1', "%$search%");
            $stmt->bindValue(':s2', "%$search%");
        }

        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'members' => $members,
            'pagination' => [
                'current_page'  => $page,
                'total_pages'   => $total_pages,
                'total_items'   => $total_members,
                'items_per_page'=> $items_per_page,
            ]
        ];
    }

    public function deleteMember($user_id) {
        // 安全性：不允許刪除管理員
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();

        if ($role === false) {
            throw new Exception("找不到此會員");
        }
        if ($role === 'admin') {
            throw new Exception("不能刪除管理員帳號");
        }

        $this->pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        return true;
    }

    public function updateMember($user_id, $username, $email, $role, $display_name, $phone) {
        // 檢查電子郵件是否被其他使用者佔用
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("此電子郵件已被其他會員使用");
        }

        // 檢查使用者名稱是否被其他使用者佔用
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("此使用者名稱已被其他會員使用");
        }

        $this->pdo->beginTransaction();
        try {
            // 1. 更新 users 資料表
            $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$username, $email, $role, $user_id]);

            // 2. 檢查 user_profiles 是否已存在該使用者的記錄
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile_exists = $stmt->fetchColumn() > 0;

            if ($profile_exists) {
                // 更新 profile
                $stmt = $this->pdo->prepare("UPDATE user_profiles SET display_name = ?, phone = ? WHERE user_id = ?");
                $stmt->execute([$display_name, $phone, $user_id]);
            } else {
                // 新增 profile
                $stmt = $this->pdo->prepare("INSERT INTO user_profiles (user_id, display_name, phone) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $display_name, $phone]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
