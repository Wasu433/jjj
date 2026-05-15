package handlers

import (
	"fmt"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"ta-management/database"

	"github.com/gin-gonic/gin"
)

func getStudentYear(studentID string, acadYear int) int {
	if len(studentID) < 2 {
		return 1
	}
	prefix, err := strconv.Atoi(studentID[:2])
	if err != nil {
		return 1
	}
	admitYear := 2500 + prefix
	year := (acadYear - admitYear) + 1
	if year > 4 {
		year = 4
	}
	if year < 1 {
		year = 1
	}
	return year
}

func getCurrentAcadYear() int {
	now := time.Now()
	year := now.Year() + 543
	if now.Month() < 6 {
		year--
	}
	return year
}

func getUploadsDir() string {
	dir := os.Getenv("UPLOADS_DIR")
	if dir == "" {
		dir = "./uploads"
	}
	return dir
}

// ============================================================
// STUDENT DASHBOARD
// ============================================================

func StudentDashboard(c *gin.Context) {
	studentID := c.GetInt("user_id")
	studentRecruID := c.GetString("student_id")

	acadYear := getCurrentAcadYear()
	stuYear := getStudentYear(studentRecruID, acadYear)

	var eligibleCount, myApplications, acceptedCount int
	database.DB.QueryRow(`
		SELECT COUNT(*) FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		WHERE r.status='open' AND SUBSTRING(s.code,4,1) <= ?
	`, strconv.Itoa(stuYear)).Scan(&eligibleCount)
	database.DB.QueryRow("SELECT COUNT(DISTINCT recruitment_id) FROM applications WHERE student_id=?", studentID).Scan(&myApplications)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE student_id=? AND status='approved'", studentID).Scan(&acceptedCount)

	rows, _ := database.DB.Query(`
		SELECT r.id, r.title, r.status, s.code AS subject_code, s.name AS subject_name,
		       u.name AS teacher_name
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		JOIN users u ON r.teacher_id = u.id
		WHERE r.status='open' AND SUBSTRING(s.code,4,1) <= ?
		ORDER BY r.created_at DESC
		LIMIT 5
	`, stuYear)

	var latestRecruitments []map[string]interface{}
	if rows != nil {
		defer rows.Close()
		for rows.Next() {
			var id int
			var title, status, subjectCode, subjectName, teacherName string
			rows.Scan(&id, &title, &status, &subjectCode, &subjectName, &teacherName)
			latestRecruitments = append(latestRecruitments, map[string]interface{}{
				"id": id, "title": title, "status": status,
				"subject_code": subjectCode, "subject_name": subjectName, "teacher_name": teacherName,
			})
		}
	}
	if latestRecruitments == nil {
		latestRecruitments = []map[string]interface{}{}
	}

	histRows, _ := database.DB.Query(`
		SELECT a.status, a.created_at, r.title, s.code, s.name
		FROM applications a
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		WHERE a.student_id=?
		ORDER BY a.created_at DESC
		LIMIT 5
	`, studentID)

	var history []map[string]interface{}
	if histRows != nil {
		defer histRows.Close()
		for histRows.Next() {
			var status, createdAt, title, subjectCode, subjectName string
			histRows.Scan(&status, &createdAt, &title, &subjectCode, &subjectName)
			history = append(history, map[string]interface{}{
				"status": status, "created_at": createdAt,
				"title": title, "subject_code": subjectCode, "subject_name": subjectName,
			})
		}
	}
	if history == nil {
		history = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{
		"eligible_count":      eligibleCount,
		"my_applications":     myApplications,
		"accepted_count":      acceptedCount,
		"latest_recruitments": latestRecruitments,
		"recent_history":      history,
		"student_year":        stuYear,
	})
}

// ============================================================
// RECRUITMENT LIST
// ============================================================

func StudentGetRecruitments(c *gin.Context) {
	studentID := c.GetInt("user_id")
	studentRecruID := c.GetString("student_id")
	search := c.Query("search")
	subjectID := c.Query("subject_id")

	acadYear := getCurrentAcadYear()
	stuYear := getStudentYear(studentRecruID, acadYear)

	query := `
		SELECT r.id, r.title, r.status, r.semester, r.description,
		       s.id AS subject_id, s.code AS subject_code, s.name AS subject_name,
		       u.name AS teacher_name,
		       COALESCE(rd.grade_requirement,''),
		       (SELECT COUNT(*) FROM applications a WHERE a.recruitment_id=r.id AND a.student_id=?) AS already_applied,
		       COALESCE((SELECT GROUP_CONCAT(
		           CONCAT(sec.id,'|',sec.name,'|',COALESCE(sec.schedule_time,''),'|',sec.quota,'|',
		               COALESCE((SELECT COUNT(*) FROM applications app WHERE app.selected_section_id=sec.id AND app.status='approved'),0))
		           ORDER BY sec.name SEPARATOR ';;')
		           FROM sections sec WHERE sec.subject_id=r.subject_id), '') AS sections_data
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		JOIN users u ON r.teacher_id = u.id
		LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
		WHERE r.status='open' AND SUBSTRING(s.code,4,1) <= ?
	`
	params := []interface{}{studentID, strconv.Itoa(stuYear)}

	if search != "" {
		query += " AND (s.name LIKE ? OR s.code LIKE ? OR r.title LIKE ?)"
		sp := "%" + search + "%"
		params = append(params, sp, sp, sp)
	}
	if subjectID != "" {
		query += " AND s.id = ?"
		params = append(params, subjectID)
	}
	query += " ORDER BY r.created_at DESC"

	rows, err := database.DB.Query(query, params...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var recruitments []map[string]interface{}
	subjectSet := map[int]map[string]interface{}{}
	for rows.Next() {
		var id, sid, alreadyApplied int
		var title, status, semester, description, subjectCode, subjectName, teacherName, gradeReq, sectionsData string
		rows.Scan(&id, &title, &status, &semester, &description, &sid, &subjectCode, &subjectName,
			&teacherName, &gradeReq, &alreadyApplied, &sectionsData)

		sections := parseStudentSections(sectionsData)
		recruitments = append(recruitments, map[string]interface{}{
			"id": id, "title": title, "status": status, "semester": semester,
			"description": description, "subject_code": subjectCode, "subject_name": subjectName,
			"teacher_name": teacherName, "grade_requirement": gradeReq,
			"already_applied": alreadyApplied > 0, "sections": sections,
		})
		subjectSet[sid] = map[string]interface{}{"id": sid, "code": subjectCode}
	}
	if recruitments == nil {
		recruitments = []map[string]interface{}{}
	}

	var subjects []map[string]interface{}
	for _, s := range subjectSet {
		subjects = append(subjects, s)
	}
	if subjects == nil {
		subjects = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{"recruitments": recruitments, "subjects": subjects, "student_year": stuYear})
}

func parseStudentSections(data string) []map[string]interface{} {
	var sections []map[string]interface{}
	if data == "" {
		return sections
	}
	parts := strings.Split(data, ";;")
	for _, part := range parts {
		fields := strings.Split(part, "|")
		if len(fields) < 5 {
			continue
		}
		id, _ := strconv.Atoi(fields[0])
		quota, _ := strconv.Atoi(fields[3])
		enrolled, _ := strconv.Atoi(fields[4])
		available := quota - enrolled
		if available < 0 {
			available = 0
		}
		sections = append(sections, map[string]interface{}{
			"id": id, "name": fields[1], "schedule_time": fields[2],
			"quota": quota, "enrolled": enrolled, "available": available,
		})
	}
	return sections
}

// ============================================================
// APPLY
// ============================================================

func StudentApply(c *gin.Context) {
	studentID := c.GetInt("user_id")

	err := c.Request.ParseMultipartForm(10 << 20)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณาส่งข้อมูลให้ถูกต้อง"})
		return
	}

	recruitmentIDStr := c.PostForm("recruitment_id")
	recruitmentID, err := strconv.Atoi(recruitmentIDStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "recruitment_id ไม่ถูกต้อง"})
		return
	}

	sectionIDStr := c.PostForm("section_id")
	grade := c.PostForm("grade")

	var gradeFilePath string
	file, header, ferr := c.Request.FormFile("grade_file")
	if ferr == nil && file != nil {
		defer file.Close()
		ext := strings.ToLower(filepath.Ext(header.Filename))
		allowed := map[string]bool{".pdf": true, ".jpg": true, ".jpeg": true, ".png": true}
		if !allowed[ext] {
			c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณาอัปโหลดไฟล์ PDF หรือรูปภาพเท่านั้น"})
			return
		}
		uploadDir := getUploadsDir() + "/grades/"
		os.MkdirAll(uploadDir, 0755)
		filename := fmt.Sprintf("grade_%d_%d%s", studentID, time.Now().Unix(), ext)
		dst, err := os.Create(uploadDir + filename)
		if err == nil {
			defer dst.Close()
			buf := make([]byte, 1024*1024)
			for {
				n, readErr := file.Read(buf)
				if n > 0 {
					dst.Write(buf[:n])
				}
				if readErr != nil {
					break
				}
			}
			gradeFilePath = "uploads/grades/" + filename
		}
	}

	// Check already applied
	var existingID int
	err = database.DB.QueryRow(
		"SELECT id FROM applications WHERE recruitment_id=? AND student_id=?",
		recruitmentID, studentID,
	).Scan(&existingID)
	if err == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "คุณสมัครประกาศนี้ไปแล้ว"})
		return
	}

	var secID interface{} = nil
	if sectionIDStr != "" {
		sid, err := strconv.Atoi(sectionIDStr)
		if err == nil && sid > 0 {
			// Check quota
			var quota, enrolled int
			database.DB.QueryRow(`
				SELECT sec.quota,
				       COALESCE((SELECT COUNT(*) FROM applications WHERE selected_section_id=? AND status='approved'),0)
				FROM sections sec WHERE sec.id=?
			`, sid, sid).Scan(&quota, &enrolled)
			if enrolled >= quota && quota > 0 {
				c.JSON(http.StatusBadRequest, gin.H{"error": "Section นี้เต็มแล้ว"})
				return
			}
			secID = sid
		}
	}

	var filePathVal interface{} = nil
	if gradeFilePath != "" {
		filePathVal = gradeFilePath
	}

	_, err = database.DB.Exec(
		"INSERT INTO applications (recruitment_id, student_id, selected_section_id, status, grade_file, grade) VALUES (?, ?, ?, 'pending', ?, ?)",
		recruitmentID, studentID, secID, filePathVal, grade,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "ไม่สามารถสมัครได้"})
		return
	}

	// Notify teacher
	var teacherID int
	var recruitmentTitle, subjectName string
	database.DB.QueryRow(`
		SELECT r.teacher_id, r.title, s.name
		FROM recruitments r JOIN subjects s ON r.subject_id=s.id
		WHERE r.id=?
	`, recruitmentID).Scan(&teacherID, &recruitmentTitle, &subjectName)

	var studentName string
	database.DB.QueryRow("SELECT name FROM users WHERE id=?", studentID).Scan(&studentName)

	if teacherID > 0 {
		msg := fmt.Sprintf("นักศึกษา %s ได้สมัครประกาศ '%s' สำหรับวิชา %s", studentName, recruitmentTitle, subjectName)
		link := fmt.Sprintf("/teacher/applicants?recruitment_id=%d", recruitmentID)
		CreateNotification(teacherID, msg, link)
	}

	c.JSON(http.StatusOK, gin.H{"message": "สมัครสำเร็จ"})
}

// ============================================================
// CANCEL APPLICATION
// ============================================================

func StudentCancelApplication(c *gin.Context) {
	studentID := c.GetInt("user_id")
	id := c.Param("id")

	var existingID int
	err := database.DB.QueryRow(
		"SELECT id FROM applications WHERE id=? AND student_id=? AND status='pending'",
		id, studentID,
	).Scan(&existingID)

	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ใบสมัครนี้อาจถูกพิจารณาไปแล้ว"})
		return
	}

	database.DB.Exec("DELETE FROM applications WHERE id=?", id)
	c.JSON(http.StatusOK, gin.H{"message": "ยกเลิกใบสมัครสำเร็จ"})
}

// ============================================================
// MY APPLICATIONS
// ============================================================

func StudentGetMyApplications(c *gin.Context) {
	studentID := c.GetInt("user_id")
	statusFilter := c.Query("status")

	query := `
		SELECT a.id, a.status, a.created_at,
		       COALESCE(a.grade,''), COALESCE(a.grade_file,''),
		       r.title, s.code AS subject_code, s.name AS subject_name,
		       u.name AS teacher_name,
		       COALESCE(sec.name,'') AS section_name, COALESCE(sec.schedule_time,'')
		FROM applications a
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		JOIN users u ON r.teacher_id = u.id
		LEFT JOIN sections sec ON a.selected_section_id = sec.id
		WHERE a.student_id=?
	`
	params := []interface{}{studentID}
	if statusFilter != "" {
		query += " AND a.status=?"
		params = append(params, statusFilter)
	}
	query += " ORDER BY a.created_at DESC"

	rows, err := database.DB.Query(query, params...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var applications []map[string]interface{}
	for rows.Next() {
		var id int
		var status, createdAt, grade, gradeFile, title, subjectCode, subjectName, teacherName, sectionName, scheduleTime string
		rows.Scan(&id, &status, &createdAt, &grade, &gradeFile, &title, &subjectCode, &subjectName, &teacherName, &sectionName, &scheduleTime)
		applications = append(applications, map[string]interface{}{
			"app_id": id, "status": status, "created_at": createdAt,
			"grade": grade, "grade_file": gradeFile,
			"title": title, "subject_code": subjectCode, "subject_name": subjectName,
			"teacher_name": teacherName, "section_name": sectionName, "schedule_time": scheduleTime,
		})
	}
	if applications == nil {
		applications = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{"applications": applications})
}

// ============================================================
// PROFILE
// ============================================================

func StudentGetProfile(c *gin.Context) {
	userID := c.GetInt("user_id")

	var id int
	var name, email, role, studentID, resumeFile, createdAt string
	err := database.DB.QueryRow(
		"SELECT id, name, email, role, COALESCE(student_id,''), COALESCE(resume_path,''), created_at FROM users WHERE id=?",
		userID,
	).Scan(&id, &name, &email, &role, &studentID, &resumeFile, &createdAt)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, map[string]interface{}{
		"id": id, "name": name, "email": email, "role": role,
		"student_id": studentID, "resume_file": resumeFile, "created_at": createdAt,
	})
}

func StudentUpdateProfile(c *gin.Context) {
	userID := c.GetInt("user_id")

	c.Request.ParseMultipartForm(10 << 20)

	name := c.PostForm("name")
	studentID := c.PostForm("student_id")

	var resumeFilePath string
	file, header, err := c.Request.FormFile("resume")
	if err == nil && file != nil {
		defer file.Close()
		ext := strings.ToLower(filepath.Ext(header.Filename))
		uploadDir := getUploadsDir()
		os.MkdirAll(uploadDir, 0755)
		filename := fmt.Sprintf("resume_%d_%d%s", userID, time.Now().Unix(), ext)
		dst, err := os.Create(uploadDir + "/" + filename)
		if err == nil {
			defer dst.Close()
			buf := make([]byte, 1024*1024)
			for {
				n, readErr := file.Read(buf)
				if n > 0 {
					dst.Write(buf[:n])
				}
				if readErr != nil {
					break
				}
			}
			resumeFilePath = "uploads/" + filename
		}
	}

	if resumeFilePath != "" {
		database.DB.Exec("UPDATE users SET name=?, student_id=?, resume_path=? WHERE id=?", name, studentID, resumeFilePath, userID)
	} else {
		database.DB.Exec("UPDATE users SET name=?, student_id=? WHERE id=?", name, studentID, userID)
	}

	c.JSON(http.StatusOK, gin.H{"message": "อัปเดตข้อมูลสำเร็จ"})
}
