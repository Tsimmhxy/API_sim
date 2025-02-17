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
    case "GET":
        // ดึงข้อมูลสินค้าโดย product_id หรือทั้งหมด
        if (isset($_GET['product_id'])) {
            $product_id = $_GET['product_id'];

            // คำสั่ง SQL ดึงข้อมูลสินค้า
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc());
            } else {
                echo json_encode(["error" => "Product not found"]);
            }
        } else {
            // ดึงข้อมูลสินค้าทั้งหมด
            $stmt = $conn->prepare("SELECT * FROM products");
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            echo json_encode($products);
        }
        break;

    case "POST":
        // รับข้อมูลสินค้าที่ต้องการเพิ่ม
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['seller_id'], $data['product_name'], $data['price'])) {
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        $seller_id = $conn->real_escape_string($data['seller_id']);
        $product_name = $conn->real_escape_string($data['product_name']);
        $product_description = $conn->real_escape_string($data['product_description'] ?? '');  // ถ้าไม่มีให้เป็นค่าว่าง
        $price = $conn->real_escape_string($data['price']);
        $category = $conn->real_escape_string($data['category'] ?? '');  // ถ้าไม่มีให้เป็นค่าว่าง

        // คำสั่ง SQL เพิ่มข้อมูลสินค้า
        $stmt = $conn->prepare("INSERT INTO products (seller_id, product_name, product_description, price, category) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $seller_id, $product_name, $product_description, $price, $category);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Product added successfully", "product_id" => $stmt->insert_id]);
        } else {
            echo json_encode(["error" => "Error inserting product: " . $conn->error]);
        }
        break;

    case "PUT":
        // รับข้อมูลที่ต้องการอัปเดต
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['product_id'], $data['product_name'], $data['price'])) {
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        $product_id = $conn->real_escape_string($data['product_id']);
        $product_name = $conn->real_escape_string($data['product_name']);
        $product_description = $conn->real_escape_string($data['product_description'] ?? '');
        $price = $conn->real_escape_string($data['price']);
        $category = $conn->real_escape_string($data['category'] ?? '');

        // คำสั่ง SQL อัปเดตข้อมูลสินค้า
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, product_description = ?, price = ?, category = ? WHERE product_id = ?");
        $stmt->bind_param("ssssi", $product_name, $product_description, $price, $category, $product_id);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Product updated successfully"]);
        } else {
            echo json_encode(["error" => "Error updating product: " . $conn->error]);
        }
        break;

    case "DELETE":
        // ลบสินค้า
        if (isset($_GET['product_id'])) {
            $product_id = $_GET['product_id'];

            // คำสั่ง SQL ลบข้อมูลสินค้า
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);

            if ($stmt->execute()) {
                echo json_encode(["message" => "Product deleted successfully"]);
            } else {
                echo json_encode(["error" => "Error deleting product: " . $conn->error]);
            }
        } else {
            echo json_encode(["error" => "Product ID is required"]);
        }
        break;

    default:
        echo json_encode(["error" => "Invalid request method"]);
        break;
}
