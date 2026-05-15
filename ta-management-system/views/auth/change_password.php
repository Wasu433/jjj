<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ตรวจสอบว่า Login หรือยัง
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน | TA System</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] }, colors: { primary: '#4f46e5' } } } }
    </script>
    <style>
        body { background-color: #f9fafb; background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px; }
    </style>
</head>
<body class="text-gray-800 font-sans min-h-screen">

    <div class="bg-white shadow-sm sticky top-0 z-50">
        <?php include __DIR__ . '/../../views/layouts/navbar.php'; ?>
    </div>

    <div class="max-w-xl mx-auto px-4 py-12">
        
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">เปลี่ยนรหัสผ่าน</h2>
                <p class="text-gray-500 text-sm mt-1">เพื่อความปลอดภัย กรุณาตั้งรหัสผ่านที่คาดเดายาก</p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?page=change_password_process" method="POST" autocomplete="off" class="space-y-5">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านปัจจุบัน</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="current_password" required 
                               class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
                               placeholder="••••••••">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition-colors"
                           onclick="togglePassword('current_password', this)"></i>
                    </div>
                </div>

                <hr class="border-gray-100 my-4">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่านใหม่ (ขั้นต่ำ 8 ตัว)</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" required 
                               class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
                               placeholder="••••••••">
                        <i class="fas fa-key absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition-colors"
                           onclick="togglePassword('new_password', this)"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่านใหม่</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required 
                               class="w-full pl-10 pr-10 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" 
                               placeholder="••••••••">
                        <i class="fas fa-check-circle absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <i class="fas fa-eye absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 cursor-pointer hover:text-indigo-600 transition-colors"
                           onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-2.5 rounded-xl font-medium hover:bg-indigo-700 shadow-md transition-all active:scale-95">
                        บันทึกการเปลี่ยนแปลง
                    </button>

                    <button type="button" onclick="history.back()" class="flex-1 bg-white border border-gray-200 text-gray-700 py-2.5 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                        ยกเลิก
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>

</body>
</html>