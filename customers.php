<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'connect.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "POST":
        $data = json_decode(file_get_contents("php://input"), true);

        // ตรวจสอบข้อมูลที่จำเป็น
        if (!isset($data['username'], $data['password'], $data['email'], $data['tel'])) {
            echo json_encode(["error" => "Missing required fields: username, password, email, tel"]);
            exit;
        }

        $username = $conn->real_escape_string($data['username']);
        $password = $conn->real_escape_string($data['password']); // เก็บรหัสผ่านแบบธรรมดา
        $email = $conn->real_escape_string($data['email']);
        $tel = $conn->real_escape_string($data['tel']);

        // ตรวจสอบว่า username หรือ email ซ้ำในระบบหรือไม่
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["error" => "Username or email already exists"]);
            exit;
        }

        // Insert ข้อมูลผู้ใช้
        $stmt_user = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt_user->bind_param("sss", $username, $password, $email);

        if ($stmt_user->execute()) {
            $user_id = $stmt_user->insert_id;

            // Insert ข้อมูลลูกค้า
            $stmt_customer = $conn->prepare("INSERT INTO customers (user_id, phone_number) VALUES (?, ?)");
            $stmt_customer->bind_param("is", $user_id, $tel);

            if ($stmt_customer->execute()) {
                echo json_encode(["message" => "Customer registered successfully", "user_id" => $user_id]);
            } else {
                echo json_encode(["error" => "Error in inserting customer details: " . $conn->error]);
            }
        } else {
            echo json_encode(["error" => "Error in inserting user details: " . $conn->error]);
        }
        break;

    default:
        echo json_encode(["error" => "Invalid request method"]);
        exit;
}
