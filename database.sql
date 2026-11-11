-- LearnLoop E-Learning Portal schema + seed data
CREATE DATABASE IF NOT EXISTS elearn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elearn;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  avatar VARCHAR(120) DEFAULT NULL,
  bio VARCHAR(300) DEFAULT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE otp_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  category_id INT NOT NULL,
  level ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Beginner',
  instructor VARCHAR(80) NOT NULL,
  duration_hours DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FULLTEXT KEY ft_courses (title, description)
) ENGINE=InnoDB;

CREATE TABLE lessons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  content TEXT NOT NULL,
  video_url VARCHAR(255) DEFAULT NULL,
  position INT NOT NULL DEFAULT 1,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  course_id INT NOT NULL,
  enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_enroll (user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE lesson_progress (
  user_id INT NOT NULL,
  lesson_id INT NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, lesson_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed: admin login  admin@learnloop.test / password  (CHANGE IT after first login)
INSERT INTO users (name,email,password,role,is_verified) VALUES
('Admin','admin@learnloop.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1);

INSERT INTO categories (name) VALUES ('Web Development'),('Databases'),('Programming'),('Design');

INSERT INTO courses (title,description,category_id,level,instructor,duration_hours) VALUES
('HTML & CSS Foundations','Build and style responsive pages from scratch using semantic HTML, Flexbox and Grid.',1,'Beginner','Admin',6.0),
('JavaScript in the Browser','DOM, events, fetch and AJAX: make pages react to what people do.',1,'Intermediate','Admin',8.5),
('PHP & MySQL Essentials','Sessions, PDO, prepared statements and CRUD apps with a MySQL backend.',2,'Intermediate','Admin',10.0),
('Git & GitHub Workflow','Branches, pull requests and deploying from a GitHub repository.',3,'Beginner','Admin',3.5);

INSERT INTO lessons (course_id,title,content,video_url,position) VALUES
(1,'Semantic page structure','Use header, nav, main, section and footer so browsers and screen readers understand your page.',NULL,1),
(1,'Flexbox layouts','Align items on one axis with display:flex, gap, justify-content and align-items.',NULL,2),
(1,'Responsive with media queries','Start mobile-first and add breakpoints only where the layout breaks.',NULL,3),
(2,'Selecting and changing the DOM','querySelector, textContent, classList and event listeners.',NULL,1),
(2,'Fetch and JSON','Call a PHP endpoint with fetch(), parse JSON, and render results safely.',NULL,2),
(3,'Connecting with PDO','Create a PDO connection with exceptions enabled and utf8mb4.',NULL,1),
(3,'Prepared statements','Never concatenate user input into SQL. Bind parameters instead.',NULL,2),
(3,'CRUD patterns','Create, read, update and delete rows, with CSRF tokens on every POST.',NULL,3),
(4,'Commits and branches','Stage, commit, branch and merge with clear commit messages.',NULL,1),
(4,'Pull requests','Push a branch, open a pull request, review and merge.',NULL,2);
