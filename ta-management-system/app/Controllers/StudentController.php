<?php
class StudentController
{

    private function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
            header("Location: index.php?page=login");
            exit;
        }
    }

    // 1. หน้า Dashboard (แก้ไข SQL ให้ดึงจำนวนคนสมัครและโควตาของ Sec มาด้วย ✅)
    public function index($acadList)
{
    $this->checkAuth();
    require __DIR__ . '/../Config/Database.php';

    $student_id = $_SESSION['user_id'];
    $student_recru_id = $_SESSION['student_id'];

    // 🔥 ปีนักศึกษา
    $stu_year = $this->getStudentYear($student_recru_id, $acadList);

    $stats = ['eligible' => 0, 'applied' => 0, 'accepted' => 0];

    try {
        $stmtUser = $pdo->prepare("SELECT name, student_id FROM users WHERE id = ?");
        $stmtUser->execute([$student_id]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $is_profile_complete = !empty($currentUser['name']) && !empty($currentUser['student_id']);

        // =========================
        // 1.1 สถิติ (🔥 เพิ่ม filter ปี)
        // =========================
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM recruitments r
            JOIN subjects s ON r.subject_id = s.id
            WHERE r.status = 'open'
            AND SUBSTRING(s.code, 4, 1) <= ?
        ");
        $stmt->execute([$stu_year]);
        $stats['eligible'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT recruitment_id) FROM applications WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $stats['applied'] = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status = 'approved'");
        $stmt->execute([$student_id]);
        $stats['accepted'] = $stmt->fetchColumn();

        // =========================
        // 1.2 ประกาศล่าสุด (🔥 filter ปี)
        // =========================
        $sql = "SELECT r.*, 
                       s.code AS subject_code, s.name AS subject_name,
                       u.name AS teacher_name,
                       rd.quota, rd.grade_requirement,
                       (SELECT COUNT(*) FROM applications a 
                        WHERE a.recruitment_id = r.id AND a.student_id = ?) as is_applied,

                       (SELECT GROUP_CONCAT(
                           CONCAT(
                               sec.id, '|', 
                               sec.name, '|', 
                               sec.schedule_time, '|', 
                               sec.quota, '|', 
                               (SELECT COUNT(*) FROM applications app 
                                WHERE app.selected_section_id = sec.id 
                                AND app.status = 'approved')
                           ) SEPARATOR ';;'
                       ) FROM sections sec WHERE sec.subject_id = r.subject_id) AS sections_data,
                       
                       (SELECT GROUP_CONCAT(selected_section_id) 
                        FROM applications 
                        WHERE recruitment_id = r.id AND student_id = ?) AS applied_section_ids

                FROM recruitments r
                JOIN subjects s ON r.subject_id = s.id
                JOIN users u ON r.teacher_id = u.id
                LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
                WHERE r.status = 'open'

                -- 🔥 ใช้ logic ปี
                AND SUBSTRING(s.code, 4, 1) <= ?

                ORDER BY r.created_at DESC 
                LIMIT 5";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $student_id, $stu_year]);
        $latest_recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // =========================
        // 1.3 ประวัติ (เหมือนเดิม)
        // =========================
        $sqlHistory = "SELECT a.status, a.created_at, r.title, s.name
                       FROM applications a 
                       JOIN recruitments r ON a.recruitment_id = r.id 
                       JOIN subjects s ON r.subject_id = s.id
                       WHERE a.student_id = ? 
                       ORDER BY a.created_at DESC 
                       LIMIT 5";

        $stmtHist = $pdo->prepare($sqlHistory);
        $stmtHist->execute([$student_id]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../../views/student/student_dashboard.php';

    } catch (PDOException $e) {
        echo "Error Dashboard: " . $e->getMessage();
    }
}

    // 2. รายการประกาศทั้งหมด (แก้ไข SQL เหมือน Dashboard ✅)
    public function recruitmentList($acadList)
{
    $this->checkAuth();
    require __DIR__ . '/../Config/Database.php';

    $student_id = $_SESSION['user_id'];
    $search = $_GET['search'] ?? '';
    $student_recru_id = $_SESSION['student_id'];
    
    $stu_year = $this->getStudentYear($student_recru_id, $acadList);

    try {
        $stmtUser = $pdo->prepare("SELECT name, student_id FROM users WHERE id = ?");
        $stmtUser->execute([$student_id]);
        $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $is_profile_complete = !empty($currentUser['name']) && !empty($currentUser['student_id']);

        $sql = "SELECT r.*, 
                       s.code AS subject_code, s.name AS subject_name,
                       u.name AS teacher_name,
                       rd.quota, rd.grade_requirement,
                       (SELECT COUNT(*) FROM applications a 
                        WHERE a.recruitment_id = r.id AND a.student_id = ?) as is_applied,

                       (SELECT GROUP_CONCAT(
                           CONCAT(
                               sec.id, '|', 
                               sec.name, '|', 
                               sec.schedule_time, '|', 
                               sec.quota, '|', 
                               (SELECT COUNT(*) FROM applications app 
                                WHERE app.selected_section_id = sec.id 
                                AND app.status = 'approved')
                           ) SEPARATOR ';;'
                       ) FROM sections sec WHERE sec.subject_id = r.subject_id) AS sections_data,

                       (SELECT GROUP_CONCAT(selected_section_id) 
                        FROM applications 
                        WHERE recruitment_id = r.id AND student_id = ?) AS applied_section_ids

                FROM recruitments r
                JOIN subjects s ON r.subject_id = s.id
                JOIN users u ON r.teacher_id = u.id
                LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
                WHERE r.status = 'open'

                -- 🔥 เห็นตั้งแต่ปี 1 ถึงปีตัวเอง
                AND SUBSTRING(s.code, 4, 1) <= ?";

        $params = [$student_id, $student_id, $stu_year];

        if (!empty($search)) {
            $sql .= " AND (s.name LIKE ? OR s.code LIKE ? OR r.title LIKE ?)";
            array_push($params, "%$search%", "%$search%", "%$search%");
        }

        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../../views/student/recruitment_list.php';

    } catch (PDOException $e) {
        echo "Error List: " . $e->getMessage();
    }
}

 public function getStudentYear($student_id, $acadList) {
        // 1. เช็คก่อนว่ามีรหัสนักศึกษาถูกส่งมาไหม ถ้าเป็นค่าว่าง (null) ให้ตีเป็นปี 1 ไว้ก่อน
        if (empty($student_id)) {
            return 1; 
        }

        // 2. เติม (string) ด้านหน้าตัวแปร เพื่อแก้บั๊กแจ้งเตือน Deprecated
        $year_prefix = substr((string)$student_id, 0, 2);

        // 3. แปลงเป็นปีเข้า
        $admit_year = 2500 + intval($year_prefix);

        // 4. คำนวณชั้นปี
        $year = ($acadList['year'] - $admit_year) + 1;

        // 5. กันค่าเกิน เช่น ปี 5+
        if ($year > 4) $year = 4;
        if ($year < 1) $year = 1;

        return $year;
    }
    

    // 3. ฟังก์ชันสมัครงาน (คงเดิมตามที่คุณให้มา ✅)
    public function apply()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $recruitment_id = $_POST['recruitment_id'];
                $student_id = $_SESSION['user_id'];
                $section_ids = $_POST['section_ids'] ?? [];
                $grade = $_POST['grade'] ?? null;

                if (empty($section_ids)) {
                    $this->showSweetAlertBack('แจ้งเตือน', 'กรุณาติ๊กเลือก Section อย่างน้อย 1 กลุ่ม', 'warning');
                    exit;
                }

                $grade_file_path = null;
                if (!empty($_FILES['grade_file']['name'])) {
                    $ext = pathinfo($_FILES['grade_file']['name'], PATHINFO_EXTENSION);
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                    if (!in_array(strtolower($ext), $allowed)) {
                        $this->showSweetAlertBack('ไฟล์ไม่ถูกต้อง', 'กรุณาอัปโหลดไฟล์ PDF หรือรูปภาพเท่านั้น', 'warning');
                        exit;
                    }

                    $filename = "grade_{$student_id}_" . time() . ".{$ext}";
                    $upload_dir = __DIR__ . '/../../public/uploads/grades/';

                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                    if (move_uploaded_file($_FILES['grade_file']['tmp_name'], $upload_dir . $filename)) {
                        $grade_file_path = "uploads/grades/" . $filename;
                    }
                }

                $success_count = 0;
                $already_count = 0;

                foreach ($section_ids as $sec_id) {
                    // เช็คซ้ำ
                    $check = $pdo->prepare("SELECT id FROM applications WHERE recruitment_id = ? AND student_id = ? AND selected_section_id = ?");
                    $check->execute([$recruitment_id, $student_id, $sec_id]);

                    if ($check->rowCount() == 0) {
                        // 🔥 เช็คโควตาอีกครั้งก่อนบันทึก (กันคนกดพร้อมกัน)
                        $checkQuota = $pdo->prepare("SELECT quota, (SELECT COUNT(*) FROM applications WHERE selected_section_id = ? AND status = 'approved') as enrolled FROM sections WHERE id = ?");
                        $checkQuota->execute([$sec_id, $sec_id]);
                        $secData = $checkQuota->fetch(PDO::FETCH_ASSOC);

                        if ($secData && $secData['enrolled'] >= $secData['quota']) {
                            // ถ้าเต็มแล้ว ข้ามไป
                            continue;
                        }

                        $sql = "INSERT INTO applications (recruitment_id, student_id, selected_section_id, status, grade_file, grade) 
                            VALUES (?, ?, ?, 'pending', ?, ?)";
                        $stmt = $pdo->prepare($sql);

                        $stmt->execute([
                            $recruitment_id,
                            $student_id,
                            $sec_id,
                            $grade_file_path,
                            $grade
                        ]);
                        $success_count++;
                    } else {
                        $already_count++;
                    }
                }

                if ($success_count > 0) {

                    try {
                        // 1. ดึงข้อมูลอาจารย์และชื่อวิชา จาก recruitment_id
                        $sql_info = "SELECT r.teacher_id, r.title, s.name AS subject_name 
                                 FROM recruitments r 
                                 JOIN subjects s ON r.subject_id = s.id 
                                 WHERE r.id = ?";
                        $stmt_info = $pdo->prepare($sql_info);
                        $stmt_info->execute([$recruitment_id]);
                        $recruitment_data = $stmt_info->fetch(PDO::FETCH_ASSOC);

                        // 2. ดึงชื่อนักศึกษา (ผู้สมัคร)
                        $stmt_std = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                        $stmt_std->execute([$student_id]);
                        $student_name = $stmt_std->fetchColumn();

                        // 3. เรียก Helper เพื่อยิงแจ้งเตือน
                        if ($recruitment_data && $student_name) {


                            $teacher_id = $recruitment_data['teacher_id'];
                            $subject = $recruitment_data['subject_name'];

                            $this->notificationCreate(
                                $teacher_id,
                                "นักศึกษา $student_name ได้สมัครประกาศ '{$recruitment_data['title']}' สำหรับวิชา $subject",
                                "index.php?page=view_applicants&subject_id=&status=&search=&recruitment_id=$recruitment_id"
                            );
                        }
                    } catch (Exception $e) {

                        // ถ้าแจ้งเตือนพัง ให้โปรแกรมทำงานต่อได้ (ไม่ต้อง throw error ใส่ user)
                    }
                    $msg = "สมัครสำเร็จ $success_count รายการ!";
                    if ($already_count > 0) $msg .= " (ซ้ำ $already_count รายการ)";
                    $this->showSweetAlertRedirect('บันทึกเรียบร้อย', $msg, 'success', 'index.php?page=student_recruitment_list');
                } else {
                    $this->showSweetAlertBack('แจ้งเตือน', 'คุณสมัคร Section ที่เลือกไปหมดแล้ว หรือ Section เต็ม', 'warning');
                }
            } catch (PDOException $e) {
                $this->showSweetAlertBack('เกิดข้อผิดพลาด', $e->getMessage(), 'error');
            }
        }
    }

    // 4. ยกเลิกการสมัคร
    public function cancelApplication()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';
        if (isset($_GET['id'])) {
            $app_id = $_GET['id'];
            $student_id = $_SESSION['user_id'];
            try {
                $check = $pdo->prepare("SELECT id FROM applications WHERE id = ? AND student_id = ? AND status = 'pending'");
                $check->execute([$app_id, $student_id]);
                if ($check->rowCount() > 0) {
                    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
                    $stmt->execute([$app_id]);
                    $this->showSweetAlertRedirect('ยกเลิกสำเร็จ', 'ลบใบสมัครเรียบร้อยแล้ว', 'success', 'index.php?page=my_applications');
                } else {
                    $this->showSweetAlertRedirect('ไม่สามารถยกเลิกได้', 'ใบสมัครนี้อาจถูกพิจารณาไปแล้ว', 'error', 'index.php?page=my_applications');
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        }
    }

    // 5. ประวัติการสมัคร
    public function myApplications()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';
        $student_id = $_SESSION['user_id'];
        try {
            $sql = "SELECT a.id, a.status, a.created_at,
                           r.title, s.code AS subject_code, s.name AS subject_name, u.name AS teacher_name,
                           sec.name AS section_name, sec.schedule_time
                    FROM applications a
                    JOIN recruitments r ON a.recruitment_id = r.id
                    JOIN subjects s ON r.subject_id = s.id
                    JOIN users u ON r.teacher_id = u.id
                    LEFT JOIN sections sec ON a.selected_section_id = sec.id 
                    WHERE a.student_id = ? ORDER BY a.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            require __DIR__ . '/../../views/student/my_applications.php';
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    // 6. Profile Functions
    public function profile()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        require __DIR__ . '/../../views/student/profile.php';
    }

    public function updateProfile()
    {
        $this->checkAuth();
        require __DIR__ . '/../Config/Database.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_SESSION['user_id'];
            $name = $_POST['name'];
            $student_id = $_POST['student_id'];
            // $skills = isset($_POST['skills']) ? implode(',', $_POST['skills']) : '';

            $resume_sql = "";
            $params = [$name, $student_id];
            if (!empty($_FILES['resume']['name'])) {
                $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
                $filename = "resume_{$user_id}_" . time() . ".{$ext}";
                $upload_dir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $filename)) {
                    $resume_sql = ", resume_path = ?";
                    $params[] = "uploads/" . $filename;
                }
            }
            $params[] = $user_id;

            $sql = "UPDATE users SET name = ?, student_id = ? $resume_sql WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $_SESSION['name'] = $name;
            $this->showSweetAlertRedirect('บันทึกสำเร็จ', 'อัปเดตข้อมูลเรียบร้อย', 'success', 'index.php?page=profile');
        }
    }

    // 1. สร้างแจ้งเตือนใหม่ (Insert)
    public  function notificationCreate($user_id, $message, $link = '#') {
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

    // 2. ดึงแจ้งเตือนของ user (Select)
    public  function getUnread($user_id) {
        $db = (new Database())->connect();
        // ดึงเฉพาะที่ยังไม่อ่าน หรือ ดึงล่าสุด 5-10 รายการก็ได้
        $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. นับจำนวนที่ยังไม่อ่าน (Count)
    public  function countUnread($user_id) {
        $db = (new Database())->connect();
        $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Helpers
    private function showSweetAlertRedirect($title, $text, $icon, $url)
    {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'><style>*{font-family:'Prompt',sans-serif;}</style></head><body><script>
            Swal.fire({ title: '$title', text: '$text', icon: '$icon', confirmButtonColor: '#4f46e5' }).then(() => { window.location.href = '$url'; });
        </script></body></html>";
        exit;
    }
    private function showSweetAlertBack($title, $text, $icon)
    {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'><style>*{font-family:'Prompt',sans-serif;}</style></head><body><script>
            Swal.fire({ title: '$title', text: '$text', icon: '$icon', confirmButtonColor: '#f59e0b' }).then(() => { window.history.back(); });
        </script></body></html>";
        exit;
    }
}