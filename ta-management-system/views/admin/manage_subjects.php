<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Controllers/SubjectController.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// ==============================
// ค้นหา + filter
// ==============================
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
// ==============================
// PAGINATION
// ==============================
$limit = 10; // จำนวนรายการต่อหน้า
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $limit;
$sql = "SELECT DISTINCT s.id, s.code,s.revisioncode, s.name, s.semester, st.teacher_name
        FROM subjects s
        LEFT JOIN subject_teachers st ON s.id = st.subject_id
        WHERE 1=1";
$params = [];

// 🔎 ค้นหา
if (!empty($search)) {
    $sql .= " AND (s.code LIKE ? 
                   OR s.name LIKE ? 
                   OR st.teacher_name LIKE ?)";
    $params = array_merge($params, [
        "%$search%",
        "%$search%",
        "%$search%"
    ]);
}

// 📚 filter semester
if (!empty($semester_filter) && $semester_filter !== 'all') {
    $sql .= " AND s.semester = ?";
    $params[] = $semester_filter;
}
// Query สำหรับนับจำนวนทั้งหมด
$countSql = "SELECT COUNT(DISTINCT s.id)
             FROM subjects s
             LEFT JOIN subject_teachers st ON s.id = st.subject_id
             WHERE 1=1";
$countParams = $params;

// เงื่อนไข search
if (!empty($search)) {
    $countSql .= " AND (s.code LIKE ? 
                        OR s.name LIKE ? 
                        OR st.teacher_name LIKE ?)";
}

// filter semester
if (!empty($semester_filter) && $semester_filter !== 'all') {
    $countSql .= " AND s.semester = ?";
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalRows = $countStmt->fetchColumn();

$totalPages = ceil($totalRows / $limit);

$sql .= " ORDER BY s.id DESC
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);


$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================
// dropdown ข้อมูลอื่น
// ==============================

// รายชื่ออาจารย์ (ดึงจาก users role=teacher)
$teachers = $pdo->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'teacher' 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// semester ทั้งหมด
$semesters = $pdo->query("
    SELECT DISTINCT semester 
    FROM subjects 
    ORDER BY semester DESC
")->fetchAll(PDO::FETCH_COLUMN);

// ปีการศึกษา (พ.ศ.)
$currentYear = date("Y") + 543;
$yearRange = range($currentYear - 1, $currentYear + 7);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายวิชา | TA System</title>
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
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl"><i class="fas fa-book-open"></i></div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">จัดการรายวิชา</h1>
                    <p class="text-gray-500 text-sm">เพิ่มรายวิชาเพื่อให้ อาจารย์ เลือกเปิดรับสมัครได้</p>
                </div>
            </div>
            <!-- <button onclick="openModal()" class="group flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95">
                <i class="fas fa-plus"></i> เพิ่มรายวิชา
            </button> -->
            <button type="button" id="syncBtn" class="w-full md:w-auto flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition-all active:scale-95">
                <i class="fas fa-sync"></i> ซิ้งค์ข้อมูลวิชา
            </button>
        </div>


        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="index.php" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <input type="hidden" name="page" value="manage_subjects">
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ภาคการศึกษา</label>
                    <div class="relative">
                        <select name="semester" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-white appearance-none">
                            <option value="all">ทั้งหมด</option><?php foreach ($semesters as $sem): ?><option value="<?php echo $sem; ?>" <?php echo $semester_filter == $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option><?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>
                <div class="md:col-span-7">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ค้นหา</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="รหัสวิชา, ชื่อวิชา, ชื่ออาจารย์..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl text-sm font-bold transition-colors shadow-sm">ค้นหา</button>
                    <a href="index.php?page=manage_subjects" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-bold transition-colors text-center">รีเซ็ต</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                            <th class="py-4 px-6 rounded-tl-2xl">รหัสวิชา</th>
                            <th class="py-4 px-6">ชื่อวิชา</th>
                            <th class="py-4 px-6">ภาคเรียน</th>
                            <th class="py-4 px-6">อาจารย์ผู้รับผิดชอบ</th>


                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="5" class="py-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center"><i class="fas fa-book-open text-2xl text-gray-300 mb-2"></i>
                                        <p>ไม่พบรายวิชาในระบบ</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $row): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6 font-bold text-indigo-600"><?php echo htmlspecialchars($row['code']) . "-" . htmlspecialchars($row['revisioncode']); ?></td>
                                    <td class="py-4 px-6 font-medium text-gray-800"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="py-4 px-6 text-gray-600"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-md text-xs font-bold"><?php echo htmlspecialchars($row['semester']); ?></span></td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs"><i class="fas fa-user"></i></div><span class="text-gray-700 text-xs font-medium"><?= htmlspecialchars($row['teacher_name'] ?? '') ?></span>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php
                $range = 2; // แสดงหน้าข้างๆ กี่หน้า

                $start = max(1, $page - $range);
                $end   = min($totalPages, $page + $range);
                ?>

                <div class="flex gap-1 items-center">

                    <!-- ปุ่มหน้าแรก -->
                    <?php if ($page > 1): ?>
                        <a href="?page=manage_subjects&p=1&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>"
                            class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-100">
                            «
                        </a>
                    <?php endif; ?>

                    <!-- จุดไข่ปลา ซ้าย -->
                    <?php if ($start > 1): ?>
                        <span class="px-2 text-gray-400">...</span>
                    <?php endif; ?>

                    <!-- เลขหน้ากลาง -->
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=manage_subjects&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>"
                            class="px-3 py-1 rounded-lg border
           <?php echo $i == $page
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- จุดไข่ปลา ขวา -->
                    <?php if ($end < $totalPages): ?>
                        <span class="px-2 text-gray-400">...</span>
                    <?php endif; ?>

                    <!-- ปุ่มหน้าสุดท้าย -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=manage_subjects&p=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&semester=<?php echo $semester_filter; ?>"
                            class="px-3 py-1 bg-white border rounded-lg hover:bg-gray-100">
                            »
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <div id="addSubjectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full modal-enter border border-gray-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl"><i class="fas fa-book-medical"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">เพิ่มรายวิชาใหม่</h3>
                            <p class="text-sm text-gray-500">กรอกข้อมูลรายวิชาและกำหนดอาจารย์ผู้สอน</p>
                        </div>
                    </div>
                    <form action="index.php?page=add_subject_process" method="POST" id="addSubjectForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสวิชา</label>
                                <input type="text" name="course_id" id="course_id" hidden class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
                                <input type="text" name="code" id="add_code" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ภาคการศึกษา</label>
                                <div class="flex gap-2 items-center">
                                    <div class="relative w-1/3">
                                        <select name="term" required class="w-full px-2 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white appearance-none">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
                                    </div>
                                    <span class="text-gray-400">/</span>
                                    <div class="relative w-2/3">
                                        <select name="year" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm bg-white appearance-none">
                                            <?php foreach ($yearRange as $y): ?>
                                                <option value="<?php echo $y; ?>" <?php echo $y == $currentYear ? 'selected' : ''; ?>>
                                                    <?php echo $y; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิชา</label><input type="text" name="name" id="add_name" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-sm"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">อาจารย์ผู้รับผิดชอบ</label>
                            <div class="relative">
                                <select name="teacher_id" id="add_teacher" required
                                    class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-xl
                                        focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm
                                         bg-white appearance-none">
                                    <option value="">-- เลือกอาจารย์ --</option>
                                    <?php foreach ($teachers as $t): ?>
                                        <option value="<?php echo $t['id']; ?>">
                                            <?php echo htmlspecialchars($t['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row gap-3">
                    <button type="button" onclick="document.getElementById('addSubjectForm').submit();" class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none transition-colors">บันทึกข้อมูล</button>
                    <button type="button" onclick="closeModal()" class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editSubjectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full modal-enter border border-gray-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xl"><i class="fas fa-pen"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">แก้ไขรายวิชา</h3>
                            <p class="text-sm text-gray-500">ปรับปรุงข้อมูลรายวิชา</p>
                        </div>
                    </div>
                    <form action="index.php?page=edit_subject_process" method="POST" id="editSubjectForm" class="space-y-4">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสวิชา</label>
                                <input type="text" name="code" id="edit_code" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ภาคการศึกษา</label>
                                <div class="flex gap-2 items-center">
                                    <div class="relative w-1/3">
                                        <select name="term" id="edit_term" required class="w-full px-2 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white appearance-none">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-[10px] pointer-events-none"></i>
                                    </div>
                                    <span class="text-gray-400">/</span>
                                    <div class="relative w-2/3">
                                        <select name="year" id="edit_year" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white appearance-none">
                                            <?php foreach ($yearRange as $y): ?>
                                                <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวิชา</label><input type="text" name="name" id="edit_name" required class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">อาจารย์ผู้รับผิดชอบ</label>
                            <div class="relative"><select name="teacher_name" id="edit_teacher_name" required class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm bg-white appearance-none">
                                    <option value="">-- เลือกอาจารย์ --</option><?php foreach ($teachers as $t): ?><option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option><?php endforeach; ?>
                                </select><i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i></div>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex flex-row gap-3">
                    <button type="button" onclick="document.getElementById('editSubjectForm').submit();" class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-6 py-2.5 bg-blue-600 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none transition-colors">บันทึกแก้ไข</button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteSubjectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full modal-enter border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">ยืนยันการลบรายวิชา</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">คุณแน่ใจหรือไม่ที่จะลบรายวิชานี้?<br>ข้อมูลประกาศรับสมัครที่เกี่ยวข้องอาจได้รับผลกระทบ</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3">
                    <a id="confirmDeleteBtn" href="#" class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none transition-colors">ลบข้อมูล</a>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
       
        function confirmSync(event) {
            event.preventDefault();

            Swal.fire({
                title: 'ยืนยันการซิงค์ข้อมูล?',
                text: 'ระบบจะดึงข้อมูลรายวิชาและอาจารย์ล่าสุด (อาจใช้เวลาหลายวินาที) คุณต้องการดำเนินการต่อหรือไม่?',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5', // สีม่วง (Indigo)
                cancelButtonColor: '#6b7280', // สีเทา
                confirmButtonText: '<i class="fas fa-sync-alt mr-1"></i> ใช่, ซิงค์เลย',
                cancelButtonText: 'ยกเลิก',
                background: '#fff',
                customClass: {
                    title: 'font-bold text-gray-800',
                    popup: 'rounded-2xl shadow-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {

                    // โชว์หน้าต่าง Loading หมุนๆ ให้รู้ว่าระบบกำลังทำงาน
                    Swal.fire({
                        title: 'กำลังซิงค์ข้อมูล...',
                        html: 'กรุณารอสักครู่ ระบบกำลังดึงข้อมูล ห้ามปิดหน้าต่างนี้',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // ยิงไปที่หน้า index.php?page=sync_subjects
                    window.location.href = 'index.php?page=sync_subjects';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('syncBtn').addEventListener('click', confirmSync);
        });
    </script>

</body>

</html>

<script>
    /* ===============================
   GLOBAL DATA
================================ */
    let classList = [];

    /* ===============================
       FETCH SUBJECTS FROM API
    ================================ */
    const fetchSubjects = async () => {
        try {
            const res = await fetch('http://localhost:8082/index.php?page=api');
            const result = await res.json();

            // ปรับ path ให้ตรงกับ API ของคุณ
            classList = result.data?.[0]?.classinfolist || [];

            console.log('Fetched classList:', classList);
        } catch (err) {
            console.error('Fetch subjects failed:', err);
        }
    };

    /* ===============================
       AUTOCOMPLETE INIT
    ================================ */
    function initAutocomplete() {
        const input = $("#add_code");

        // ป้องกัน init ซ้ำ
        if (input.data("ui-autocomplete")) {
            input.autocomplete("destroy");
        }

        input.autocomplete({
            minLength: 1,
            delay: 200,
            source: function(request, response) {
                const term = request.term.toLowerCase();
                const results = classList
                    .filter(item =>
                        item.coursecode?.toLowerCase().includes(term) ||
                        item.coursename?.toLowerCase().includes(term)
                    )
                    .map(item => ({
                        label: `${item.coursecode}-${item.revisioncode} ${item.coursename}`,
                        value: item.coursecode,
                        data: item
                    }));

                response(results);
            },
            select: function(event, ui) {
                const data = ui.item.data;

                $("#add_code").val(data.coursecode);
                $("#add_name").val(data.coursename);
                $("#course_id").val(data.courseid);

                // 🔥 instructor เป็น array
                //$("#add_teacher").empty();

                // if (Array.isArray(data.instructor) && data.instructor.length > 0) {
                //     data.instructor.forEach((inst, index) => {
                //         const fullName =
                //             `${inst.prefixname}${inst.officername} ${inst.officersurname}`;

                //         $("#add_teacher").append(
                //             new Option(fullName, fullName, index === 0, index === 0)
                //         );
                //     });
                // } else {
                //     $("#add_teacher").append(
                //         new Option("ไม่พบข้อมูลอาจารย์", "")
                //     );
                // }

                return false;
            }

        });
    }

    /* ===============================
       MODAL CONTROLS
    ================================ */

    // Add Modal
    function openModal() {
        document.getElementById('addSubjectModal').classList.remove('hidden');
        setTimeout(() => {
            initAutocomplete();
        }, 100);
    }

    function closeModal() {
        document.getElementById('addSubjectModal').classList.add('hidden');
    }

    // Edit Modal
    function openEditModal(id, code, name, semester, teacher_id) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_code').value = code;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_teacher_id').value = teacher_id;

        if (semester && semester.includes('/')) {
            const [term, year] = semester.split('/');
            document.getElementById('edit_term').value = term;
            document.getElementById('edit_year').value = year;
        }

        document.getElementById('editSubjectModal').classList.remove('hidden');

        // 🔥 สำคัญ: init autocomplete หลัง modal แสดง
        ;
    }

    function closeEditModal() {
        document.getElementById('editSubjectModal').classList.add('hidden');
    }

    // Delete Modal
    function openDeleteModal(id) {
        document.getElementById('confirmDeleteBtn').href =
            'index.php?page=delete_subject&id=' + id;
        document.getElementById('deleteSubjectModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteSubjectModal').classList.add('hidden');
    }

    /* ===============================
       ON PAGE LOAD
    ================================ */
    document.addEventListener('DOMContentLoaded', () => {
        fetchSubjects();
    });
</script>