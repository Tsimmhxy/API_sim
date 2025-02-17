<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php'; // เชื่อมต่อฐานข้อมูล

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "POST":
        $data = json_decode(file_get_contents("php://input"), true);

        // ตรวจสอบค่าที่จำเป็น
        if (!isset($data['store_name'], $data['store_address'], $data['username'], $data['password'], $data['tel'], $data['email'], $data['store_description'])) {
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // ป้องกัน SQL Injection
        $store_name = $conn->real_escape_string($data['store_name']);
        $store_address = $conn->real_escape_string($data['store_address']);
        $username = $conn->real_escape_string($data['username']);
        $password = $conn->real_escape_string($data['password']); // ใช้รหัสผ่านแบบ plaintext
        $tel = $conn->real_escape_string($data['tel']);
        $email = $conn->real_escape_string($data['email']);
        $store_description = $conn->real_escape_string($data['store_description']);

        // ตรวจสอบว่าผู้ใช้มีอยู่แล้วหรือไม่
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_user = $result->fetch_assoc();

        if ($existing_user) {
            $user_id = $existing_user['user_id']; // ใช้ user_id เดิม
        } else {
            // ถ้ายังไม่มีผู้ใช้นี้ ให้สร้างบัญชีใหม่
            $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $password, $email);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id; // ดึง user_id ใหม่
            } else {
                echo json_encode(["error" => "Error creating user: " . $conn->error]);
                exit;
            }
        }

        // ตรวจสอบว่าผู้ใช้มีร้านค้าระเบียนหรือยัง
        $stmt = $conn->prepare("SELECT seller_id FROM sellers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->fetch_assoc()) {
            echo json_encode(["error" => "User already has a registered store"]);
            exit;
        }

        // เพิ่มข้อมูลร้านค้าลงใน sellers
        $stmt = $conn->prepare("INSERT INTO sellers (user_id, store_name, store_address, store_phone, store_description) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $store_name, $store_address, $tel, $store_description);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Store registered successfully", "seller_id" => $stmt->insert_id]);
        } else {
            echo json_encode(["error" => "Error inserting seller details: " . $conn->error]);
        }
        exit;

    default:
        echo json_encode(["error" => "Invalid request method"]);
        exit;
}
