<?php

class AdminController {

    // ====================================================
    // 1. จัดการผู้ใช้งาน (User Management)
    // ====================================================

    public function addUser() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = $_POST['password'];

            // เช็คอีเมลซ้ำ
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo "<script>alert('อีเมลนี้มีอยู่ในระบบแล้ว'); window.location.href='index.php?page=manage_users';</script>";
                exit;
            }

            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $email, $hashed_password, $role]);
                header("Location: index.php?page=manage_users");
                exit;
            } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
        }
    }

    public function editUser() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'];
                $name = $_POST['name'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $password = $_POST['password'];

                // เช็คว่าอีเมลไปซ้ำกับคนอื่นไหม (ยกเว้นตัวเอง)
                $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmtCheck->execute([$email, $id]);
                if ($stmtCheck->fetch()) {
                    echo "<script>alert('อีเมลนี้มีผู้ใช้งานอื่นใช้แล้ว'); window.history.back();</script>";
                    exit;
                }

                if (!empty($password)) {
                    // กรณีเปลี่ยนรหัสผ่าน
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $email, $role, $hash, $id]);
                } else {
                    // กรณีไม่เปลี่ยนรหัสผ่าน
                    $sql = "UPDATE users SET name=?, email=?, role=? WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $email, $role, $id]);
                }
                
                header("Location: index.php?page=manage_users");
                exit;
            } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
        }
    }

    public function deleteUser() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id'])) {
            if ($_GET['id'] == $_SESSION['user_id']) {
                echo "<script>alert('ไม่สามารถลบบัญชีตัวเองได้'); window.history.back();</script>";
                exit;
            }
            try {
                $user_id = $_GET['id'];
                
                // ลบข้อมูลที่เกี่ยวข้องก่อน (เช่น ใบสมัครของนักศึกษา) เพื่อป้องกัน Error
                $pdo->beginTransaction();

                // 1. ถ้าเป็นนักศึกษา ให้ลบใบสมัครทิ้งก่อน
                $stmtDelApp = $pdo->prepare("DELETE FROM applications WHERE student_id = ?");
                $stmtDelApp->execute([$user_id]);

                // 2. ลบผู้ใช้
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);

                $pdo->commit();
                header("Location: index.php?page=manage_users");
                exit;
            } catch (PDOException $e) { 
                $pdo->rollBack();
                // ถ้าเป็นอาจารย์ที่มีวิชาสอนอยู่ อาจจะลบไม่ได้ (ติด FK) ให้แจ้งเตือน
                echo "<script>alert('ลบไม่สำเร็จ! ผู้ใช้นี้อาจมีข้อมูลเชื่อมโยงอยู่ (เช่น เป็นอาจารย์ที่มีรายวิชา)'); window.location.href='index.php?page=manage_users';</script>"; 
            }
        }
    }


    // ====================================================
    // 2. จัดการรายวิชา (Subject Management)
    // ====================================================

    public function addSubject() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 🔥 แก้ไข: รับค่า term และ year แล้วเอามารวมกันเป็น semester
                $semester = $_POST['term'] . '/' . $_POST['year'];

                $sql = "INSERT INTO subjects (code,course_id ,name, semester, teacher_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                // ใช้ตัวแปร $semester ที่รวมแล้ว แทน $_POST['semester']
                $stmt->execute([$_POST['code'], $_POST['course_id'], $_POST['name'], $semester, $_POST['teacher_id']]);
                
                header("Location: index.php?page=manage_subjects");
                exit;
            } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
        }
    }

    public function editSubject() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // 🔥 แก้ไข: รับค่า term และ year แล้วเอามารวมกันเป็น semester
                $semester = $_POST['term'] . '/' . $_POST['year'];

                $sql = "UPDATE subjects SET code=?, name=?, semester=?, teacher_id=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                
                // ใช้ตัวแปร $semester ที่รวมแล้ว แทน $_POST['semester']
                $stmt->execute([$_POST['code'], $_POST['name'], $semester, $_POST['teacher_id'], $_POST['id']]);
                
                header("Location: index.php?page=manage_subjects");
                exit;
            } catch (PDOException $e) { echo "Error: " . $e->getMessage(); }
        }
    }

    public function deleteSubject() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id'])) {
            try {
                // ใช้ Logic ลบแบบ Cascade (ลบ Sections ก่อน) ตามที่เราเคยคุยกัน
                $pdo->beginTransaction();
                $subject_id = $_GET['id'];
                
                // 1. ลบ Sections
                $stmt = $pdo->prepare("DELETE FROM sections WHERE subject_id = ?");
                $stmt->execute([$subject_id]);

                // 2. ลบ วิชา
                $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                $stmt->execute([$subject_id]);

                $pdo->commit();
                header("Location: index.php?page=manage_subjects");
                exit;
            } catch (PDOException $e) { 
                $pdo->rollBack();
                echo "Error: " . $e->getMessage(); 
            }
        }
    }


    // ====================================================
    // 3. จัดการประกาศ (Recruitment Management)
    // ====================================================

   public function addRecruitment() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo->beginTransaction();

                // 1. บันทึกประกาศหลัก
                $sql1 = "INSERT INTO recruitments (teacher_id, title, description, subject_id, status) VALUES (?, ?, ?, ?, 'open')";
                $stmt = $pdo->prepare($sql1);
                $stmt->execute([
                    $_POST['teacher_id'], 
                    $_POST['title'], 
                    $_POST['description'], 
                    $_POST['subject_id']
                ]);
                $recruitment_id = $pdo->lastInsertId();

                // 2. บันทึกรายละเอียด
                $total_quota = array_sum($_POST['sec_quotas'] ?? []);
                
                $sql2 = "INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql2);
                $stmt->execute([$recruitment_id, $total_quota, $_POST['grade_req']]);

                // 3. บันทึก Section
                if (!empty($_POST['sec_names'])) {
                    $sql3 = "INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)";
                    $stmt3 = $pdo->prepare($sql3);
                    
                    foreach ($_POST['sec_names'] as $key => $sec_name) {
                        $sec_time = $_POST['sec_times'][$key];
                        $sec_quota = $_POST['sec_quotas'][$key];

                        if (!empty(trim($sec_name))) {
                            $stmt3->execute([$_POST['subject_id'], $sec_name, $sec_time, $sec_quota]);
                        }
                    }
                }

                $pdo->commit();
                header("Location: index.php?page=manage_recruitments");
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    }
    
   public function editRecruitment() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo->beginTransaction();

                // 1. อัปเดตข้อมูลหลัก (เพิ่ม status=?)
                $sql1 = "UPDATE recruitments SET title=?, description=?, subject_id=?, teacher_id=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql1);
                
                // 🔥 ใส่ตัวแปร status ลงไปใน execute
                $stmt->execute([
                    $_POST['title'], 
                    $_POST['description'], 
                    $_POST['subject_id'], 
                    $_POST['teacher_id'], 
                    $_POST['status'], // ✅ รับค่าจาก Dropdown
                    $_POST['id']
                ]);
                
                // 2. อัปเดตรายละเอียด
                $total_quota = array_sum($_POST['sec_quotas'] ?? []);
                $check = $pdo->prepare("SELECT id FROM recruitment_details WHERE recruitment_id = ?");
                $check->execute([$_POST['id']]);
                if ($check->fetch()) {
                    $sql2 = "UPDATE recruitment_details SET quota=?, grade_requirement=? WHERE recruitment_id=?";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([$total_quota, $_POST['grade_req'], $_POST['id']]);
                } else {
                    $sql2 = "INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([$_POST['id'], $total_quota, $_POST['grade_req']]);
                }

                // 3. จัดการ Section (เช็คก่อนลบ เพื่อป้องกัน Error 1451)
                $checkApps = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruitment_id = ?");
                $checkApps->execute([$_POST['id']]);
                $hasApps = $checkApps->fetchColumn() > 0;

                if (!$hasApps) {
                    $delSec = $pdo->prepare("DELETE FROM sections WHERE subject_id = ?");
                    $delSec->execute([$_POST['subject_id']]);

                    if (!empty($_POST['sec_names'])) {
                        $sql3 = "INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)";
                        $stmt3 = $pdo->prepare($sql3);
                        foreach ($_POST['sec_names'] as $key => $sec_name) {
                            $sec_time = $_POST['sec_times'][$key];
                            $sec_quota = $_POST['sec_quotas'][$key];
                            if (!empty(trim($sec_name))) {
                                $stmt3->execute([$_POST['subject_id'], $sec_name, $sec_time, $sec_quota]);
                            }
                        }
                    }
                }

                $pdo->commit();
                header("Location: index.php?page=manage_recruitments");
                exit;
            } catch (PDOException $e) { 
                $pdo->rollBack(); 
                echo "Error: " . $e->getMessage(); 
            }
        }
    }

    public function deleteRecruitment() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }
        require_once __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id'])) {
            try {
                $recruitment_id = $_GET['id'];
                $pdo->beginTransaction();

                // 1. หาว่าประกาศนี้ เป็นของวิชา (subject_id) อะไร?
                $stmtFindSub = $pdo->prepare("SELECT subject_id FROM recruitments WHERE id = ?");
                $stmtFindSub->execute([$recruitment_id]);
                $row = $stmtFindSub->fetch();

                if ($row) {
                    $subject_id = $row['subject_id'];
                    // 2. ลบ Sections ทั้งหมดที่เป็นของวิชานี้ (เฉพาะกรณี Admin ลบประกาศ)
                    // หมายเหตุ: การลบ Section อาจจะกระทบถ้ามีประกาศอื่นใช้วิชาเดียวกัน แต่ในระบบนี้ 1 วิชา = 1 ประกาศ
                    $stmtDelSec = $pdo->prepare("DELETE FROM sections WHERE subject_id = ?");
                    $stmtDelSec->execute([$subject_id]);
                }

                // 3. ลบใบสมัคร
                $stmt = $pdo->prepare("DELETE FROM applications WHERE recruitment_id = ?");
                $stmt->execute([$recruitment_id]);

                // 4. ลบรายละเอียดประกาศ
                $stmt = $pdo->prepare("DELETE FROM recruitment_details WHERE recruitment_id = ?");
                $stmt->execute([$recruitment_id]);

                // 5. ลบตัวประกาศ
                $stmt = $pdo->prepare("DELETE FROM recruitments WHERE id = ?");
                $stmt->execute([$recruitment_id]);

                $pdo->commit();
                header("Location: index.php?page=manage_recruitments");
                exit;

            } catch (PDOException $e) { 
                $pdo->rollBack(); 
                echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>"; 
            }
        }
    }

    public function getSubject(){
        require_once __DIR__ . '/../Config/Database.php';
        $stmt = $pdo->query("SELECT * FROM subjects ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>