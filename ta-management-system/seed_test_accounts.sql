-- =====================================================
-- TEST ACCOUNTS - TA Management System
-- รหัสผ่านทั้งหมด: 12345678
-- =====================================================

-- Admin Account
INSERT INTO users (name, email, password_hash, role) 
VALUES ('ผู้ดูแลระบบ', 'admin@silpakorn.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Teacher Account
INSERT INTO users (name, email, password_hash, role) 
VALUES ('อาจารย์ ทดสอบ', 'teacher@silpakorn.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- Student Account
INSERT INTO users (name, email, password_hash, role) 
VALUES ('นักศึกษา ทดสอบ', 'student@silpakorn.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');
