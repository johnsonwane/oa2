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
('首页', 'dashboard_home', '/dashboard/home', 'home', 1, 1),
('学员管理', 'student_manage', '/student/list', 'user', 2, 1),
('课程管理', 'course_manage', '/course/list', 'book', 3, 1)
ON DUPLICATE KEY UPDATE menu_name = VALUES(menu_name);
