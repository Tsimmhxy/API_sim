<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';  // เชื่อมต่อฐานข้อมูล

// เริ่ม session ถ้าบริการการ login
session_start();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "GET":
        // ดึงคำสั่งซื้อของผู้ใช้หรือผู้ขาย
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;  // ดึง user_id จาก session
        $seller_id = isset($_GET['seller_id']) ? $_GET['seller_id'] : null;  // รับ seller_id จาก query parameter

        // กรณีที่มี user_id (สำหรับคำสั่งซื้อของผู้ใช้)
        if ($user_id) {
            $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
        }
        // กรณีที่มี seller_id (สำหรับคำสั่งซื้อที่เกี่ยวข้องกับผู้ขาย)
        else if ($seller_id) {
            $stmt = $conn->prepare("SELECT orders.* FROM orders 
                                    JOIN order_items ON orders.order_id = order_items.order_id
                                    WHERE order_items.seller_id = ?");
            $stmt->bind_param("i", $seller_id);
        } else {
            $stmt = $conn->prepare("SELECT * FROM orders");  // ดึงคำสั่งซื้อทั้งหมด
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);

        if (count($orders) > 0) {
            echo json_encode($orders);
        } else {
            echo json_encode(["message" => "No orders found"]);
        }
        break;

    case "POST":
        // สร้างคำสั่งซื้อใหม่
        $data = json_decode(file_get_contents("php://input"), true);

        // ตรวจสอบข้อมูลที่จำเป็น
        if (!isset($data['total_amount'], $data['order_items'])) {
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // ดึง user_id จาก session
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        if (!$user_id) {
            echo json_encode(["error" => "User is not logged in"]);
            exit;
        }

        $total_amount = $conn->real_escape_string($data['total_amount']);
        $order_status = 'pending';  // เริ่มต้นสถานะคำสั่งซื้อเป็น pending

        // เพิ่มคำสั่งซื้อใหม่
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, order_status) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $total_amount, $order_status);

        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;

            // เพิ่มรายการสินค้าในคำสั่งซื้อ
            foreach ($data['order_items'] as $item) {
                $product_id = $conn->real_escape_string($item['product_id']);
                $quantity = $conn->real_escape_string($item['quantity']);
                $price = $conn->real_escape_string($item['price']);
                $total = $quantity * $price;

                // เพิ่มข้อมูลสินค้าในคำสั่งซื้อ
                $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
                $stmt_item->bind_param("iiidd", $order_id, $product_id, $quantity, $price, $total);
                $stmt_item->execute();
            }

            echo json_encode(["message" => "Order created successfully", "order_id" => $order_id]);
        } else {
            echo json_encode(["error" => "Error creating order"]);
        }
        break;

    case "PUT":
        // อัปเดตสถานะคำสั่งซื้อ
        $order_id = isset($_GET['id']) ? $_GET['id'] : null;
        $data = json_decode(file_get_contents("php://input"), true);

        // ตรวจสอบข้อมูลที่จำเป็น
        if (!$order_id || !isset($data['order_status'])) {
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        $order_status = $conn->real_escape_string($data['order_status']);

        // ตรวจสอบสถานะคำสั่งซื้อก่อนการอัปเดต
        $stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // ถ้าคำสั่งซื้อมีสถานะ 'cancelled' ไม่สามารถเปลี่ยนสถานะได้
        if ($row['order_status'] == 'cancelled') {
            echo json_encode(["error" => "Cannot update status from 'cancelled' to another status."]);
            exit;
        }

        // ถ้าคำสั่งซื้อมีสถานะ 'paid' ไม่สามารถเปลี่ยนสถานะได้
        if ($row['order_status'] == 'paid' && $order_status != 'shipped') {
            echo json_encode(["error" => "Order is already paid, status can only be updated to 'shipped'."]);
            exit;
        }

        // อัปเดตสถานะคำสั่งซื้อ
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $order_status, $order_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Order status updated successfully"]);
        } else {
            echo json_encode(["error" => "Error updating order status"]);
        }
        break;

    case "DELETE":
        // ลบคำสั่งซื้อ หรือเปลี่ยนสถานะเป็น 'cancelled'
        $order_id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$order_id) {
            echo json_encode(["error" => "Order ID is required"]);
            exit;
        }

        // ลบคำสั่งซื้อ หรือเปลี่ยนสถานะเป็น 'cancelled'
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Order cancelled successfully"]);
        } else {
            echo json_encode(["error" => "Error cancelling order"]);
        }
        break;

    default:
        echo json_encode(["error" => "Invalid request method"]);
        break;
}
