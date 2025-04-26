<?php
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDashboardStats() {
        $stats = [
            'total_orders' => 0,
            'pending_orders' => 0,
            'total_users' => 0,
            'total_restaurants' => 0,
            'total_revenue' => 0,
            'today_orders' => 0,
            'today_revenue' => 0
        ];

        // Total orders and pending orders
        $stmt = $this->conn->query(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(total_amount) as total_revenue,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
             FROM orders"
        );
        $result = $stmt->fetch();
        $stats['total_orders'] = $result['total_orders'];
        $stats['pending_orders'] = $result['pending_orders'];
        $stats['total_revenue'] = $result['total_revenue'];
        $stats['today_orders'] = $result['today_orders'];
        $stats['today_revenue'] = $result['today_revenue'];

        // Total users
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
        $stats['total_users'] = $stmt->fetch()['count'];

        // Total restaurants
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM restaurants");
        $stats['total_restaurants'] = $stmt->fetch()['count'];

        return $stats;
    }

    public function getRecentOrders($limit = 10) {
        $stmt = $this->conn->prepare(
            "SELECT o.*, u.first_name, u.last_name, u.email,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
             FROM orders o
             JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT); // Bind as an integer
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRestaurantsList($search = null) {
        $sql = "SELECT r.*, 
                       (SELECT COUNT(*) FROM meals WHERE restaurant_id = r.id) as meals_count,
                       (SELECT AVG(rating) FROM reviews WHERE restaurant_id = r.id) as avg_rating
                FROM restaurants r
                WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (r.name LIKE ? OR r.address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY r.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUsersList($role = null, $search = null) {
        $sql = "SELECT u.*,
                       (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as orders_count
                FROM users u
                WHERE 1=1";
        $params = [];

        if ($role) {
            $sql .= " AND u.role = ?";
            $params[] = $role;
        }

        if ($search) {
            $sql .= " AND (u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY u.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getMealsList($restaurant_id = null) {
        $sql = "SELECT m.*, r.name as restaurant_name, c.name as category_name
                FROM meals m
                JOIN restaurants r ON m.restaurant_id = r.id
                JOIN categories c ON m.category_id = c.id
                WHERE 1=1";
        $params = [];

        if ($restaurant_id) {
            $sql .= " AND m.restaurant_id = ?";
            $params[] = $restaurant_id;
        }

        $sql .= " ORDER BY r.name, c.name, m.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateRestaurant($id, $data) {
        $sql = "UPDATE restaurants 
                SET name = ?, description = ?, address = ?, phone = ?, 
                    image_url = ?, is_active = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['address'],
            $data['phone'],
            $data['image_url'],
            $data['is_active'],
            $id
        ]);
    }

    public function updateMeal($id, $data) {
        $sql = "UPDATE meals 
                SET name = ?, description = ?, price = ?, category_id = ?,
                    image_url = ?, is_available = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category_id'],
            $data['image_url'],
            $data['is_available'],
            $id
        ]);
    }

    public function createRestaurant($data) {
        $sql = "INSERT INTO restaurants 
                (name, description, address, phone, image_url, is_active)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['address'],
            $data['phone'],
            $data['image_url'],
            $data['is_active']
        ]);
    }

    public function createMeal($data) {
        $sql = "INSERT INTO meals 
                (restaurant_id, category_id, name, description, price, image_url, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['restaurant_id'],
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['image_url'],
            $data['is_available']
        ]);
    }
}
