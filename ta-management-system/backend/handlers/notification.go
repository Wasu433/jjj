package handlers

import (
	"database/sql"
	"net/http"

	"ta-management/database"

	"github.com/gin-gonic/gin"
)

func CreateNotification(userID int, message, link string) {
	database.DB.Exec(
		"INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())",
		userID, message, link,
	)
}

func GetNotifications(c *gin.Context) {
	userID := c.GetInt("user_id")

	rows, err := database.DB.Query(
		"SELECT id, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10",
		userID,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var notifications []map[string]interface{}
	for rows.Next() {
		var id int
		var title sql.NullString
		var message, link string
		var isRead int
		var createdAt string
		rows.Scan(&id, &title, &message, &link, &isRead, &createdAt)
		notifications = append(notifications, map[string]interface{}{
			"id":         id,
			"title":      title.String,
			"message":    message,
			"link":       link,
			"is_read":    isRead,
			"created_at": createdAt,
		})
	}
	if notifications == nil {
		notifications = []map[string]interface{}{}
	}

	var unreadCount int
	database.DB.QueryRow("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", userID).Scan(&unreadCount)

	c.JSON(http.StatusOK, gin.H{
		"notifications": notifications,
		"unread_count":  unreadCount,
	})
}

func MarkNotificationRead(c *gin.Context) {
	userID := c.GetInt("user_id")
	id := c.Param("id")

	database.DB.Exec("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", id, userID)
	c.JSON(http.StatusOK, gin.H{"message": "ok"})
}
