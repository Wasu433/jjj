<?php

class AuthController {

    // 1. หน้า Login
    public function index() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['user_id'])) {
             if ($_SESSION['role'] == 'admin') header("Location: index.php?page=admin_dashboard");
             elseif ($_SESSION['role'] == 'teacher') header("Location: index.php?page=teacher_dashboard");
             else header("Location: index.php?page=student_dashboard");
             exit;
        }
        $error = ''; 
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    // 2. ประมวลผล Login
    public function login() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        require __DIR__ . '/../Config/Database.php'; 
        
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            try {
                $stmt = $pdo->prepare("SELECT id, name, email, password_hash, student_id, role FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['student_id'] = $user['student_id'];
                    
                    // ✅✅✅ เพิ่มบรรทัดนี้ครับ (สำคัญมาก)
                    $_SESSION['email'] = $user['email']; 

                    if ($user['role'] === 'admin') header("Location: index.php?page=admin_dashboard");
                    elseif ($user['role'] === 'teacher') header("Location: index.php?page=teacher_dashboard");
                    elseif ($user['role'] === 'student') header("Location: index.php?page=student_dashboard");
                    else header("Location: index.php?page=home");
                    exit;
                } else {
                    $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
                    require_once __DIR__ . '/../../views/auth/login.php';
                }
            } catch (PDOException $e) {
                $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
                require_once __DIR__ . '/../../views/auth/login.php';
            }
        }
    }
    
    // 3. Logout
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }

    // 4. Register
    public function register() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        require __DIR__ . '/../Config/Database.php'; 

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = 'student'; 
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // ✅ เพิ่มตัวดักจับอีเมลของมหาวิทยาลัย (@silpakorn.edu) ตรงนี้
            if (!preg_match('/@silpakorn\.edu$/i', $email)) {
                $_SESSION['error'] = "กรุณาใช้อีเมลของมหาวิทยาลัย (@silpakorn.edu) ในการลงทะเบียนเท่านั้น";
                session_write_close();
                header("Location: index.php?page=register");
                exit; 
            }

            if (strlen($password) < 8) {
                $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
                session_write_close();
                header("Location: index.php?page=register");
                exit; 
            }

            if ($password !== $confirm_password) {
                $_SESSION['error'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
                session_write_close();
                header("Location: index.php?page=register");
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = "อีเมลนี้ถูกใช้งานแล้ว";
                session_write_close();
                header("Location: index.php?page=register");
                exit;
            }

            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hashed_password, $role]);

                $_SESSION['success'] = "ลงทะเบียนสำเร็จเรียบร้อย! สามารถเข้าสู่ระบบได้เลย";
                session_write_close();
                header("Location: index.php?page=login");
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
                session_write_close();
                header("Location: index.php?page=register");
                exit;
            }
        }
    }

    // --------------------------------------------------------
    // ฟังก์ชันเปลี่ยนรหัสผ่าน (Change Password) - สำหรับคนที่ Login อยู่แล้ว
    // --------------------------------------------------------
    public function changePasswordProcess() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // ต้อง Login ก่อนถึงจะเปลี่ยนได้
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?page=login");
            exit;
        }

        require __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // 1. ตรวจสอบรหัสผ่านใหม่
            if (strlen($new_password) < 8) {
                $_SESSION['error'] = "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร";
                header("Location: index.php?page=change_password");
                exit;
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "รหัสผ่านใหม่ไม่ตรงกัน";
                header("Location: index.php?page=change_password");
                exit;
            }

            try {
                // 2. ดึงข้อมูล User มาเช็ครหัสผ่านเดิม
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || !password_verify($current_password, $user['password_hash'])) {
                    $_SESSION['error'] = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
                    header("Location: index.php?page=change_password");
                    exit;
                }

                // 3. อัปเดตรหัสผ่านใหม่
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $update->execute([$new_hash, $user_id]);

                $_SESSION['success'] = "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อย!";
                header("Location: index.php?page=change_password"); // กลับมาหน้าเดิมพร้อมข้อความสำเร็จ
                exit;

            } catch (PDOException $e) {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
                header("Location: index.php?page=change_password");
                exit;
            }
        }
    }
    }
?>