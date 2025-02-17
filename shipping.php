<?php
// ตั้งค่า CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// เชื่อมต่อกับฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "market_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// API สำหรับดึงข้อมูลการจัดส่งทั้งหมด (GET /shipping)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT * FROM shipping";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $shipping_data = [];
        while ($row = $result->fetch_assoc()) {
            $shipping_data[] = $row;
        }
        echo json_encode($shipping_data);
    } else {
        echo json_encode(["message" => "No shipping data found."]);
    }
}

// API สำหรับบันทึกข้อมูลการจัดส่ง (POST /shipping)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // ตรวจสอบการส่งข้อมูล
    if (isset($data['order_id'], $data['shipping_status'], $data['tracking_number'], $data['shipping_date'], $data['admin_id'])) {
        $order_id = $data['order_id'];
        $shipping_status = $data['shipping_status'];
        $tracking_number = $data['tracking_number'];
        $shipping_date = $data['shipping_date'];
        $admin_id = $data['admin_id'];

        // เตรียมคำสั่ง SQL เพื่อบันทึกข้อมูล
        $stmt = $conn->prepare("INSERT INTO shipping (order_id, shipping_status, tracking_number, shipping_date, admin_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $order_id, $shipping_status, $tracking_number, $shipping_date, $admin_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Shipping data successfully added."]);
        } else {
            echo json_encode(["message" => "Failed to add shipping data."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["message" => "Invalid input data."]);
    }
}

// API สำหรับอัปเดตสถานะการจัดส่ง (PUT /shipping/{id})
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $shipping_id = $_GET['id']; // รับ id จาก URL parameter

    if (isset($data['shipping_status'])) {
        $shipping_status = $data['shipping_status'];

        // เตรียมคำสั่ง SQL เพื่ออัปเดตสถานะการจัดส่ง
        $stmt = $conn->prepare("UPDATE shipping SET shipping_status = ? WHERE shipping_id = ?");
        $stmt->bind_param("si", $shipping_status, $shipping_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Shipping status updated successfully."]);
        } else {
            echo json_encode(["message" => "Failed to update shipping status."]);
        }

        $stmt->close();
    } else {
        echo json_encode(["message" => "Invalid input data."]);
    }
}

$conn->close();
