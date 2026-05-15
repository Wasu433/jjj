<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../../index.php");
    exit;
}

require_once __DIR__ . '/../../app/Config/Database.php';
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// --- 1. LOGIC ดึงข้อมูลสถิติ ---
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM recruitments WHERE teacher_id = ? AND status != 'deleted'");
$stmt->execute([$user_id]);
$my_recruitments_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM applications a
    JOIN recruitments r ON a.recruitment_id = r.id
    WHERE r.teacher_id = ? AND a.status = 'pending' AND r.status != 'deleted'
");
$stmt->execute([$user_id]);
$pending_applications = $stmt->fetch()['total'];

// --- 2. LOGIC ดึงรายวิชา (สำหรับใส่ใน Dropdown ของ Modal แก้ไข) ---
$my_subjects = $pdo->prepare("
    SELECT 
        s.id,
        s.code,
        s.name,
        s.semester,
        u.name AS teacher_name,
        u.email
    FROM subject_teachers st
    INNER JOIN subjects s 
        ON st.subject_id = s.id
    INNER JOIN users u 
        ON st.teacher_name = u.name
    WHERE u.name = ?
      AND u.role = 'teacher'
    ORDER BY s.code ASC
");
$my_subjects->execute([$user_name]);
$subject_options = $my_subjects->fetchAll(PDO::FETCH_ASSOC);

// --- 3. LOGIC ดึงประกาศล่าสุด (ต้องดึงละเอียดขึ้น เพื่อเอาข้อมูลไปใส่ Modal แก้ไข) ---
$stmt = $pdo->prepare("
    SELECT r.*, 
           rd.quota AS total_quota, rd.grade_requirement,
           (SELECT COUNT(*) FROM applications WHERE recruitment_id = r.id) as applicant_count,
           (SELECT GROUP_CONCAT(CONCAT(name, '|', schedule_time, '|', quota) SEPARATOR ';;') FROM sections WHERE subject_id = r.subject_id) AS sections_data
    FROM recruitments r 
    LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
    WHERE r.teacher_id = ? AND r.status != 'deleted' 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_recruitments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักอาจารย์ | TA System</title>
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
        <?php include __DIR__ . '/../layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">สวัสดี, อาจารย์ <?php echo htmlspecialchars($_SESSION['name'] ?? 'อาจารย์'); ?></h1>
            <p class="text-gray-500 mt-1">จัดการประกาศรับสมัครและคัดเลือกผู้ช่วยสอนของคุณได้ที่นี่</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-transform hover:-translate-y-1 duration-300">
                <div>
                    <p class="text-gray-500 text-sm font-medium">ประกาศรับสมัครทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo $my_recruitments_count; ?></h3>
                    <a href="index.php?page=my_recruitments" class="text-primary text-xs font-medium hover:underline mt-2 inline-flex items-center gap-1">ดูรายการประกาศ <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-bullhorn"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between transition-transform hover:-translate-y-1 duration-300">
                <div>
                    <p class="text-gray-500 text-sm font-medium">ใบสมัครรอตรวจสอบ</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo $pending_applications; ?></h3>
                    <?php if ($pending_applications > 0): ?>
                        <span class="text-orange-500 text-xs font-medium flex items-center gap-1 mt-1"><span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span> มีรายการต้องจัดการ</span>
                    <?php else: ?>
                        <span class="text-green-500 text-xs font-medium mt-1">✓ ตรวจสอบครบแล้ว</span>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-user-clock"></i></div>
            </div>
            <a href="index.php?page=my_recruitments&open_modal=1" class="group bg-gradient-to-br from-indigo-600 to-indigo-700 p-6 rounded-2xl shadow-md shadow-indigo-200 flex items-center justify-between text-white hover:shadow-lg transition-all transform hover:-translate-y-1 cursor-pointer">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">ต้องการผู้ช่วยสอน?</p>
                    <h3 class="text-xl font-bold mt-1">สร้างประกาศใหม่</h3>
                    <span class="mt-3 inline-flex items-center gap-1 bg-white/20 px-3 py-1.5 rounded-lg text-xs backdrop-blur-sm group-hover:bg-white/30 transition-colors">คลิกเพื่อเริ่ม <i class="fas fa-plus"></i></span>
                </div>
                <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform"><i class="fas fa-plus"></i></div>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2"><i class="fas fa-history text-indigo-500"></i> ประกาศล่าสุดของคุณ</h2>
                <a href="index.php?page=my_recruitments" class="text-primary hover:text-indigo-800 text-sm font-medium hover:underline">ดูทั้งหมด</a>
            </div>

            <?php if (count($recent_recruitments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-gray-50 border-b border-gray-100">
                                <th class="py-3 px-4 font-medium rounded-tl-lg">หัวข้อวิชา/งาน</th>
                                <th class="py-3 px-4 font-medium text-center">ผู้สมัคร</th>
                                <th class="py-3 px-4 font-medium">วันที่ลงประกาศ</th>
                                <th class="py-3 px-4 font-medium text-center">สถานะ</th>
                                <th class="py-3 px-4 font-medium text-right rounded-tr-lg">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($recent_recruitments as $job): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4 font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></td>
                                    <td class="py-3 px-4 text-center"><span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md text-xs font-bold"><?php echo $job['applicant_count']; ?> คน</span></td>
                                    <td class="py-3 px-4 text-gray-500"><?php echo date('d/m/Y', strtotime($job['created_at'])); ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($job['status'] == 'open'): ?><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> เปิดรับ</span><?php else: ?><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">ปิดแล้ว</span><?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="index.php?page=teacher_view_applicants&id=<?php echo $job['id']; ?>" class="w-8 h-8 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-colors" title="ดูผู้สมัคร"><i class="fas fa-users"></i></a>

                                            <button onclick="openEditModal(this)"
                                                data-id="<?php echo $job['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($job['title']); ?>"
                                                data-subject="<?php echo $job['subject_id']; ?>"
                                                data-desc="<?php echo htmlspecialchars($job['description']); ?>"
                                                data-grade="<?php echo $job['grade_requirement']; ?>"
                                                data-sections='<?php echo $job['sections_data']; ?>'
                                                class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm" title="แก้ไข">
                                                <i class="fas fa-pen text-xs"></i>
                                            </button>

                                            <button onclick="openDeleteModal('<?php echo $job['id']; ?>')"
                                                class="w-8 h-8 flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-red-600 hover:border-red-200 transition-all shadow-sm" title="ลบ">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-gray-400 bg-gray-50/50 rounded-xl border-2 border-dashed border-gray-200">
                    <p class="font-medium text-gray-500">ยังไม่มีประกาศรับสมัคร</p>
                    <a href="index.php?page=my_recruitments&open_modal=1" class="text-primary hover:underline mt-3 inline-block font-medium text-sm">+ สร้างประกาศใหม่</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="editRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl w-full modal-enter">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl"><i class="fas fa-pen"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">แก้ไขประกาศ</h3>
                            <p class="text-sm text-gray-500">แก้ไขข้อมูลประกาศรับสมัคร</p>
                        </div>
                    </div>
                    <form action="index.php?page=teacher_edit_recruitment" method="POST" id="editRecruitmentForm" class="space-y-4">
                        <input type="hidden" name="id" id="edit_id">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วิชา</label>
                            <select name="subject_id" id="edit_subject_id" required class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm bg-white">
                                <?php foreach ($subject_options as $subj): ?>
                                    <option value="<?php echo $subj['id']; ?>"><?php echo $subj['code'] . " - " . $subj['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div><label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อประกาศ</label><input type="text" name="title" id="edit_title" required class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">เกรดขั้นต่ำ</label><select name="grade_req" id="edit_grade_req" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm bg-white">
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
                            <div id="edit_sections_container" class="space-y-2.5"></div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดงาน</label><textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-sm"></textarea></div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row gap-3 justify-end">
                    <button type="button" onclick="document.getElementById('editRecruitmentForm').submit();" class="w-full sm:w-auto inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none transition-colors order-first">บันทึกแก้ไข</button>
                    <button type="button" onclick="closeEditModal()" class="w-full sm:w-auto mt-3 sm:mt-0 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors order-last">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteRecruitmentModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full modal-enter">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">ยืนยันการลบประกาศ</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">คุณแน่ใจหรือไม่ที่จะลบประกาศนี้?<br>การกระทำนี้ไม่สามารถย้อนกลับได้</p>
                            </div>
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
                <div class="w-1/3"><input type="text" name="sec_names[]" value="${name}" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 placeholder-gray-400" placeholder="ชื่อเซค"></div>
                <div class="w-1/3"><input type="text" name="sec_times[]" value="${time}" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 placeholder-gray-400" placeholder="วัน-เวลา"></div>
                <div class="w-1/4"><input type="number" name="sec_quotas[]" value="${quota}" required min="1" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 placeholder-gray-400" placeholder="จำนวน"></div>
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
    </script>

</body>

</html>