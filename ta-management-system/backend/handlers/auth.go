package handlers

import (
	"database/sql"
	"net/http"
	"regexp"
	"time"

	"ta-management/database"
	"ta-management/middleware"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

func Login(c *gin.Context) {
	var req struct {
		Email    string `json:"email" binding:"required"`
		Password string `json:"password" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	var user struct {
		ID           int
		Name         string
		Email        string
		PasswordHash string
		StudentID    sql.NullString
		Role         string
	}

	err := database.DB.QueryRow(
		"SELECT id, name, email, password_hash, student_id, role FROM users WHERE email = ?",
		req.Email,
	).Scan(&user.ID, &user.Name, &user.Email, &user.PasswordHash, &user.StudentID, &user.Role)

	if err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "อีเมลหรือรหัสผ่านไม่ถูกต้อง"})
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.PasswordHash), []byte(req.Password)); err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"error": "อีเมลหรือรหัสผ่านไม่ถูกต้อง"})
		return
	}

	studentID := ""
	if user.StudentID.Valid {
		studentID = user.StudentID.String
	}

	claims := &middleware.Claims{
		UserID:    user.ID,
		Role:      user.Role,
		Name:      user.Name,
		StudentID: studentID,
		Email:     user.Email,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(24 * time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	tokenStr, err := token.SignedString(middleware.GetJWTSecret())
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด"})
		return
	}

	c.SetCookie("token", tokenStr, 86400, "/", "", false, true)
	c.JSON(http.StatusOK, gin.H{
		"message": "เข้าสู่ระบบสำเร็จ",
		"user": gin.H{
			"id":         user.ID,
			"name":       user.Name,
			"email":      user.Email,
			"role":       user.Role,
			"student_id": studentID,
		},
	})
}

func Logout(c *gin.Context) {
	c.SetCookie("token", "", -1, "/", "", false, true)
	c.JSON(http.StatusOK, gin.H{"message": "ออกจากระบบสำเร็จ"})
}

func Register(c *gin.Context) {
	var req struct {
		Name            string `json:"name" binding:"required"`
		Email           string `json:"email" binding:"required"`
		Password        string `json:"password" binding:"required"`
		ConfirmPassword string `json:"confirm_password" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	emailRegex := regexp.MustCompile(`(?i)@silpakorn\.edu$`)
	if !emailRegex.MatchString(req.Email) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณาใช้อีเมลของมหาวิทยาลัย (@silpakorn.edu) ในการลงทะเบียนเท่านั้น"})
		return
	}

	if len(req.Password) < 8 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร"})
		return
	}

	if req.Password != req.ConfirmPassword {
		c.JSON(http.StatusBadRequest, gin.H{"error": "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน"})
		return
	}

	var existingID int
	err := database.DB.QueryRow("SELECT id FROM users WHERE email = ?", req.Email).Scan(&existingID)
	if err == nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "อีเมลนี้ถูกใช้งานแล้ว"})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.Password), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด"})
		return
	}

	_, err = database.DB.Exec(
		"INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')",
		req.Name, req.Email, string(hash),
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด: "+err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "ลงทะเบียนสำเร็จเรียบร้อย!"})
}

func GetMe(c *gin.Context) {
	c.JSON(http.StatusOK, gin.H{
		"id":         c.GetInt("user_id"),
		"name":       c.GetString("name"),
		"role":       c.GetString("role"),
		"student_id": c.GetString("student_id"),
		"email":      c.GetString("email"),
	})
}

func ChangePassword(c *gin.Context) {
	userID := c.GetInt("user_id")

	var req struct {
		CurrentPassword string `json:"current_password" binding:"required"`
		NewPassword     string `json:"new_password" binding:"required"`
		ConfirmPassword string `json:"confirm_password" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "กรุณากรอกข้อมูลให้ครบ"})
		return
	}

	if len(req.NewPassword) < 8 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร"})
		return
	}

	if req.NewPassword != req.ConfirmPassword {
		c.JSON(http.StatusBadRequest, gin.H{"error": "รหัสผ่านใหม่ไม่ตรงกัน"})
		return
	}

	var currentHash string
	err := database.DB.QueryRow("SELECT password_hash FROM users WHERE id = ?", userID).Scan(&currentHash)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด"})
		return
	}

	if err := bcrypt.CompareHashAndPassword([]byte(currentHash), []byte(req.CurrentPassword)); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "รหัสผ่านปัจจุบันไม่ถูกต้อง"})
		return
	}

	newHash, err := bcrypt.GenerateFromPassword([]byte(req.NewPassword), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด"})
		return
	}

	_, err = database.DB.Exec("UPDATE users SET password_hash = ? WHERE id = ?", string(newHash), userID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "เกิดข้อผิดพลาด"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อย!"})
}
