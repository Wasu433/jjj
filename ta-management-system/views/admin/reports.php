<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../app/Config/Database.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php"); exit;
}

// ----------------------------------------------------
// 1. ดึงข้อมูลตัวเลขสรุป (Summary Cards)
// ----------------------------------------------------
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
$total_jobs = $pdo->query("SELECT COUNT(*) FROM recruitments WHERE status != 'deleted'")->fetchColumn();
$total_apps = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// ----------------------------------------------------
// 2. ดึงข้อมูลสำหรับกราฟ (Charts)
// ----------------------------------------------------

// A. สัดส่วนผู้ใช้งาน (Pie Chart)
$sql_roles = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$roles_data = $pdo->query($sql_roles)->fetchAll(PDO::FETCH_KEY_PAIR);
// แปลงให้เป็น JSON เพื่อส่งให้ JS
$js_roles_labels = json_encode(array_keys($roles_data));
$js_roles_data = json_encode(array_values($roles_data));

// B. สถานะการสมัคร (Doughnut Chart)
$sql_status = "SELECT status, COUNT(*) as count FROM applications GROUP BY status";
$status_data = $pdo->query($sql_status)->fetchAll(PDO::FETCH_KEY_PAIR);
// Map ชื่อไทยให้สวยงาม
$status_mapping = ['pending'=>'รอพิจารณา', 'accepted'=>'ผ่านการคัดเลือก', 'rejected'=>'ไม่ผ่าน'];
$labels_status = [];
$data_status = [];
foreach($status_data as $key => $val){
    $labels_status[] = $status_mapping[$key] ?? $key;
    $data_status[] = $val;
}
$js_status_labels = json_encode($labels_status);
$js_status_data = json_encode($data_status);

// C. 5 อันดับวิชายอดฮิต (Bar Chart) - Join 3 ตาราง
$sql_top_subjects = "SELECT s.code, s.name, COUNT(a.id) as total 
                     FROM applications a
                     JOIN recruitments r ON a.recruitment_id = r.id
                     JOIN subjects s ON r.subject_id = s.id
                     GROUP BY s.id 
                     ORDER BY total DESC 
                     LIMIT 5";
$top_subjects = $pdo->query($sql_top_subjects)->fetchAll(PDO::FETCH_ASSOC);

$subj_labels = [];
$subj_data = [];
foreach($top_subjects as $row){
    $subj_labels[] = $row['code'] . " " . $row['name']; // รหัส+ชื่อวิชา
    $subj_data[] = $row['total'];
}
$js_subj_labels = json_encode($subj_labels);
$js_subj_data = json_encode($subj_data);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสถิติ | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script> tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } } } } </script>
    <style> body { background-color: #f9fafb; background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px; } </style>
</head>
<body class="text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-chart-line text-indigo-600 mr-2"></i> รายงานสถิติภาพรวม
            </h1>
            <p class="text-gray-500 text-sm mt-1">ข้อมูลสรุปการใช้งานระบบและสถิติการรับสมัคร TA</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase">ผู้ใช้งานทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_users); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-users"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase">รายวิชาในระบบ</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_subjects); ?></h3>
                </div>
                <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-book"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase">ประกาศรับสมัคร</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_jobs); ?></h3>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-bullhorn"></i></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-medium uppercase">ใบสมัครทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo number_format($total_apps); ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6">5 อันดับวิชาที่มีคนสมัครมากที่สุด</h3>
                <div class="relative h-72 w-full">
                    <canvas id="topSubjectsChart"></canvas>
                </div>
            </div>

            <div class="space-y-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">สัดส่วนผู้ใช้งาน</h3>
                    <div class="relative h-48 w-full flex justify-center">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">สถานะการคัดเลือก</h3>
                    <div class="relative h-48 w-full flex justify-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // 1. Top Subjects Bar Chart
        const ctxBar = document.getElementById('topSubjectsChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo $js_subj_labels; ?>,
                datasets: [{
                    label: 'จำนวนผู้สมัคร (คน)',
                    data: <?php echo $js_subj_data; ?>,
                    backgroundColor: '#4f46e5',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });

        // 2. Role Pie Chart
        const ctxPie = document.getElementById('roleChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?php echo $js_roles_labels; ?>,
                datasets: [{
                    data: <?php echo $js_roles_data; ?>,
                    backgroundColor: ['#22c55e', '#6366f1', '#ef4444'], // เขียว, ม่วง, แดง
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });

        // 3. Status Doughnut Chart
        const ctxDoughnut = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: <?php echo $js_status_labels; ?>,
                datasets: [{
                    data: <?php echo $js_status_data; ?>,
                    backgroundColor: ['#eab308', '#22c55e', '#ef4444'], // เหลือง, เขียว, แดง
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } }
            }
        });
    </script>

</body>
</html>