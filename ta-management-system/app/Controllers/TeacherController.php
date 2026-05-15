<?php

class TeacherController
{

    // 1. ฟังก์ชันตรวจสอบสิทธิ์
    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            header("Location: index.php?page=login");
            exit;
        }
    }

    // 2. หน้า Dashboard
    public function dashboard()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        try {
            $teacher_id = $_SESSION['user_id'];

            $sql = "SELECT r.*, s.code, s.name as subject_name, 
                           (SELECT COUNT(*) FROM applications a WHERE a.recruitment_id = r.id) as applicant_count
                    FROM recruitments r
                    JOIN subjects s ON r.subject_id = s.id
                    WHERE r.teacher_id = ? AND r.status != 'deleted'
                    ORDER BY r.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$teacher_id]);
            $recruitments = $stmt->fetchAll();

            require __DIR__ . '/../../views/teacher/teacher_dashboard.php';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    // 3. สร้างประกาศใหม่ (แก้ไข: รับค่า status จาก Dropdown)
    public function createRecruitment()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $pdo->beginTransaction();

                // 🔥 รับค่า status (ถ้าไม่เลือกให้ Default เป็น open)
                $status = $_POST['status'] ?? 'open';

                // 1. บันทึกประกาศ (แก้ SQL ให้รับ status)
                $sql1 = "INSERT INTO recruitments (teacher_id, title, description, subject_id, status) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql1);
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['subject_id'],
                    $status // ✅ ใส่ค่าที่รับมาจากฟอร์ม
                ]);
                $recruitment_id = $pdo->lastInsertId();

                // 2. บันทึกรายละเอียด
                $total_quota = array_sum($_POST['sec_quotas'] ?? []);
                $sql2 = "INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql2);
                $stmt->execute([$recruitment_id, $total_quota, $_POST['grade_req']]);

                // 3. จัดการ Section (Logic เดิม: เช็คก่อนลบ)
                $checkUsage = $pdo->prepare("SELECT COUNT(*) FROM applications a 
                                             JOIN sections s ON a.selected_section_id = s.id 
                                             WHERE s.subject_id = ?");
                $checkUsage->execute([$_POST['subject_id']]);
                $isUsed = $checkUsage->fetchColumn() > 0;

                if (!$isUsed) {
                    $delSec = $pdo->prepare("DELETE FROM sections WHERE subject_id = ?");
                    $delSec->execute([$_POST['subject_id']]);
                }

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

                try {
                    // 1. ดึง ID ของนักศึกษาทุกคน
                    $stmt_std = $pdo->prepare("SELECT id FROM users WHERE role = 'student'");
                    $stmt_std->execute();
                    $students = $stmt_std->fetchAll(PDO::FETCH_COLUMN);

                    if ($students) {

                        $teacher_name = $_SESSION['name'] ?? 'อาจารย์';
                        $subject_id = $_POST['subject_id'];
                        // 2. ดึงชื่อวิชา                        
                        $stmt_sub = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
                        $stmt_sub->execute([$subject_id]);
                        $subject = $stmt_sub->fetchColumn();
                        // ข้อความแจ้งเตือน
                        $msg = "📢 ประกาศรับสมัครใหม่: $subject (โดย $teacher_name)";
                        // ลิงก์ไปหน้ารวมประกาศของนักศึกษา
                        $link = "index.php?page=student_recruitment_list";

                        // วนลูปส่งให้ทุกคน
                        foreach ($students as $std_id) {
                            // เปลี่ยนมาใช้ $this->notificationCreate แทน
                            $this->notificationCreate($std_id, $msg, $link);
                        }
                    }
                } catch (Exception $e) {
                    // ดัก Error ไว้: ถ้าแจ้งเตือนพัง ให้ข้ามไป ไม่ต้องให้หน้าเว็บ Error
                }

                // ✅✅✅ เพิ่มบรรทัดนี้ครับ: ลบแจ้งเตือนที่เก่ากว่า 60 วันทิ้งทันที
                $pdo->exec("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");

                header("Location: index.php?page=my_recruitments");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    }


    // 4. แก้ไขประกาศ (ฉบับอัปเกรด: เพิ่ม/แก้ Sec ได้ แม้มีคนสมัครแล้ว)
   public function editRecruitment()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $teacher_id = $_SESSION['user_id'];
                $recruitment_id = $_POST['id'];

                // 1. ดึง subject_id จากฐานข้อมูลโดยตรง ป้องกันค่าว่าง
                $check = $pdo->prepare("SELECT id, subject_id FROM recruitments WHERE id = ? AND teacher_id = ?");
                $check->execute([$recruitment_id, $teacher_id]);
                $recData = $check->fetch(PDO::FETCH_ASSOC);
                
                if (!$recData) {
                    echo "<script>alert('คุณไม่มีสิทธิ์แก้ไขประกาศนี้'); window.history.back();</script>";
                    exit;
                }
                
                $subject_id = $recData['subject_id']; 

                $pdo->beginTransaction();

                // 2. อัปเดตข้อมูลหลัก + สถานะ 
                $sql1 = "UPDATE recruitments SET title=?, description=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql1);
                $stmt->execute([
                    $_POST['title'],
                    $_POST['description'],
                    $_POST['status'],
                    $recruitment_id
                ]);

                // 3. อัปเดตรายละเอียดโควตาและเกรด
                $total_quota = 0;
                if (!empty($_POST['sec_quotas'])) {
                    foreach ($_POST['sec_quotas'] as $q) {
                        $total_quota += (int)$q;
                    }
                }

                $checkDetails = $pdo->prepare("SELECT id FROM recruitment_details WHERE recruitment_id = ?");
                $checkDetails->execute([$recruitment_id]);

                if ($checkDetails->fetch()) {
                    $sql2 = "UPDATE recruitment_details SET quota=?, grade_requirement=? WHERE recruitment_id=?";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([$total_quota, $_POST['grade_req'], $recruitment_id]);
                } else {
                    $sql2 = "INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)";
                    $stmt2 = $pdo->prepare($sql2);
                    $stmt2->execute([$recruitment_id, $total_quota, $_POST['grade_req']]);
                }

                // ========================================================
                // 4. จัดการ Section แบบใหม่ (รองรับชื่อ Section ซ้ำกันได้) 🧠
                // ========================================================
                
                // 4.1 ดึง Section เดิมที่มีอยู่ทั้งหมดของวิชานี้มากางรอไว้
                $stmtExisting = $pdo->prepare("SELECT id, name FROM sections WHERE subject_id = ? ORDER BY id ASC");
                $stmtExisting->execute([$subject_id]);
                $existingSections = $stmtExisting->fetchAll(PDO::FETCH_ASSOC);

                $stmtInsert = $pdo->prepare("INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)");
                $stmtUpdate = $pdo->prepare("UPDATE sections SET schedule_time = ?, quota = ? WHERE id = ?");

                // 4.2 วนลูปจับคู่ข้อมูลที่ส่งมาจากหน้าจอ
                if (!empty($_POST['sec_names'])) {
                    foreach ($_POST['sec_names'] as $key => $sec_name) {
                        $sec_name = trim($sec_name);
                        $sec_time = trim($_POST['sec_times'][$key] ?? '');
                        $sec_quota = (int)($_POST['sec_quotas'][$key] ?? 0);

                        if (empty($sec_name)) continue;

                        $matched_id = null;
                        
                        // ค้นหาว่ามีชื่อนี้ในฐานข้อมูลเดิมไหม
                        foreach ($existingSections as $index => $es) {
                            if ($es['name'] === $sec_name) {
                                $matched_id = $es['id'];
                                // เจอแล้วให้ "ดึงออกจากคิว" เพื่อให้คิวต่อไปไม่มาจับคู่ซ้ำกับอันนี้อีก! (แก้บั๊กชื่อซ้ำ)
                                unset($existingSections[$index]); 
                                break;
                            }
                        }

                        if ($matched_id) {
                            // เจอคู่เดิม -> อัปเดตทับ
                            $stmtUpdate->execute([$sec_time, $sec_quota, $matched_id]);
                        } else {
                            // ไม่เจอชื่อนี้เลย -> เพิ่มเป็น Section ใหม่
                            $stmtInsert->execute([$subject_id, $sec_name, $sec_time, $sec_quota]);
                        }
                    }
                }

                // 4.3 Section เดิมอันไหนที่ "เหลืออยู่ในคิว" (ไม่ถูกจับคู่) แปลว่าโดนลบจากหน้าจอ!
                if (!empty($existingSections)) {
                    // ดึง ID ของตัวที่เหลือมาลบทิ้งให้หมด
                    $idsToDelete = array_column($existingSections, 'id');
                    $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
                    
                    // ป้องกัน: ห้ามลบเซคชั่นที่มีเด็กนักศึกษาสมัครเข้ามาแล้วเด็ดขาด (ป้องกันข้อมูลหาย)
                    $delSec = $pdo->prepare("
                        DELETE FROM sections 
                        WHERE id IN ($placeholders)
                        AND NOT EXISTS (SELECT 1 FROM applications WHERE applications.selected_section_id = sections.id)
                    ");
                    $delSec->execute($idsToDelete);
                }

                $pdo->commit();
                header("Location: index.php?page=my_recruitments");
                exit;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<script>alert('เกิดข้อผิดพลาดในระบบฐานข้อมูล: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
            }
        }
    }

    // 5. ลบประกาศ
    public function deleteRecruitment()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id'])) {
            try {
                $recruitment_id = $_GET['id'];
                $teacher_id = $_SESSION['user_id'];

                $check = $pdo->prepare("SELECT id, subject_id FROM recruitments WHERE id = ? AND teacher_id = ?");
                $check->execute([$recruitment_id, $teacher_id]);
                $data = $check->fetch();

                if (!$data) {
                    echo "<script>alert('คุณไม่มีสิทธิ์ลบประกาศนี้'); window.history.back();</script>";
                    exit;
                }

                $pdo->beginTransaction();

                $stmtDelSec = $pdo->prepare("DELETE FROM sections WHERE subject_id = ?");
                $stmtDelSec->execute([$data['subject_id']]);

                $stmt = $pdo->prepare("DELETE FROM applications WHERE recruitment_id = ?");
                $stmt->execute([$recruitment_id]);

                $stmt = $pdo->prepare("DELETE FROM recruitment_details WHERE recruitment_id = ?");
                $stmt->execute([$recruitment_id]);

                $stmt = $pdo->prepare("DELETE FROM recruitments WHERE id = ?");
                $stmt->execute([$recruitment_id]);

                $pdo->commit();
                header("Location: index.php?page=my_recruitments");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
            }
        }
    }

    // 6. ดูรายชื่อผู้สมัคร (อัปเดต: เพิ่ม a.grade_file เพื่อดูไฟล์แนบ)
    public function viewApplicants()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        $teacher_id = $_SESSION['user_id'];
        $search = $_GET['search'] ?? '';
        $subject_filter = $_GET['subject_id'] ?? '';
        $status_filter = $_GET['status'] ?? '';

        try {
            $stmtSub = $pdo->prepare("SELECT DISTINCT s.id, s.code, s.name FROM subjects s JOIN recruitments r ON s.id = r.subject_id WHERE r.teacher_id = ?");
            $stmtSub->execute([$teacher_id]);
            $subjects = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

            // 🔥 แก้ SQL: เพิ่ม a.grade_file
            $sql = "SELECT a.id as app_id, a.status, a.created_at, 
                           a.grade as student_grade,
                           a.grade_file,  /* ✅ 1. ดึงไฟล์แนบ */
                           u.name as student_name, u.email, u.id as user_id,
                           u.student_id,
                           s.code as subject_code,s.revisioncode, s.name as subject_name,
                           r.title as job_title, r.id as recruitment_id,
                           sec.name as section_name, sec.schedule_time
                    FROM applications a
                    JOIN users u ON a.student_id = u.id
                    JOIN recruitments r ON a.recruitment_id = r.id
                    JOIN subjects s ON r.subject_id = s.id
                    LEFT JOIN sections sec ON a.selected_section_id = sec.id
                    WHERE r.teacher_id = ? AND a.status != 'deleted'";

            $params = [$teacher_id];

            if (!empty($search)) {
                $sql .= " AND (u.name LIKE ? OR u.student_id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if (!empty($subject_filter) && $subject_filter != 'all') {
                $sql .= " AND s.id = ?";
                $params[] = $subject_filter;
            }
            if (!empty($status_filter) && $status_filter != 'all') {
                $sql .= " AND a.status = ?";
                $params[] = $status_filter;
            }

            // ✅ ใช้ FIELD() เพื่อจัดเรียงสถานะตามลำดับที่เราต้องการ
            // pending -> approved -> rejected 
            // จากนั้นจัดเรียงตามวันที่ล่าสุด (created_at DESC)
            $sql .= " ORDER BY FIELD(a.status, 'pending', 'approved', 'rejected'), a.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../../views/teacher/view_applicants.php';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    // 7. อัปเดตสถานะใบสมัคร
    // 7. อัปเดตสถานะใบสมัคร
    public function updateStatus()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id']) && isset($_GET['status'])) { // รองรับ GET request จากปุ่มในตาราง
            $application_id = $_GET['id'];
            $status = $_GET['status'];
            $recruitment_id = 'ALL'; // Default redirect location

            try {
                $sql = "UPDATE applications SET status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$status, $application_id]);

                // ✅ --- เริ่มส่วนแจ้งเตือน (จุดที่ 1) ---
                if ($stmt->rowCount() > 0) {
                    $this->sendNotification($pdo, $application_id, $status);
                }
                // ✅ --- จบส่วนแจ้งเตือน ---

                header("Location: index.php?page=teacher_view_applicants");
                exit;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $application_id = $_POST['application_id'];
            $status = $_POST['status'];
            $recruitment_id = $_POST['recruitment_id'];

            // ✅ รับค่า URL ปัจจุบันที่ส่งมาจาก Form
            $redirect_url = $_POST['redirect_url'] ?? 'index.php?page=teacher_view_applicants';

            try {
                $sql = "UPDATE applications SET status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$status, $application_id]);

                // ✅ --- เริ่มส่วนแจ้งเตือน (จุดที่ 2) ---
                if ($stmt->rowCount() > 0) {
                    $this->sendNotification($pdo, $application_id, $status);
                }
                // ✅ --- จบส่วนแจ้งเตือน ---

                // 🔥 แก้ Redirect: ให้เด้งกลับไปที่ URL เดิมที่ถูกส่งมา (จะจำค่า Filter ด้วย)
                header("Location: " . $redirect_url);
                exit;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    // ==========================================
    // 🌟 ฟังก์ชันใหม่: อัปเดตสถานะ (สำหรับหน้า view_applicants แบบรายวิชา)
    // ==========================================
    public function updateApplicationStatus()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if (isset($_GET['id']) && isset($_GET['status']) && isset($_GET['recruitment_id'])) {
            $app_id = $_GET['id'];
            $status = $_GET['status']; // 'approved', 'rejected', 'pending'
            $recruitment_id = $_GET['recruitment_id'];

            try {
                // 1. อัปเดตตาราง applications
                $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
                $stmt->execute([$status, $app_id]);

                // 2. ดึงข้อมูลนักศึกษาและวิชาเพื่อส่งแจ้งเตือนกลับ
                if ($stmt->rowCount() > 0 && $status !== 'pending') {
                    $this->sendNotification($pdo, $app_id, $status);
                }

                // 3. เด้งกลับหน้าเดิมพร้อมแจ้งเตือน Alert ด้วย SweetAlert2
                echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'><style>*{font-family:'Prompt',sans-serif;}</style></head><body><script>
                    Swal.fire({ title: 'บันทึกสำเร็จ!', text: 'อัปเดตสถานะเรียบร้อยแล้ว', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => { window.location.href = 'index.php?page=teacher_view_applicants&id={$recruitment_id}'; });
                </script></body></html>";
                exit;
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    // 🔔 ฟังก์ชันเสริมสำหรับส่งแจ้งเตือน (เพิ่มฟังก์ชันนี้เข้าไปใน Class ล่างสุด ก่อนปิดปีกกา } ของ Class)
    private function sendNotification($pdo, $app_id, $status)
    {
        // เช็คสถานะก่อนส่ง (แจ้งเฉพาะ อนุมัติ หรือ ปฏิเสธ)
        if ($status !== 'approved' && $status !== 'rejected') return;

        try {
            // ดึงข้อมูล Student ID และชื่อวิชา
            $sql = "SELECT a.student_id, s.name as subject_name 
                    FROM applications a
                    JOIN recruitments r ON a.recruitment_id = r.id
                    JOIN subjects s ON r.subject_id = s.id
                    WHERE a.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$app_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {

                $title = ($status == 'approved') ? "✅ ยินดีด้วย! ผ่านการคัดเลือก" : "❌ ผลการคัดเลือก TA";
                $message = ($status == 'approved')
                    ? "✅คุณได้รับการอนุมัติเป็น TA ในรายวิชา " . $data['subject_name']
                    : "❌ คุณไม่ผ่านการคัดเลือกในรายวิชา " . $data['subject_name'];

                $this->notificationCreate(
                    $data['student_id'],
                    $message,
                    "index.php?page=my_applications" // ลิงก์ไปหน้าผลการสมัครของนศ.
                );
                // NotificationHelper::notificationCreate(
                //     $data['student_id'], 
                //     $title, 
                //     $message, 
                //     "/student/my_applications.php" // ลิงก์ไปหน้าผลการสมัครของนศ.
                // );
            }
        } catch (Exception $e) {
            // เงียบไว้ถ้าแจ้งเตือนพัง เพื่อไม่ให้กระทบการทำงานหลัก
        }
    }

    public  function notificationCreate($user_id, $message, $link = '#')
    {
        $db = (new Database())->connect();
        $sql = "INSERT INTO notifications (user_id, message, link, is_read, created_at) 
                VALUES (:user_id, :message, :link, 0, NOW())";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':message' => $message,
            ':link' => $link
        ]);
    }

    // 8. หน้าผู้ช่วยสอนของฉัน (อัปเดต: ดึงเกรดและไฟล์แนบ)
    public function myTas()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        $teacher_id = $_SESSION['user_id'];
        $search = $_GET['search'] ?? '';
        $subject_filter = $_GET['subject_id'] ?? '';
        $semester_filter = $_GET['semester'] ?? '';

        try {
            // รายวิชา
            $stmtSub = $pdo->prepare("SELECT DISTINCT s.id, s.code,s.revisioncode, s.name FROM subjects s JOIN recruitments r ON s.id = r.subject_id WHERE r.teacher_id = ?");
            $stmtSub->execute([$teacher_id]);
            $subjects = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

            // เทอม
            $stmtSem = $pdo->prepare("SELECT DISTINCT s.semester FROM subjects s JOIN recruitments r ON s.id = r.subject_id WHERE r.teacher_id = ? ORDER BY s.semester DESC");
            $stmtSem->execute([$teacher_id]);
            $semesters = $stmtSem->fetchAll(PDO::FETCH_COLUMN);

            // 🔥 แก้ SQL: เพิ่ม COALESCE(grade, gpa) และ grade_file
            $sql = "SELECT 
        a.id as app_id,
        u.name as student_name,
        u.student_id,
        u.email,

        s.code as subject_code,s.revisioncode,
        s.name as subject_name,

        sec.id as section_id,        -- ⭐ เพิ่ม
        sec.name as section_name,
        sec.schedule_time,

        a.grade as student_grade,
        a.grade_file

        FROM applications a
        JOIN users u ON a.student_id = u.id
        JOIN recruitments r ON a.recruitment_id = r.id
        JOIN subjects s ON r.subject_id = s.id
        LEFT JOIN sections sec ON a.selected_section_id = sec.id

        WHERE r.teacher_id = ?
        AND a.status = 'approved'";

            $params = [$teacher_id];

            if (!empty($search)) {
                $sql .= " AND (u.name LIKE ? OR u.student_id LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if (!empty($subject_filter) && $subject_filter != 'all') {
                $sql .= " AND s.id = ?";
                $params[] = $subject_filter;
            }
            if (!empty($semester_filter) && $semester_filter != 'all') {
                $sql .= " AND s.semester = ?";
                $params[] = $semester_filter;
            }

            $sql .= " ORDER BY s.code ASC, sec.id ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tas = $stmt->fetchAll(PDO::FETCH_ASSOC);


            // นับจำนวน (Stats)
            $total_tas = count($tas);
            $unique_subjects = count(array_unique(array_column($tas, 'subject_code')));
            $total_sections = 0;
            $sec_check = [];
            foreach ($tas as $t) {
                if (!empty($t['section_name'])) {
                    $key = $t['subject_code'] . '-' . $t['section_id'];
                    if (!in_array($key, $sec_check)) {
                        $sec_check[] = $key;
                        $total_sections++;
                    }
                }
            }

            require __DIR__ . '/../../views/teacher/my_tas.php';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // 9. ประกาศของฉัน (My Recruitments)
    public function myRecruitments()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        $teacher_id = $_SESSION['user_id'];   // ✅ ใช้ id

        $search = $_GET['search'] ?? '';
        $semester_filter = $_GET['semester'] ?? '';

        try {

            // 1️⃣ ดึงรายชื่อวิชา (join users เพื่อ map id -> name)
            $stmtSub = $pdo->query("
            SELECT id, code, name, revisioncode
            FROM subjects
            ORDER BY code ASC
        ");
            $subject_options = $stmtSub->fetchAll(PDO::FETCH_ASSOC);


            // 2️⃣ ดึง Semester (ใช้ teacher_id ตรง ๆ)
            $stmtSem = $pdo->prepare("
            SELECT DISTINCT s.semester
            FROM recruitments r
            JOIN subjects s ON r.subject_id = s.id
            WHERE r.teacher_id = ?
              AND r.status != 'deleted'
            ORDER BY s.semester DESC
        ");
            $stmtSem->execute([$teacher_id]);
            $semesters = $stmtSem->fetchAll(PDO::FETCH_COLUMN);


            // 3️⃣ ดึงประกาศ
            $sql = "SELECT r.id, r.title, r.status, r.created_at, r.description, r.subject_id, 
                       s.code AS subject_code,s.revisioncode, s.name AS subject_name, s.semester,
                       rd.quota AS total_quota, rd.grade_requirement,
                       (SELECT GROUP_CONCAT(CONCAT(name, '|', schedule_time, '|', quota) SEPARATOR ';;') 
                        FROM sections 
                        WHERE subject_id = r.subject_id) AS sections_data
                FROM recruitments r
                JOIN subjects s ON r.subject_id = s.id
                LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
                WHERE r.teacher_id = ?
                  AND r.status != 'deleted'";

            $params = [$teacher_id];

            if (!empty($search)) {
                $sql .= " AND (r.title LIKE ? OR s.code LIKE ? OR s.name LIKE ?)";
                $searchParam = "%$search%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            }

            if (!empty($semester_filter) && $semester_filter != 'all') {
                $sql .= " AND s.semester = ?";
                $params[] = $semester_filter;
            }

            $sql .= " ORDER BY r.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require __DIR__ . '/../../views/teacher/my_recruitments.php';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // 10. สลับสถานะ เปิด/ปิด รับสมัคร (AJAX)
    public function toggleStatus()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        header('Content-Type: application/json'); // บอก Browser ว่าจะส่งกลับเป็น JSON

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'];
                $status = $_POST['status']; // 'open' หรือ 'closed'
                $teacher_id = $_SESSION['user_id'];

                // ตรวจสอบว่าเป็นเจ้าของประกาศไหม
                $check = $pdo->prepare("SELECT id FROM recruitments WHERE id = ? AND teacher_id = ?");
                $check->execute([$id, $teacher_id]);

                if ($check->rowCount() > 0) {
                    $sql = "UPDATE recruitments SET status = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$status, $id]);

                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }
    }

    public function exportMyTasPDF($getAcad)
{
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../Config/Database.php';

    $db = (new Database())->connect();

    $teacher_id = $_SESSION['user_id'];
    $teacher_name = (string)$_SESSION['name'];

    $subject_code = (string)$_GET['subject_code'];
    $subject_name = (string)$_GET['subject_name'];
    $section_id = (int)$_GET['section_id'];

    $months = $_GET['month'] ?? [];
    $years  = $_GET['year'] ?? [];
    $dates  = $_GET['dates'] ?? [];

    // =========================
    // ✅ VALIDATE INPUT
    // =========================
    $hasEmptyField = false;

    if (empty($_GET['section_id']) || empty($months)) {
        $hasEmptyField = true;
    } else {
        for ($i = 0; $i < count($months); $i++) {
            if (
                trim($months[$i] ?? '') === '' ||
                trim($years[$i] ?? '') === '' ||
                trim($dates[$i] ?? '') === ''
            ) {
                $hasEmptyField = true;
                break;
            }
        }
    }

    if ($hasEmptyField) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'>
        <style>*{font-family:'Prompt',sans-serif;}</style></head><body>
        <script>
            Swal.fire({
                title: 'ข้อมูลไม่ครบถ้วน',
                text: 'กรุณาเลือก Section และกรอกข้อมูลให้ครบทุกช่อง',
                icon: 'warning'
            }).then(() => { window.close(); });
        </script></body></html>";
        exit;
    }

    $enrollacadyear = $getAcad['year'];
    $enrollsemester = $getAcad['semester'];

    // =========================
    // QUERY
    // =========================
    $sql = "
        SELECT 
            sec.id AS section_id,
            sec.schedule_time,
            sec.name AS section_type,
            u.student_id,
            u.name AS student_name
        FROM applications a
        JOIN users u ON a.student_id = u.id
        JOIN recruitments r ON a.recruitment_id = r.id
        JOIN subjects s ON r.subject_id = s.id
        JOIN sections sec ON a.selected_section_id = sec.id
        WHERE r.teacher_id = ?
        AND s.code = ?
        AND sec.id = ?
        AND a.status = 'approved'
        ORDER BY u.student_id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$teacher_id, $subject_code, $section_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>
        <script>
            Swal.fire({
                title: 'ไม่สามารถออกเอกสารได้',
                text: 'Section นี้ยังไม่มี TA ที่อนุมัติ',
                icon: 'warning'
            }).then(() => { window.close(); });
        </script></body></html>";
        exit;
    }

    date_default_timezone_set('Asia/Bangkok');

    // =========================
    // mPDF CONFIG
    // =========================
    $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs = $defaultConfig['fontDir'];

    $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData = $defaultFontConfig['fontdata'];

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'fontDir' => array_merge($fontDirs, [__DIR__ . '/../../public/fonts']),
        'fontdata' => $fontData + [
            'sarabun' => ['R' => 'THSarabunNew.ttf']
        ],
        'default_font' => 'sarabun'
    ]);

    // =========================
    // TEMPLATE
    // =========================
    $pagecount = $mpdf->SetSourceFile(__DIR__ . '/../../public/template/form.pdf');
    $templateId = $mpdf->ImportPage(1);

    $assistant_type = $_GET['assistant_type'] ?? 'LAB';

    $mpdf->AddPage();
    $mpdf->UseTemplate($templateId);

    $count_ta = count($rows);

    // =========================
    // HEADER
    // =========================
    $x1 = ($assistant_type === 'TA') ? 29.5 : 79;

    $x2 = 0;
    if ($enrollsemester == 1) $x2 = 114.5;
    else if ($enrollsemester == 2) $x2 = 126.5;
    else if ($enrollsemester == 3) $x2 = 142.5;

    $mpdf->Image(__DIR__ . '/../../public/check.png', $x2, 45.5, 3.5);
    $mpdf->Image(__DIR__ . '/../../public/check.png', $x1, 67, 3.5);

    $mpdf->SetFont('sarabun', '', 16);

    $mpdf->SetXY(160, 64);
    $mpdf->WriteCell(0, 10, strval($count_ta));

    $mpdf->SetXY(55, 50);
    $mpdf->WriteCell(0, 10, strval($subject_name));

    $mpdf->SetXY(55, 43);
    $mpdf->WriteCell(0, 10, strval($subject_code));

    $mpdf->SetXY(189, 42.5);
    $mpdf->WriteCell(0, 10, strval($enrollacadyear));

    $mpdf->SetXY(55, 57.5);
    $mpdf->WriteCell(0, 10, strval($teacher_name));


    // =========================
    // รายชื่อ TA
    // =========================
    $y = 87;
    foreach ($rows as $ta) {
        $mpdf->SetXY(40, $y);
        $mpdf->WriteCell(30, 8, strval($ta['student_id']));

        $mpdf->SetXY(70, $y);
        $mpdf->WriteCell(80, 8, strval($ta['student_name']));

        $y += 7.5;
    }

    // =========================
    // SCHEDULE
    // =========================
    $schedule = $rows[0]['schedule_time'] ?? '';
    $scheduleData = $this->parseSchedule($schedule);

    $mpdf->SetXY(43, 174);
    $mpdf->WriteCell(0, 10, strval($scheduleData['day']));

    $mpdf->SetXY(80, 174);
    $mpdf->WriteCell(0, 10, strval($scheduleData['start']));

    $mpdf->SetXY(109, 174);
    $mpdf->WriteCell(0, 10, strval($scheduleData['end']));

    $mpdf->SetXY(163, 174);
    $mpdf->WriteCell(0, 10, strval($scheduleData['hours']));

    // =========================
    // WORK LOG (🔥 คำนวณ count จาก dates)
    // =========================
    $startY = 202.5;
    $total = 0;

    for ($i = 0; $i < count($months); $i++) {

        if (empty($months[$i])) continue;

        $month = $months[$i];
        $year  = $years[$i];
        $date  = $dates[$i];

        // 🔥 แปลง dates → count
        $dateArray = array_filter(array_map(function ($d) {
            $d = trim($d);
            return (is_numeric($d) && $d >= 1 && $d <= 31) ? $d : null;
        }, explode(',', $date)));

        $count = count($dateArray);

        $total += $count;

        $y = $startY + ($i * 7.2);

        $mpdf->SetXY(42, $y);
        $mpdf->WriteCell(0, 10, strval($month));

        $mpdf->SetXY(68, $y);
        $mpdf->WriteCell(0, 10, strval($year));

        $mpdf->SetXY(96.5, $y);
        $mpdf->WriteCell(0, 10, strval($count));

        $mpdf->SetXY(142, $y);
        $mpdf->WriteCell(0, 10, strval($date));
    }

    // =========================
    // SUMMARY
    // =========================
    $y3 = 245;

    $mpdf->SetXY(42.5, $y3);
    $mpdf->WriteCell(0, 10, strval($total));

    $mpdf->SetXY(67.5, $y3);
    $mpdf->WriteCell(0, 10, strval($count_ta));

    $mpdf->SetXY(93.5, $y3);
    $mpdf->WriteCell(0, 10, strval($scheduleData['hours']));

    $cost = 50;

    $mpdf->SetXY(123, $y3);
    $mpdf->WriteCell(0, 10, strval($cost));

    $result = $total * $count_ta * $cost * $scheduleData['hours'];

    $mpdf->SetXY(172, $y3);
    $mpdf->WriteCell(0, 10, strval($result));

    $mpdf->Output('TA_Report.pdf', 'I');
}

    public function parseSchedule($schedule)
    {
        // ✅ ปรับ Regex ใหม่ให้ยืดหยุ่นขึ้น
        // รองรับทั้ง : และ . และการเว้นวรรคตรงเครื่องหมาย - 
        // เช่น "พุธ 10:20-12:05", "จันทร์ 10.00 - 12.00", "อังคาร 8:30-10.30"
        if (preg_match('/([^\s]+)\s+(\d{1,2})[:.](\d{2})\s*-\s*(\d{1,2})[:.](\d{2})/u', (string)$schedule, $match)) {
            $day = $match[1];
            $startH = (int)$match[2];
            $startM = (int)$match[3];
            $endH = (int)$match[4];
            $endM = (int)$match[5];

            $startTime = sprintf("%02d.%02d", $startH, $startM);
            $endTime = sprintf("%02d.%02d", $endH, $endM);

            $start = $startH + ($startM / 60);
            $end = $endH + ($endM / 60);

            // ปัดเป็นชั่วโมงเต็ม
            $hours = round($end - $start);

            return [
                'day' => $day,
                'start' => $startTime,
                'end' => $endTime,
                'hours' => strval($hours)
            ];
        }

        // 🌟 ถ้าข้อมูลไม่ตรงรูปแบบจริงๆ ค่อยคืนค่า Default
        return [
            'day' => '-',
            'start' => '-',
            'end' => '-',
            'hours' => '0'
        ];
    }
}
