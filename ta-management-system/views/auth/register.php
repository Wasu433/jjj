<?php 
// 1. start session บนสุด
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ลงทะเบียน | TA Management</title>

  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  
  <script>
    tailwind.config = {
      theme: {
        extend: { fontFamily: { sans: ['Prompt', 'sans-serif'] } }
      }
    }
  </script>
  <style>
    body {
      background-image: radial-gradient(#d1d5db 1px, transparent 1px);
      background-size: 20px 20px;
    }
  </style>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen text-gray-800 py-10">

  <div class="bg-white w-full max-w-[500px] p-8 md:p-10 rounded-2xl shadow-[0_20px_25px_-5px_rgba(0,0,0,0.1),0_10px_10px_-5px_rgba(0,0,0,0.04)] m-4 transition-transform duration-300 hover:-translate-y-1">
    
    <div class="text-center mb-8">
        <i class="fas fa-user-plus text-5xl text-indigo-600 mb-4"></i>
        <h2 class="text-3xl font-semibold text-gray-800">ลงทะเบียนสมาชิกใหม่</h2>
        <p class="text-gray-500 text-sm mt-2">สำหรับนักศึกษา (Student)</p>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
      <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 text-sm">
         <i class="fas fa-check-circle text-lg"></i>
         <span><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3 text-sm">
         <i class="fas fa-exclamation-circle text-lg"></i>
         <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=register_process" autocomplete="off" class="space-y-4">
      
      <div>
        <label for="name" class="block text-gray-700 font-medium text-sm mb-2">ชื่อ-นามสกุล</label>
        <div class="relative">
            <input type="text" name="name" id="name" required
                   class="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                   placeholder="กรอกชื่อและนามสกุลจริง">
            <i class="fas fa-id-card absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors"></i>
        </div>
      </div>

      <div>
        <label for="email" class="block text-gray-700 font-medium text-sm mb-2">อีเมล (มหาวิทยาลัย)</label>
        <div class="relative">
            <input type="email" name="email" id="email" required
                   pattern=".+@silpakorn\.edu$" 
                   title="กรุณาใช้อีเมลของมหาวิทยาลัย (@silpakorn.edu) เท่านั้น"
                   class="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                   placeholder="name@silpakorn.edu">
            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors"></i>
        </div>
        <p class="text-xs text-gray-500 mt-1.5 ml-1"><i class="fas fa-info-circle mr-1"></i>ต้องลงท้ายด้วย @silpakorn.edu เท่านั้น</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="password" class="block text-gray-700 font-medium text-sm mb-2">รหัสผ่าน</label>
            <div class="relative">
                <input type="password" name="password" id="password" required minlength="8"
                       class="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                       placeholder="ขั้นต่ำ 8 ตัวอักษร">
                <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors"></i>
            </div>
          </div>
          <div>
            <label for="confirm_password" class="block text-gray-700 font-medium text-sm mb-2">ยืนยันรหัสผ่าน</label>
            <div class="relative">
                <input type="password" name="confirm_password" id="confirm_password" required minlength="8"
                       class="peer w-full pl-11 pr-4 py-3 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/10 transition-all"
                       placeholder="ยืนยันอีกครั้ง">
                <i class="fas fa-check-circle absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 peer-focus:text-indigo-600 transition-colors"></i>
            </div>
          </div>
      </div>

      <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3.5 rounded-lg transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg mt-4 flex justify-center items-center gap-2">
        <i class="fas fa-paper-plane"></i> ลงทะเบียน
      </button>

      <div class="text-center mt-6">
        <span class="text-gray-500 text-sm">มีบัญชีอยู่แล้ว?</span>
        <a href="index.php?page=login" class="text-indigo-600 font-semibold hover:text-indigo-800 hover:underline transition-colors text-sm ml-1">เข้าสู่ระบบ</a>
      </div>
    </form>

    <div class="text-center text-gray-400 text-xs mt-8 leading-relaxed">
        © 2568 ระบบจัดการผู้ช่วยสอน (TA System)<br>
        มหาวิทยาลัยศิลปากร
    </div>
  </div>

  <script>
      // เพิ่มสคริปต์ตรวจสอบรหัสผ่านให้ตรงกันก่อนส่งฟอร์มด้วยเลย
      document.querySelector('form').addEventListener('submit', function(e) {
          const password = document.getElementById('password').value;
          const confirm = document.getElementById('confirm_password').value;
          
          if (password !== confirm) {
              e.preventDefault();
              alert('รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน');
          }
      });
  </script>
</body>
</html>