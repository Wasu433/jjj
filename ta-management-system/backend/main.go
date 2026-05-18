package main

import (
	"net/http"
	"os"
	"strings"

	"ta-management/database"
	"ta-management/handlers"
	"ta-management/middleware"

	"github.com/gin-contrib/cors"
	"github.com/gin-gonic/gin"
)

func main() {
	database.Connect()

	r := gin.Default()

	frontendOrigin := os.Getenv("FRONTEND_ORIGIN")
	if frontendOrigin == "" {
		frontendOrigin = "http://localhost:5173"
	}
	allowedOrigins := strings.Split(frontendOrigin, ",")

	r.Use(cors.New(cors.Config{
		AllowOrigins:     allowedOrigins,
		AllowMethods:     []string{"GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"},
		AllowHeaders:     []string{"Origin", "Content-Type", "Authorization"},
		AllowCredentials: true,
	}))

	// Static files for uploads
	uploadsDir := os.Getenv("UPLOADS_DIR")
	if uploadsDir == "" {
		uploadsDir = "./uploads"
	}
	r.Static("/uploads", uploadsDir)

	api := r.Group("/api")

	// ====================================================
	// PUBLIC ROUTES
	// ====================================================
	api.POST("/login", handlers.Login)
	api.POST("/register", handlers.Register)

	// ====================================================
	// AUTHENTICATED ROUTES
	// ====================================================
	auth := api.Group("/")
	auth.Use(middleware.AuthMiddleware())
	{
		auth.POST("/logout", handlers.Logout)
		auth.GET("/me", handlers.GetMe)
		auth.POST("/change-password", handlers.ChangePassword)

		// Notifications
		auth.GET("/notifications", handlers.GetNotifications)
		auth.PUT("/notifications/:id/read", handlers.MarkNotificationRead)
	}

	// ====================================================
	// ADMIN ROUTES
	// ====================================================
	adminGroup := api.Group("/admin")
	adminGroup.Use(middleware.AuthMiddleware(), middleware.RequireRole("admin"))
	{
		adminGroup.GET("/dashboard", handlers.AdminDashboard)

		// Users
		adminGroup.GET("/users", handlers.AdminGetUsers)
		adminGroup.POST("/users", handlers.AdminAddUser)
		adminGroup.PUT("/users/:id", handlers.AdminEditUser)
		adminGroup.DELETE("/users/:id", handlers.AdminDeleteUser)

		// Subjects
		adminGroup.GET("/subjects", handlers.AdminGetSubjects)
		adminGroup.GET("/teachers", handlers.AdminGetTeachers)
		adminGroup.POST("/subjects", handlers.AdminAddSubject)
		adminGroup.PUT("/subjects/:id", handlers.AdminEditSubject)
		adminGroup.DELETE("/subjects/:id", handlers.AdminDeleteSubject)
		adminGroup.POST("/sync-subjects", handlers.SyncSubjects)

		// Recruitments
		adminGroup.GET("/recruitments", handlers.AdminGetRecruitments)
		adminGroup.POST("/recruitments", handlers.AdminAddRecruitment)
		adminGroup.PUT("/recruitments/:id", handlers.AdminEditRecruitment)
		adminGroup.DELETE("/recruitments/:id", handlers.AdminDeleteRecruitment)

		// Reports
		adminGroup.GET("/reports", handlers.AdminGetReports)
	}

	// ====================================================
	// TEACHER ROUTES
	// ====================================================
	teacherGroup := api.Group("/teacher")
	teacherGroup.Use(middleware.AuthMiddleware(), middleware.RequireRole("teacher"))
	{
		teacherGroup.GET("/dashboard", handlers.TeacherDashboard)
		teacherGroup.GET("/subjects", handlers.TeacherGetSubjects)

		// Recruitments
		teacherGroup.GET("/recruitments", handlers.TeacherGetMyRecruitments)
		teacherGroup.POST("/recruitments", handlers.TeacherCreateRecruitment)
		teacherGroup.PUT("/recruitments/:id", handlers.TeacherEditRecruitment)
		teacherGroup.DELETE("/recruitments/:id", handlers.TeacherDeleteRecruitment)
		teacherGroup.PATCH("/recruitments/:id/toggle", handlers.TeacherToggleStatus)

		// Applicants
		teacherGroup.GET("/applicants", handlers.TeacherViewApplicants)
		teacherGroup.PATCH("/applications/:id/status", handlers.TeacherUpdateApplicationStatus)

		// TAs
		teacherGroup.GET("/tas", handlers.TeacherGetMyTAs)
	}

	// ====================================================
	// STUDENT ROUTES
	// ====================================================
	studentGroup := api.Group("/student")
	studentGroup.Use(middleware.AuthMiddleware(), middleware.RequireRole("student"))
	{
		studentGroup.GET("/dashboard", handlers.StudentDashboard)
		studentGroup.GET("/recruitments", handlers.StudentGetRecruitments)
		studentGroup.POST("/apply", handlers.StudentApply)
		studentGroup.DELETE("/applications/:id", handlers.StudentCancelApplication)
		studentGroup.GET("/applications", handlers.StudentGetMyApplications)
		studentGroup.GET("/profile", handlers.StudentGetProfile)
		studentGroup.PUT("/profile", handlers.StudentUpdateProfile)
	}

	// Health check
	r.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"status": "ok"})
	})

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	r.Run(":" + port)
}
