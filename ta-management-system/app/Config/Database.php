<?php
// app/Config/Database.php

// ✅ เพิ่มบรรทัดนี้: เช็คก่อนว่ามี Class Database หรือยัง? ถ้ามีแล้ว ไม่ต้องสร้างใหม่
if (!class_exists('Database')) {

    class Database {
        private $host = 'db';
        private $db_name = 'TA1';
        private $username = 'root';
        private $password = '1234567890';
        public $conn;

        public function connect() {
            $this->conn = null;
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                echo "Connection Error: " . $e->getMessage();
            }
            return $this->conn;
        }
    }

} // ✅ อย่าลืมปิดปีกกานี้ (ของ if)

// ส่วน Hybrid (เพื่อให้โค้ดเก่าทำงานได้)
// ใส่เงื่อนไขป้องกันการสร้างซ้ำด้วย
if (!isset($pdo)) {
    $database = new Database();
    $pdo = $database->connect();
}
?>