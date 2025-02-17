<?php
// ตั้งค่า CORS สำหรับการเข้าถึง
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การอัปโหลดสำเร็จ</title>
    <link rel="stylesheet" href="styles.css"> <!-- ถ้าคุณมีไฟล์ CSS -->
</head>

<body>
    <div class="container">
        <h1>การอัปโหลดสลิปสำเร็จ!</h1>
        <p>ข้อมูลของคุณได้รับการบันทึกสำเร็จ และกำลังถูกดำเนินการต่อไป</p>

        <p><a href="index_test.html">กลับไปที่หน้าหลัก</a></p> <!-- ลิงค์กลับไปที่หน้าหลัก -->
    </div>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        h1 {
            color: green;
        }

        a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</body>

</html>