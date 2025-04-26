<?php
class Cart {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCartItems($user_id) {
        $sql = "SELECT c.*, m.name, m.price, m.image_url, r.name as restaurant_name, r.id as restaurant_id 
                FROM cart c
                JOIN meals m ON c.meal_id = m.id 
                JOIN restaurants r ON m.restaurant_id = r.id
                WHERE c.user_id = ?
                ORDER BY r.name, m.name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function addItem($user_id, $meal_id, $quantity) {
        // Check if item from same restaurant
        $stmt = $this->conn->prepare(
            "SELECT r1.id as new_restaurant_id, r2.id as cart_restaurant_id
             FROM meals m1
             JOIN restaurants r1 ON m1.restaurant_id = r1.id
             LEFT JOIN (
                 SELECT DISTINCT r.id
                 FROM cart c
                 JOIN meals m ON c.meal_id = m.id
                 JOIN restaurants r ON m.restaurant_id = r.id
                 WHERE c.user_id = ?
             ) r2 ON 1=1
             WHERE m1.id = ?"
        );
        $stmt->execute([$user_id, $meal_id]);
        $result = $stmt->fetch();

        if ($result && $result['cart_restaurant_id'] !== null && 
            $result['new_restaurant_id'] !== $result['cart_restaurant_id']) {
            throw new Exception("Vous ne pouvez commander que d'un seul restaurant à la fois.");
        }

        // Check if item already exists in cart
        $stmt = $this->conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND meal_id = ?");
        $stmt->execute([$user_id, $meal_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantity
            $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            return $stmt->execute([$existing['quantity'] + $quantity, $existing['id']]);
        } else {
            // Add new item
            $stmt = $this->conn->prepare("INSERT INTO cart (user_id, meal_id, quantity) VALUES (?, ?, ?)");
            return $stmt->execute([$user_id, $meal_id, $quantity]);
        }
    }

    public function updateQuantity($cart_id, $user_id, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cart_id, $user_id);
        }

        $stmt = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$quantity, $cart_id, $user_id]);
    }

    public function removeItem($cart_id, $user_id) {
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        return $stmt->execute([$cart_id, $user_id]);
    }

    public function clearCart($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }

    public function getCartTotal($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT SUM(c.quantity * m.price) as total
             FROM cart c
             JOIN meals m ON c.meal_id = m.id
             WHERE c.user_id = ?"
        );
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getRestaurantInfo($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT r.* 
             FROM cart c
             JOIN meals m ON c.meal_id = m.id
             JOIN restaurants r ON m.restaurant_id = r.id
             WHERE c.user_id = ?"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}
