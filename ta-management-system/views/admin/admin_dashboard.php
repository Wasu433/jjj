<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// แก้เป็นแบบนี้ (ถอย 2 ชั้น ไปหา app)
require_once __DIR__ . '/../../app/Config/Database.php';

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// --- Logic การดึงข้อมูลสถิติ ---
$stats = [];
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_recruitments'] = $pdo->query("SELECT COUNT(*) FROM recruitments WHERE status != 'deleted'")->fetchColumn();
$stats['selected_tas'] = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'approved'")->fetchColumn();

$user_roles = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_KEY_PAIR);
$recent_users = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// คำนวณกราฟ
$total = $stats['total_users'] > 0 ? $stats['total_users'] : 1;
$student_percent = isset($user_roles['student']) ? round(($user_roles['student'] / $total) * 100) : 0;
$teacher_percent = isset($user_roles['teacher']) ? round(($user_roles['teacher'] / $total) * 100) : 0;
$admin_percent = isset($user_roles['admin']) ? round(($user_roles['admin'] / $total) * 100) : 0;

$circumference = 2 * pi() * 80;
$student_dash = ($student_percent / 100) * $circumference;
$teacher_dash = ($teacher_percent / 100) * $circumference;
$admin_dash = ($admin_percent / 100) * $circumference;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ - TA Management</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
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

        <div class="bg-white rounded-2xl p-6 mb-8 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tachometer-alt text-indigo-600"></i> แดชบอร์ดผู้ดูแลระบบ
                </h1>
                <p class="text-gray-500 mt-1 text-sm">
                    <i class="far fa-calendar-alt mr-1"></i>
                    มหาวิทยาลัยศิลปากร
                </p>
            </div>

            <div class="flex gap-3">
                <button onclick="openModal()" class="flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 hover:text-indigo-600 hover:border-indigo-200 transition-all active:scale-95">
                    <i class="fas fa-user-plus text-lg"></i> <span>เพิ่มผู้ใช้</span>
                </button>
                <a href="index.php?page=manage_recruitments&open_modal=1" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-md shadow-indigo-200 transition-all flex items-center gap-2">
                    <i class="fas fa-bullhorn"></i> เพิ่มข่าวประกาศ
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-gray-500 text-sm font-medium">ผู้ใช้ระบบทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total_users']); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-users"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-gray-500 text-sm font-medium">ประกาศทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['total_recruitments']); ?></h3>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-clipboard-list"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-shadow">
                <div>
                    <p class="text-gray-500 text-sm font-medium">TA ที่ผ่านการคัดเลือก</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($stats['selected_tas']); ?></h3><span class="text-orange-500 text-xs font-medium flex items-center mt-1"><i class="fas fa-check-circle mr-1"></i> อนุมัติแล้ว</span>
                </div>
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-user-check"></i></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">จัดการผู้ใช้งานล่าสุด</h2>
                        <p class="text-gray-500 text-xs">รายชื่อสมาชิกใหม่ 5 คนล่าสุด</p>
                    </div>
                    <a href="index.php?page=manage_users" class="text-primary hover:text-indigo-800 text-sm font-bold hover:underline flex items-center gap-1">
                        ดูทั้งหมด <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-xs uppercase bg-gray-50 border-b border-gray-100">
                                <th class="py-3 px-4 font-medium rounded-tl-lg">ชื่อผู้ใช้</th>
                                <th class="py-3 px-4 font-medium">อีเมล</th>
                                <th class="py-3 px-4 font-medium text-center">สถานะ</th>
                                <th class="py-3 px-4 font-medium text-right rounded-tr-lg">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($recent_users as $user): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></span>
                                            <span class="text-xs text-gray-400 capitalize"><?php echo $user['role']; ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <?php
                                        if ($user['role'] == 'student') echo '<span class="px-2 py-1 rounded-md text-xs font-bold bg-green-100 text-green-700">นักศึกษา</span>';
                                        elseif ($user['role'] == 'teacher') echo '<span class="px-2 py-1 rounded-md text-xs font-bold bg-indigo-100 text-indigo-700">อาจารย์</span>';
                                        else echo '<span class="px-2 py-1 rounded-md text-xs font-bold bg-red-100 text-red-700">แอดมิน</span>';
                                        ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <button onclick="openEditModal('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo $user['role']; ?>')"
                                            class="w-8 h-8 inline-flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-indigo-600 hover:border-indigo-200 transition-colors mx-1 p-1">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <?php if ($user['role'] !== 'admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                            <button onclick="openDeleteModal('<?php echo $user['id']; ?>')"
                                                class="w-8 h-8 inline-flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-red-600 hover:border-red-200 transition-colors mx-1 p-1">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center">
                <div class="w-full mb-4">
                    <h2 class="text-lg font-bold text-gray-900">สัดส่วนผู้ใช้งาน</h2>
                    <p class="text-gray-500 text-xs">แบ่งตามประเภทสิทธิ์การใช้งาน</p>
                </div>
                <div class="relative w-48 h-48 mb-6">
                    <svg width="100%" height="100%" viewBox="0 0 200 200" class="transform -rotate-90">
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#f3f4f6" stroke-width="25" />
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#4ade80" stroke-width="25" stroke-dasharray="<?php echo $student_dash . ' ' . $circumference; ?>" stroke-dashoffset="0" />
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#6366f1" stroke-width="25" stroke-dasharray="<?php echo $teacher_dash . ' ' . $circumference; ?>" stroke-dashoffset="-<?php echo $student_dash; ?>" />
                        <circle cx="100" cy="100" r="80" fill="none" stroke="#f87171" stroke-width="25" stroke-dasharray="<?php echo $admin_dash . ' ' . $circumference; ?>" stroke-dashoffset="-<?php echo $student_dash + $teacher_dash; ?>" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center z-10"><span class="text-3xl font-bold text-gray-800"><?php echo $total; ?></span><span class="text-xs text-gray-400">Total Users</span></div>
                </div>
                <div class="w-full space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-green-400"></span><span class="text-gray-600">นักศึกษา</span></div><span class="font-bold text-gray-900"><?php echo $student_percent; ?>%</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-indigo-500"></span><span class="text-gray-600">อาจารย์</span></div><span class="font-bold text-gray-900"><?php echo $teacher_percent; ?>%</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-red-400"></span><span class="text-gray-600">แอดมิน</span></div><span class="font-bold text-gray-900"><?php echo $admin_percent; ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full modal-enter border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-user-plus text-indigo-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">เพิ่มผู้ใช้งานใหม่</h3>
                            <div class="mt-4 space-y-4">
                                <form action="index.php?page=add_user_process" method="POST" autocomplete="off" id="addUserForm">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label><input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="เช่น นายสมชาย ใจดี"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label><input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="email@silpakorn.edu"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ประเภทผู้ใช้</label><select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm bg-white">
                                            <option value="student">นักศึกษา</option>
                                            <option value="teacher">อาจารย์</option>
                                            <option value="admin">ผู้ดูแลระบบ</option>
                                        </select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label><input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="กำหนดรหัสผ่านเบื้องต้น"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('addUserForm').submit();" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none sm:w-auto transition-colors order-first sm:order-none">บันทึกข้อมูล</button>
                    <button type="button" onclick="closeModal()" class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:w-auto transition-colors order-1 sm:order-none">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full modal-enter">
                <form action="index.php?page=edit_user_process" method="POST" id="editUserForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-user-edit text-blue-600"></i></div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-bold text-gray-900">แก้ไขข้อมูลผู้ใช้</h3>
                                <div class="mt-4 space-y-4">
                                    <input type="hidden" name="id" id="edit_id">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label><input type="text" name="name" id="edit_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label><input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ประเภทผู้ใช้</label><select name="role" id="edit_role" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm bg-white">
                                            <option value="student">นักศึกษา</option>
                                            <option value="teacher">อาจารย์</option>
                                            <option value="admin">แอดมิน</option>
                                        </select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่ (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label><input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm" placeholder="********"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3">

                        <button type="submit"
                            class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none transition-colors">
                            บันทึกแก้ไข
                        </button>

                        <button type="button" onclick="closeEditModal()"
                            class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                            ยกเลิก
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full modal-enter">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">ยืนยันการลบผู้ใช้</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">คุณแน่ใจหรือไม่ที่จะลบผู้ใช้งานรายนี้?<br>การกระทำนี้ไม่สามารถย้อนกลับได้</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3">

                    <a id="confirmDeleteBtn" href="#"
                        class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none transition-colors">
                        ลบข้อมูล
                    </a>

                    <button type="button" onclick="closeDeleteModal()"
                        class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">
                        ยกเลิก
                    </button>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Add Modal
        function openModal() {
            document.getElementById('addUserModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('addUserModal').classList.add('hidden');
        }

        // Edit Modal
        function openEditModal(id, name, email, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editUserModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        // Delete Modal
        function openDeleteModal(id) {
            document.getElementById('confirmDeleteBtn').href = 'index.php?page=delete_user&id=' + id;
            document.getElementById('deleteUserModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteUserModal').classList.add('hidden');
        }
    </script>

</body>

</html>