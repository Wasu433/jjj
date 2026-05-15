<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$recruitments = $latest_recruitments ?? [];
$stats = $stats ?? ['eligible' => 0, 'applied' => 0, 'accepted' => 0];
$history = $history ?? [];
$student_name = $_SESSION['name'] ?? 'นักศึกษา';

// รับค่าสถานะจาก Controller
$is_profile_complete = $is_profile_complete ?? false;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักนักศึกษา | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script> tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } } } } </script>
</head>
<body class="text-gray-800 font-sans min-h-screen bg-[#f9fafb]">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100 relative overflow-hidden">
            <div class="relative z-10">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">สวัสดี, <?php echo htmlspecialchars($student_name); ?></h1>
                <div class="mt-3"><span class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wide">Student</span></div>
            </div>
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-indigo-50 rounded-full opacity-50 blur-3xl"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-all">
                <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl"><i class="fas fa-clipboard-list"></i></div>
                <div><p class="text-gray-400 text-xs font-medium uppercase">ประกาศที่สมัครได้</p><h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['eligible']; ?> <span class="text-sm font-normal text-gray-400">รายการ</span></h3></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-all">
                <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl"><i class="fas fa-paper-plane"></i></div>
                <div><p class="text-gray-400 text-xs font-medium uppercase">สมัครแล้ว</p><h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['applied']; ?> <span class="text-sm font-normal text-gray-400">วิชา</span></h3></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-all">
                <div class="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center text-2xl"><i class="fas fa-check-circle"></i></div>
                <div><p class="text-gray-400 text-xs font-medium uppercase">ผ่านการคัดเลือก</p><h3 class="text-3xl font-bold text-gray-800"><?php echo $stats['accepted']; ?> <span class="text-sm font-normal text-gray-400">วิชา</span></h3></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="flex justify-between items-center px-1">
                    <h2 class="text-xl font-bold text-gray-800">ประกาศรับสมัครล่าสุด</h2>
                    <a href="index.php?page=student_recruitment_list" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">ดูทั้งหมด →</a>
                </div>

                <div class="space-y-4">
                    <?php if (empty($recruitments)): ?>
                        <div class="text-center py-10 bg-white rounded-2xl border border-dashed border-gray-300"><p class="text-gray-400">ยังไม่มีประกาศรับสมัคร</p></div>
                    <?php else: ?>
                        <?php foreach ($recruitments as $job): ?>
                        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:border-indigo-200 transition-all group">
                            <div class="flex flex-col sm:flex-row justify-between gap-4 items-center">
                                <div class="flex gap-4 items-center w-full sm:w-auto">
                                    <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-400 flex items-center justify-center text-xl group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors flex-shrink-0">
                                        <i class="fas fa-laptop-code"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-gray-800 group-hover:text-indigo-700 transition-colors truncate"><?php echo htmlspecialchars($job['subject_name']); ?></h3>
                                        <p class="text-sm text-gray-500 mt-1 truncate"><i class="fas fa-user-tie mr-1"></i> <?php echo htmlspecialchars($job['teacher_name']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                    
                                    <button onclick="openDetailModal(this)"
                                        data-title="<?php echo htmlspecialchars($job['title']); ?>"
                                        data-subject="<?php echo htmlspecialchars($job['subject_name']); ?>"
                                        data-code="<?php echo htmlspecialchars($job['subject_code']); ?>"
                                        data-teacher="<?php echo htmlspecialchars($job['teacher_name']); ?>"
                                        data-quota="<?php echo $job['quota']; ?>"
                                        data-grade="<?php echo $job['grade_requirement'] ?: '-'; ?>"
                                        data-desc="<?php echo htmlspecialchars($job['description'] ?: '-'); ?>"
                                        data-id="<?php echo $job['id']; ?>"
                                        data-sections='<?php echo htmlspecialchars($job['sections_data'] ?? ''); ?>'
                                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all active:scale-95 w-full sm:w-auto">
                                        ดูรายละเอียด
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

           <div class="space-y-6">
                 <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-50">สถานะการสมัคร</h3>
                    <div class="space-y-4">
                        <?php if (empty($history)): ?>
                            <div class="text-center py-6"><p class="text-sm text-gray-400">ยังไม่มีประวัติการสมัคร</p></div>
                        <?php else: ?>
                            <?php foreach ($history as $item): ?>
                                <?php 
                                    // 🔥 กำหนดสีตามสถานะ
                                    $statusConfig = match($item['status']) {
                                        'approved' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'dot' => 'bg-green-500', 'label' => 'ผ่านการคัดเลือก'],
                                        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'dot' => 'bg-red-500', 'label' => 'ไม่ผ่าน'],
                                        default    => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500', 'label' => 'รอผล']
                                    };
                                ?>
                            <div class="flex items-start justify-between group hover:bg-gray-50 p-2 rounded-lg transition-colors -mx-2">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1.5 w-2 h-2 rounded-full <?php echo $statusConfig['dot']; ?>"></div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800  w-28 group-hover:text-indigo-600 transition-colors">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-400"><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-1 rounded-md h-fit <?php echo $statusConfig['bg'] . ' ' . $statusConfig['text']; ?>">
                                    <?php echo $item['status']; // หรือใช้ $statusConfig['label'] ถ้าอยากโชว์ภาษาไทย ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </div>

    <div id="detailModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeDetailModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-xl flex-shrink-0"><i class="fas fa-info-circle"></i></div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900" id="detail_title">...</h3>
                            <p class="text-sm text-gray-500 mt-1" id="detail_subject">...</p>
                        </div>
                        <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-lg"></i></button>
                    </div>
                    <div class="mt-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100"><p class="text-xs text-gray-500 uppercase font-bold">อาจารย์</p><p class="text-sm font-medium text-gray-800 mt-1" id="detail_teacher">-</p></div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100"><p class="text-xs text-gray-500 uppercase font-bold">รับจำนวน</p><p class="text-sm font-medium text-gray-800 mt-1"><span id="detail_quota">0</span> คน</p></div>
                            <div class="col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-100"><p class="text-xs text-gray-500 uppercase font-bold">เกรดขั้นต่ำ</p><p class="text-sm font-medium text-indigo-600 mt-1" id="detail_grade">-</p></div>
                        </div>
                        <div><p class="text-xs text-gray-500 uppercase font-bold mb-2">รายละเอียด</p><div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-700 whitespace-pre-line" id="detail_desc">...</div></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                    <button onclick="switchToApplyModal()" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-sm">สมัครงานนี้</button>
                      <button onclick="closeDetailModal()" class="px-5 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-bold">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <div id="applyModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeApplyModal()"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="index.php?page=student_apply_process" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="recruitment_id" id="apply_recruitment_id">
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="mb-5 text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-3"><i class="fas fa-paper-plane text-indigo-600 text-lg"></i></div>
                            <h3 class="text-lg font-bold text-gray-900">ยื่นใบสมัคร</h3>
                            <p class="text-sm text-gray-500" id="apply_subject_name_label">...</p>
                        </div>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">เลือก Section (เลือกได้มากกว่า 1) <span class="text-red-500">*</span></label>
                                <div id="section_checkboxes" class="max-h-32 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 space-y-2"></div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">เกรดที่ได้ในรายวิชานี้ <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="gpa" required class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 outline-none appearance-none">
                                        <option value="" disabled selected>-- กรุณาเลือกเกรด --</option>
                                        <option value="A">A</option>
                                        <option value="B+">B+</option>
                                        <option value="B">B</option>
                                        <option value="C+">C+</option>
                                        <option value="C">C</option>
                                        <option value="D+">D+</option>
                                        <option value="D">D</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500"><i class="fas fa-chevron-down text-xs"></i></div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">แนบไฟล์ผลการเรียน (Transcript) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" name="grade_file" accept=".pdf,.jpg,.jpeg,.png" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-300 rounded-lg bg-white focus:outline-none">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">รองรับไฟล์ PDF, JPG, PNG (ขนาดไม่เกิน 5MB)</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md transition-all active:scale-95">ยืนยันการสมัคร</button>
                        <button type="button" onclick="closeApplyModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const isProfileComplete = <?php echo $is_profile_complete ? 'true' : 'false'; ?>;
        let currentJobData = {};

        function checkProfile() {
            if (!isProfileComplete) {
                Swal.fire({
                    title: 'กรุณากรอกข้อมูลส่วนตัว',
                    text: 'คุณต้องกรอกข้อมูลส่วนตัว (เช่น รหัสนักศึกษา) ให้ครบถ้วนก่อนทำการสมัคร',
                    icon: 'warning',
                    confirmButtonText: 'ไปที่หน้าข้อมูลส่วนตัว',
                    confirmButtonColor: '#4f46e5',
                    showCancelButton: true,
                    cancelButtonText: 'ไว้ทีหลัง'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'index.php?page=profile';
                    }
                });
                return false;
            }
            return true;
        }

        function openDetailModal(btn) {
            currentJobData = {
                id: btn.getAttribute('data-id'),
                title: btn.getAttribute('data-title'),
                subject: btn.getAttribute('data-subject'),
                code: btn.getAttribute('data-code'),
                teacher: btn.getAttribute('data-teacher'),
                quota: btn.getAttribute('data-quota'),
                grade: btn.getAttribute('data-grade'),
                desc: btn.getAttribute('data-desc'),
                sections: btn.getAttribute('data-sections')
            };
            document.getElementById('detail_title').innerText = currentJobData.title;
            document.getElementById('detail_subject').innerText = currentJobData.subject + ' (' + currentJobData.code + ')';
            document.getElementById('detail_teacher').innerText = currentJobData.teacher;
            document.getElementById('detail_quota').innerText = currentJobData.quota;
            document.getElementById('detail_grade').innerText = currentJobData.grade;
            document.getElementById('detail_desc').innerText = currentJobData.desc;
            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() { document.getElementById('detailModal').classList.add('hidden'); }

        function switchToApplyModal() {
            if (!checkProfile()) return;
            closeDetailModal();
            setTimeout(() => { openApplyModal(); }, 200);
        }

        function openApplyModal() {
            document.getElementById('apply_recruitment_id').value = currentJobData.id;
            document.getElementById('apply_subject_name_label').innerText = "วิชา: " + currentJobData.subject;
            const container = document.getElementById('section_checkboxes');
            container.innerHTML = '';
            if (currentJobData.sections) {
                const sections = currentJobData.sections.split(';;');
                sections.forEach(sec => {
                    const parts = sec.split('|'); 
                    const div = document.createElement('div');
                    div.className = "flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-100 shadow-sm hover:border-indigo-300 cursor-pointer select-none transition-all";
                    div.onclick = function(e) { if (e.target.type !== 'checkbox') { const cb = this.querySelector('input'); cb.checked = !cb.checked; } };
                    div.innerHTML = `<input type="checkbox" name="section_ids[]" value="${parts[0]}" class="mt-1 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer"><div class="text-sm"><span class="font-bold text-gray-800 block">Sec ${parts[1]}</span><span class="text-gray-500 text-xs">${parts[2]}</span></div>`;
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = '<p class="text-xs text-red-400 text-center py-4">ไม่พบข้อมูล Section</p>';
            }
            document.getElementById('applyModal').classList.remove('hidden');
        }

        function closeApplyModal() { document.getElementById('applyModal').classList.add('hidden'); }
    </script>

</body>
</html>