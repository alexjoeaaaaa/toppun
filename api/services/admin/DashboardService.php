<?php
namespace Api\Services\Admin;

use PDO;

class DashboardService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getStats() {
        $total_revenue = $this->pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'cancelled'")->fetchColumn() ?: 0;
        
        $order_count_sql = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN user_profiles p ON u.id = p.user_id WHERE 1=1";
        $total_orders = $this->pdo->query($order_count_sql)->fetchColumn() ?: 0;

        $total_products = $this->pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn() ?: 0;
        
        $total_members = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn() ?: 0;

        return [
            'total_revenue' => (float)$total_revenue,
            'total_orders' => (int)$total_orders,
            'total_products' => (int)$total_products,
            'total_members' => (int)$total_members
        ];
    }
}
