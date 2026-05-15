<?php
// app/Helpers/NotificationHelper.php
require __DIR__ . '/../Config/Database.php';

class NotificationHelper {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect(); // หรือเรียกใช้ connection ตามที่คุณเขียนไว้
    }

    // ฟังก์ชันส่งแจ้งเตือน (ใช้เรียกตอนอาจารย์กด Approve/Reject)
    public static function send($user_id, $title, $message, $link = '#') {
        $db = (new Database())->connect();
        $sql = "INSERT INTO notifications (user_id, title, message, link) VALUES (:user_id, :title, :message, :link)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':message' => $message,
            ':link' => $link
        ]);
    }

    // ฟังก์ชันดึงแจ้งเตือนที่ยังไม่ได้อ่าน (ใช้แสดงใน Navbar)
    public static function getUnread($user_id) {
        $db = (new Database())->connect();
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ฟังก์ชันนับจำนวนแจ้งเตือน (เอาไว้โชว์ตัวเลขแดงๆ)
    public static function countUnread($user_id) {
        $db = (new Database())->connect();
        $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // ฟังก์ชันกดแล้วเปลี่ยนสถานะเป็น "อ่านแล้ว"
    public static function markAsRead($notification_id) {
        $db = (new Database())->connect();
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([':id' => $notification_id]);
    }
}
?>