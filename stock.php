<?php
// เพิ่มการตั้งค่า CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "market_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();  // เริ่มการใช้งาน session เพื่อดึงข้อมูลผู้ใช้

// ตรวจสอบว่าเป็นผู้ขายที่ล็อกอินหรือไม่
if (!isset($_SESSION['seller_id'])) {
    echo json_encode(["message" => "Unauthorized access. Only sellers can access this resource."]);
    exit();
}

// GET /stock : ดึงข้อมูลสต็อกสินค้าทั้งหมด
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM stock");

    if ($result->num_rows > 0) {
        $stock_data = array();
        while ($row = $result->fetch_assoc()) {
            $stock_data[] = $row;
        }
        echo json_encode($stock_data);
    } else {
        echo json_encode(["message" => "No stock found."]);
    }
}

// POST /stock : เพิ่มหรือลดจำนวนสต็อก (โดยผู้ขาย)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $product_id = $data['product_id'];
    $quantity = $data['quantity'];

    if (isset($product_id) && isset($quantity)) {
        $stmt = $conn->prepare("INSERT INTO stock (product_id, quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
        $stmt->bind_param("iii", $product_id, $quantity, $quantity);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Stock updated successfully."]);
        } else {
            echo json_encode(["message" => "Failed to update stock."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "Product ID and quantity are required."]);
    }
}

// PUT /stock/{id} : แก้ไขข้อมูลจำนวนสินค้าคงคลัง
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $stock_id = $data['stock_id'];
    $quantity = $data['quantity'];

    if (isset($stock_id) && isset($quantity)) {
        $stmt = $conn->prepare("UPDATE stock SET quantity = ? WHERE stock_id = ?");
        $stmt->bind_param("ii", $quantity, $stock_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Stock quantity updated successfully."]);
        } else {
            echo json_encode(["message" => "Failed to update stock quantity."]);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "Stock ID and quantity are required."]);
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
