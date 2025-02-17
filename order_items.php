<?php
// เพิ่มการตั้งค่า CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "market_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูลจาก body
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'];
    $product_id = $data['product_id'];
    $quantity = $data['quantity'];
    $price = $data['price'];
    $total = $quantity * $price;  // คำนวณราคาโดยรวม

    // ตรวจสอบว่า order_id และ product_id มีอยู่ในฐานข้อมูลหรือไม่
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();

    if ($order_result->num_rows == 0) {
        echo json_encode(["message" => "Order ID does not exist."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if ($product_result->num_rows == 0) {
        echo json_encode(["message" => "Product ID does not exist."]);
        exit;
    }

    // SQL query เพิ่มรายการสินค้า
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiii", $order_id, $product_id, $quantity, $price, $total);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Item added to order successfully."]);
    } else {
        echo json_encode(["message" => "Failed to add item to order."]);
    }

    $stmt->close();
}

// ตรวจสอบว่าเป็น GET request
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // รับ order_id จาก URL
    $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : die("order_id is required.");

    // SQL query
    $sql = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    echo json_encode($items);

    $stmt->close();
}

// ตรวจสอบว่าเป็น PUT request
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // รับข้อมูลจาก body
    $data = json_decode(file_get_contents('php://input'), true);
    $item_id = $data['item_id'];
    $quantity = $data['quantity'];
    $price = $data['price'];
    $total = $quantity * $price;  // คำนวณราคาใหม่

    // SQL query
    $stmt = $conn->prepare("UPDATE order_items SET quantity = ?, price = ?, total = ? WHERE item_id = ?");
    $stmt->bind_param("iiii", $quantity, $price, $total, $item_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Item updated successfully."]);
    } else {
        echo json_encode(["message" => "Failed to update item."]);
    }

    $stmt->close();
}

// ตรวจสอบว่าเป็น DELETE request
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // รับข้อมูลจาก body
    $data = json_decode(file_get_contents('php://input'), true);
    $item_id = $data['item_id'];

    // SQL query
    $stmt = $conn->prepare("DELETE FROM order_items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Item deleted successfully."]);
    } else {
        echo json_encode(["message" => "Failed to delete item."]);
    }

    $stmt->close();
}

$conn->close();
