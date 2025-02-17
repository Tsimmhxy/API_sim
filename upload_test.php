<?php
// เพิ่มการตั้งค่า CORS
header("Access-Control-Allow-Origin: *");  // อนุญาตให้ทุกโดเมนสามารถเข้าถึง
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");  // อนุญาต HTTP Methods ที่ต้องการ
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // อนุญาต headers ที่จำเป็น

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "market_database");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    // ตรวจสอบว่าไฟล์ได้รับการอัปโหลดหรือไม่
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] == 0) {
        $amount = $_POST['amount'];
        $transfer_date = $_POST['transfer_date'];

        // กำหนด order_id และ user_id ที่จะใช้
        $order_id = 1;  // หรือค่าที่คุณต้องการ
        $user_id = 1;   // หรือค่าที่คุณต้องการ

        // ตรวจสอบว่า order_id มีในตาราง orders
        $order_check = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
        $order_check->bind_param("i", $order_id);
        $order_check->execute();
        $order_result = $order_check->get_result();

        // ตรวจสอบว่า user_id มีในตาราง users
        $user_check = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $user_check->bind_param("i", $user_id);
        $user_check->execute();
        $user_result = $user_check->get_result();

        // ตรวจสอบว่า order_id และ user_id มีข้อมูล
        if ($order_result->num_rows > 0 && $user_result->num_rows > 0) {
            // เก็บไฟล์สลิปลงในโฟลเดอร์ uploads/slips
            $target_dir = "uploads/slips/";
            $target_file = $target_dir . basename($_FILES['slip']['name']);

            // ตรวจสอบว่าไฟล์อัปโหลดได้สำเร็จหรือไม่
            if (move_uploaded_file($_FILES['slip']['tmp_name'], $target_file)) {
                // เตรียมคำสั่ง SQL เพื่อบันทึกข้อมูล
                $stmt = $conn->prepare("INSERT INTO payments (order_id, user_id, amount, payment_date, slip_image) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisss", $order_id, $user_id, $amount, $transfer_date, $target_file);

                if ($stmt->execute()) {
                    // รีไดเรกต์ไปยังหน้าที่คุณต้องการหลังจากการส่งข้อมูลสำเร็จ
                    header("Location: success_page.php");  // เปลี่ยน URL ตามที่คุณต้องการ
                    exit();  // เพื่อหยุดการทำงานของสคริปต์
                } else {
                    echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล." . $stmt->error;
                }

                $stmt->close();
            } else {
                echo "เกิดข้อผิดพลาดในการอัปโหลดไฟล์.";
            }
        } else {
            echo "Invalid Order ID or User ID.";
        }
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
