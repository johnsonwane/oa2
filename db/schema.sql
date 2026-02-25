CREATE DATABASE IF NOT EXISTS oa2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oa2;

CREATE TABLE IF NOT EXISTS oa_menu (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  menu_name VARCHAR(100) NOT NULL,
  menu_key VARCHAR(100) NOT NULL,
  path VARCHAR(255) NOT NULL,
  icon VARCHAR(100) DEFAULT '',
  sort_no INT NOT NULL DEFAULT 0,
  status TINYINT NOT NULL DEFAULT 1 COMMENT '1启用 0禁用',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_menu_key (menu_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO oa_menu (menu_name, menu_key, path, icon, sort_no, status)
VALUES
('首页工作台', 'workbench', '/workbench', '🏠', 1, 1),
('学员管理', 'student', '/student', '🎓', 2, 1),
('教师管理', 'teacher', '/teacher', '👩‍🏫', 3, 1),
('课程管理', 'course', '/course', '📘', 4, 1),
('排课管理', 'schedule', '/schedule', '🗓️', 5, 1),
('班级管理', 'classroom', '/classroom', '🏫', 6, 1),
('考勤管理', 'attendance', '/attendance', '🕘', 7, 1),
('考试与成绩', 'exam', '/exam', '📝', 8, 1),
('教务审批', 'approval', '/approval', '✅', 9, 1),
('财务收费', 'finance', '/finance', '💰', 10, 1),
('市场招生', 'marketing', '/marketing', '📢', 11, 1),
('人事行政', 'hr_admin', '/hr-admin', '👥', 12, 1),
('系统设置', 'system', '/system', '⚙️', 13, 1)
ON DUPLICATE KEY UPDATE
  menu_name = VALUES(menu_name),
  path = VALUES(path),
  icon = VALUES(icon),
  sort_no = VALUES(sort_no),
  status = VALUES(status);
