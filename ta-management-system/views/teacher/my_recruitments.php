<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/Config/Database.php';

// เช็คสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
// 1. ดึงรายวิชา (สำหรับ Dropdown ใน Modal)
$stmtSub = $pdo->query("
    SELECT id, code, revisioncode, name, course_id
    FROM subjects
    ORDER BY code ASC
");
$subject_options = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

// 2. ดึงรายการภาคการศึกษา (สำหรับ Dropdown Filter)
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

// 3. ดึงประกาศ (พร้อม Filter)
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

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

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recruitments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recruitments = [];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศรับสมัครของฉัน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif']
                    },
                    colors: {
                        primary: '#4f46e5'
                    }
                }
            }
        }
    </script>
    <style>
        .modal-enter {
            animation: fadeIn 0.2s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">ประกาศรับสมัครผู้ช่วยสอน</h1>
                    <p class="text-gray-500 text-sm">รายการประกาศรับสมัครผู้ช่วยสอนทั้งหมดของคุณ</p>
                </div>
            </div>
            <button onclick="openModal()" class="group flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95">
                <i class="fas fa-plus"></i> สร้างประกาศใหม่
            </button>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4">
                <input type="hidden" name="page" value="my_recruitments">
                
                <div class="w-full md:w-1/4 relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ภาคการศึกษา</label>
                    <select name="semester" onchange="this.form.submit()" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none text-gray-600 cursor-pointer">
                        <option value="all">ทั้งหมด</option>
                        <?php if (isset($semesters)): foreach ($semesters as $sem): ?>
                                <option value="<?php echo $sem; ?>" <?php echo (isset($_GET['semester']) && $_GET['semester'] == $sem) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sem); ?>
                                </option>
                        <?php endforeach;
                        endif; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <div class="flex-1 relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ค้นหา</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="รหัสวิชา หรือชื่อวิชา..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors">
                    <i class="fas fa-search absolute left-3 bottom-2.5 text-gray-400 text-sm"></i>
                </div>

                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm">ค้นหา</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                            <th class="py-4 px-6">รหัสวิชา</th>
                            <th class="py-4 px-6">ชื่อวิชา / หัวข้องาน</th>
                            <th class="py-4 px-6 text-center">ภาคการศึกษา</th>
                            <th class="py-4 px-6 text-center">จำนวนรับ</th>
                            <th class="py-4 px-6 text-center">สถานะ</th>
                            <th class="py-4 px-6 text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($recruitments)): ?>
                            <tr>
                                <td colspan="6" class="py-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center"><i class="fas fa-folder-open text-2xl text-gray-300 mb-2"></i>
                                        <p>ไม่พบรายการประกาศ</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recruitments as $row): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-indigo-600"><?php echo htmlspecialchars($row['subject_code']. ' - '. $row['revisioncode']); ?></td>
                                    <td class="py-4 px-6">
                                        <div class="flex flex-col"><span class="text-gray-900 font-medium"><?php echo htmlspecialchars($row['subject_name']); ?></span><span class="text-xs text-gray-500"><?php echo htmlspecialchars($row['title']); ?></span></div>
                                    </td>
                                    <td class="py-4 px-6 text-center text-gray-600"><?php echo htmlspecialchars($row['semester']); ?></td>
                                    <td class="py-4 px-6 text-center"><span class="bg-gray-100 px-2 py-1 rounded-md text-xs font-bold"><?php echo $row['total_quota']; ?></span></td>

                                    <td class="py-4 px-6 text-center">
                                        <?php if ($row['status'] == 'open'): ?>
                                            <span class="text-green-600 bg-green-100 px-2 py-1 rounded-full text-xs font-bold">เปิดรับสมัคร</span>
                                        <?php else: ?>
                                            <span class="text-red-600 bg-red-100 px-2 py-1 rounded-full text-xs font-bold">ปิดรับสมัคร</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="py-4 px-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="index.php?page=teacher_view_applicants&id=<?php echo $row['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100" title="ดูผู้สมัคร"><i class="fas fa-users"></i></a>

                                            <button type="button" onclick="openEditModal(this)"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-subject="<?php echo $row['subject_id']; ?>"
                                                data-desc="<?php echo htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-grade="<?php echo $row['grade_requirement']; ?>"
                                                data-status="<?php echo $row['status']; ?>"
                                                data-sections="<?php echo htmlspecialchars($row['sections_data'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-blue-600 hover:border-blue-200" title="แก้ไข">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>

                                            <button type="button" onclick="openDeleteModal('<?php echo $row['id']; ?>')" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-red-600 hover:border-red-200" title="ลบ">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full modal-enter">
                <div class="bg-white px-8 pt-8 pb-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">สร้างประกาศรับสมัครใหม่</h3>
                            <p class="text-sm text-gray-500">กรอกข้อมูลสำหรับรายวิชาของคุณ</p>
                        </div>
                    </div>
                    <form action="index.php?page=teacher_create_recruitment" method="POST" id="addRecruitmentForm" class="space-y-5">
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">เลือกรายวิชา <span class="text-red-500">*</span></label>
                            <select id="subjectSelect" name="subject_id" required
                                class="w-full px-3 py-2 border rounded-lg">
                                <option value="">-- เลือกวิชา --</option>

                                <?php foreach ($subject_options as $subj): ?>
                                    <option
                                        value="<?php echo $subj['id']; ?>"
                                        data-course-id="<?php echo $subj['course_id']; ?>">
                                        <?php echo $subj['code'] . " - " .$subj['revisioncode'] . " (" . $subj['name'] . ")"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <input type="text" name="course_id" value="">
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">หัวข้อประกาศ <span class="text-red-500">*</span></label><input type="text" name="title" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm" placeholder="เช่น รับสมัคร TA ช่วยสอน Lab"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">เกรดขั้นต่ำ</label><select name="grade_req" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm bg-white shadow-sm">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="A">A</option>
                                    <option value="B+">B+</option>
                                    <option value="B">B</option>
                                    <option value="C+">C+</option>
                                    <option value="C">C</option>
                                </select></div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">สถานะเริ่มต้น</label>
                                <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm bg-white shadow-sm">
                                    <option value="open" selected>เปิดรับสมัคร</option>
                                    <option value="closed">ปิดรับสมัคร</option>
                                </select>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="flex justify-between items-center mb-3">
                                <label class="block text-sm font-bold text-gray-700">ข้อมูลหมู่เรียน (Section) และจำนวนรับ</label>
                                <button type="button" onclick="addSectionRow('sections_container')" class="text-xs font-bold bg-white border border-indigo-200 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition-colors shadow-sm"><i class="fas fa-plus"></i> เพิ่มเซค</button>
                            </div>
                            <div id="sections_container" class="space-y-3">
                                <div class="flex gap-3 items-start section-row">
                                    <div class="w-1/3"><input type="text" name="sec_names[]" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="ชื่อเซค (เช่น 101)"></div>
                                    <div class="w-1/3"><input type="text" name="sec_times[]" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="วัน-เวลาเรียน"></div>
                                    <div class="w-1/4"><input type="number" name="sec_quotas[]" required min="1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="จำนวน"></div>
                                    <button type="button" disabled class="w-10 h-10 flex items-center justify-center bg-white border border-red-100 text-red-300 rounded-xl cursor-not-allowed"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-1">รายละเอียดงาน</label><textarea name="description" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm shadow-sm"></textarea></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-8 py-4 flex flex-row gap-3 justify-end border-t border-gray-100">
                    <button type="button"
                        onclick="submitFormWithAlert()"
                        class="px-6 py-2.5 bg-indigo-600 text-sm font-bold text-white rounded-xl hover:bg-indigo-700 order-first">
                        สร้างประกาศ
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-2.5 bg-white text-sm font-bold text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50 order-last">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl"><i class="fas fa-pen"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">แก้ไขประกาศ</h3>
                        </div>
                    </div>
                    <form action="index.php?page=teacher_edit_recruitment" method="POST" id="editRecruitmentForm" class="space-y-4">
                        <input type="hidden" name="id" id="edit_id">
                        <div><label class="block text-sm font-bold text-gray-700">วิชา</label><select name="subject_id" id="edit_subject_id" class="w-full px-3 py-2 border rounded-lg pointer-events-none">
                                <?php foreach ($subject_options as $subj): ?><option value="<?php echo $subj['id']; ?>"><?php echo $subj['code'] . " - " .$subj['revisioncode'] . " (" . $subj['name'] . ")"; ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-sm font-bold text-gray-700">หัวข้อ</label><input type="text" name="title" id="edit_title" class="w-full px-3 py-2 border rounded-lg"></div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">เกรด</label>
                                <select name="grade_req" id="edit_grade_req" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="A">A</option>
                                    <option value="B+">B+ ขึ้นไป</option>
                                    <option value="B">B ขึ้นไป</option>
                                    <option value="C+">C+ ขึ้นไป</option>
                                    <option value="C">C ขึ้นไป</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700">สถานะ</label>
                                <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="open">เปิดรับสมัคร</option>
                                    <option value="closed">ปิดรับสมัคร</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="flex justify-between items-center mb-3">
                                <label class="block text-sm font-bold text-gray-700">ข้อมูลหมู่เรียน (Section)</label>
                                <button type="button" onclick="addSectionRow('edit_sections_container')" class="text-xs font-bold bg-white border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors shadow-sm"><i class="fas fa-plus"></i> เพิ่มเซค</button>
                            </div>
                            <div id="edit_sections_container" class="space-y-2"></div>
                        </div>
                        <div><label class="block text-sm font-bold text-gray-700">รายละเอียด</label><textarea name="description" id="edit_description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button onclick="document.getElementById('editRecruitmentForm').submit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold">บันทึก</button>
                    <button onclick="closeEditModal()" class="bg-white border px-4 py-2 rounded-lg">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">ยืนยันลบประกาศ</h3>
                            <p class="text-sm text-gray-500 mt-2">ต้องการลบประกาศนี้ใช่หรือไม่?</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3 justify-end">
                    <a id="confirmDeleteBtn" href="#" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none transition-colors order-first">ลบข้อมูล</a>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors order-last">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let classList = [];
        document.getElementById('subjectSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            const subjectId = this.value; // subjects.id (FK)
            const courseId = selectedOption.dataset.courseId; // course_id (API)

            console.log('subject_id:', subjectId);
            console.log('course_id:', courseId);

            if (courseId) {
                fetchSubjects(courseId);
            }
        });

        const fetchSubjects = async (courseId) => {
            try {
                const res = await fetch(
                    `http://localhost:8082/index.php?page=api_subject_detail&course_id=${courseId}`
                );

                const result = await res.json();
                console.log('API response:', result);
                removeAllSectionRows('sections_container');
                classList = result.data?.[0]?.classtimetable || [];

                if (!Array.isArray(classList) || classList.length === 0) {
                    console.warn('No classtimetable found');
                    return;
                }

                // ลูปทุก section
                classList.forEach((item, index) => {
                    const name = item.studycodedes || '';
                    const time = `${item.weekcalllong || ''} ${item.timeslotfrom || ''}-${item.timeslotto || ''}`;
                    const quota = item.quota || '';

                    addSectionRow(
                        'sections_container',
                        name,
                        time,
                        quota
                    );


                    console.log(`Section ${index + 1}`, {
                        name,
                        time,
                        quota
                    });
                });

            } catch (err) {
                console.error('Fetch subjects failed:', err);
            }

        };

        function openModal() {
            document.getElementById('addRecruitmentModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('addRecruitmentModal').classList.add('hidden');
        }

        function closeEditModal() {
            document.getElementById('editRecruitmentModal').classList.add('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteRecruitmentModal').classList.add('hidden');
        }

        function addSectionRow(containerId, name = '', time = '', quota = '') {
            const container = document.getElementById(containerId);
            const newRow = document.createElement('div');
            newRow.className = 'flex gap-3 items-start section-row';
            newRow.innerHTML = `
                <div class="w-1/3"><input type="text" name="sec_names[]" value="${name}" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="ชื่อเซค"></div>
                <div class="w-1/3"><input type="text" name="sec_times[]" value="${time}" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="วัน-เวลา"></div>
                <div class="w-1/4"><input type="number" name="sec_quotas[]" value="${quota}" required min="1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm" placeholder="จำนวน"></div>
                <button type="button" onclick="this.parentElement.remove()" class="w-10 h-10 flex items-center justify-center bg-white border border-red-200 text-red-400 rounded-xl hover:bg-red-50 transition-colors shadow-sm"><i class="fas fa-trash-alt"></i></button>
            `;
            container.appendChild(newRow);
        }

        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_title').value = btn.getAttribute('data-title');
            document.getElementById('edit_subject_id').value = btn.getAttribute('data-subject');
            document.getElementById('edit_description').value = btn.getAttribute('data-desc');
            document.getElementById('edit_grade_req').value = btn.getAttribute('data-grade');

            // 🔥 เซ็ตค่าสถานะใน Dropdown
            document.getElementById('edit_status').value = btn.getAttribute('data-status');

            const container = document.getElementById('edit_sections_container');
            container.innerHTML = '';

            const sectionsStr = btn.getAttribute('data-sections');
            if (sectionsStr) {
                const sections = sectionsStr.split(';;');
                sections.forEach(sec => {
                    const parts = sec.split('|');
                    addSectionRow('edit_sections_container', parts[0] || '', parts[1] || '', parts[2] || '');
                });
            } else {
                addSectionRow('edit_sections_container');
            }
            document.getElementById('editRecruitmentModal').classList.remove('hidden');
        }

        function openDeleteModal(id) {
            document.getElementById('confirmDeleteBtn').href = 'index.php?page=teacher_delete_recruitment&id=' + id;
            document.getElementById('deleteRecruitmentModal').classList.remove('hidden');
        }

        function removeAllSectionRows(containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            const targetId = urlParams.get('id');

            if (urlParams.has('open_modal')) {
                openModal();
            } else if (action === 'edit' && targetId) {
                const editBtn = document.querySelector(`button[data-id='${targetId}'][title='แก้ไข']`);
                if (editBtn) editBtn.click();
            } else if (action === 'delete' && targetId) {
                openDeleteModal(targetId);
            }

            if (urlParams.has('open_modal') || action) {
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=my_recruitments';
                window.history.replaceState({
                    path: newUrl
                }, '', newUrl);
            }
        });
        function submitFormWithAlert() {
    const form = document.getElementById('addRecruitmentForm');

    if (!form.checkValidity()) {
        form.reportValidity(); // 👈 ให้ browser แสดงข้อความเตือน
        return;
    }

    form.submit();
}
    </script>

</body>

</html>