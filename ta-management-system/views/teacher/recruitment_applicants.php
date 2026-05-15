<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . '/../../app/Config/Database.php';

// รับค่า ID ของประกาศ
if (!isset($_GET['id'])) {
    header("Location: index.php?page=my_recruitments");
    exit;
}

$recruitment_id = $_GET['id'];
$teacher_id = $_SESSION['user_id'];

// 1. ดึงข้อมูลประกาศและวิชา
$stmtJob = $pdo->prepare("
    SELECT r.title, s.code, s.name, rd.quota 
    FROM recruitments r
    JOIN subjects s ON r.subject_id = s.id
    LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
    WHERE r.id = ? AND r.teacher_id = ?
");
$stmtJob->execute([$recruitment_id, $teacher_id]);
$job = $stmtJob->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "ไม่พบข้อมูล หรือคุณไม่มีสิทธิ์เข้าถึงหน้านี้"; 
    exit;
}

// 2. ดึงรายชื่อเด็กที่สมัครวิชานี้
$stmtApp = $pdo->prepare("
    SELECT a.*, u.student_id, u.name as student_name, sec.name as section_name, sec.schedule_time
    FROM applications a
    JOIN users u ON a.student_id = u.id
    JOIN sections sec ON a.selected_section_id = sec.id
    WHERE a.recruitment_id = ?
    ORDER BY a.created_at DESC
");
$stmtApp->execute([$recruitment_id]);
$applicants = $stmtApp->fetchAll(PDO::FETCH_ASSOC);

// 3. นับจำนวนที่รับไปแล้ว
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE recruitment_id = ? AND status = 'approved'");
$stmtCount->execute([$recruitment_id]);
$approved_count = $stmtCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิจารณาผู้สมัคร | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'Prompt', sans-serif; background-color: #f9fafb; }</style>
</head>
<body class="text-gray-800">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-6">
            <a href="index.php?page=my_recruitments" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> กลับหน้ารายการประกาศ
            </a>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl shadow-inner">
                    <i class="fas fa-book-reader"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($job['code'] . ' ' . $job['name']); ?></h1>
                    <p class="text-gray-500 text-sm mt-1">หัวข้อประกาศ: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($job['title']); ?></span></p>
                </div>
            </div>
            
            <div class="bg-gray-50 px-6 py-3 rounded-xl border border-gray-200 text-center md:text-right min-w-[200px]">
                <p class="text-xs text-gray-500 font-medium mb-1 uppercase tracking-wide">สถานะการรับเข้าทำงาน</p>
                <div class="text-2xl font-bold <?php echo ($approved_count >= $job['quota'] && $job['quota'] > 0) ? 'text-red-500' : 'text-indigo-600'; ?>">
                    <?php echo $approved_count; ?> <span class="text-lg text-gray-400 font-normal">/ <?php echo $job['quota'] > 0 ? $job['quota'] : '-'; ?> คน</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-white flex justify-between items-center">
                <h2 class="font-bold text-gray-800 text-lg"><i class="fas fa-users mr-2 text-indigo-500"></i>รายชื่อผู้สมัครในประกาศนี้</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold">
                            <th class="py-4 px-6">นักศึกษา</th>
                            <th class="py-4 px-6">กลุ่มเรียน (Section)</th>
                            <th class="py-4 px-6 text-center">เกรดที่ได้</th>
                            <th class="py-4 px-6 text-center">ไฟล์เกรด</th>
                            <th class="py-4 px-6 text-center">สถานะ</th>
                            <th class="py-4 px-6 text-right">การพิจารณา</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($applicants)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-user-slash text-4xl text-gray-200 mb-3"></i>
                                        <p>ยังไม่มีนักศึกษาสมัครเข้ามาในประกาศนี้</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($app['student_name']); ?></div>
                                        <div class="text-gray-500 text-xs mt-1"><i class="fas fa-id-card mr-1"></i><?php echo htmlspecialchars($app['student_id']); ?></div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-block bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-bold border border-indigo-100 mb-1">
                                            <?php echo htmlspecialchars($app['section_name']); ?>
                                        </span>
                                        <div class="text-gray-500 text-xs"><i class="far fa-clock mr-1"></i><?php echo htmlspecialchars($app['schedule_time']); ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-center font-bold text-gray-700 text-base">
                                        <?php echo htmlspecialchars($app['grade'] ?? '-'); ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if (!empty($app['grade_file'])): ?>
                                            <a href="<?php echo htmlspecialchars($app['grade_file']); ?>" target="_blank" class="inline-flex items-center justify-center bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors text-xs font-medium">
                                                <i class="fas fa-file-image mr-1.5"></i>ดูไฟล์
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-300 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php 
                                            if($app['status'] == 'pending') echo '<span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">รอพิจารณา</span>';
                                            elseif($app['status'] == 'approved') echo '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">อนุมัติแล้ว</span>';
                                            else echo '<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">ปฏิเสธ</span>';
                                        ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <?php if($app['status'] == 'pending'): ?>
                                            <div class="flex justify-end gap-2">
                                                <button onclick="updateStatus(<?php echo $app['id']; ?>, 'approved', '<?php echo htmlspecialchars($app['student_name']); ?>')" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-xl text-xs font-bold shadow-sm transition-all active:scale-95">
                                                    <i class="fas fa-check mr-1"></i>รับ
                                                </button>
                                                <button onclick="updateStatus(<?php echo $app['id']; ?>, 'rejected', '<?php echo htmlspecialchars($app['student_name']); ?>')" class="bg-white border border-gray-200 text-red-500 hover:bg-red-50 hover:border-red-200 px-3 py-2 rounded-xl text-xs font-bold shadow-sm transition-all active:scale-95">
                                                    ปฏิเสธ
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button onclick="updateStatus(<?php echo $app['id']; ?>, 'pending', '<?php echo htmlspecialchars($app['student_name']); ?>')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-2 rounded-xl text-xs font-bold transition-colors">
                                                <i class="fas fa-undo mr-1"></i> ยกเลิกสถานะ
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(app_id, status, studentName) {
            let actionText = status === 'approved' ? 'รับเข้าทำงาน' : (status === 'rejected' ? 'ปฏิเสธ' : 'ยกเลิกสถานะกลับเป็นรอพิจารณา');
            let confirmColor = status === 'approved' ? '#22c55e' : (status === 'rejected' ? '#ef4444' : '#6b7280');
            
            // เช็คโควตาก่อน (ถ้าจะรับเข้า และโควตาเต็มแล้ว แจ้งเตือน)
            let currentApproved = <?php echo $approved_count; ?>;
            let quota = <?php echo $job['quota'] ?: 0; ?>;
            
            if (status === 'approved' && quota > 0 && currentApproved >= quota) {
                Swal.fire({
                    title: 'โควตาเต็มแล้ว!',
                    text: 'คุณรับผู้ช่วยสอนครบตามจำนวนที่กำหนดไว้แล้ว ไม่สามารถรับเพิ่มได้',
                    icon: 'error',
                    confirmButtonColor: '#4f46e5'
                });
                return; // หยุดการทำงาน
            }

            Swal.fire({
                title: `ยืนยันการ${actionText}?`,
                text: `คุณกำลังจะ${actionText} "${studentName}"`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#e5e7eb',
                confirmButtonText: `ใช่, ${actionText}`,
                cancelButtonText: '<span class="text-gray-700">ยกเลิก</span>'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    // ส่งไปอัปเดตที่ Controller
                    window.location.href = `index.php?page=teacher_update_application&id=${app_id}&status=${status}&recruitment_id=<?php echo $recruitment_id; ?>`;
                }
            });
        }
    </script>
</body>
</html>