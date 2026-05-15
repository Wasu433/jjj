<?php
// เริ่ม Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เปิด Error Reporting (สำหรับ Dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เรียกใช้ AuthController (Controller อื่นๆ จะถูกเรียกในแต่ละ case)
require_once '../app/Controllers/AuthController.php';

// รับค่า page จาก URL (ถ้าไม่มีให้ไป login)
$page = $_GET['page'] ?? 'login';

// สร้าง Object Controller หลัก
$authController = new AuthController();

// ตรวจสอบเส้นทาง (Routing)
switch ($page) {
    
    // ====================================================
    // 1. AUTHENTICATION (Login, Register, Logout)
    // ====================================================
    case 'login':
        $authController->index();
        break;
    case 'login_process':
        $authController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'register':
        require_once '../views/auth/register.php';
        break;
    case 'register_process':
        $authController->register();
        break;
    case 'change_password':
        require_once '../views/auth/change_password.php';
        break;
    case 'change_password_process':
        $authController->changePasswordProcess();
        break;


    // ====================================================
    // 2. ADMIN ZONE
    // ====================================================
    case 'admin_dashboard':
        require_once '../views/admin/admin_dashboard.php';
        break;
        
    // --- จัดการผู้ใช้ ---
    case 'manage_users': 
        require_once '../views/admin/manage_users.php';
        break;
    case 'add_user_process':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->addUser();
        break;    
    case 'edit_user_process':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->editUser();
        break;
    case 'delete_user':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->deleteUser();
        break;

    // --- จัดการรายวิชา ---
    case 'manage_subjects': 
         require_once '../views/admin/manage_subjects.php';
        break;
    case 'add_subject_process': 
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->addSubject();
        break;    
    case 'edit_subject_process': 
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->editSubject();
        break;
    case 'delete_subject': 
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->deleteSubject();
        break;

    // --- จัดการประกาศ ---
    case 'manage_recruitments': 
        require_once '../views/admin/manage_recruitments.php';
        break;
    case 'add_recruitment_process':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->addRecruitment();
        break;
    case 'edit_recruitment_process':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->editRecruitment();
        break;
    case 'delete_recruitment':
        require_once '../app/Controllers/AdminController.php';
        $adminController = new AdminController();
        $adminController->deleteRecruitment();
        break;

    case 'reports':
        require_once '../views/admin/reports.php';
        break;


    // ====================================================
    // 3. TEACHER ZONE
    // ====================================================
    case 'teacher_dashboard':
        require_once '../views/teacher/teacher_dashboard.php';
        break;

    case 'my_recruitments':
        require_once '../app/Controllers/TeacherController.php'; // เรียกผ่าน Controller
        $teacherController = new TeacherController();
        $teacherController->myRecruitments(); 
        break;

    // --- สร้าง/แก้ไข/ลบ ประกาศ (ของอาจารย์) ---
    case 'teacher_create_recruitment':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->createRecruitment();
        break;  

    case 'teacher_edit_recruitment':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->editRecruitment();
        break;

    case 'teacher_delete_recruitment':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->deleteRecruitment();
        break;    

    // ใน index.php ตรงส่วน switch case
case 'teacher_toggle_status':
    require_once 'app/Controllers/TeacherController.php';
    $controller = new TeacherController();
    $controller->toggleStatus();
    break;
        
    // --- ดูผู้สมัคร ---
    case 'view_applicants': 
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->viewApplicants();
        break;
        
    // 🌟 --- ดูผู้สมัคร (หน้าใหม่ เจาะจงเฉพาะรายวิชา) ---
    case 'teacher_view_applicants': 
        // เรียกไฟล์ View ใหม่ที่เราเพิ่งสร้าง
        require_once '../views/teacher/recruitment_applicants.php';
        break;
        
    // 🌟 --- อัปเดตสถานะ รับเข้า/ปฏิเสธ (ของหน้าใหม่) ---
    case 'teacher_update_application':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->updateApplicationStatus(); // เรียกฟังก์ชันใหม่
        break;    

    // --- อัปเดตสถานะ (รับ/ไม่รับ) ---
    case 'teacher_update_status':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->updateStatus();
        break;

    case 'my_tas':
        require_once '../app/Controllers/TeacherController.php';
        $teacherController = new TeacherController();
        $teacherController->myTas();
        break;


    // ====================================================
    // 4. STUDENT ZONE
    // ====================================================
    case 'student_dashboard':
        require_once '../app/Controllers/StudentController.php';
        require_once '../app/Controllers/SubjectController.php';
        $studentController = new StudentController();
         $subjectController = new SubjectController();
        $acadList = $subjectController->getAcad($subjectController->getToken());
        $studentController->index($acadList); // เรียกฟังก์ชัน index() เพื่อดึงข้อมูล

        break;

    // 🔥🔥 เพิ่มส่วนนี้เข้าไปครับ (เพื่อให้หน้าประกาศนักศึกษาทำงานได้) 🔥🔥
    case 'student_recruitment_list': 
        require_once '../app/Controllers/StudentController.php';
        require_once '../app/Controllers/SubjectController.php';
        $studentController = new StudentController();
        $subjectController = new SubjectController();
        $acadList = $subjectController->getAcad($subjectController->getToken());
        $studentController->recruitmentList($acadList);
        break;

    case 'student_apply_process':
        require_once '../app/Controllers/StudentController.php';
        $studentController = new StudentController();
        $studentController->apply();
        break;
    // 🔥🔥 จบส่วนที่เพิ่ม 🔥🔥

    case 'recruitment_list': // (อันเก่า เผื่อลิงก์ค้าง)
        header("Location: index.php?page=student_recruitment_list");
        exit;
        break;

    case 'my_applications':
        require_once '../app/Controllers/StudentController.php';
        $studentController = new StudentController();
        $studentController->myApplications(); // เรียกฟังก์ชันใหม่
        break;

    case 'student_cancel_application': // 🔥 เพิ่ม Route นี้
        require_once '../app/Controllers/StudentController.php';
        $studentController = new StudentController();
        $studentController->cancelApplication();
        break;

    case 'profile':
        require_once '../app/Controllers/StudentController.php';
        $studentController = new StudentController();
        $studentController->profile();
        break;

    case 'student_update_profile': // 🔥 Route สำหรับกดบันทึก
        require_once '../app/Controllers/StudentController.php';
        $studentController = new StudentController();
        $studentController->updateProfile();
        break;


    // ====================================================
    // 5. GENERAL & ERROR HANDLING
    // ====================================================
    case 'home':
        require_once '../views/home.php';
        break;
        
    case 'api':
        require_once '../app/api/subject/subject.php';
        break;    

    case 'api_subject_detail':
        require_once '../app/api/subject/detail.php'; 
        break; 
        
    case 'api_subject_dbcourse':
        require_once '../app/api/subject/dbcourse.php';
        break;

    case 'sync_subjects':
    require_once '../app/Controllers/SubjectController.php';
    $subjectController = new SubjectController();

    $result = $subjectController->syncSubjects();

    case 'export_report_pdf':
    require_once '../app/Controllers/ReportController.php';
    $controller = new ReportController();
    $controller->exportPDF();
    break;

    case 'export_my_tas_pdf':
    require_once '../app/Controllers/SubjectController.php';    
    require_once '../app/Controllers/TeacherController.php';
    $teacherController = new TeacherController();
    $subjectController = new SubjectController();
    $getAcad = $subjectController->getAcad($subjectController->getToken());
    $teacherController->exportMyTasPDF($getAcad);
    break;
    
    case 'acad_list':
    require_once '../app/Controllers/SubjectController.php';
    $subjectController = new SubjectController();
    $acadList = $subjectController->getAcad($subjectController->getToken());
    echo "<pre>";
    print_r($acadList);
    echo "</pre>";
    break;

?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: '<?= $result["status"] === "success" ? "success" : "error" ?>',
                title: '<?= $result["status"] === "success" ? "สำเร็จ!" : "เกิดข้อผิดพลาด!" ?>',
                text: '<?= $result["message"] ?>',
                confirmButtonText: 'ตกลง'
            }).then(() => {
                window.location.href = 'index.php?page=manage_subjects';
            });
        </script>
    </body>
    </html>
<?php
    break;

    case 'template':
        require_once '../views/teacher/template.php';
        break;
            
    default:
        http_response_code(404);
        echo "<div style='text-align:center; margin-top:50px; font-family: sans-serif;'>";
        echo "<h1 style='font-size: 40px; color: #4f46e5;'>404 Page Not Found</h1>";
        echo "<p style='color: #666;'>ไม่พบหน้าที่คุณต้องการในระบบ (Page: " . htmlspecialchars($page) . ")</p>";
        echo "<br><a href='index.php' style='text-decoration: none; color: white; background: #4f46e5; padding: 10px 20px; border-radius: 8px;'>กลับหน้าหลัก</a>";
        echo "</div>";
        break;
}
?>