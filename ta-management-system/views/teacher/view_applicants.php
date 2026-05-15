<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// รับค่าตัวแปร $applicants, $subjects มาจาก Controller
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อผู้สมัคร | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script> tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } } } } </script>
</head>
<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100">
            <h1 class="text-2xl font-bold text-gray-900">รายชื่อผู้สมัครทั้งหมด</h1>
            <p class="text-gray-500 mt-1 text-sm">จัดการและประเมินผู้สมัครตำแหน่งผู้ช่วยสอน</p>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="hidden" name="page" value="view_applicants">
                
                <div class="w-full md:w-1/4 relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">วิชา</label>
                    <select name="subject_id" onchange="this.form.submit()" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none text-gray-600">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['id']; ?>" <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $sub['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub['code'] . ' ' . $sub['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <div class="w-full md:w-1/4 relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">สถานะ</label>
                    <select name="status" onchange="this.form.submit()" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none text-gray-600">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>รอพิจารณา</option>
                        <option value="approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'approved') ? 'selected' : ''; ?>>ผ่านการคัดเลือก</option>
                        <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status'] == 'rejected') ? 'selected' : ''; ?>>ไม่ผ่าน</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <div class="relative flex-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1 ml-1">ค้นหา</label>
                    <span class="absolute inset-y-0 left-0 top-4 pl-3 flex items-center text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all" 
                           placeholder="ชื่อ หรือรหัสนักศึกษา...">
                </div>
                
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 shadow-sm">ค้นหา</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold">
                            <th class="py-4 px-6 w-16 text-center">ลำดับ</th>
                            <th class="py-4 px-6">ชื่อ-นามสกุล / รหัสนศ.</th>
                            <th class="py-4 px-6">วิชาที่สมัคร</th>
                            <th class="py-4 px-6">Section / เวลา</th>
                            <th class="py-4 px-6 text-center">เกรด</th>
                            <th class="py-4 px-6">วันที่สมัคร</th>
                            <th class="py-4 px-6 text-center">สถานะ</th>
                            <th class="py-4 px-6 text-center whitespace-nowrap">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($applicants)): ?>
                            <tr><td colspan="8" class="py-10 text-center text-gray-400">ไม่พบข้อมูลผู้สมัคร</td></tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $index => $row): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6 text-center text-gray-400"><?php echo $index + 1; ?></td>
                                
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                            <?php echo mb_substr($row['student_name'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900"><?php echo htmlspecialchars($row['student_name']); ?></p>
                                            <p class="text-xs text-gray-500 mt-0.5 font-mono">
                                                <i class="fas fa-id-card mr-1"></i> <?php echo htmlspecialchars($row['student_id'] ?? '-'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-6">
                                    <span class="text-indigo-600 font-bold text-xs"><?php echo htmlspecialchars($row['subject_code']. ' - '. $row['revisioncode']); ?></span>
                                    <p class="text-gray-700 font-medium"><?php echo htmlspecialchars($row['subject_name']); ?></p>
                                </td>

                                <td class="py-4 px-6">
                                    <?php if (!empty($row['section_name'])): ?>
                                        <div class="font-bold text-gray-800">Sec <?php echo htmlspecialchars($row['section_name']); ?></div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            <i class="far fa-clock"></i> <?php echo htmlspecialchars($row['schedule_time']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">- ไม่ระบุ -</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-4 px-6 text-center font-bold text-gray-800">
                                    <?php echo htmlspecialchars($row['student_grade'] ?? '-'); ?>
                                </td>

                                <td class="py-4 px-6 text-gray-500 text-xs">
                                    <?php echo date('d/m/Y', strtotime($row['created_at'])); ?><br>
                                    <?php echo date('H:i', strtotime($row['created_at'])); ?> น.
                                </td>

                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <?php 
                                        if($row['status'] == 'pending') echo '<span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold inline-block">รอพิจารณา</span>';
                                        elseif($row['status'] == 'approved') echo '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold inline-block">อนุมัติแล้ว</span>';
                                        else echo '<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold inline-block">ปฏิเสธ</span>';
                                    ?>
                                </td>

                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <div class="flex justify-center items-center gap-2">
                                        
                                        <button onclick="openInfoModal(this)"
                                            data-name="<?php echo htmlspecialchars($row['student_name']); ?>"
                                            data-id="<?php echo htmlspecialchars($row['student_id']); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                            data-subject="<?php echo htmlspecialchars($row['subject_name']); ?>"
                                            data-section="<?php echo htmlspecialchars($row['section_name'] ?? '-'); ?>"
                                            data-grade="<?php echo htmlspecialchars($row['student_grade'] ?? '-'); ?>"
                                            data-file="<?php echo htmlspecialchars($row['grade_file'] ?? ''); ?>"
                                            class="px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold hover:bg-blue-100 transition cursor-pointer">
                                            ข้อมูล
                                        </button>

                                        <?php if ($row['status'] == 'pending'): ?>
                                            <form id="form-approve-<?php echo $row['app_id']; ?>" action="index.php?page=teacher_update_status" method="POST" class="m-0 flex">
                                                <input type="hidden" name="application_id" value="<?php echo $row['app_id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="recruitment_id" value="ALL">
                                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                <button type="button" 
                                                        onclick="confirmUpdate(<?php echo $row['app_id']; ?>, 'approved', '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                                        class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                                    รับ
                                                </button>
                                            </form>

                                            <form id="form-reject-<?php echo $row['app_id']; ?>" action="index.php?page=teacher_update_status" method="POST" class="m-0 flex">
                                                <input type="hidden" name="application_id" value="<?php echo $row['app_id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <input type="hidden" name="recruitment_id" value="ALL">
                                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                <button type="button" 
                                                        onclick="confirmUpdate(<?php echo $row['app_id']; ?>, 'rejected', '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                                        class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                                    ไม่รับ
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form id="form-undo-<?php echo $row['app_id']; ?>" action="index.php?page=teacher_update_status" method="POST" class="m-0 flex">
                                                <input type="hidden" name="application_id" value="<?php echo $row['app_id']; ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <input type="hidden" name="recruitment_id" value="ALL">
                                                <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                <button type="button" 
                                                        onclick="confirmUpdate(<?php echo $row['app_id']; ?>, 'pending', '<?php echo htmlspecialchars($row['student_name']); ?>')"
                                                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors">
                                                    <i class="fas fa-undo mr-1"></i> ยกเลิกสถานะ
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
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

    <div id="infoModal" class="hidden fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeInfoModal()"></div>
            <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold flex-shrink-0">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900" id="modal_name">...</h3>
                            <p class="text-sm text-gray-500" id="modal_id">...</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-xl p-4 space-y-3 text-sm border border-gray-100">
                        <div class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">อีเมล</span>
                            <span class="font-medium text-gray-800" id="modal_email">...</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">วิชาที่สมัคร</span>
                            <span class="font-medium text-gray-800" id="modal_subject">...</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-200 pb-2">
                            <span class="text-gray-500">Section</span>
                            <span class="font-medium text-gray-800" id="modal_section">...</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">เกรดที่ได้</span>
                            <span class="font-bold text-indigo-600" id="modal_grade">...</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <a id="modal_file_btn" href="#" target="_blank" class="flex items-center justify-center w-full px-4 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition shadow-sm gap-2 active:scale-95">
                            <i class="fas fa-file-pdf"></i> ดูเกรดรายวิชานี้ 
                        </a>
                        <p id="modal_no_file" class="hidden text-center text-red-400 text-sm mt-2 bg-red-50 py-2 rounded-lg">ไม่พบไฟล์แนบ</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button onclick="closeInfoModal()" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-100 transition">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชัน Popup ยืนยันการรับ/ไม่รับ
        function confirmUpdate(appId, status, studentName) {
            let title, text, color, btnText;

            if (status === 'approved') {
                title = 'ยืนยันการรับเข้าเป็น TA?';
                text = `คุณต้องการรับ "${studentName}" เข้าเป็นผู้ช่วยสอนใช่หรือไม่?`;
                color = '#10b981'; // สีเขียว
                btnText = 'ยืนยัน, รับเลย!';
            } else if (status === 'rejected') {
                title = 'ยืนยันการปฏิเสธ?';
                text = `คุณต้องการปฏิเสธ "${studentName}" ใช่หรือไม่?`;
                color = '#ef4444'; // สีแดง
                btnText = 'ยืนยัน, ไม่รับ';
            } else {
                title = 'ยกเลิกสถานะ?';
                text = `คุณต้องการเปลี่ยนสถานะของ "${studentName}" กลับไปเป็น "รอพิจารณา" ใช่หรือไม่?`;
                color = '#6b7280'; // สีเทา
                btnText = 'ใช่, ยกเลิกสถานะ';
            }

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: '#6b7280',
                confirmButtonText: btnText,
                cancelButtonText: 'ยกเลิก',
                background: '#fff',
                customClass: {
                    title: 'font-bold text-gray-800',
                    popup: 'rounded-2xl shadow-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งแบบฟอร์มเมื่อกดยืนยัน
                    let formId;
                    if (status === 'approved') formId = `form-approve-${appId}`;
                    else if (status === 'rejected') formId = `form-reject-${appId}`;
                    else formId = `form-undo-${appId}`;

                    document.getElementById(formId).submit();
                }
            });
        }

        // ฟังก์ชัน Modal ข้อมูล (เหมือนเดิม)
        function openInfoModal(btn) {
            const name = btn.getAttribute('data-name');
            const id = btn.getAttribute('data-id');
            const email = btn.getAttribute('data-email');
            const subject = btn.getAttribute('data-subject');
            const section = btn.getAttribute('data-section');
            const grade = btn.getAttribute('data-grade');
            const file = btn.getAttribute('data-file');

            document.getElementById('modal_name').innerText = name;
            document.getElementById('modal_id').innerText = 'รหัส: ' + id;
            document.getElementById('modal_email').innerText = email;
            document.getElementById('modal_subject').innerText = subject;
            document.getElementById('modal_section').innerText = 'Sec ' + section;
            document.getElementById('modal_grade').innerText = grade;

            const fileBtn = document.getElementById('modal_file_btn');
            const noFileMsg = document.getElementById('modal_no_file');

            if (file && file !== 'NULL' && file !== '') {
                fileBtn.href = file;
                fileBtn.classList.remove('hidden');
                noFileMsg.classList.add('hidden');
            } else {
                fileBtn.classList.add('hidden');
                noFileMsg.classList.remove('hidden');
            }

            document.getElementById('infoModal').classList.remove('hidden');
        }

        function closeInfoModal() {
            document.getElementById('infoModal').classList.add('hidden');
        }
    </script>

</body>
</html>