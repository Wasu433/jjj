<?php
session_start();

// เช็ก Path ให้ตรงกับโครงสร้างโฟลเดอร์ของคุณ
// จาก public/read_notification.php วิ่งไปหา app/Config/Database.php
require_once __DIR__ . '/../app/Config/Database.php'; 

$id = $_GET['id'] ?? 0;
$link = $_GET['link'] ?? 'index.php'; // ถ้าไม่มีลิงก์ ให้กลับหน้าแรก

// ต้องมี ID และต้อง Login แล้วเท่านั้นถึงจะแก้สถานะได้
if ($id && isset($_SESSION['user_id'])) {
    try {
        $db = (new Database())->connect();
        // อัปเดตให้เป็นอ่านแล้ว (is_read = 1) เฉพาะของ User คนนี้
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
    } catch (Exception $e) {
        // ถ้า Error ไม่ต้องทำอะไร ให้ Redirect ไปเลย
    }
}

// ดีดไปหน้าปลายทาง
header("Location: " . $link);
exit;
?>