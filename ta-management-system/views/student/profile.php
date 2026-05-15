<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } } }
        }
    </script>
</head>
<body class="bg-[#f9fafb] text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">การตั้งค่าบัญชี</h1>
            <p class="text-gray-500 text-sm">จัดการข้อมูลส่วนตัวและไฟล์ประกอบการสมัคร</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <div class="md:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                    <div class="relative inline-block">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-3xl font-bold shadow-md mx-auto mb-4 border-4 border-white">
                            <?php echo mb_substr($user['name'], 0, 1); ?>
                        </div>
                        <div class="absolute bottom-0 right-0 bg-green-500 w-5 h-5 rounded-full border-2 border-white"></div>
                    </div>
                    <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="text-sm text-gray-500 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="inline-block bg-indigo-50 text-indigo-700 text-xs px-3 py-1 rounded-full font-bold uppercase tracking-wide">Student Account</div>
                    
                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <a href="index.php?page=change_password" class="flex items-center justify-center gap-2 text-gray-600 hover:text-indigo-600 transition-colors text-sm font-medium">
                            <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                        </a>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800">แก้ไขข้อมูลส่วนตัว</h3>
                    </div>
                    
                    <div class="p-6">
                        <form action="index.php?page=student_update_profile" method="POST" enctype="multipart/form-data">
                            <div class="grid grid-cols-1 gap-6">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-user"></i></span>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสนักศึกษา <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-id-card"></i></span>
                                        <input type="text" name="student_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" required placeholder="เช่น 6401xxxx" class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล (ใช้สำหรับเข้าสู่ระบบ)</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-envelope"></i></span>
                                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-500 text-sm cursor-not-allowed">
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">ไฟล์ข้อมูลใน reg </label>
                                    
                                    <?php if (!empty($user['resume_path'])): ?>
                                        <div class="flex items-center gap-3 mb-3 bg-green-50 border border-green-100 p-3 rounded-xl">
                                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-green-800">มีไฟล์เอกสารปัจจุบันแล้ว</p>
                                                <a href="<?php echo htmlspecialchars($user['resume_path']); ?>" target="_blank" class="text-xs text-green-600 hover:underline">คลิกเพื่อดูไฟล์เดิม</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-xl hover:bg-gray-50 transition-colors relative group cursor-pointer">
                                        <input id="resume-upload" name="resume" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".pdf,.jpg,.png,.jpeg">
                                        <div class="space-y-1 text-center">
                                            <div class="mx-auto h-12 w-12 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                                <i class="fas fa-cloud-upload-alt text-3xl"></i>
                                            </div>
                                            <div class="flex text-sm text-gray-600 justify-center">
                                                <span class="relative bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                                    <span>อัปโหลดไฟล์ใหม่</span>
                                                </span>
                                                <p class="pl-1">หรือลากไฟล์มาวาง</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PDF, PNG, JPG (ไม่เกิน 5MB)</p>
                                            <p id="file-name" class="text-sm text-indigo-600 font-bold mt-2 hidden"></p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="mt-8 flex items-center justify-end">
                                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all active:scale-95">
                                    <i class="fas fa-save mr-2"></i> บันทึกการเปลี่ยนแปลง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // โชว์ชื่อไฟล์เมื่อเลือก
        document.getElementById('resume-upload').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            const label = document.getElementById('file-name');
            if (fileName) {
                label.textContent = "ไฟล์ที่เลือก: " + fileName;
                label.classList.remove('hidden');
            } else {
                label.classList.add('hidden');
            }
        });
    </script>

</body>
</html>