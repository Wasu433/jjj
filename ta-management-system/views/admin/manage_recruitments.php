<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/Config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$semester_filter = $_GET['semester'] ?? '';

$subjects = $pdo->query("SELECT course_id, id, code, name FROM subjects ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$semesters = $pdo->query("SELECT DISTINCT semester FROM subjects ORDER BY semester DESC")->fetchAll(PDO::FETCH_COLUMN);

$sql = "SELECT r.id, r.title, r.status, r.created_at, r.description, r.subject_id, r.teacher_id, 
               s.code AS subject_code, s.name AS subject_name, s.semester, 
               u.name AS teacher_name, 
               rd.quota AS total_quota, rd.grade_requirement,
               (SELECT GROUP_CONCAT(CONCAT(name, '|', schedule_time, '|', quota) SEPARATOR ';;') FROM sections WHERE subject_id = r.subject_id) AS sections_data
        FROM recruitments r
        JOIN subjects s ON r.subject_id = s.id
        JOIN users u ON r.teacher_id = u.id
        LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
        WHERE r.status != 'deleted'";

$params = [];
if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR s.code LIKE ? OR s.name LIKE ? OR u.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}
if (!empty($status_filter) && $status_filter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}
if (!empty($semester_filter) && $semester_filter !== 'all') {
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
    <title>จัดการประกาศ | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
        body {
            background-color: #f9fafb;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 24px 24px;
        }

        .modal-enter {
            animation: fadeIn 0.3s ease-out forwards;
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

<body class="text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl"><i class="fas fa-bullhorn"></i></div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">จัดการประกาศ</h1>
                    <p class="text-gray-500 text-sm">รายการประกาศรับสมัครผู้ช่วยสอนทั้งหมด</p>
                </div>
            </div>
            <button onclick="openModal()" class="group flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95">
                <i class="fas fa-plus"></i> เพิ่มประกาศใหม่
            </button>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <input type="hidden" name="page" value="manage_recruitments">
                <div class="md:col-span-3"><label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ภาคการศึกษา</label>
                    <div class="relative"><select name="semester" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-white appearance-none">
                            <option value="all">ทั้งหมด</option><?php foreach ($semesters as $sem): ?><option value="<?php echo $sem; ?>" <?php echo $semester_filter == $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option><?php endforeach; ?>
                        </select><i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i></div>
                </div>
                <div class="md:col-span-3"><label class="block text-xs font-medium text-gray-500 mb-1 ml-1">สถานะ</label>
                    <div class="relative"><select name="status" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-white appearance-none">
                            <option value="all">ทั้งหมด</option>
                            <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>เปิดรับสมัคร</option>
                            <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>ปิดแล้ว</option>
                        </select><i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i></div>
                </div>
                <div class="md:col-span-4"><label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ค้นหา</label>
                    <div class="relative"><input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อประกาศ, รหัสวิชา..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i></div>
                </div>
                <div class="md:col-span-2 flex gap-2"><button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">ค้นหา</button><a href="index.php?page=manage_recruitments" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-bold transition-colors text-center">รีเซ็ต</a></div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                            <th class="py-4 px-6 rounded-tl-2xl">ชื่อประกาศ</th>
                            <th class="py-4 px-6">รายวิชา / อาจารย์</th>
                            <th class="py-4 px-6 text-center">จำนวนรับรวม</th>
                            <th class="py-4 px-6">วันที่ประกาศ</th>
                            <th class="py-4 px-6 text-center">สถานะ</th>
                            <th class="py-4 px-6 text-right rounded-tr-2xl">จัดการ</th>
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
                                    <td class="py-4 px-6 font-semibold text-gray-800"><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td class="py-4 px-6">
                                        <div class="flex flex-col">
                                            <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($row['subject_code'] . " " . $row['subject_name']); ?></span>
                                            <span class="text-xs text-gray-400 mt-0.5"><i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($row['teacher_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs font-bold"><?php echo $row['total_quota']; ?> คน</span></td>
                                    <td class="py-4 px-6 text-gray-600"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($row['status'] == 'open'): ?>
                                            <span class="px-2 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-700">เปิดรับ</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-600">ปิดแล้ว</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button onclick="openEditModal(this)"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                                data-subject="<?php echo $row['subject_id']; ?>"
                                                data-teacher="<?php echo $row['teacher_id']; ?>"
                                                data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                                data-grade="<?php echo $row['grade_requirement']; ?>"
                                                data-status="<?php echo $row['status']; ?>"
                                                data-sections='<?php echo $row['sections_data']; ?>'
                                                class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm" title="แก้ไข"><i class="fas fa-pen text-xs"></i></button>

                                            <button onclick="openDeleteModal('<?php echo $row['id']; ?>')" class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-red-600 hover:border-red-200 transition-all shadow-sm" title="ลบ"><i class="fas fa-trash text-xs"></i></button>
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
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full modal-enter">
                <div class="bg-white px-6 pt-6 pb-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">เพิ่มประกาศใหม่</h3>

                    <form action="index.php?page=add_recruitment_process" method="POST" id="addRecruitmentForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">รายวิชา <span class="text-red-500">*</span></label>
                            <select id="subjectSelect" name="subject_id" required
                                    class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">-- เลือกวิชา --</option>

                                    <?php foreach ($subjects as $subj): ?>
                                        <option
                                            value="<?php echo $subj['id']; ?>"
                                            data-course-id="<?php echo $subj['course_id']; ?>">
                                            <?php echo $subj['code'] . " - " . $subj['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">อาจารย์ผู้สอน <span class="text-red-500">*</span></label><select name="teacher_id" required class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">-- เลือกอาจารย์ --</option><?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo $t['name']; ?></option><?php endforeach; ?>
                                </select></div>
                            <input type="text" name="course_id" value="">
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อประกาศ <span class="text-red-500">*</span></label><input type="text" name="title" required class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">เกรดขั้นต่ำ</label><select name="grade_req" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="A">A</option>
                                    <option value="B+">B+</option>
                                    <option value="B">B</option>
                                    <option value="C+">C+</option>
                                    <option value="C">C</option>
                                </select></div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                                    <option value="open" selected>เปิดรับสมัคร</option>
                                    <option value="closed">ปิดรับสมัคร</option>
                                </select>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="flex justify-between items-center mb-3"><label class="block text-sm font-bold text-gray-700">ข้อมูลหมู่เรียน (Section) และจำนวนรับ</label><button type="button" onclick="addSectionRow('add_sections_container')" class="text-xs font-bold bg-white border border-indigo-200 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-50 transition-colors shadow-sm"><i class="fas fa-plus"></i> เพิ่มเซค</button></div>
                            <div id="add_sections_container" class="space-y-2">
                                <!-- <div class="flex gap-2"><input type="text" name="sec_names[]" placeholder="Sec" class="w-1/4 border px-2 py-1 rounded text-sm"><input type="text" name="sec_times[]" placeholder="เวลา" class="w-1/2 border px-2 py-1 rounded text-sm"><input type="number" name="sec_quotas[]" placeholder="รับ" class="w-1/4 border px-2 py-1 rounded text-sm"></div> -->
                            </div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดงาน</label><textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm"></textarea></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2"><button onclick="document.getElementById('addRecruitmentForm').submit()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold">บันทึก</button><button onclick="closeModal()" class="bg-white border px-4 py-2 rounded-lg text-sm font-bold">ยกเลิก</button></div>
            </div>
        </div>
    </div>

    <div id="editRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full modal-enter">
                <div class="bg-white px-6 pt-6 pb-4">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">แก้ไขประกาศ</h3>

                    <form action="index.php?page=edit_recruitment_process" method="POST" id="editRecruitmentForm" class="space-y-4">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">รายวิชา</label><select name="subject_id" id="edit_subject_id" class="w-full px-3 py-2 border rounded-lg"><?php foreach ($subjects as $s) echo "<option value='{$s['id']}'>{$s['code']} {$s['name']}</option>"; ?></select></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">อาจารย์ผู้สอน</label><select name="teacher_id" id="edit_teacher_id" class="w-full px-3 py-2 border rounded-lg"><?php foreach ($teachers as $t) echo "<option value='{$t['id']}'>{$t['name']}</option>"; ?></select></div>
                        </div>
                        <div><label class="block text-sm font-bold mb-1">หัวข้อประกาศ</label><input type="text" name="title" id="edit_title" class="w-full px-3 py-2 border rounded-lg"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">เกรดขั้นต่ำ</label><select name="grade_req" id="edit_grade_req" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="A">A</option>
                                    <option value="B+">B+</option>
                                    <option value="B">B</option>
                                    <option value="C+">C+</option>
                                    <option value="C">C</option>
                                </select></div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                                <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg">
                                    <option value="open">เปิดรับสมัคร</option>
                                    <option value="closed">ปิดรับสมัคร</option>
                                </select>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                            <div class="flex justify-between items-center mb-3"><label class="block text-sm font-bold text-gray-700">ข้อมูลหมู่เรียน (Section)</label><button type="button" onclick="addSectionRow('edit_sections_container')" class="text-xs font-bold bg-white border border-blue-200 text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-colors shadow-sm"><i class="fas fa-plus"></i> เพิ่มเซค</button></div>
                            <div id="edit_sections_container" class="space-y-2.5"></div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดงาน</label><textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm"></textarea></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2"><button onclick="document.getElementById('editRecruitmentForm').submit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold">บันทึก</button><button onclick="closeEditModal()" class="bg-white border px-4 py-2 rounded-lg text-sm font-bold">ยกเลิก</button></div>
            </div>
        </div>
    </div>

    <div id="deleteRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-center">
                    <h3 class="text-lg font-bold text-gray-900">ยืนยันการลบประกาศ</h3>
                    <p class="text-sm text-gray-500 mt-2">ต้องการลบประกาศนี้ใช่หรือไม่?</p>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-2 justify-center"><a id="confirmDeleteBtn" href="#" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold">ลบข้อมูล</a><button onclick="closeDeleteModal()" class="bg-white border px-4 py-2 rounded-lg text-sm font-bold">ยกเลิก</button></div>
            </div>
        </div>
    </div>

    <script>
        let classList = [];
       document.getElementById('subjectSelect').addEventListener('change', function () {
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
                removeAllSectionRows('add_sections_container');
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
                        'add_sections_container',
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
            newRow.innerHTML = `<div class="w-1/3"><input type="text" name="sec_names[]" value="${name}" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="ชื่อเซค"></div><div class="w-1/3"><input type="text" name="sec_times[]" value="${time}" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="เวลา"></div><div class="w-1/4"><input type="number" name="sec_quotas[]" value="${quota}" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="รับ"></div><button type="button" onclick="this.parentElement.remove()" class="w-10 h-10 flex items-center justify-center bg-white border text-red-400 rounded-xl"><i class="fas fa-trash-alt"></i></button>`;
            container.appendChild(newRow);
        }

        function removeAllSectionRows(containerId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
        }

        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_title').value = btn.getAttribute('data-title');
            document.getElementById('edit_subject_id').value = btn.getAttribute('data-subject');
            document.getElementById('edit_teacher_id').value = btn.getAttribute('data-teacher');
            document.getElementById('edit_description').value = btn.getAttribute('data-desc');
            document.getElementById('edit_grade_req').value = btn.getAttribute('data-grade');

            // 🔥 รับค่า Status ใส่ Dropdown
            document.getElementById('edit_status').value = btn.getAttribute('data-status');

            const container = document.getElementById('edit_sections_container');
            container.innerHTML = '';
            const sectionsStr = btn.getAttribute('data-sections');
            if (sectionsStr) {
                const sections = sectionsStr.split(';;');
                sections.forEach(sec => {
                    const parts = sec.split('|');
                    const name = parts[0] || '';
                    const time = parts[1] || '';
                    const quota = parts[2] || '';
                    addSectionRow('edit_sections_container', name, time, quota);
                });
            } else {
                addSectionRow('edit_sections_container');
            }
            document.getElementById('editRecruitmentModal').classList.remove('hidden');
        }

        function openDeleteModal(id) {
            // 🔥 แก้ Link Delete ให้ตรงกับ index.php (delete_recruitment)
            document.getElementById('confirmDeleteBtn').href = 'index.php?page=delete_recruitment&id=' + id;
            document.getElementById('deleteRecruitmentModal').classList.remove('hidden');
        }

        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('open_modal')) {
                openModal();
                window.history.replaceState({}, '', window.location.pathname + '?page=manage_recruitments');
            }
        });
    </script>
</body>

</html>