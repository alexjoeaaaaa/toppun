<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class OrderService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getOrders($page = 1, $items_per_page = 10, $status = 'all', $search = '') {
        $offset = ($page - 1) * $items_per_page;

        $count_sql = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE 1=1";
        $params = [];
        
        if ($status !== 'all') {
            $count_sql .= " AND o.order_status = ?";
            $params[] = $status;
        }
        
        if ($search !== '') {
            $count_sql .= " AND (o.id = ? OR u.username LIKE ? OR p.display_name LIKE ?)";
            $search_term = "%$search%";
            $params[] = (int)str_replace('#', '', $search);
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_orders = $stmt->fetchColumn() ?: 0;
        $total_pages = max(1, ceil($total_orders / $items_per_page));

        $order_sql = "SELECT o.*, u.username, p.display_name as customer_name FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE 1=1";
        if ($status !== 'all') { $order_sql .= " AND o.order_status = :status"; }
        if ($search !== '') { $order_sql .= " AND (o.id = :oid OR u.username LIKE :search OR p.display_name LIKE :search2)"; }
        $order_sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $order_stmt = $this->pdo->prepare($order_sql);
        if ($status !== 'all') { $order_stmt->bindValue(':status', $status); }
        if ($search !== '') {
            $order_stmt->bindValue(':oid', (int)str_replace('#', '', $search), PDO::PARAM_INT);
            $order_stmt->bindValue(':search', "%$search%");
            $order_stmt->bindValue(':search2', "%$search%");
        }
        $order_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $order_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $order_stmt->execute();
        $orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => $total_orders,
                'items_per_page' => $items_per_page
            ]
        ];
    }

    public function getOrderDetails($order_id) {
        $stmt = $this->pdo->prepare("SELECT oi.*, p.name, p.image_main FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($order_id, $status) {
        $allowed_status = ['unpaid', 'paid', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowed_status)) {
            throw new Exception("無效的狀態");
        }
        
        $stmt = $this->pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $order_id]);
        if (!$success) {
            throw new Exception("訂單狀態更新失敗");
        }
        return true;
    }
}
