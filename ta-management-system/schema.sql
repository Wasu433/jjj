-- =====================================================
-- TA Management System - Database Schema
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. users
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(255) NOT NULL,
  `email`         VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
  `student_id`    VARCHAR(20)  NULL,
  `resume_path`   VARCHAR(500) NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. subjects
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`         VARCHAR(20)  NOT NULL,
  `course_id`    VARCHAR(50)  NULL,
  `name`         VARCHAR(255) NOT NULL,
  `semester`     VARCHAR(20)  NULL COMMENT 'e.g. 1/2567',
  `revisioncode` VARCHAR(50)  NULL,
  `teacher_id`   INT UNSIGNED NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. subject_teachers (ตารางเชื่อม อาจารย์ <-> วิชา สำหรับ sync API)
CREATE TABLE IF NOT EXISTS `subject_teachers` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subject_id`   INT UNSIGNED NOT NULL,
  `teacher_name` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. sections
CREATE TABLE IF NOT EXISTS `sections` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subject_id`    INT UNSIGNED NOT NULL,
  `name`          VARCHAR(100) NOT NULL,
  `schedule_time` VARCHAR(100) NULL,
  `quota`         INT UNSIGNED NOT NULL DEFAULT 1,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. recruitments
CREATE TABLE IF NOT EXISTS `recruitments` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `teacher_id`  INT UNSIGNED NOT NULL,
  `subject_id`  INT UNSIGNED NOT NULL,
  `title`       VARCHAR(255) NOT NULL,
  `description` TEXT         NULL,
  `status`      ENUM('open','closed','deleted') NOT NULL DEFAULT 'open',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. recruitment_details
CREATE TABLE IF NOT EXISTS `recruitment_details` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recruitment_id`    INT UNSIGNED NOT NULL UNIQUE,
  `quota`             INT UNSIGNED NOT NULL DEFAULT 1,
  `grade_requirement` VARCHAR(10)  NULL,
  FOREIGN KEY (`recruitment_id`) REFERENCES `recruitments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. applications
CREATE TABLE IF NOT EXISTS `applications` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recruitment_id`      INT UNSIGNED NOT NULL,
  `student_id`          INT UNSIGNED NOT NULL,
  `selected_section_id` INT UNSIGNED NULL,
  `status`              ENUM('pending','approved','rejected','deleted') NOT NULL DEFAULT 'pending',
  `grade`               VARCHAR(10)  NULL,
  `grade_file`          VARCHAR(500) NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`recruitment_id`)      REFERENCES `recruitments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`)          REFERENCES `users`(`id`)        ON DELETE CASCADE,
  FOREIGN KEY (`selected_section_id`) REFERENCES `sections`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(255) NULL,
  `message`    TEXT         NOT NULL,
  `link`       VARCHAR(500) NULL DEFAULT '#',
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
