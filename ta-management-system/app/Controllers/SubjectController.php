<?php

class SubjectController
{
    const BASE_ENDPOINT = "https://reg6.su.ac.th/regapiweb3/api/th";
    const GET_TOKEN_URL = self::BASE_ENDPOINT . "/Validate/tokenservice";
    const GET_ACAD_URL  = self::BASE_ENDPOINT . "/Schg/Getacad";

    /* ===============================
       Decode Base64 + Gzip
    =============================== */
    public function decodeBase64Gzip($input)
    {
        $decoded = base64_decode($input, true);
        if ($decoded === false) {
            throw new Exception("Base64 decode failed");
        }

        $unzipped = gzdecode($decoded);
        if ($unzipped === false) {
            throw new Exception("Gzip decode failed");
        }

        return $unzipped;
    }

    /* ===============================
       HTTP GET
    =============================== */
    public function httpGet($url, $headers = [])
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception("cURL Error: " . curl_error($ch));
        }

        curl_close($ch);
        return $response;
    }

    /* ===============================
       Get Token
    =============================== */
    public function getToken()
    {
        $response = $this->httpGet(self::GET_TOKEN_URL);
        $json = json_decode($response, true);

        if (!isset($json['token'])) {
            throw new Exception("Token not found");
        }

        return $json['token'];
    }

    /* ===============================
       Get Academic Year / Semester
    =============================== */
    public function getAcad($token)
    {
        $headers = ["Authorization: Bearer " . $token];

        $response = $this->httpGet(self::GET_ACAD_URL, $headers);

        $json = json_decode($response, true);

        if (!$json) {
            throw new Exception("GetAcad failed");
        }

        return [
            'year' => $json['enrollacadyear'],
            'semester' => $json['enrollsemester']
        ];
    }

    /* ===============================
       Get Course List
    =============================== */
    public function getCourseList($token, $year, $semester)
    {
        $headers = ["Authorization: Bearer " . $token];

        $url = self::BASE_ENDPOINT .
            "/Classinfo/Classinfo/1/800/$year/$semester/-9/7/1/5*/null/1/-9/-9/-9";

        $response = $this->httpGet($url, $headers);
        $json = json_decode($response, true);

        if (!isset($json['result'])) {
            throw new Exception("Course list result not found");
        }

        $decoded = $this->decodeBase64Gzip($json['result']);

        return json_decode($decoded, true);
    }

    /* ===============================
       Get Course Detail
    =============================== */
    public function getCourseDetail($token, $year, $semester, $courseId)
    {
        $headers = ["Authorization: Bearer " . $token];

        $url = self::BASE_ENDPOINT .
            "/Classinfo/Classdetail/$year/$semester/$courseId";

        $response = $this->httpGet($url, $headers);

        $json = json_decode($response, true);

        if (!isset($json['result'])) {
            throw new Exception("Course detail result not found");
        }

        $decoded = $this->decodeBase64Gzip($json['result']);

        return json_decode($decoded, true);
    }

    /* ===============================
       Insert / Update Subject
    =============================== */
    public function insertSubject($course_id, $code, $revisioncode, $name, $semester)
    {
        require_once __DIR__ . '/../Config/Database.php';
        $db = (new Database())->connect();

        $sql = "INSERT INTO subjects (course_id, code, revisioncode, name, semester)
                VALUES (:course_id, :code, :revisioncode, :name, :semester)
                ON DUPLICATE KEY UPDATE
                code = VALUES(code),
                revisioncode = VALUES(revisioncode),
                name = VALUES(name),
                semester = VALUES(semester)";

        $stmt = $db->prepare($sql);

        $stmt->execute([
            ':course_id' => $course_id,
            ':code' => $code,
            ':revisioncode' => $revisioncode,
            ':name' => $name,
            ':semester' => $semester
        ]);

        $stmt = $db->prepare("SELECT id FROM subjects WHERE course_id = ?");
        $stmt->execute([$course_id]);

        return $stmt->fetchColumn();
    }

    /* ===============================
       Insert Teachers
    =============================== */
    public function insertTeachers($subject_id, $instructors)
    {
        require_once __DIR__ . '/../Config/Database.php';
        $db = (new Database())->connect();

        $stmt = $db->prepare("DELETE FROM subject_teachers WHERE subject_id = ?");
        $stmt->execute([$subject_id]);

        if (empty($instructors)) return;

        $stmt = $db->prepare("
            INSERT IGNORE INTO subject_teachers (subject_id, teacher_name)
            VALUES (:subject_id, :teacher_name)
        ");

        foreach ($instructors as $ins) {

            $prefix = $ins['prefixname'] ?? '';
            $fname  = $ins['officername'] ?? '';
            $lname  = $ins['officersurname'] ?? '';

            if (!$fname) continue;

            $fullName = trim("$prefix $fname $lname");

            $stmt->execute([
                ':subject_id' => $subject_id,
                ':teacher_name' => $fullName
            ]);
        }
    }

    /* ===============================
       SYNC SUBJECTS
    =============================== */
  public function syncSubjects()
    {
        // เรียก Database มาเพื่อใช้เช็คและอัปเดตข้อมูล
        require_once __DIR__ . '/../Config/Database.php';
        $db = (new Database())->connect();

        try {

            $token = $this->getToken();

            $acad = $this->getAcad($token);
            $year = $acad['year'];
            $semester = $acad['semester'];

            $courses = $this->getCourseList($token, $year, $semester);

            $classInfoList = $courses[0]['classinfolist'] ?? [];

            foreach ($classInfoList as $course) {

                $courseId = $course['courseid'] ?? null;
                if (!$courseId) continue;

                $detail = $this->getCourseDetail($token, $year, $semester, $courseId);

                $details = $detail[0] ?? [];

                $code     = $details['coursecode'] ?? null;
                $revisioncode = $details['revisioncode'] ?? null;
                $name     = $details['coursename'] ?? null;
                $semester = $details['semester'] ?? null;
                $courseid = $details['courseid'] ?? null;

                if (!$courseid || !$code) continue;

                // ⭐ เพิ่มระบบ Check & Update (ป้องกันข้อมูลซ้ำ)
                $stmtCheck = $db->prepare("SELECT id FROM subjects WHERE course_id = ?");
                $stmtCheck->execute([$courseid]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // ✅ 1. มีข้อมูลอยู่แล้ว -> ให้อัปเดต (Update)
                    $subject_id = $existing['id'];
                    $stmtUpdate = $db->prepare("UPDATE subjects SET code = ?, revisioncode = ?, name = ?, semester = ? WHERE id = ?");
                    $stmtUpdate->execute([$code, $revisioncode, $name, $semester, $subject_id]);

                    // เคลียร์รายชื่ออาจารย์เก่าของวิชานี้ออกก่อน แล้วค่อยให้ insertTeachers ลงไปใหม่ (กันชื่ออาจารย์ซ้ำซ้อน)
                    $stmtDelTeacher = $db->prepare("DELETE FROM subject_teachers WHERE subject_id = ?");
                    $stmtDelTeacher->execute([$subject_id]);

                } else {
                    // ✅ 2. ถ้ายังไม่มีข้อมูล -> ให้ทำการเพิ่มใหม่ (Insert ปกติ)
                    $subject_id = $this->insertSubject(
                        $courseid,
                        $code,
                        $revisioncode,
                        $name,
                        $semester
                    );
                }
                // ⭐ จบส่วนที่เพิ่ม

                $this->insertTeachers(
                    $subject_id,
                    $details['instructor'] ?? []
                );

                usleep(200000);
            }

            // ✅ เปลี่ยนการ Return เป็นการแจ้งเตือน (SweetAlert) และเด้งกลับหน้าเดิม
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'><style>*{font-family:'Prompt',sans-serif;}</style></head><body><script>
                Swal.fire({ 
                    title: 'ซิงค์ข้อมูลสำเร็จ!', 
                    text: 'อัปเดตข้อมูลรายวิชาและอาจารย์เรียบร้อยแล้ว', 
                    icon: 'success', 
                    timer: 2000, 
                    showConfirmButton: false 
                }).then(() => { 
                    window.location.href = 'index.php?page=manage_subjects'; 
                });
            </script></body></html>";
            exit;

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            
            // ✅ แสดง Error แจ้งเตือน และเด้งกลับ
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css2?family=Prompt&display=swap' rel='stylesheet'><style>*{font-family:'Prompt',sans-serif;}</style></head><body><script>
                Swal.fire({ 
                    title: 'เกิดข้อผิดพลาด!', 
                    text: '{$errorMsg}', 
                    icon: 'error', 
                    confirmButtonColor: '#4f46e5' 
                }).then(() => { 
                    window.location.href = 'index.php?page=manage_subjects'; 
                });
            </script></body></html>";
            exit;
        }
    }
}