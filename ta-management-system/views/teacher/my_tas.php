<?php

if (session_status() === PHP_SESSION_NONE) session_start();
// รับค่าตัวแปร $tas, $subjects, $semesters, $total_tas... จาก Controller

// ==========================================
// 🌟 นำข้อมูล $tas มาจัดกลุ่มตาม "รายวิชา"
// ==========================================
$grouped_subjects = [];
if (!empty($tas)) {
    foreach ($tas as $ta) {
        $code = $ta['subject_code'];
        // ถ้ายังไม่มีวิชานี้ใน Array ให้สร้างชุดข้อมูลใหม่
        if (!isset($grouped_subjects[$code])) {
            $grouped_subjects[$code] = [
                'subject_code' => $ta['subject_code'],
                'revisioncode' => $ta['revisioncode'], // ⭐ เพิ่ม Revision Code
                'subject_name' => $ta['subject_name'],
                'students' => []
            ];
        }
        // เอาข้อมูลเด็กยัดเข้าไปในวิชานั้นๆ
        $grouped_subjects[$code]['students'][] = $ta;
    }
}
// ดึง Section ของวิชาที่อาจารย์สอน
$stmtSec = $pdo->prepare("
    SELECT 
        sec.id,
        sec.name,
        sec.schedule_time,
        s.code AS subject_code,s.revisioncode
    FROM sections sec
    JOIN subjects s ON sec.subject_id = s.id
    JOIN recruitments r ON r.subject_id = s.id
    WHERE r.teacher_id = ?
");

$stmtSec->execute([$teacher_id]);
$sections = $stmtSec->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผู้ช่วยสอนของฉัน | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
</head>

<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100">
            <h1 class="text-2xl font-bold text-gray-900">ผู้ช่วยสอนของฉัน</h1>
            <p class="text-gray-500 mt-1 text-sm">จัดการผู้ช่วยสอน แบ่งตามรายวิชาที่รับผิดชอบ</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500 flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-users"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_tas; ?></h3>
                    <p class="text-gray-400 text-xs">ผู้ช่วยสอนทั้งหมด</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500 flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-book"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo count($grouped_subjects); ?></h3>
                    <p class="text-gray-400 text-xs">จำนวนรายวิชา</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500 flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-50 text-yellow-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-clock"></i></div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_sections; ?></h3>
                    <p class="text-gray-400 text-xs">ห้องปฏิบัติการ (Sec)</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <form action="index.php" method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="hidden" name="page" value="my_tas">

                <div class="w-full md:w-1/4 relative">
                    <select name="subject_id" onchange="this.form.submit()" class="w-full pl-4 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 appearance-none text-gray-600">
                        <option value="">วิชา: ทั้งหมด</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['id']; ?>" <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $sub['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub['code'] . ' ' . $sub['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                </div>

                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                        placeholder="ค้นหาจากชื่อ หรือรหัสนักศึกษา...">
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
                            <th class="py-4 px-6">รหัสวิชา</th>
                            <th class="py-4 px-6">ชื่อรายวิชา</th>
                            <th class="py-4 px-6 text-center">จำนวน TA ที่รับแล้ว</th>
                            <th class="py-4 px-6 text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-gray-50">
                        <?php if (empty($grouped_subjects)): ?>
                            <tr>
                                <td colspan="5" class="py-10 text-center text-gray-400">ยังไม่มีข้อมูลรายวิชาที่มีผู้ช่วยสอน</td>
                            </tr>
                        <?php else: ?>
                            <?php $index = 1;
                            foreach ($grouped_subjects as $code => $subjectData): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-6 text-center text-gray-400 font-medium"><?php echo $index++; ?></td>

                                    <td class="py-4 px-6 font-bold text-indigo-600">
                                        <?php echo htmlspecialchars($subjectData['subject_code']. ' - '. $subjectData['revisioncode']); ?>
                                    </td>

                                    <td class="py-4 px-6 font-medium text-gray-800">
                                        <?php echo htmlspecialchars($subjectData['subject_name']); ?>
                                    </td>

                                    <td class="py-4 px-6 text-center">
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-xs font-bold">
                                            <i class="fas fa-user-check mr-1"></i> <?php echo count($subjectData['students']); ?> คน
                                        </span>
                                    </td>

                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <div class="flex justify-end gap-2 items-center">

                                            <button onclick="openSubjectModal('<?php echo htmlspecialchars($code); ?>')"
                                                class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold hover:bg-indigo-100 transition shadow-sm flex items-center gap-1.5">
                                                <i class="fas fa-list-ul"></i> ดูรายชื่อ TA
                                            </button>

                                            <button
                                                onclick="openExportModal('<?php echo $subjectData['subject_code']; ?>','<?php echo htmlspecialchars($subjectData['subject_name']); ?>')"
                                                class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-xs font-bold hover:bg-blue-100 transition shadow-sm flex items-center gap-1.5">

                                                <i class="fas fa-file-pdf"></i> Export PDF
                                            </button>

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

    <div id="subjectModal" class="hidden fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm" onclick="closeSubjectModal()"></div>

            <div class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-4xl w-full border border-gray-100">

                <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-white" id="modal_sub_title">รายชื่อผู้ช่วยสอน</h3>
                        <p class="text-indigo-100 text-sm" id="modal_sub_code">วิชา...</p>
                    </div>
                    <button onclick="closeSubjectModal()" class="text-indigo-200 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="p-6">
                    <div class="overflow-x-auto border border-gray-100 rounded-xl shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500 text-xs uppercase font-semibold">
                                    <th class="py-3 px-4 w-12 text-center">#</th>
                                    <th class="py-3 px-4">ชื่อ-นามสกุล / รหัสนศ.</th>
                                    <th class="py-3 px-4">Section / เวลา</th>
                                    <th class="py-3 px-4 text-center">เกรด</th>
                                    <th class="py-3 px-4 text-right">ถอดถอน</th>
                                </tr>
                            </thead>
                            <tbody id="modal_student_list" class="text-sm divide-y divide-gray-50">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button onclick="closeSubjectModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-100 transition shadow-sm">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>
    <div id="exportModal" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4 sm:p-0">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeExportModal()"></div>
        
        <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl relative z-10 overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/80">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-50 text-red-500 flex items-center justify-center text-xl shadow-sm border border-red-100">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">กรอกข้อมูลก่อน Export PDF</h3>
                        <p class="text-xs text-gray-500 mt-0.5">ระบุรายละเอียดการปฏิบัติงานเพื่อนำไปสร้างเอกสาร</p>
                    </div>
                </div>
                <button type="button" onclick="closeExportModal()" class="text-gray-400 hover:text-gray-700 transition-colors w-8 h-8 flex items-center justify-center rounded-xl hover:bg-gray-200">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                <form id="exportForm" action="index.php" method="GET" target="_blank" class="space-y-5">
                    
                    <input type="hidden" name="page" value="export_my_tas_pdf">
                    <input type="hidden" name="subject_code" id="export_subject_code">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">รายวิชา</label>
                            <input id="export_subject_name" name="subject_name" 
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-100 text-sm text-gray-600 cursor-not-allowed outline-none" 
                                readonly>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1.5">ประเภทผู้ช่วย <span class="text-red-500">*</span></label>
                            <select name="assistant_type" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all hover:border-gray-400 cursor-pointer bg-white" required>
                                <option value="LAB">นักศึกษาช่วยคุม (Lab Boy)</option>
                                <option value="TA">นักศึกษาช่วยสอน (TA)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Section ที่ปฏิบัติงาน <span class="text-red-500">*</span></label>
                        <select name="section_id" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all hover:border-gray-400 cursor-pointer bg-white" required>
                            <option value="">-- กรุณาเลือก Section --</option>
                            </select>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-sm font-bold text-gray-700">ประวัติการปฏิบัติงาน (รายเดือน) <span class="text-red-500">*</span></label>
                            
                            <button type="button" onclick="addWorkRow()" class="px-3 py-1.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-lg text-xs font-bold hover:bg-indigo-100 transition-colors flex items-center gap-1.5 shadow-sm active:scale-95">
                                <i class="fas fa-plus"></i> เพิ่มเดือน
                            </button>
                        </div>

                        <div class="grid grid-cols-12 gap-2 px-3 py-2 bg-gray-50 rounded-lg text-xs font-bold text-gray-500 text-center mb-2 border border-gray-100">
                            <div class="col-span-3 text-left">เดือน</div>
                            <div class="col-span-2">ปี (พ.ศ.)</div>
                        
                            <div class="col-span-4 text-left">วันที่ทำ (เช่น 1, 8, 15)</div>
                            <div class="col-span-1">ลบ</div>
                        </div>

                        <div id="workRows" class="space-y-2"></div>
                        
                    </div>
                </form>
            </div>

            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-100 flex justify-end gap-3 rounded-b-3xl">
                <button type="button" onclick="closeExportModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-50 transition-colors shadow-sm">
                    ยกเลิก
                </button>
                <button type="button" onclick="document.getElementById('exportForm').submit()" class="px-6 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 shadow-md shadow-red-500/30 transition-all active:scale-95 flex items-center gap-2">
                    <i class="fas fa-file-export"></i> สร้างเอกสาร PDF
                </button>
            </div>
        </div>
    </div>

    <template id="workRowTemplate">
        <div class="grid grid-cols-12 gap-2 items-center workRow bg-white border border-gray-200 p-2 rounded-xl shadow-sm hover:border-indigo-300 transition-colors group">
            
            <select name="month[]" class="col-span-3 border border-gray-300 rounded-lg px-2 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 hover:bg-white transition-colors cursor-pointer" required>
                <option value="ม.ค.">มกราคม</option>
                <option value="ก.พ.">กุมภาพันธ์</option>
                <option value="มี.ค.">มีนาคม</option>
                <option value="เม.ย.">เมษายน</option>
                <option value="พ.ค.">พฤษภาคม</option>
                <option value="มิ.ย.">มิถุนายน</option>
                <option value="ก.ค.">กรกฎาคม</option>
                <option value="ส.ค.">สิงหาคม</option>
                <option value="ก.ย.">กันยายน</option>
                <option value="ต.ค.">ตุลาคม</option>
                <option value="พ.ย.">พฤศจิกายน</option>
                <option value="ธ.ค.">ธันวาคม</option>
            </select>

            <input type="number" name="year[]" placeholder="เช่น 2568" class="col-span-2 border border-gray-300 rounded-lg px-2 py-2 text-xs text-center focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 hover:bg-white transition-colors" required min="2500" max="2600">


            <input type="text" name="dates[]" placeholder="เช่น 1, 8, 15, 22" class="col-span-4 border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 hover:bg-white transition-colors" required>

            <button type="button" onclick="this.closest('.workRow').remove()" class="col-span-1 h-full flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="ลบแถวนี้">
                <i class="fas fa-trash-alt"></i>
            </button>

        </div>
    </template>
    <script>
        let maxWorkRows = 5;

        function addWorkRow() {

            const container = document.getElementById("workRows");
            const rows = container.querySelectorAll(".workRow");

            if (rows.length >= maxWorkRows) {
                Swal.fire({
                    icon: 'warning',
                    title: 'เพิ่มได้สูงสุด 5 เดือน'
                });
                return;
            }

            const template = document.getElementById("workRowTemplate");
            const clone = template.content.cloneNode(true);

            container.appendChild(clone);
        }

        function removeWorkRow(btn) {
            btn.closest(".workRow").remove();
        }

        // เพิ่มแถวแรกตอนเปิด modal
        function initWorkRows() {

            const container = document.getElementById("workRows");
            container.innerHTML = "";

            addWorkRow();
        }
        const sections = <?php echo json_encode($sections, JSON_UNESCAPED_UNICODE); ?>;

        function openExportModal(code, name) {

            document.getElementById("export_subject_code").value = code
            document.getElementById("export_subject_name").value = name

            const select = document.querySelector("select[name='section_id']")
            select.innerHTML = '<option value="">เลือก Section</option>'

            sections.forEach(sec => {
                if (sec.subject_code === code) {

                    select.innerHTML += `
                <option value="${sec.id}">
                    Sec ${sec.name} (${sec.schedule_time})
                </option>
            `
                }
            })
            initWorkRows(); // ⭐ เพิ่มบรรทัดนี้

            document.getElementById("exportModal").classList.remove("hidden")
        }

        function closeExportModal() {

            document.getElementById("exportModal").classList.add("hidden")

        }
        const groupedSubjects = <?php echo json_encode($grouped_subjects, JSON_UNESCAPED_UNICODE); ?>;
        console.log(groupedSubjects);

        // ฟังก์ชันเปิด Modal รายชื่อวิชา
        function openSubjectModal(subjectCode) {
            const data = groupedSubjects[subjectCode];
            if (!data) return;

            // เปลี่ยนข้อความหัว Modal
            document.getElementById('modal_sub_title').innerText = "ผู้ช่วยสอน: " + data.subject_name;
            document.getElementById('modal_sub_code').innerText = "รหัสวิชา: " + data.subject_code;

            // สร้างตารางรายชื่อ
            let tbodyHtml = '';
            data.students.forEach((student, index) => {
                let sectionText = student.section_id ?
                    `Sec ${student.section_id} (${student.section_name})` :
                    '-';
                let timeText = student.schedule_time ? student.schedule_time : '';
                let gradeText = student.student_grade ? student.student_grade : '-';

                tbodyHtml += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-center text-gray-400">${index + 1}</td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900">${student.student_name}</div>
                            <div class="text-xs text-gray-500 font-mono"><i class="fas fa-id-card mr-1"></i>${student.student_id}</div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-800 text-xs">${sectionText}</div>
                            <div class="text-xs text-gray-500">${timeText}</div>
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-indigo-600">${gradeText}</td>
                        <td class="py-3 px-4 text-right">
                            <form id="form-delete-${student.app_id}" action="index.php?page=teacher_update_status" method="POST" class="m-0 inline-block">
                                <input type="hidden" name="application_id" value="${student.app_id}">
                                <input type="hidden" name="status" value="rejected">
                                <input type="hidden" name="recruitment_id" value="MY_TAS">
                                <button type="button" 
                                    onclick="confirmDelete(${student.app_id}, '${student.student_name}')"
                                    class="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-xs font-bold hover:bg-red-100 transition shadow-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> ถอดถอน
                                </button>
                            </form>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('modal_student_list').innerHTML = tbodyHtml;
            document.getElementById('subjectModal').classList.remove('hidden');
        }

        // ฟังก์ชันปิด Modal
        function closeSubjectModal() {
            document.getElementById('subjectModal').classList.add('hidden');
        }

        // ฟังก์ชัน Popup ถอดถอน TA (ลบ) เหมือนเดิม
        function confirmDelete(appId, name) {
            Swal.fire({
                title: 'ยืนยันการถอดถอน?',
                text: `คุณต้องการถอดถอน "${name}" ออกจากรายชื่อผู้ช่วยสอนใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ใช่, ถอดถอน',
                cancelButtonText: 'ยกเลิก',
                background: '#fff',
                customClass: {
                    title: 'font-bold text-gray-800',
                    popup: 'rounded-2xl shadow-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังดำเนินการ...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    document.getElementById('form-delete-' + appId).submit();
                }
            });
        }
    </script>

</body>

</html>