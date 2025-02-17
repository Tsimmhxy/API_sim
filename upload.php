<?php
// เพิ่มการตั้งค่า CORS
header("Access-Control-Allow-Origin: *");  // อนุญาตให้ทุกโดเมนสามารถเข้าถึง
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");  // อนุญาต HTTP Methods ที่ต้องการ
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // อนุญาต headers ที่จำเป็น

// เริ่ม session ถ้าไม่ได้เริ่มไว้
session_start();

// ตรวจสอบว่า session มีการตั้งค่าผู้ใช้หรือไม่
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // ดึง user_id จาก session
} else {
    echo "ไม่พบผู้ใช้, กรุณาล็อกอิน";
    exit();
}

// ตรวจสอบว่า order_id ถูกส่งเข้ามาหรือไม่ หรือสร้างใหม่
if (isset($_POST['order_id'])) {
    $order_id = $_POST['order_id']; // หรือใช้ order_id ที่มีอยู่จากการเลือก
} else {
    echo "ไม่พบคำสั่งซื้อ";
    exit();
}

if (isset($_POST['submit'])) {
    // ตรวจสอบว่าไฟล์ได้รับการอัปโหลดหรือไม่
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
        $amount = $_POST['amount'];
        $transfer_date = $_POST['transfer_date']; // กรณีถ้าไม่ได้กรอกให้ใช้เวลาในปัจจุบัน

        // เก็บไฟล์สลิปลงในโฟลเดอร์ uploads/slips
        $target_dir = "uploads/slips/";
        $target_file = $target_dir . basename($_FILES['slip']['name']);

        if (move_uploaded_file($_FILES['slip']['tmp_name'], $target_file)) {
            // เชื่อมต่อฐานข้อมูล
            $conn = new mysqli("localhost", "root", "", "market_database");

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // บันทึกข้อมูลการชำระเงิน
            $stmt = $conn->prepare("INSERT INTO payments (order_id, user_id, amount, payment_date, slip_image) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)");
            $stmt->bind_param("iiss", $order_id, $user_id, $amount, $target_file);

            if ($stmt->execute()) {
                // อัปเดตสถานะคำสั่งซื้อเป็น "paid" เมื่อชำระเงินเสร็จ
                $updateStmt = $conn->prepare("UPDATE orders SET order_status = 'paid' WHERE order_id = ?");
                $updateStmt->bind_param("i", $order_id);
                $updateStmt->execute();

                echo json_encode(["message" => "Payment uploaded and order updated to 'Paid'"]);
            } else {
                echo json_encode(["message" => "Error uploading payment"]);
            }

            $stmt->close();
            $conn->close();
        } else {
            echo json_encode(["message" => "Failed to upload slip."]);
        }
    }
}

// เพิ่ม header เพื่อทำการรีเฟรชหน้าเว็บ
header("Location: {$_SERVER['PHP_SELF']}");  // รีเฟรชหน้าเว็บ
exit();
