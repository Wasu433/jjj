<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. เชื่อมต่อ Database
require_once __DIR__ . '/../../app/Config/Database.php';

// 2. ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// 3. Logic ค้นหา
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter) && $role_filter !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน | TA System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Prompt', 'sans-serif'] },
                    colors: { primary: '#4f46e5' }
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
        .modal-enter { animation: fadeIn 0.3s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-40">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">จัดการผู้ใช้งาน</h1>
                    <p class="text-gray-500 text-sm">เพิ่ม แก้ไข หรือลบข้อมูลสมาชิกในระบบ</p>
                </div>
            </div>
            <button onclick="openModal()" class="group flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-md transition-all active:scale-95">
                <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
            </button>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-4 items-end">
                <input type="hidden" name="page" value="manage_users">
                
                <div class="w-full md:w-48">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ประเภทผู้ใช้</label>
                    <div class="relative">
                        <select name="role" onchange="this.form.submit()" class="w-full pl-3 pr-8 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors cursor-pointer appearance-none">
                            <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                            <option value="student" <?php echo $role_filter == 'student' ? 'selected' : ''; ?>>นักศึกษา</option>
                            <option value="teacher" <?php echo $role_filter == 'teacher' ? 'selected' : ''; ?>>อาจารย์</option>
                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>แอดมิน</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex-1 w-full">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ค้นหา</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหาชื่อ หรืออีเมล..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 bg-gray-50 hover:bg-white transition-colors">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    </div>
                </div>

                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-medium transition-colors shadow-sm">
                        ค้นหา
                    </button>
                    <a href="index.php?page=manage_users" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-medium transition-colors">
                        รีเซ็ต
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                            <th class="py-4 px-6 rounded-tl-2xl">ชื่อผู้ใช้</th>
                            <th class="py-4 px-6">อีเมล</th>
                            <th class="py-4 px-6 text-center">สถานะ (Role)</th>
                            <th class="py-4 px-6 text-right rounded-tr-2xl">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="py-10 text-center text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-user-slash text-2xl text-gray-300"></i>
                                        </div>
                                        <p>ไม่พบข้อมูลผู้ใช้งาน</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors text-base">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-4 px-6 text-center">
                                    <?php 
                                        if ($user['role'] == 'student') echo '<span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-600">นักศึกษา</span>';
                                        elseif ($user['role'] == 'teacher') echo '<span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-indigo-100 text-indigo-600">อาจารย์</span>';
                                        else echo '<span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-600">แอดมิน</span>';
                                    ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        <button onclick="openEditModal('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo $user['role']; ?>')" 
                                                class="w-8 h-8 inline-flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-indigo-600 hover:border-indigo-200 transition-colors shadow-sm"
                                                title="แก้ไข">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>

                                        <?php if ($user['role'] !== 'admin' && $user['id'] !== $_SESSION['user_id']): ?>
                                        <button onclick="openDeleteModal('<?php echo $user['id']; ?>')" 
                                                class="w-8 h-8 inline-flex items-center justify-center bg-white border border-gray-200 text-gray-400 rounded-lg hover:text-red-600 hover:border-red-200 transition-colors shadow-sm"
                                                title="ลบ">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                <span class="text-xs text-gray-500">แสดง <?php echo count($users); ?> รายการ</span>
                <div class="flex gap-1">
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 bg-white hover:bg-gray-50 cursor-not-allowed"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm text-xs font-bold">1</button>
                    <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 bg-white hover:bg-gray-50"><i class="fas fa-chevron-right text-xs"></i></button>
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
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-user-plus text-indigo-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">เพิ่มผู้ใช้งานใหม่</h3>
                            <div class="mt-4 space-y-4">
                                <form action="index.php?page=add_user_process" method="POST" autocomplete="off" id="addUserForm">
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label><input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="เช่น นายสมชาย ใจดี"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label><input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="email@silpakorn.edu"></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ประเภทผู้ใช้</label><select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm bg-white"><option value="teacher">อาจารย์</option><option value="admin">ผู้ดูแลระบบ</option></select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label><input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all sm:text-sm" placeholder="กำหนดรหัสผ่านเบื้องต้น"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3">
                    <button type="button" onclick="document.getElementById('addUserForm').submit();" class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none transition-colors">บันทึกข้อมูล</button>
                    <button type="button" onclick="closeModal()" class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>

    <div id="editUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full modal-enter border border-gray-100">
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
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">ประเภทผู้ใช้</label><select name="role" id="edit_role" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm bg-white"><option value="teacher">อาจารย์</option><option value="admin">แอดมิน</option></select></div>
                                    <div><label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่ (เว้นว่างถ้าไม่เปลี่ยน)</label><input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 transition-all sm:text-sm" placeholder="********"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row gap-3">
                        <button type="button" onclick="document.getElementById('editUserForm').submit();" class="w-full flex-1 inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2.5 bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none transition-colors">บันทึกแก้ไข</button>
                        <button type="button" onclick="closeEditModal()" class="mt-3 sm:mt-0 w-full flex-1 inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-5 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none transition-colors">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDeleteModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full modal-enter border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><i class="fas fa-exclamation-triangle text-red-600"></i></div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">ยืนยันการลบผู้ใช้</h3>
                            <div class="mt-2"><p class="text-sm text-gray-500">คุณแน่ใจหรือไม่ที่จะลบผู้ใช้งานรายนี้?<br>การกระทำนี้ไม่สามารถย้อนกลับได้</p></div>
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

    <script>
        // Add Modal
        function openModal() { document.getElementById('addUserModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('addUserModal').classList.add('hidden'); }

        // Edit Modal
        function openEditModal(id, name, email, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editUserModal').classList.remove('hidden');
        }
        function closeEditModal() { document.getElementById('editUserModal').classList.add('hidden'); }

        // Delete Modal
        function openDeleteModal(id) {
            document.getElementById('confirmDeleteBtn').href = 'index.php?page=delete_user&id=' + id;
            document.getElementById('deleteUserModal').classList.remove('hidden');
        }
        function closeDeleteModal() { document.getElementById('deleteUserModal').classList.add('hidden'); }
    </script>

</body>
</html>