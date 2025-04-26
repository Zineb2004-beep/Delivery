<?php
class Order {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createOrder($user_id, $cart_items, $delivery_address) {
        try {
            $this->conn->beginTransaction();

            // Calculate total amount
            $total_amount = 0;
            foreach ($cart_items as $item) {
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            // Add delivery fee
            $total_amount += 2.99;

            // Create order
            $stmt = $this->conn->prepare(
                "INSERT INTO orders (user_id, total_amount, delivery_address, status) 
                 VALUES (?, ?, ?, 'pending')"
            );
            $stmt->execute([$user_id, $total_amount, $delivery_address]);
            $order_id = $this->conn->lastInsertId();

            // Add order items
            $stmt = $this->conn->prepare(
                "INSERT INTO order_items (order_id, meal_id, quantity, price) 
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['meal_id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Clear cart
            $stmt = $this->conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $this->conn->commit();
            return $order_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getOrderById($order_id, $user_id = null) {
        $sql = "SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";
        $params = [$order_id];

        if ($user_id !== null) {
            $sql .= " AND o.user_id = ?";
            $params[] = $user_id;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getOrderItems($order_id) {
        $stmt = $this->conn->prepare(
            "SELECT oi.*, m.name, m.image_url, r.name as restaurant_name
             FROM order_items oi
             JOIN meals m ON oi.meal_id = m.id
             JOIN restaurants r ON m.restaurant_id = r.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$order_id]);
        return $stmt->fetchAll();
    }

    public function getUserOrders($user_id) {
        $stmt = $this->conn->prepare(
            "SELECT o.*, 
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public function updateOrderStatus($order_id, $status, $delivery_user_id = null) {
        $sql = "UPDATE orders SET status = ?";
        $params = [$status];

        if ($delivery_user_id !== null) {
            $sql .= ", delivery_user_id = ?";
            $params[] = $delivery_user_id;
        }

        $sql .= " WHERE id = ?";
        $params[] = $order_id;

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function getAvailableOrders() {
        $stmt = $this->conn->prepare(
            "SELECT o.*, u.first_name, u.last_name, u.phone,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.status = 'pending'
             ORDER BY o.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDeliveryOrders($delivery_user_id) {
        $stmt = $this->conn->prepare(
            "SELECT o.*, u.first_name, u.last_name, u.phone,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
             FROM orders o
             JOIN users u ON o.user_id = u.id
             WHERE o.delivery_user_id = ? AND o.status IN ('delivering', 'completed')
             ORDER BY 
                CASE o.status
                    WHEN 'delivering' THEN 1
                    WHEN 'completed' THEN 2
                END,
                o.created_at DESC"
        );
        $stmt->execute([$delivery_user_id]);
        return $stmt->fetchAll();
    }
}
