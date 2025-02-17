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
    $admin_name = $data['admin_name'];
    $email = $data['email'];
    $password = $data['password'];  // ใช้รหัสผ่านที่รับเข้ามาเป็นข้อความธรรมดา

    // SQL query เพื่อเพิ่มผู้ใช้ (admin)
    $stmt = $conn->prepare("INSERT INTO admin (admin_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $admin_name, $email, $password);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Admin created successfully."]);
    } else {
        echo json_encode(["message" => "Failed to create admin."]);
    }

    $stmt->close();
}

// ตรวจสอบว่าเป็น GET request
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // รับ admin_id จาก URL
    $admin_id = isset($_GET['admin_id']) ? $_GET['admin_id'] : null;

    // SQL query สำหรับดึงข้อมูลผู้ใช้ (admin)
    if ($admin_id) {
        $sql = "SELECT * FROM admin WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
    } else {
        $sql = "SELECT * FROM admin"; // ดึงข้อมูลผู้ใช้ทั้งหมด
        $stmt = $conn->prepare($sql);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $admins = [];

    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }

    echo json_encode($admins);

    $stmt->close();
}

// ตรวจสอบว่าเป็น PUT request
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // รับข้อมูลจาก body
    $data = json_decode(file_get_contents('php://input'), true);
    $admin_id = $data['admin_id'];
    $admin_name = isset($data['admin_name']) ? $data['admin_name'] : null;
    $email = isset($data['email']) ? $data['email'] : null;
    $password = isset($data['password']) ? $data['password'] : null;  // เก็บรหัสผ่านเป็นข้อความธรรมดา

    // SQL query สำหรับอัปเดตข้อมูล
    $sql = "UPDATE admin SET admin_name = ?, email = ?, password = ? WHERE admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $admin_name, $email, $password, $admin_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Admin updated successfully."]);
    } else {
        echo json_encode(["message" => "Failed to update admin."]);
    }

    $stmt->close();
}

// ตรวจสอบว่าเป็น DELETE request
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // รับข้อมูลจาก body
    $data = json_decode(file_get_contents('php://input'), true);
    $admin_id = $data['admin_id'];

    // SQL query สำหรับลบผู้ใช้
    $stmt = $conn->prepare("DELETE FROM admin WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Admin deleted successfully."]);
    } else {
        echo json_encode(["message" => "Failed to delete admin."]);
    }

    $stmt->close();
}

$conn->close();
