<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// รับค่าจาก Controller
$is_profile_complete = $is_profile_complete ?? false;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประกาศรับสมัครทั้งหมด | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
</head>

<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">ประกาศรับสมัครผู้ช่วยสอนทั้งหมด</h1>
            <p class="text-gray-500 mt-1 text-sm">ค้นหาและสมัครรายวิชาที่คุณสนใจ</p>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8">
            <form action="index.php" method="GET" class="flex gap-3">
                <input type="hidden" name="page" value="student_recruitment_list">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
                        placeholder="ค้นหารายวิชา หรือ รหัสวิชา...">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm">
                    ค้นหา
                </button>
            </form>
        </div>

        <div class="space-y-4">
            <?php if (empty($recruitments)): ?>
                <div class="text-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-folder-open text-gray-300 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">ไม่พบประกาศรับสมัคร</p>
                    <a href="index.php?page=student_recruitment_list" class="inline-block mt-4 text-indigo-600 text-sm font-medium hover:underline">ล้างคำค้นหา</a>
                </div>
            <?php else: ?>
                <?php foreach ($recruitments as $job): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:border-indigo-200 transition-all group relative overflow-hidden">
                        <div class="flex flex-col md:flex-row justify-between gap-6 items-start md:items-center">
                            <div class="flex gap-5 items-start">
                                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-laptop-code"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                            <?php echo htmlspecialchars($job['subject_name']); ?>
                                        </h3>
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-mono font-medium">
                                            <?php echo htmlspecialchars($job['subject_code']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-500 text-sm mb-2">
                                        <i class="fas fa-user-tie w-4 text-center mr-1"></i>
                                        อาจารย์: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($job['teacher_name']); ?></span>
                                        <span class="mx-2 text-gray-300">|</span>
                                        <span class="text-gray-400">รับ: <?php echo $job['quota']; ?> คน</span>
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                                            เกรดขั้นต่ำ <?php echo $job['grade_requirement'] ?: '-'; ?>
                                        </span>
                                        <?php if (!empty($job['is_applied']) && $job['is_applied'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                                <i class="fas fa-check-circle mr-1"></i> สมัครแล้ว
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <button onclick="directApply(this)"
                                    data-id="<?php echo $job['id']; ?>"
                                    data-subject="<?php echo htmlspecialchars($job['subject_name']); ?>"
                                    data-sections='<?php echo htmlspecialchars($job['sections_data'] ?? ''); ?>'
                                    data-applied='<?php echo htmlspecialchars($job['applied_section_ids'] ?? ''); ?>'
                                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all active:scale-95">
                                    สมัคร
                                </button>

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
                                    data-applied='<?php echo htmlspecialchars($job['applied_section_ids'] ?? ''); ?>'
                                    class="px-6 py-2.5 bg-white border border-indigo-100 text-indigo-600 rounded-xl text-sm font-bold hover:bg-indigo-50 hover:border-indigo-200 transition-all shadow-sm">
                                    ดูรายละเอียด
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase font-bold">อาจารย์</p>
                                <p class="text-sm font-medium text-gray-800 mt-1" id="detail_teacher">-</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase font-bold">รับจำนวน</p>
                                <p class="text-sm font-medium text-gray-800 mt-1"><span id="detail_quota">0</span> คน</p>
                            </div>
                            <div class="col-span-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <p class="text-xs text-gray-500 uppercase font-bold">เกรดขั้นต่ำ</p>
                                <p class="text-sm font-medium text-indigo-600 mt-1" id="detail_grade">-</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold mb-2">รายละเอียด</p>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 text-sm text-gray-700 whitespace-pre-line" id="detail_desc">...</div>
                        </div>
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
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-3">
                                <i class="fas fa-paper-plane text-indigo-600 text-lg"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">ยื่นใบสมัคร</h3>
                            <p class="text-sm text-gray-500" id="apply_subject_name_label">...</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">เลือก Section (เลือกได้มากกว่า 1) <span class="text-red-500">*</span></label>
                                <div id="section_checkboxes" class="max-h-48 overflow-y-auto border border-gray-200 rounded-xl p-2 bg-gray-50 space-y-2">
                                    </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">เกรดที่ได้ในรายวิชานี้ <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="grade" required class="block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 outline-none appearance-none">
                                        <option value="" disabled selected>-- กรุณาเลือกเกรด --</option>
                                        <option value="A">A</option>
                                        <option value="B+">B+</option>
                                        <option value="B">B</option>
                                        <option value="C+">C+</option>
                                        <option value="C">C</option>
                                        <option value="D+">D+</option>
                                        <option value="D">D</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">แนบไฟล์ผลการเรียน (Transcript) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" name="grade_file" accept=".pdf,.jpg,.jpeg,.png" required
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-300 rounded-lg bg-white focus:outline-none">
                                </div>
                                <p class="text-xs text-gray-400 mt-1">รองรับไฟล์ PDF, JPG, PNG (ขนาดไม่เกิน 5MB)</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all active:scale-95">
                            ยืนยันการสมัคร
                        </button>
                        <button type="button" onclick="closeApplyModal()"
                            class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors">
                            ยกเลิก
                        </button>
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
                    text: 'คุณต้องกรอกข้อมูลส่วนตัว (ชื่อ, รหัสนักศึกษา) ให้ครบถ้วนก่อนทำการสมัคร',
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
                sections: btn.getAttribute('data-sections'),
                applied: btn.getAttribute('data-applied') // รับค่าที่เคยสมัครไปแล้ว
            };

            document.getElementById('detail_title').innerText = currentJobData.title;
            document.getElementById('detail_subject').innerText = currentJobData.subject + ' (' + currentJobData.code + ')';
            document.getElementById('detail_teacher').innerText = currentJobData.teacher;
            document.getElementById('detail_quota').innerText = currentJobData.quota;
            document.getElementById('detail_grade').innerText = currentJobData.grade;
            document.getElementById('detail_desc').innerText = currentJobData.desc;

            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        function directApply(btn) {
            if (!checkProfile()) return;

            currentJobData = {
                id: btn.getAttribute('data-id'),
                subject: btn.getAttribute('data-subject'),
                sections: btn.getAttribute('data-sections'),
                applied: btn.getAttribute('data-applied') // รับค่าที่เคยสมัครไปแล้ว
            };
            openApplyModal();
        }

        function switchToApplyModal() {
            if (!checkProfile()) return;

            closeDetailModal();
            setTimeout(() => {
                openApplyModal();
            }, 200);
        }

        function openApplyModal() {
            document.getElementById('apply_recruitment_id').value = currentJobData.id;
            document.getElementById('apply_subject_name_label').innerText = "วิชา: " + currentJobData.subject;

            const container = document.getElementById('section_checkboxes');
            container.innerHTML = '';

            // แปลง id ที่เคยสมัครมาเป็น Array ตัวเลข
            const appliedIds = currentJobData.applied ? currentJobData.applied.split(',').map(id => id.trim()) : [];

            if (currentJobData.sections) {
                const sections = currentJobData.sections.split(';;');
                sections.forEach(sec => {
                    const parts = sec.split('|');
                    const id = parts[0];
                    const name = parts[1];
                    const time = parts[2];
                    const quota = parseInt(parts[3]) || 0;
                    const enrolled = parseInt(parts[4]) || 0;

                    // เช็ค 2 เงื่อนไข: เต็ม หรือ เคยสมัครแล้ว
                    const isFull = enrolled >= quota;
                    const hasApplied = appliedIds.includes(id); 

                    // ✅ ปรับสีและการคลิกตามสถานะ
                    let containerClass = "flex items-start gap-3 p-3 bg-white rounded-lg border border-gray-100 shadow-sm hover:border-indigo-300 cursor-pointer select-none transition-all";
                    let isDisableBox = false;

                    if (isFull || hasApplied) {
                        containerClass = "flex items-start gap-3 p-3 bg-gray-100/80 rounded-lg border border-gray-200 cursor-not-allowed opacity-70";
                        isDisableBox = true;
                    }
                    
                    const div = document.createElement('div');
                    div.className = containerClass;
                    
                    if (!isDisableBox) {
                        div.onclick = function(e) {
                            if (e.target.type !== 'checkbox') {
                                const cb = this.querySelector('input');
                                cb.checked = !cb.checked;
                            }
                        };
                    }

                    // สร้างป้ายสถานะ
                    let statusBadge = '';
                    if (hasApplied) {
                        statusBadge = `<span class="ml-auto text-[11px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100"><i class="fas fa-check mr-1"></i>สมัครแล้ว</span>`;
                    } else if (isFull) {
                        statusBadge = `<span class="ml-auto text-[11px] font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded border border-red-100">เต็มแล้ว (${enrolled}/${quota})</span>`;
                    } else {
                        statusBadge = `<span class="ml-auto text-[11px] font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded">ว่าง (${enrolled}/${quota})</span>`;
                    }

                    div.innerHTML = `
                        <input type="checkbox" name="section_ids[]" value="${id}" 
                            class="mt-1 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 cursor-pointer disabled:bg-gray-300 disabled:cursor-not-allowed"
                            ${isDisableBox ? 'disabled' : ''}>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-800 text-sm block">Sec ${name}</span>
                                ${statusBadge}
                            </div>
                            <span class="text-gray-500 text-xs flex items-center gap-1 mt-0.5">
                                <i class="far fa-clock"></i> ${time}
                            </span>
                        </div>
                    `;
                    container.appendChild(div);
                });
            } else {
                container.innerHTML = '<p class="text-xs text-red-400 text-center py-4">ไม่พบข้อมูล Section</p>';
            }

            document.getElementById('applyModal').classList.remove('hidden');
        }

        function closeApplyModal() {
            document.getElementById('applyModal').classList.add('hidden');
        }
    </script>

</body>

</html>