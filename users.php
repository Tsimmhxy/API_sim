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

        if (isset($data['username']) && isset($data['password'])) {
            // กรณี Login
            $username = $conn->real_escape_string($data['username']);
            $password = $data['password'];

            // คำสั่ง SQL ดึงข้อมูลผู้ใช้จากตาราง users โดยใช้ username
            $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);  // ผูกค่า parameter
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            // ตรวจสอบรหัสผ่านที่กรอกกับที่เก็บไว้ในฐานข้อมูล (แบบตรงๆ)
            if ($user && $password == $user['password']) {  // ตรวจสอบรหัสผ่านแบบตรงๆ (plaintext)
                echo json_encode(["message" => "Login successful", "user_id" => $user['user_id']]);
            } else {
                echo json_encode(["error" => "Invalid username or password"]);
            }
            exit;
        }
        break;

    case "GET":
        // ถ้าส่ง username มา จะทำการค้นหา user ด้วย username
        if (isset($_GET['username'])) {
            $username = $_GET['username'];

            // คำสั่ง SQL ดึงข้อมูลผู้ใช้จากตาราง users โดยใช้ username
            $stmt = $conn->prepare("SELECT user_id, username, email FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);  // ผูกค่า parameter
            $stmt->execute();
            $result = $stmt->get_result();

            // ถ้ามีข้อมูลผู้ใช้
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc());
            } else {
                echo json_encode(["error" => "No user found"]);
            }
        } else {
            // ถ้าไม่ส่ง username มา ให้ดึงข้อมูลทั้งหมดจาก users
            $stmt = $conn->prepare("SELECT user_id, username, email FROM users");
            $stmt->execute();
            $result = $stmt->get_result();

            // ถ้ามีข้อมูลผู้ใช้
            if ($result->num_rows > 0) {
                $users = [];
                while ($row = $result->fetch_assoc()) {
                    $users[] = $row;
                }
                echo json_encode($users);  // ส่งคืนข้อมูลทั้งหมดของผู้ใช้
            } else {
                echo json_encode(["error" => "No users found"]);
            }
        }
        break;

    default:
        echo json_encode(["error" => "Method not allowed"]);
        break;
}
