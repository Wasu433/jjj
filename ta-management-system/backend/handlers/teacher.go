package handlers

import (
	"database/sql"
	"net/http"
	"strings"

	"ta-management/database"

	"github.com/gin-gonic/gin"
)

// ============================================================
// TEACHER DASHBOARD
// ============================================================

func TeacherDashboard(c *gin.Context) {
	teacherID := c.GetInt("user_id")

	var myRecruitments, totalApplicants, approvedTAs int
	database.DB.QueryRow("SELECT COUNT(*) FROM recruitments WHERE teacher_id=? AND status!='deleted'", teacherID).Scan(&myRecruitments)
	database.DB.QueryRow(`SELECT COUNT(*) FROM applications a JOIN recruitments r ON a.recruitment_id=r.id WHERE r.teacher_id=? AND a.status!='deleted'`, teacherID).Scan(&totalApplicants)
	database.DB.QueryRow(`SELECT COUNT(*) FROM applications a JOIN recruitments r ON a.recruitment_id=r.id WHERE r.teacher_id=? AND a.status='approved'`, teacherID).Scan(&approvedTAs)

	rows, _ := database.DB.Query(`
		SELECT r.id, r.title, r.status, r.created_at,
		       s.code, s.name AS subject_name,
		       (SELECT COUNT(*) FROM applications a WHERE a.recruitment_id=r.id AND a.status!='deleted') AS applicant_count
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		WHERE r.teacher_id=? AND r.status!='deleted'
		ORDER BY r.created_at DESC
		LIMIT 5
	`, teacherID)

	var recruitments []map[string]interface{}
	if rows != nil {
		defer rows.Close()
		for rows.Next() {
			var id, applicantCount int
			var title, status, createdAt, code, subjectName string
			rows.Scan(&id, &title, &status, &createdAt, &code, &subjectName, &applicantCount)
			recruitments = append(recruitments, map[string]interface{}{
				"id": id, "title": title, "status": status, "created_at": createdAt,
				"subject_code": code, "subject_name": subjectName, "applicant_count": applicantCount,
			})
		}
	}
	if recruitments == nil {
		recruitments = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{
		"my_recruitments":   myRecruitments,
		"total_applicants":  totalApplicants,
		"approved_tas":      approvedTAs,
		"recent_recruitments": recruitments,
	})
}

// ============================================================
// MY RECRUITMENTS
// ============================================================

func TeacherGetMyRecruitments(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	search := c.Query("search")
	semesterFilter := c.Query("semester")

	rows, err := database.DB.Query(`
		SELECT id, code, revisioncode, name
		FROM subjects
		ORDER BY code ASC
	`)
	var subjectOptions []map[string]interface{}
	if err == nil {
		defer rows.Close()
		for rows.Next() {
			var id int
			var code, revisionCode, name string
			rows.Scan(&id, &code, &revisionCode, &name)
			subjectOptions = append(subjectOptions, map[string]interface{}{"id": id, "code": code, "revisioncode": revisionCode, "name": name})
		}
	}
	if subjectOptions == nil {
		subjectOptions = []map[string]interface{}{}
	}

	semRows, _ := database.DB.Query(`
		SELECT DISTINCT s.semester
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		WHERE r.teacher_id=? AND r.status!='deleted'
		ORDER BY s.semester DESC
	`, teacherID)
	var semesters []string
	if semRows != nil {
		defer semRows.Close()
		for semRows.Next() {
			var sem string
			semRows.Scan(&sem)
			semesters = append(semesters, sem)
		}
	}

	query := `
		SELECT r.id, r.title, r.status, r.created_at, r.description, r.subject_id,
		       s.code AS subject_code, s.revisioncode, s.name AS subject_name, s.semester,
		       COALESCE(rd.quota,0), COALESCE(rd.grade_requirement,''),
		       COALESCE((SELECT GROUP_CONCAT(CONCAT(sec.name,'|',sec.schedule_time,'|',sec.quota) SEPARATOR ';;')
		                 FROM sections sec WHERE sec.subject_id = r.subject_id), '') AS sections_data
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
		WHERE r.teacher_id=? AND r.status!='deleted'
	`
	params := []interface{}{teacherID}

	if search != "" {
		query += " AND (r.title LIKE ? OR s.code LIKE ? OR s.name LIKE ?)"
		sp := "%" + search + "%"
		params = append(params, sp, sp, sp)
	}
	if semesterFilter != "" && semesterFilter != "all" {
		query += " AND s.semester = ?"
		params = append(params, semesterFilter)
	}
	query += " ORDER BY r.created_at DESC"

	recRows, err := database.DB.Query(query, params...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer recRows.Close()

	var recruitments []map[string]interface{}
	for recRows.Next() {
		var id, subjectID, quota int
		var title, status, createdAt, description, subjectCode, revisionCode, subjectName, semester, gradeReq, sectionsData string
		recRows.Scan(&id, &title, &status, &createdAt, &description, &subjectID,
			&subjectCode, &revisionCode, &subjectName, &semester, &quota, &gradeReq, &sectionsData)

		sections := parseTeacherSections(sectionsData)
		recruitments = append(recruitments, map[string]interface{}{
			"id": id, "title": title, "status": status, "created_at": createdAt,
			"description": description, "subject_id": subjectID,
			"subject_code": subjectCode, "revisioncode": revisionCode,
			"subject_name": subjectName, "semester": semester,
			"quota": quota, "grade_requirement": gradeReq, "sections": sections,
		})
	}
	if recruitments == nil {
		recruitments = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{
		"recruitments":    recruitments,
		"subject_options": subjectOptions,
		"semesters":       semesters,
	})
}

func parseTeacherSections(data string) []map[string]interface{} {
	var sections []map[string]interface{}
	if data == "" {
		return sections
	}
	parts := strings.Split(data, ";;")
	for _, part := range parts {
		fields := strings.Split(part, "|")
		if len(fields) < 3 {
			continue
		}
		sections = append(sections, map[string]interface{}{
			"name": fields[0], "schedule_time": fields[1], "quota": fields[2],
		})
	}
	return sections
}

// ============================================================
// CREATE RECRUITMENT
// ============================================================

func TeacherCreateRecruitment(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	teacherName := c.GetString("name")

	var req struct {
		SubjectID   int            `json:"subject_id" binding:"required"`
		Title       string         `json:"title" binding:"required"`
		Description string         `json:"description"`
		Status      string         `json:"status"`
		GradeReq    string         `json:"grade_req"`
		Sections    []SectionInput `json:"sections"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	if req.Status == "" {
		req.Status = "open"
	}

	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	result, err := tx.Exec(
		"INSERT INTO recruitments (teacher_id, title, description, subject_id, status) VALUES (?, ?, ?, ?, ?)",
		teacherID, req.Title, req.Description, req.SubjectID, req.Status,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	recruitmentID, _ := result.LastInsertId()
	totalQuota := 0
	for _, s := range req.Sections {
		totalQuota += s.Quota
	}

	tx.Exec("INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)",
		recruitmentID, totalQuota, req.GradeReq)

	var hasUsed int
	tx.QueryRow(`SELECT COUNT(*) FROM applications a
		JOIN sections s ON a.selected_section_id = s.id
		WHERE s.subject_id = ?`, req.SubjectID).Scan(&hasUsed)

	if hasUsed == 0 {
		tx.Exec("DELETE FROM sections WHERE subject_id = ?", req.SubjectID)
	}

	for _, s := range req.Sections {
		if strings.TrimSpace(s.Name) != "" {
			tx.Exec("INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)",
				req.SubjectID, s.Name, s.ScheduleTime, s.Quota)
		}
	}

	tx.Commit()

	// Notify all students
	var subjectName string
	database.DB.QueryRow("SELECT name FROM subjects WHERE id = ?", req.SubjectID).Scan(&subjectName)

	studentRows, _ := database.DB.Query("SELECT id FROM users WHERE role = 'student'")
	if studentRows != nil {
		defer studentRows.Close()
		msg := "📢 ประกาศรับสมัครใหม่: " + subjectName + " (โดย " + teacherName + ")"
		link := "/student/recruitments"
		for studentRows.Next() {
			var stdID int
			studentRows.Scan(&stdID)
			CreateNotification(stdID, msg, link)
		}
	}

	database.DB.Exec("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)")

	c.JSON(http.StatusOK, gin.H{"message": "สร้างประกาศสำเร็จ"})
}

// ============================================================
// EDIT RECRUITMENT
// ============================================================

func TeacherEditRecruitment(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	id := c.Param("id")

	var req struct {
		Title       string         `json:"title" binding:"required"`
		Description string         `json:"description"`
		Status      string         `json:"status"`
		GradeReq    string         `json:"grade_req"`
		Sections    []SectionInput `json:"sections"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	var subjectID int
	err := database.DB.QueryRow("SELECT subject_id FROM recruitments WHERE id=? AND teacher_id=?", id, teacherID).Scan(&subjectID)
	if err != nil {
		c.JSON(http.StatusForbidden, gin.H{"error": "คุณไม่มีสิทธิ์แก้ไขประกาศนี้"})
		return
	}

	tx, _ := database.DB.Begin()

	tx.Exec("UPDATE recruitments SET title=?, description=?, status=? WHERE id=?",
		req.Title, req.Description, req.Status, id)

	totalQuota := 0
	for _, s := range req.Sections {
		totalQuota += s.Quota
	}

	var detailID int
	if err := tx.QueryRow("SELECT id FROM recruitment_details WHERE recruitment_id=?", id).Scan(&detailID); err == nil {
		tx.Exec("UPDATE recruitment_details SET quota=?, grade_requirement=? WHERE recruitment_id=?",
			totalQuota, req.GradeReq, id)
	} else {
		tx.Exec("INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)",
			id, totalQuota, req.GradeReq)
	}

	// Smart section update: match by name
	existingRows, _ := tx.Query("SELECT id, name FROM sections WHERE subject_id=? ORDER BY id ASC", subjectID)
	existingSections := []map[string]interface{}{}
	if existingRows != nil {
		for existingRows.Next() {
			var secID int
			var secName string
			existingRows.Scan(&secID, &secName)
			existingSections = append(existingSections, map[string]interface{}{"id": secID, "name": secName})
		}
		existingRows.Close()
	}

	for _, s := range req.Sections {
		secName := strings.TrimSpace(s.Name)
		if secName == "" {
			continue
		}
		matchedID := 0
		matchedIdx := -1
		for i, es := range existingSections {
			if es["name"] == secName {
				matchedID = es["id"].(int)
				matchedIdx = i
				break
			}
		}
		if matchedID > 0 {
			tx.Exec("UPDATE sections SET schedule_time=?, quota=? WHERE id=?", s.ScheduleTime, s.Quota, matchedID)
			existingSections = append(existingSections[:matchedIdx], existingSections[matchedIdx+1:]...)
		} else {
			tx.Exec("INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)",
				subjectID, secName, s.ScheduleTime, s.Quota)
		}
	}

	// Delete orphaned sections (only if no applications reference them)
	for _, es := range existingSections {
		secID := es["id"].(int)
		tx.Exec(`DELETE FROM sections WHERE id=?
			AND NOT EXISTS (SELECT 1 FROM applications WHERE selected_section_id=?)`, secID, secID)
	}

	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "แก้ไขประกาศสำเร็จ"})
}

// ============================================================
// DELETE RECRUITMENT
// ============================================================

func TeacherDeleteRecruitment(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	id := c.Param("id")

	var subjectID int
	err := database.DB.QueryRow("SELECT subject_id FROM recruitments WHERE id=? AND teacher_id=?", id, teacherID).Scan(&subjectID)
	if err != nil {
		c.JSON(http.StatusForbidden, gin.H{"error": "คุณไม่มีสิทธิ์ลบประกาศนี้"})
		return
	}

	tx, _ := database.DB.Begin()
	tx.Exec("DELETE FROM sections WHERE subject_id=?", subjectID)
	tx.Exec("DELETE FROM applications WHERE recruitment_id=?", id)
	tx.Exec("DELETE FROM recruitment_details WHERE recruitment_id=?", id)
	tx.Exec("DELETE FROM recruitments WHERE id=?", id)
	tx.Commit()

	c.JSON(http.StatusOK, gin.H{"message": "ลบประกาศสำเร็จ"})
}

// ============================================================
// TOGGLE STATUS
// ============================================================

func TeacherToggleStatus(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	id := c.Param("id")

	var req struct {
		Status string `json:"status" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณาระบุสถานะ"})
		return
	}

	var ownerID int
	err := database.DB.QueryRow("SELECT teacher_id FROM recruitments WHERE id=?", id).Scan(&ownerID)
	if err != nil || ownerID != teacherID {
		c.JSON(http.StatusForbidden, gin.H{"error": "Unauthorized"})
		return
	}

	database.DB.Exec("UPDATE recruitments SET status=? WHERE id=?", req.Status, id)
	c.JSON(http.StatusOK, gin.H{"success": true})
}

// ============================================================
// VIEW APPLICANTS
// ============================================================

func TeacherViewApplicants(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	search := c.Query("search")
	subjectFilter := c.Query("subject_id")
	statusFilter := c.Query("status")

	subjectRows, _ := database.DB.Query(`
		SELECT DISTINCT s.id, s.code, s.name
		FROM subjects s
		JOIN recruitments r ON s.id = r.subject_id
		WHERE r.teacher_id=?
	`, teacherID)
	var subjects []map[string]interface{}
	if subjectRows != nil {
		defer subjectRows.Close()
		for subjectRows.Next() {
			var id int
			var code, name string
			subjectRows.Scan(&id, &code, &name)
			subjects = append(subjects, map[string]interface{}{"id": id, "code": code, "name": name})
		}
	}
	if subjects == nil {
		subjects = []map[string]interface{}{}
	}

	query := `
		SELECT a.id AS app_id, a.status, a.created_at,
		       a.grade AS student_grade, COALESCE(a.grade_file,''),
		       u.name AS student_name, u.email, u.id AS user_id,
		       COALESCE(u.student_id,''),
		       s.code AS subject_code, COALESCE(s.revisioncode,''), s.name AS subject_name,
		       r.title AS job_title, r.id AS recruitment_id,
		       COALESCE(sec.name,'') AS section_name, COALESCE(sec.schedule_time,'')
		FROM applications a
		JOIN users u ON a.student_id = u.id
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		LEFT JOIN sections sec ON a.selected_section_id = sec.id
		WHERE r.teacher_id=? AND a.status != 'deleted'
	`
	params := []interface{}{teacherID}

	if search != "" {
		query += " AND (u.name LIKE ? OR u.student_id LIKE ?)"
		sp := "%" + search + "%"
		params = append(params, sp, sp)
	}
	if subjectFilter != "" && subjectFilter != "all" {
		query += " AND s.id = ?"
		params = append(params, subjectFilter)
	}
	if statusFilter != "" && statusFilter != "all" {
		query += " AND a.status = ?"
		params = append(params, statusFilter)
	}

	query += " ORDER BY FIELD(a.status, 'pending', 'approved', 'rejected'), a.created_at DESC"

	appRows, err := database.DB.Query(query, params...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer appRows.Close()

	var applicants []map[string]interface{}
	for appRows.Next() {
		var appID, userID, recruitmentID int
		var status, createdAt, studentGrade, gradeFile string
		var studentName, email, studentID, subjectCode, revisionCode, subjectName, jobTitle string
		var sectionName, scheduleTime string
		appRows.Scan(&appID, &status, &createdAt, &studentGrade, &gradeFile,
			&studentName, &email, &userID, &studentID,
			&subjectCode, &revisionCode, &subjectName, &jobTitle, &recruitmentID,
			&sectionName, &scheduleTime)
		applicants = append(applicants, map[string]interface{}{
			"app_id": appID, "status": status, "created_at": createdAt,
			"student_grade": studentGrade, "grade_file": gradeFile,
			"student_name": studentName, "email": email, "user_id": userID, "student_id": studentID,
			"subject_code": subjectCode, "revisioncode": revisionCode, "subject_name": subjectName,
			"job_title": jobTitle, "recruitment_id": recruitmentID,
			"section_name": sectionName, "schedule_time": scheduleTime,
		})
	}
	if applicants == nil {
		applicants = []map[string]interface{}{}
	}

	c.JSON(http.StatusOK, gin.H{"applicants": applicants, "subjects": subjects})
}

// ============================================================
// UPDATE APPLICATION STATUS
// ============================================================

func TeacherUpdateApplicationStatus(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		Status string `json:"status" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณาระบุสถานะ"})
		return
	}

	_, err := database.DB.Exec("UPDATE applications SET status=? WHERE id=?", req.Status, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	if req.Status == "approved" || req.Status == "rejected" {
		var studentID int
		var subjectName string
		err := database.DB.QueryRow(`
			SELECT a.student_id, s.name
			FROM applications a
			JOIN recruitments r ON a.recruitment_id = r.id
			JOIN subjects s ON r.subject_id = s.id
			WHERE a.id = ?
		`, id).Scan(&studentID, &subjectName)

		if err == nil {
			var msg string
			if req.Status == "approved" {
				msg = "✅ คุณได้รับการอนุมัติเป็น TA ในรายวิชา " + subjectName
			} else {
				msg = "❌ คุณไม่ผ่านการคัดเลือกในรายวิชา " + subjectName
			}
			CreateNotification(studentID, msg, "/student/applications")
		}
	}

	c.JSON(http.StatusOK, gin.H{"message": "อัปเดตสถานะสำเร็จ"})
}

// ============================================================
// MY TAs
// ============================================================

func TeacherGetMyTAs(c *gin.Context) {
	teacherID := c.GetInt("user_id")
	search := c.Query("search")
	subjectFilter := c.Query("subject_id")
	semesterFilter := c.Query("semester")

	subjectRows, _ := database.DB.Query(`
		SELECT DISTINCT s.id, s.code, s.revisioncode, s.name
		FROM subjects s
		JOIN recruitments r ON s.id = r.subject_id
		WHERE r.teacher_id=?
	`, teacherID)
	var subjects []map[string]interface{}
	if subjectRows != nil {
		defer subjectRows.Close()
		for subjectRows.Next() {
			var id int
			var code, revCode, name string
			subjectRows.Scan(&id, &code, &revCode, &name)
			subjects = append(subjects, map[string]interface{}{"id": id, "code": code, "revisioncode": revCode, "name": name})
		}
	}
	if subjects == nil {
		subjects = []map[string]interface{}{}
	}

	semRows, _ := database.DB.Query(`
		SELECT DISTINCT s.semester
		FROM subjects s
		JOIN recruitments r ON s.id = r.subject_id
		WHERE r.teacher_id=?
		ORDER BY s.semester DESC
	`, teacherID)
	var semesters []string
	if semRows != nil {
		defer semRows.Close()
		for semRows.Next() {
			var sem string
			semRows.Scan(&sem)
			semesters = append(semesters, sem)
		}
	}

	query := `
		SELECT a.id AS app_id,
		       u.name AS student_name, COALESCE(u.student_id,''), u.email,
		       s.code AS subject_code, COALESCE(s.revisioncode,''), s.name AS subject_name,
		       COALESCE(sec.id,0) AS section_id, COALESCE(sec.name,'') AS section_name,
		       COALESCE(sec.schedule_time,''),
		       COALESCE(a.grade,''), COALESCE(a.grade_file,'')
		FROM applications a
		JOIN users u ON a.student_id = u.id
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		LEFT JOIN sections sec ON a.selected_section_id = sec.id
		WHERE r.teacher_id=? AND a.status='approved'
	`
	params := []interface{}{teacherID}

	if search != "" {
		query += " AND (u.name LIKE ? OR u.student_id LIKE ?)"
		sp := "%" + search + "%"
		params = append(params, sp, sp)
	}
	if subjectFilter != "" && subjectFilter != "all" {
		query += " AND s.id = ?"
		params = append(params, subjectFilter)
	}
	if semesterFilter != "" && semesterFilter != "all" {
		query += " AND s.semester = ?"
		params = append(params, semesterFilter)
	}

	query += " ORDER BY s.code ASC, sec.id ASC"

	rows, err := database.DB.Query(query, params...)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var tas []map[string]interface{}
	for rows.Next() {
		var appID, sectionID int
		var studentName, studentID, email, subjectCode, revCode, subjectName, sectionName, scheduleTime, grade, gradeFile string
		rows.Scan(&appID, &studentName, &studentID, &email, &subjectCode, &revCode, &subjectName,
			&sectionID, &sectionName, &scheduleTime, &grade, &gradeFile)
		tas = append(tas, map[string]interface{}{
			"app_id":        appID,
			"student_name":  studentName,
			"student_id":    studentID,
			"email":         email,
			"subject_code":  subjectCode,
			"revisioncode":  revCode,
			"subject_name":  subjectName,
			"section_id":    sectionID,
			"section_name":  sectionName,
			"schedule_time": scheduleTime,
			"grade":         grade,
			"grade_file":    gradeFile,
		})
	}
	if tas == nil {
		tas = []map[string]interface{}{}
	}

	// Stats
	totalTAs := len(tas)
	uniqueSubjects := map[string]bool{}
	for _, ta := range tas {
		uniqueSubjects[ta["subject_code"].(string)] = true
	}

	c.JSON(http.StatusOK, gin.H{
		"tas":             tas,
		"subjects":        subjects,
		"semesters":       semesters,
		"total_tas":       totalTAs,
		"unique_subjects": len(uniqueSubjects),
	})
}

// ============================================================
// GET MY SUBJECTS (for create recruitment dropdown)
// ============================================================

func TeacherGetSubjects(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id, code, revisioncode, name, semester FROM subjects ORDER BY code ASC")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var subjects []map[string]interface{}
	for rows.Next() {
		var id int
		var code, revCode, name, semester string
		rows.Scan(&id, &code, &revCode, &name, &semester)
		subjects = append(subjects, map[string]interface{}{
			"id": id, "code": code, "revisioncode": revCode, "name": name, "semester": semester,
		})
	}
	if subjects == nil {
		subjects = []map[string]interface{}{}
	}

	// Use sql.NullString for revisioncode
	_ = sql.NullString{}

	c.JSON(http.StatusOK, subjects)
}
