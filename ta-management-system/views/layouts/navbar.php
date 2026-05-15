<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$user_role = $_SESSION['role'] ?? 'guest';
$user_name = $_SESSION['name'] ?? 'ผู้ใช้งาน';

// ✅ 1. ส่วนเพิ่ม: ดึงข้อมูลแจ้งเตือน (PHP)
$noti_count = 0;
$notifications = [];

// เช็คว่า User Login หรือยัง
if (isset($_SESSION['user_id'])) {
    // ปรับ Path ให้วิ่งไปหา Helper (views/layouts/ -> app/Helpers/)
    $helperPath = __DIR__ . '/../../app/Helpers/NotificationHelper.php';

    if (file_exists($helperPath)) {
        require_once $helperPath;
        // ดึงจำนวนที่ยังไม่อ่าน
        $noti_count = NotificationHelper::countUnread($_SESSION['user_id']);
        // ดึงรายการแจ้งเตือนล่าสุด
        $notifications = NotificationHelper::getUnread($_SESSION['user_id']);
    }
}
// ✅ จบส่วนเพิ่ม

// ฟังก์ชันเช็คว่าหน้าไหนเปิดอยู่ ให้เปลี่ยนสีปุ่ม
function getMenuClass($page_name, $is_mobile = false)
{
    $current_page = $_GET['page'] ?? '';
    $isActive = $current_page === $page_name;

    if ($is_mobile) {
        return $isActive
            ? 'block px-3 py-2 rounded-xl text-base font-medium text-indigo-700 bg-indigo-50 border-l-4 border-indigo-600'
            : 'block px-3 py-2 rounded-xl text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50';
    } else {
        return $isActive
            ? 'px-4 py-2 rounded-xl text-sm font-semibold text-indigo-700 bg-indigo-50 transition-all shadow-sm'
            : 'px-4 py-2 rounded-xl text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-gray-50 transition-all';
    }
}

// กำหนดเมนูของแต่ละ Role
$menus = [];
if ($user_role === 'admin') {
    $menus = [
        ['page' => 'admin_dashboard',     'label' => 'หน้าหลัก',           'icon' => 'fas fa-home'],
        ['page' => 'manage_users',        'label' => 'จัดการผู้ใช้',        'icon' => 'fas fa-users-cog'],
        ['page' => 'manage_recruitments', 'label' => 'จัดการประกาศ',      'icon' => 'fas fa-bullhorn'],
        ['page' => 'manage_subjects',     'label' => 'เพิ่มรายวิชา',        'icon' => 'fas fa-book-open'],
        ['page' => 'reports',             'label' => 'รายงานสถิติ',       'icon' => 'fas fa-chart-line'],
    ];
} elseif ($user_role === 'teacher') {
    $menus = [
        ['page' => 'teacher_dashboard',   'label' => 'หน้าหลัก',           'icon' => 'fas fa-home'],
        ['page' => 'my_recruitments',     'label' => 'ประกาศรับสมัคร',     'icon' => 'fas fa-bullhorn'],
        ['page' => 'view_applicants',     'label' => 'ผู้สมัคร',           'icon' => 'fas fa-user-friends'],
        ['page' => 'my_tas',              'label' => 'ผู้ช่วยสอนของฉัน',   'icon' => 'fas fa-chalkboard-teacher'],
    ];
} elseif ($user_role === 'student') {
    $menus = [
        ['page' => 'student_dashboard',    'label' => 'หน้าหลัก',           'icon' => 'fas fa-home'],
        ['page' => 'student_recruitment_list',     'label' => 'ประกาศทั้งหมด',       'icon' => 'fas fa-newspaper'],
        ['page' => 'my_applications',     'label' => 'ประวัติการสมัคร',     'icon' => 'fas fa-history'],
        ['page' => 'profile',             'label' => 'ข้อมูลส่วนตัว',       'icon' => 'fas fa-user-edit'],
    ];
}
?>

<nav class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.05)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <div class="flex items-center gap-8">
                <a href="#" class="flex-shrink-0 flex items-center gap-2 group">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-md group-hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-lg tracking-tight group-hover:text-indigo-700 transition-colors">TA System</span>
                </a>

                <div class="hidden md:flex md:space-x-2">
                    <?php foreach ($menus as $menu): ?>
                        <a href="index.php?page=<?php echo $menu['page']; ?>"
                            class="<?php echo getMenuClass($menu['page'], false); ?>">
                            <i class="<?php echo $menu['icon']; ?> mr-1.5 opacity-70"></i>
                            <?php echo $menu['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="hidden md:flex md:items-center">

                <div class="relative ml-3">
                    <button onclick="toggleNotificationDropdown()"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>

                        <?php if ($noti_count > 0): ?>
                            <span class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full border-2 border-white">
                                <?= $noti_count ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div id="notification-dropdown"
                        class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] py-2 ring-1 ring-black ring-opacity-5 z-50">
                        <div class="px-4 py-3 border-b border-gray-50 flex justify-between items-center">
                            <span class="text-sm font-semibold text-gray-700">การแจ้งเตือน</span>
                            <?php if ($noti_count > 0): ?>
                                <span class="bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full"><?= $noti_count ?> ใหม่</span>
                            <?php endif; ?>
                        </div>

                        <div class="max-h-80 overflow-y-auto">
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $noti): ?>
                                   <a href="read_notification.php?id=<?= $noti['id'] ?>&link=<?= urlencode($noti['link']) ?>"
                                        class="block px-4 py-3 hover:bg-gray-50 transition border-b border-gray-50 last:border-0 relative">
                                        <?php if ($noti['is_read'] == 0): ?>
                                            <span class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-500 rounded-l"></span>
                                        <?php endif; ?>
                                        <p class="text-sm font-semibold text-gray-800 mb-0.5 <?= $noti['is_read'] == 0 ? 'text-indigo-700' : '' ?>">
                                            <?= htmlspecialchars($noti['title'] ?? 'แจ้งเตือน') ?>
                                        </p>
                                        <p class="text-xs text-gray-500 line-clamp-2"><?= htmlspecialchars($noti['message']) ?></p>
                                        <p class="text-[10px] text-gray-400 mt-1 text-right"><?= $noti['created_at'] ?></p>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-4 py-8 text-center">
                                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-2 text-gray-300">
                                        <i class="fas fa-bell-slash"></i>
                                    </div>
                                    <p class="text-sm text-gray-500">ไม่มีการแจ้งเตือนใหม่</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="relative ml-3">
                    <button type="button" onclick="toggleUserDropdown()" id="user-menu-button"
                        class="flex items-center gap-3 bg-white rounded-full pl-1 pr-3 py-1 hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-all focus:outline-none">

                        <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-sm ring-2 ring-white">
                            <i class="fas fa-user text-sm"></i>
                        </div>

                        <div class="flex flex-col items-start text-left">
                            <span class="text-sm font-semibold text-gray-700 leading-none"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider mt-0.5"><?php echo $user_role; ?></span>
                        </div>

                        <i class="fas fa-chevron-down text-gray-300 text-xs transition-transform duration-200" id="user-menu-arrow"></i>
                    </button>

                    <div id="user-menu-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] py-2 bg-white ring-1 ring-black ring-opacity-5 transition-all duration-200 z-50">
                        <div class="px-5 py-3 border-b border-gray-50 mb-1">
                            <p class="text-xs text-gray-400">สถานะบัญชี</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <span class="text-sm font-medium text-green-600">ออนไลน์</span>
                            </div>
                        </div>


                        <a href="index.php?page=change_password" class="flex items-center px-5 py-2.5 text-sm text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-key w-6 opacity-70"></i> เปลี่ยนรหัสผ่าน
                        </a>

                        <div class="border-t border-gray-50 my-1"></div>

                        <a href="index.php?page=logout" class="flex items-center px-5 py-2.5 text-sm text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors rounded-b-2xl">
                            <i class="fas fa-sign-out-alt w-6 opacity-70"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>

            <div class="-mr-2 flex items-center md:hidden">
                <button type="button" onclick="toggleMobileMenu()" class="inline-flex items-center justify-center p-2 rounded-xl text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="md:hidden hidden bg-white border-t border-gray-100" id="mobile-menu">
        <div class="pt-3 pb-3 space-y-1 px-4">
            <?php foreach ($menus as $menu): ?>
                <a href="index.php?page=<?php echo $menu['page']; ?>"
                    class="<?php echo getMenuClass($menu['page'], true); ?>">
                    <i class="<?php echo $menu['icon']; ?> mr-3 w-5 text-center"></i>
                    <?php echo $menu['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="pt-4 pb-4 border-t border-gray-100 bg-gray-50/50">
            <div class="flex items-center px-5">
                <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                <div class="ml-3">
                    <div class="text-base font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="text-sm font-medium text-gray-500 capitalize"><?php echo $user_role; ?></div>
                </div>
            </div>
            <div class="mt-4 space-y-1 px-2">
                <a href="index.php?page=profile" class="block px-3 py-2 rounded-xl text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-white transition-colors">
                    <i class="fas fa-user-circle mr-2 opacity-70"></i> ข้อมูลส่วนตัว
                </a>
                <a href="index.php?page=logout" class="block px-3 py-2 rounded-xl text-base font-medium text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2 opacity-70"></i> ออกจากระบบ
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    }

    function toggleUserDropdown() {
        const dropdown = document.getElementById('user-menu-dropdown');
        const arrow = document.getElementById('user-menu-arrow');
        dropdown.classList.toggle('hidden');
        arrow.classList.toggle('rotate-180');
    }

    // Close Dropdown when clicking outside
    window.addEventListener('click', function(e) {
        const button = document.getElementById('user-menu-button');
        const dropdown = document.getElementById('user-menu-dropdown');
        const arrow = document.getElementById('user-menu-arrow');

        if (!button.contains(e.target) && !dropdown.contains(e.target)) {
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
                arrow.classList.remove('rotate-180');
            }
        }
    });
    
    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.classList.toggle('hidden');
    }

    // ปิดแจ้งเตือนเมื่อคลิกนอก
    window.addEventListener('click', function(e) {
        const bellButton = e.target.closest('button[onclick="toggleNotificationDropdown()"]');
        const dropdown = document.getElementById('notification-dropdown');

        if (!bellButton && dropdown && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

</script>