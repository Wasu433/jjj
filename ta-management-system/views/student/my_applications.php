<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสมัคร | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } }
            }
        }
    </script>
</head>
<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 mb-6 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 text-2xl">
                <i class="fas fa-history"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">ประวัติการสมัคร</h1>
                <p class="text-gray-500 mt-1 text-sm">รายการสมัครงานผู้ช่วยสอนทั้งหมดของคุณ</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold tracking-wider">
                            <th class="py-4 px-6 w-16 text-center">#</th>
                            <th class="py-4 px-6">วิชา / ตำแหน่ง</th>
                            <th class="py-4 px-6">Section / เวลาเรียน</th>
                            <th class="py-4 px-6">วันที่สมัคร</th>
                            <th class="py-4 px-6 text-center">สถานะ</th>
                            <th class="py-4 px-6 text-right">เพิ่มเติม</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="6" class="py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-file-alt text-2xl text-gray-300"></i>
                                        </div>
                                        <p class="font-medium">ยังไม่มีประวัติการสมัคร</p>
                                        <a href="index.php?page=student_recruitment_list" class="text-indigo-600 text-xs mt-2 hover:underline">ไปดูประกาศรับสมัคร</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $index => $app): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-6 text-center text-gray-400 font-medium"><?php echo $index + 1; ?></td>
                                <td class="py-4 px-6">
                                    <div>
                                        <span class="text-indigo-600 font-bold text-xs bg-indigo-50 px-2 py-0.5 rounded-md mb-1 inline-block">
                                            <?php echo htmlspecialchars($app['subject_code']); ?>
                                        </span>
                                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($app['subject_name']); ?></p>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            <i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($app['teacher_name']); ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <?php if (!empty($app['section_name'])): ?>
                                        <div class="text-gray-700 font-medium text-sm">
                                            Sec <?php echo htmlspecialchars($app['section_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                                            <i class="far fa-clock"></i> <?php echo htmlspecialchars($app['schedule_time']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">- ไม่ระบุ -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-gray-600">
                                    <?php echo date('d/m/Y', strtotime($app['created_at'])); ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($app['status'] == 'approved' || $app['status'] == 'accepted'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-green-50 text-green-600 border border-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> ผ่านการคัดเลือก
                                        </span>
                                    <?php elseif ($app['status'] == 'rejected'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-red-50 text-red-600 border border-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> ไม่ผ่าน
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-yellow-50 text-yellow-600 border border-yellow-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> รอผลการพิจารณา
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <?php if ($app['status'] == 'pending'): ?>
                                        <button onclick="confirmCancel('<?php echo $app['id']; ?>', '<?php echo htmlspecialchars($app['subject_name']); ?>')" 
                                                class="inline-flex items-center gap-1 text-red-500 hover:text-red-700 transition-colors text-sm font-medium border border-red-200 hover:border-red-300 rounded-lg px-3 py-1.5 bg-red-50 hover:bg-red-100">
                                            <i class="fas fa-times-circle"></i> ยกเลิก
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-xs">-</span>
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
        function confirmCancel(appId, subjectName) {
            Swal.fire({
                title: 'ยืนยันการยกเลิก?',
                text: "คุณต้องการยกเลิกการสมัครวิชา " + subjectName + " ใช่หรือไม่?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#e5e7eb',
                cancelButtonText: '<span style="color:black">ปิด</span>',
                confirmButtonText: 'ใช่, ยกเลิกเลย'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?page=student_cancel_application&id=' + appId;
                }
            })
        }
    </script>

</body>
</html>