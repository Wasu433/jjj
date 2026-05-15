package handlers

import (
	"compress/gzip"
	"bytes"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strconv"
	"strings"
	"time"

	"ta-management/database"

	"github.com/gin-gonic/gin"
	"golang.org/x/crypto/bcrypt"
)

// ============================================================
// ADMIN DASHBOARD
// ============================================================

func AdminDashboard(c *gin.Context) {
	stats := map[string]interface{}{}

	var totalUsers, totalStudents, totalTeachers, totalSubjects int
	var totalRecruitments, openRecruitments, totalApps, pendingApps, approvedApps int

	database.DB.QueryRow("SELECT COUNT(*) FROM users").Scan(&totalUsers)
	database.DB.QueryRow("SELECT COUNT(*) FROM users WHERE role='student'").Scan(&totalStudents)
	database.DB.QueryRow("SELECT COUNT(*) FROM users WHERE role='teacher'").Scan(&totalTeachers)
	database.DB.QueryRow("SELECT COUNT(*) FROM subjects").Scan(&totalSubjects)
	database.DB.QueryRow("SELECT COUNT(*) FROM recruitments WHERE status != 'deleted'").Scan(&totalRecruitments)
	database.DB.QueryRow("SELECT COUNT(*) FROM recruitments WHERE status = 'open'").Scan(&openRecruitments)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status != 'deleted'").Scan(&totalApps)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status = 'pending'").Scan(&pendingApps)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status = 'approved'").Scan(&approvedApps)

	stats["total_users"] = totalUsers
	stats["total_students"] = totalStudents
	stats["total_teachers"] = totalTeachers
	stats["total_subjects"] = totalSubjects
	stats["total_recruitments"] = totalRecruitments
	stats["open_recruitments"] = openRecruitments
	stats["total_applications"] = totalApps
	stats["pending_applications"] = pendingApps
	stats["approved_applications"] = approvedApps

	rows, err := database.DB.Query("SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 5")
	if err == nil {
		defer rows.Close()
		var recentUsers []map[string]interface{}
		for rows.Next() {
			var id int
			var name, email, role string
			rows.Scan(&id, &name, &email, &role)
			recentUsers = append(recentUsers, map[string]interface{}{"id": id, "name": name, "email": email, "role": role})
		}
		if recentUsers == nil {
			recentUsers = []map[string]interface{}{}
		}
		stats["recent_users"] = recentUsers
	}

	roleRows, err := database.DB.Query("SELECT role, COUNT(*) as count FROM users GROUP BY role")
	if err == nil {
		defer roleRows.Close()
		roleCounts := map[string]int{}
		for roleRows.Next() {
			var role string
			var count int
			roleRows.Scan(&role, &count)
			roleCounts[role] = count
		}
		stats["role_counts"] = roleCounts
	}

	c.JSON(http.StatusOK, stats)
}

// ============================================================
// USER MANAGEMENT
// ============================================================

func AdminGetUsers(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id, name, email, role, COALESCE(student_id,''), created_at FROM users ORDER BY created_at DESC")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var users []map[string]interface{}
	for rows.Next() {
		var id int
		var name, email, role, studentID, createdAt string
		rows.Scan(&id, &name, &email, &role, &studentID, &createdAt)
		users = append(users, map[string]interface{}{
			"id": id, "name": name, "email": email, "role": role, "student_id": studentID, "created_at": createdAt,
		})
	}
	if users == nil {
		users = []map[string]interface{}{}
	}
	c.JSON(http.StatusOK, users)
}

func AdminAddUser(c *gin.Context) {
	var req struct {
		Name     string `json:"name" binding:"required"`
		Email    string `json:"email" binding:"required"`
		Role     string `json:"role" binding:"required"`
		Password string `json:"password" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	var existingID int
	if err := database.DB.QueryRow("SELECT id FROM users WHERE email = ?", req.Email).Scan(&existingID); err == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "อีเมลนี้มีอยู่ในระบบแล้ว"})
		return
	}

	hash, _ := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
	_, err := database.DB.Exec(
		"INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)",
		req.Name, req.Email, string(hash), req.Role,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "เพิ่มผู้ใช้สำเร็จ"})
}

func AdminEditUser(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		Name     string `json:"name" binding:"required"`
		Email    string `json:"email" binding:"required"`
		Role     string `json:"role" binding:"required"`
		Password string `json:"password"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	var existingID int
	if err := database.DB.QueryRow("SELECT id FROM users WHERE email = ? AND id != ?", req.Email, id).Scan(&existingID); err == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "อีเมลนี้มีผู้ใช้งานอื่นใช้แล้ว"})
		return
	}

	var err error
	if req.Password != "" {
		hash, _ := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
		_, err = database.DB.Exec("UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?",
			req.Name, req.Email, req.Role, string(hash), id)
	} else {
		_, err = database.DB.Exec("UPDATE users SET name=?, email=?, role=? WHERE id=?",
			req.Name, req.Email, req.Role, id)
	}
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "แก้ไขผู้ใช้สำเร็จ"})
}

func AdminDeleteUser(c *gin.Context) {
	id := c.Param("id")
	currentUserID := strconv.Itoa(c.GetInt("user_id"))
	if id == currentUserID {
		c.JSON(http.StatusBadRequest, gin.H{"error": "ไม่สามารถลบบัญชีตัวเองได้"})
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	tx.Exec("DELETE FROM applications WHERE student_id = ?", id)
	_, err = tx.Exec("DELETE FROM users WHERE id = ?", id)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": "ลบไม่สำเร็จ! ผู้ใช้นี้อาจมีข้อมูลเชื่อมโยงอยู่ (เช่น เป็นอาจารย์ที่มีรายวิชา)"})
		return
	}
	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "ลบผู้ใช้สำเร็จ"})
}

// ============================================================
// SUBJECT MANAGEMENT
// ============================================================

func AdminGetSubjects(c *gin.Context) {
	rows, err := database.DB.Query(`
		SELECT s.id, s.code, COALESCE(s.course_id,''), s.name, COALESCE(s.semester,''), COALESCE(s.revisioncode,''),
		       COALESCE(s.teacher_id,0), COALESCE(u.name,'')
		FROM subjects s
		LEFT JOIN users u ON s.teacher_id = u.id
		ORDER BY s.name ASC
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var subjects []map[string]interface{}
	for rows.Next() {
		var id, teacherID int
		var code, courseID, name, semester, revisionCode, teacherName string
		rows.Scan(&id, &code, &courseID, &name, &semester, &revisionCode, &teacherID, &teacherName)
		subjects = append(subjects, map[string]interface{}{
			"id": id, "code": code, "course_id": courseID, "name": name,
			"semester": semester, "revisioncode": revisionCode, "teacher_id": teacherID, "teacher_name": teacherName,
		})
	}
	if subjects == nil {
		subjects = []map[string]interface{}{}
	}
	c.JSON(http.StatusOK, subjects)
}

func AdminGetTeachers(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name ASC")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var teachers []map[string]interface{}
	for rows.Next() {
		var id int
		var name string
		rows.Scan(&id, &name)
		teachers = append(teachers, map[string]interface{}{"id": id, "name": name})
	}
	if teachers == nil {
		teachers = []map[string]interface{}{}
	}
	c.JSON(http.StatusOK, teachers)
}

func AdminAddSubject(c *gin.Context) {
	var req struct {
		Code      string  `json:"code" binding:"required"`
		CourseID  string  `json:"course_id"`
		Name      string  `json:"name" binding:"required"`
		Term      string  `json:"term" binding:"required"`
		Year      string  `json:"year" binding:"required"`
		TeacherID *int    `json:"teacher_id"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	semester := req.Term + "/" + req.Year
	var teacherID interface{} = nil
	if req.TeacherID != nil && *req.TeacherID != 0 {
		teacherID = *req.TeacherID
	}

	_, err := database.DB.Exec(
		"INSERT INTO subjects (code, course_id, name, semester, teacher_id) VALUES (?, ?, ?, ?, ?)",
		req.Code, req.CourseID, req.Name, semester, teacherID,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "เพิ่มรายวิชาสำเร็จ"})
}

func AdminEditSubject(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		Code      string `json:"code" binding:"required"`
		Name      string `json:"name" binding:"required"`
		Term      string `json:"term" binding:"required"`
		Year      string `json:"year" binding:"required"`
		TeacherID *int   `json:"teacher_id"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	semester := req.Term + "/" + req.Year
	var teacherID interface{} = nil
	if req.TeacherID != nil && *req.TeacherID != 0 {
		teacherID = *req.TeacherID
	}

	_, err := database.DB.Exec(
		"UPDATE subjects SET code=?, name=?, semester=?, teacher_id=? WHERE id=?",
		req.Code, req.Name, semester, teacherID, id,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "แก้ไขรายวิชาสำเร็จ"})
}

func AdminDeleteSubject(c *gin.Context) {
	id := c.Param("id")
	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	tx.Exec("DELETE FROM sections WHERE subject_id = ?", id)
	_, err = tx.Exec("DELETE FROM subjects WHERE id = ?", id)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "ลบรายวิชาสำเร็จ"})
}

// ============================================================
// RECRUITMENT MANAGEMENT
// ============================================================

type SectionInput struct {
	Name         string `json:"name"`
	ScheduleTime string `json:"schedule_time"`
	Quota        int    `json:"quota"`
}

func AdminGetRecruitments(c *gin.Context) {
	rows, err := database.DB.Query(`
		SELECT r.id, r.title, r.description, r.status, r.created_at,
		       r.subject_id, s.code, s.name AS subject_name, s.semester,
		       r.teacher_id, u.name AS teacher_name,
		       COALESCE(rd.quota,0), COALESCE(rd.grade_requirement,''),
		       COALESCE((SELECT GROUP_CONCAT(CONCAT(sec.id,'|',sec.name,'|',sec.schedule_time,'|',sec.quota) SEPARATOR ';;')
		                 FROM sections sec WHERE sec.subject_id = r.subject_id), '') AS sections_data
		FROM recruitments r
		JOIN subjects s ON r.subject_id = s.id
		JOIN users u ON r.teacher_id = u.id
		LEFT JOIN recruitment_details rd ON r.id = rd.recruitment_id
		WHERE r.status != 'deleted'
		ORDER BY r.created_at DESC
	`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var recruitments []map[string]interface{}
	for rows.Next() {
		var id, subjectID, teacherID, quota int
		var title, description, status, createdAt string
		var subjectCode, subjectName, semester, teacherName, gradeReq, sectionsData string
		rows.Scan(&id, &title, &description, &status, &createdAt,
			&subjectID, &subjectCode, &subjectName, &semester,
			&teacherID, &teacherName, &quota, &gradeReq, &sectionsData)

		sections := parseSections(sectionsData)
		recruitments = append(recruitments, map[string]interface{}{
			"id": id, "title": title, "description": description, "status": status, "created_at": createdAt,
			"subject_id": subjectID, "subject_code": subjectCode, "subject_name": subjectName, "semester": semester,
			"teacher_id": teacherID, "teacher_name": teacherName,
			"quota": quota, "grade_requirement": gradeReq, "sections": sections,
		})
	}
	if recruitments == nil {
		recruitments = []map[string]interface{}{}
	}
	c.JSON(http.StatusOK, recruitments)
}

func parseSections(data string) []map[string]interface{} {
	var sections []map[string]interface{}
	if data == "" {
		return sections
	}
	parts := strings.Split(data, ";;")
	for _, part := range parts {
		fields := strings.Split(part, "|")
		if len(fields) < 4 {
			continue
		}
		id, _ := strconv.Atoi(fields[0])
		quota, _ := strconv.Atoi(fields[3])
		sections = append(sections, map[string]interface{}{
			"id": id, "name": fields[1], "schedule_time": fields[2], "quota": quota,
		})
	}
	return sections
}

func AdminAddRecruitment(c *gin.Context) {
	var req struct {
		TeacherID   int            `json:"teacher_id" binding:"required"`
		SubjectID   int            `json:"subject_id" binding:"required"`
		Title       string         `json:"title" binding:"required"`
		Description string         `json:"description"`
		GradeReq    string         `json:"grade_req"`
		Sections    []SectionInput `json:"sections"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	result, err := tx.Exec(
		"INSERT INTO recruitments (teacher_id, title, description, subject_id, status) VALUES (?, ?, ?, ?, 'open')",
		req.TeacherID, req.Title, req.Description, req.SubjectID,
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

	_, err = tx.Exec(
		"INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)",
		recruitmentID, totalQuota, req.GradeReq,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	for _, s := range req.Sections {
		if strings.TrimSpace(s.Name) != "" {
			tx.Exec(
				"INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)",
				req.SubjectID, s.Name, s.ScheduleTime, s.Quota,
			)
		}
	}

	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "เพิ่มประกาศสำเร็จ"})
}

func AdminEditRecruitment(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		TeacherID   int            `json:"teacher_id" binding:"required"`
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

	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	_, err = tx.Exec(
		"UPDATE recruitments SET title=?, description=?, subject_id=?, teacher_id=?, status=? WHERE id=?",
		req.Title, req.Description, req.SubjectID, req.TeacherID, req.Status, id,
	)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	totalQuota := 0
	for _, s := range req.Sections {
		totalQuota += s.Quota
	}

	var detailID int
	if err := tx.QueryRow("SELECT id FROM recruitment_details WHERE recruitment_id = ?", id).Scan(&detailID); err == nil {
		tx.Exec("UPDATE recruitment_details SET quota=?, grade_requirement=? WHERE recruitment_id=?", totalQuota, req.GradeReq, id)
	} else {
		tx.Exec("INSERT INTO recruitment_details (recruitment_id, quota, grade_requirement) VALUES (?, ?, ?)", id, totalQuota, req.GradeReq)
	}

	var hasApps int
	tx.QueryRow("SELECT COUNT(*) FROM applications WHERE recruitment_id = ?", id).Scan(&hasApps)
	if hasApps == 0 {
		tx.Exec("DELETE FROM sections WHERE subject_id = ?", req.SubjectID)
		for _, s := range req.Sections {
			if strings.TrimSpace(s.Name) != "" {
				tx.Exec("INSERT INTO sections (subject_id, name, schedule_time, quota) VALUES (?, ?, ?, ?)",
					req.SubjectID, s.Name, s.ScheduleTime, s.Quota)
			}
		}
	}

	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "แก้ไขประกาศสำเร็จ"})
}

func AdminDeleteRecruitment(c *gin.Context) {
	id := c.Param("id")

	var subjectID int
	database.DB.QueryRow("SELECT subject_id FROM recruitments WHERE id = ?", id).Scan(&subjectID)

	tx, err := database.DB.Begin()
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	if subjectID > 0 {
		tx.Exec("DELETE FROM sections WHERE subject_id = ?", subjectID)
	}
	tx.Exec("DELETE FROM applications WHERE recruitment_id = ?", id)
	tx.Exec("DELETE FROM recruitment_details WHERE recruitment_id = ?", id)
	_, err = tx.Exec("DELETE FROM recruitments WHERE id = ?", id)
	if err != nil {
		tx.Rollback()
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	tx.Commit()
	c.JSON(http.StatusOK, gin.H{"message": "ลบประกาศสำเร็จ"})
}

// ============================================================
// REPORTS
// ============================================================

func AdminGetReports(c *gin.Context) {
	result := map[string]interface{}{}

	var appStats struct {
		Total    int
		Pending  int
		Approved int
		Rejected int
	}
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status != 'deleted'").Scan(&appStats.Total)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status='pending'").Scan(&appStats.Pending)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status='approved'").Scan(&appStats.Approved)
	database.DB.QueryRow("SELECT COUNT(*) FROM applications WHERE status='rejected'").Scan(&appStats.Rejected)
	result["app_stats"] = appStats

	rows, _ := database.DB.Query(`
		SELECT s.name, COUNT(a.id) as total, SUM(a.status='approved') as approved
		FROM applications a
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		WHERE a.status != 'deleted'
		GROUP BY s.id, s.name
		ORDER BY total DESC
		LIMIT 10
	`)
	if rows != nil {
		defer rows.Close()
		var subjectStats []map[string]interface{}
		for rows.Next() {
			var name string
			var total, approved int
			rows.Scan(&name, &total, &approved)
			subjectStats = append(subjectStats, map[string]interface{}{"name": name, "total": total, "approved": approved})
		}
		if subjectStats == nil {
			subjectStats = []map[string]interface{}{}
		}
		result["subject_stats"] = subjectStats
	}

	recentRows, _ := database.DB.Query(`
		SELECT u.name as student_name, u.student_id, s.code, s.name as subject_name,
		       u2.name as teacher_name, a.created_at
		FROM applications a
		JOIN users u ON a.student_id = u.id
		JOIN recruitments r ON a.recruitment_id = r.id
		JOIN subjects s ON r.subject_id = s.id
		JOIN users u2 ON r.teacher_id = u2.id
		WHERE a.status = 'approved'
		ORDER BY a.created_at DESC
		LIMIT 10
	`)
	if recentRows != nil {
		defer recentRows.Close()
		var recentTAs []map[string]interface{}
		for recentRows.Next() {
			var studentName, studentID, code, subjectName, teacherName, createdAt string
			recentRows.Scan(&studentName, &studentID, &code, &subjectName, &teacherName, &createdAt)
			recentTAs = append(recentTAs, map[string]interface{}{
				"student_name": studentName, "student_id": studentID,
				"subject_code": code, "subject_name": subjectName,
				"teacher_name": teacherName, "created_at": createdAt,
			})
		}
		if recentTAs == nil {
			recentTAs = []map[string]interface{}{}
		}
		result["recent_tas"] = recentTAs
	}

	c.JSON(http.StatusOK, result)
}

// ============================================================
// SYNC SUBJECTS FROM EXTERNAL API
// ============================================================

func SyncSubjects(c *gin.Context) {
	const baseURL = "https://reg6.su.ac.th/regapiweb3/api/th"

	token, err := fetchToken(baseURL)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "ดึง Token ไม่สำเร็จ: " + err.Error()})
		return
	}

	acad, err := fetchAcad(baseURL, token)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "ดึงปีการศึกษาไม่สำเร็จ: " + err.Error()})
		return
	}

	year := int(acad["enrollacadyear"].(float64))
	semester := int(acad["enrollsemester"].(float64))

	courseListURL := fmt.Sprintf("%s/Classinfo/Classinfo/1/800/%d/%d/-9/7/1/5*/null/1/-9/-9/-9", baseURL, year, semester)
	courses, err := fetchAndDecode(courseListURL, token)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "ดึงรายวิชาไม่สำเร็จ: " + err.Error()})
		return
	}

	courseList, ok := courses[0]["classinfolist"].([]interface{})
	if !ok {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "รูปแบบข้อมูลไม่ถูกต้อง"})
		return
	}

	inserted := 0
	for _, item := range courseList {
		course, ok := item.(map[string]interface{})
		if !ok {
			continue
		}
		courseID, _ := course["courseid"].(string)
		if courseID == "" {
			continue
		}

		detailURL := fmt.Sprintf("%s/Classinfo/Classdetail/%d/%d/%s", baseURL, year, semester, courseID)
		details, err := fetchAndDecode(detailURL, token)
		if err != nil || len(details) == 0 {
			continue
		}

		d := details[0]
		code, _ := d["coursecode"].(string)
		revisionCode, _ := d["revisioncode"].(string)
		name, _ := d["coursename"].(string)
		sem, _ := d["semester"].(string)
		cid, _ := d["courseid"].(string)

		if cid == "" || code == "" {
			continue
		}

		var existingSubjectID int
		err = database.DB.QueryRow("SELECT id FROM subjects WHERE course_id = ?", cid).Scan(&existingSubjectID)
		if err == sql.ErrNoRows {
			result, err := database.DB.Exec(
				"INSERT INTO subjects (course_id, code, revisioncode, name, semester) VALUES (?, ?, ?, ?, ?)",
				cid, code, revisionCode, name, sem,
			)
			if err == nil {
				id, _ := result.LastInsertId()
				existingSubjectID = int(id)
			}
		} else if err == nil {
			database.DB.Exec(
				"UPDATE subjects SET code=?, revisioncode=?, name=?, semester=? WHERE id=?",
				code, revisionCode, name, sem, existingSubjectID,
			)
			database.DB.Exec("DELETE FROM subject_teachers WHERE subject_id = ?", existingSubjectID)
		}

		if instructors, ok := d["instructor"].([]interface{}); ok {
			for _, ins := range instructors {
				insMap, ok := ins.(map[string]interface{})
				if !ok {
					continue
				}
				prefix, _ := insMap["prefixname"].(string)
				fname, _ := insMap["officername"].(string)
				lname, _ := insMap["officersurname"].(string)
				if fname == "" {
					continue
				}
				fullName := strings.TrimSpace(prefix + " " + fname + " " + lname)
				database.DB.Exec(
					"INSERT IGNORE INTO subject_teachers (subject_id, teacher_name) VALUES (?, ?)",
					existingSubjectID, fullName,
				)
			}
		}

		inserted++
		time.Sleep(200 * time.Millisecond)
	}

	c.JSON(http.StatusOK, gin.H{"message": fmt.Sprintf("ซิงค์รายวิชาสำเร็จ %d วิชา", inserted)})
}

func fetchToken(baseURL string) (string, error) {
	resp, err := http.Get(baseURL + "/Validate/tokenservice")
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()

	var result map[string]interface{}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return "", err
	}

	token, ok := result["token"].(string)
	if !ok {
		return "", fmt.Errorf("token not found")
	}
	return token, nil
}

func fetchAcad(baseURL, token string) (map[string]interface{}, error) {
	req, _ := http.NewRequest("GET", baseURL+"/Schg/Getacad", nil)
	req.Header.Set("Authorization", "Bearer "+token)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var result map[string]interface{}
	json.NewDecoder(resp.Body).Decode(&result)
	return result, nil
}

func fetchAndDecode(url, token string) ([]map[string]interface{}, error) {
	req, _ := http.NewRequest("GET", url, nil)
	req.Header.Set("Authorization", "Bearer "+token)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	var result map[string]interface{}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return nil, err
	}

	encoded, ok := result["result"].(string)
	if !ok {
		return nil, fmt.Errorf("result field not found")
	}

	decoded, err := base64.StdEncoding.DecodeString(encoded)
	if err != nil {
		return nil, err
	}

	gr, err := gzip.NewReader(bytes.NewReader(decoded))
	if err != nil {
		return nil, err
	}
	defer gr.Close()

	unzipped, err := io.ReadAll(gr)
	if err != nil {
		return nil, err
	}

	var data []map[string]interface{}
	json.Unmarshal(unzipped, &data)
	return data, nil
}
