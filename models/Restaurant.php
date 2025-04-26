<?php
class Restaurant {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllActive($limit = null) {
        $sql = "SELECT * FROM restaurants WHERE is_active = 1 ORDER BY name";
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $this->conn->query($sql)->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM restaurants WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getMeals($restaurant_id, $category_id = null) {
        $sql = "SELECT m.*, c.name as category_name 
                FROM meals m 
                JOIN categories c ON m.category_id = c.id 
                WHERE m.restaurant_id = ? AND m.is_available = 1";
        $params = [$restaurant_id];
        
        if ($category_id) {
            $sql .= " AND m.category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY c.name, m.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCategories($restaurant_id) {
        $sql = "SELECT DISTINCT c.* 
                FROM categories c 
                JOIN meals m ON m.category_id = c.id 
                WHERE m.restaurant_id = ? AND m.is_available = 1 
                ORDER BY c.name";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$restaurant_id]);
        return $stmt->fetchAll();
    }

    public function getAverageRating($restaurant_id) {
        $stmt = $this->conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE restaurant_id = ?");
        $stmt->execute([$restaurant_id]);
        $result = $stmt->fetch();
        return round($result['avg_rating'], 1) ?: 0;
    }

    public function getReviews($restaurant_id, $limit = null) {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.restaurant_id = ? 
                ORDER BY r.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$restaurant_id]);
        return $stmt->fetchAll();
    }
}
