<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class BlogService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 取得部落格文章列表 (含分頁與篩選)
     */
    public function getArticles($filter = 'all', $page = 1, $items_per_page = 10) {
        $offset = ($page - 1) * $items_per_page;

        // 計算總數
        $count_sql = "SELECT COUNT(*) FROM articles WHERE status != 'draft'";
        $count_params = [];
        if ($filter !== 'all') {
            $count_sql .= " AND status = ?";
            $count_params[] = $filter;
        }
        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute($count_params);
        $total_items = $stmt->fetchColumn() ?: 0;
        $total_pages = max(1, ceil($total_items / $items_per_page));

        // 取得資料
        $sql = "SELECT a.*, u.username, p.display_name as author_name 
                FROM articles a 
                JOIN users u ON a.user_id = u.id 
                LEFT JOIN user_profiles p ON u.id = p.user_id 
                WHERE a.status != 'draft'";
        
        $params = [];
        if ($filter !== 'all') {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filter;
        }
        $sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        if ($filter !== 'all') {
            $stmt->bindValue(':status', $filter);
        }
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => $total_items,
                'items_per_page' => $items_per_page
            ]
        ];
    }

    /**
     * 更新文章狀態 (核准或拒絕)
     */
    public function updateStatus($id, $status) {
        $allowed_statuses = ['published', 'rejected', 'pending', 'draft'];
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception("無效的狀態值。");
        }

        $stmt = $this->pdo->prepare("UPDATE articles SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);

        if (!$success) {
            throw new Exception("更新文章狀態失敗。");
        }

        return true;
    }
}
