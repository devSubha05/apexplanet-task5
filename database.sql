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

INSERT INTO categories (name) VALUES ('Web Development'),('Databases'),('Programming'),('Design'),('Data Science'),('Mobile Development'),('DevOps'),('Cybersecurity');

INSERT INTO courses (title,description,category_id,level,instructor,duration_hours) VALUES
('HTML & CSS Foundations','Build and style responsive pages from scratch using semantic HTML, Flexbox and Grid.',1,'Beginner','Admin',6.0),
('JavaScript in the Browser','DOM, events, fetch and AJAX: make pages react to what people do.',1,'Intermediate','Admin',8.5),
('PHP & MySQL Essentials','Sessions, PDO, prepared statements and CRUD apps with a MySQL backend.',2,'Intermediate','Admin',10.0),
('Git & GitHub Workflow','Branches, pull requests and deploying from a GitHub repository.',3,'Beginner','Admin',3.5),
('React.js Fundamentals','Build dynamic single-page apps with components, hooks, state management and React Router.',1,'Intermediate','Admin',12.0),
('Node.js & Express API','Create RESTful APIs with Express, middleware, JWT authentication and error handling.',1,'Intermediate','Admin',9.5),
('Tailwind CSS Masterclass','Utility-first CSS framework: responsive design, custom themes, dark mode and component patterns.',1,'Beginner','Admin',5.0),
('TypeScript from Scratch','Static typing for JavaScript: interfaces, generics, enums, decorators and compiler config.',3,'Intermediate','Admin',7.5),
('Python for Beginners','Variables, loops, functions, modules and file handling with real-world mini projects.',3,'Beginner','Admin',8.0),
('Data Structures & Algorithms','Arrays, linked lists, trees, graphs, sorting and searching with Big-O analysis.',3,'Advanced','Admin',14.0),
('MongoDB Essentials','NoSQL document database: CRUD, indexes, aggregation pipeline and Mongoose ODM.',2,'Beginner','Admin',6.0),
('PostgreSQL Deep Dive','Advanced SQL, joins, window functions, CTEs, indexing strategies and query optimization.',2,'Advanced','Admin',10.5),
('Redis Caching & Queues','In-memory key-value store for caching, sessions, pub/sub and task queues.',2,'Intermediate','Admin',4.5),
('UI/UX Design Principles','Visual hierarchy, color theory, typography, spacing and accessibility guidelines.',4,'Beginner','Admin',5.5),
('Figma for Developers','Auto layout, components, variants, prototyping and developer handoff in Figma.',4,'Beginner','Admin',4.0),
('Responsive Web Design','Mobile-first design, fluid grids, container queries and modern CSS layout techniques.',4,'Intermediate','Admin',6.5),
('Python Data Analysis','NumPy, Pandas, Matplotlib and Seaborn for exploring, cleaning and visualizing datasets.',5,'Intermediate','Admin',9.0),
('Machine Learning Basics','Regression, classification, decision trees, random forests and model evaluation with scikit-learn.',5,'Advanced','Admin',15.0),
('SQL for Data Analytics','Window functions, pivoting, cohort analysis and dashboard queries for business insights.',5,'Intermediate','Admin',7.0),
('Flutter App Development','Cross-platform mobile apps with Dart: widgets, state management, navigation and Firebase.',6,'Intermediate','Admin',11.0),
('React Native Quickstart','Build native iOS and Android apps using React Native, Expo and device APIs.',6,'Intermediate','Admin',8.0),
('Docker & Containers','Images, containers, volumes, networks, Docker Compose and multi-stage builds.',7,'Beginner','Admin',6.0),
('CI/CD with GitHub Actions','Automate testing, building and deployment pipelines with GitHub Actions workflows.',7,'Intermediate','Admin',5.0),
('Web Security Fundamentals','OWASP Top 10, XSS, CSRF, SQL injection, HTTPS, CSP headers and secure coding practices.',8,'Intermediate','Admin',8.5);

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
(4,'Pull requests','Push a branch, open a pull request, review and merge.',NULL,2),
(5,'Components & JSX','Create functional components, use JSX syntax and pass data with props.',NULL,1),
(5,'Hooks: useState & useEffect','Manage component state and side effects with React hooks.',NULL,2),
(5,'React Router & Navigation','Set up client-side routing with React Router v6 and dynamic segments.',NULL,3),
(6,'Express Server Setup','Install Express, create routes, handle requests and send JSON responses.',NULL,1),
(6,'Middleware & Error Handling','Use built-in and custom middleware for logging, auth and error responses.',NULL,2),
(6,'JWT Authentication','Generate and verify JSON Web Tokens for stateless API authentication.',NULL,3),
(7,'Utility-First Workflow','Install Tailwind, configure your theme and build layouts with utility classes.',NULL,1),
(7,'Responsive & Dark Mode','Use responsive prefixes and the dark variant for adaptive designs.',NULL,2),
(8,'Types, Interfaces & Enums','Annotate variables, define object shapes and use enums for constants.',NULL,1),
(8,'Generics & Utility Types','Write reusable type-safe functions with generics and built-in utility types.',NULL,2),
(8,'TypeScript with React','Type props, state, events and refs in a React + TypeScript project.',NULL,3),
(9,'Variables, Types & Control Flow','Numbers, strings, booleans, if-else statements and loops.',NULL,1),
(9,'Functions & Modules','Define functions, import modules and organize code into packages.',NULL,2),
(9,'File Handling & Exceptions','Read and write files, handle errors gracefully with try/except.',NULL,3),
(10,'Arrays, Stacks & Queues','Linear data structures, their operations and time complexity.',NULL,1),
(10,'Trees & Binary Search Trees','Tree traversals, BST insert/delete/search and balancing concepts.',NULL,2),
(10,'Graphs & Shortest Paths','BFS, DFS, Dijkstra and when to use adjacency lists vs matrices.',NULL,3),
(10,'Sorting & Searching','Merge sort, quicksort, binary search and Big-O comparisons.',NULL,4),
(11,'Documents & Collections','Create databases, insert documents and query with find and filters.',NULL,1),
(11,'Indexes & Aggregation','Speed up queries with indexes and transform data with the aggregation pipeline.',NULL,2),
(12,'Advanced Joins & Subqueries','Inner, outer, cross joins, correlated subqueries and CTEs.',NULL,1),
(12,'Window Functions','ROW_NUMBER, RANK, LAG, LEAD and running totals with OVER clauses.',NULL,2),
(12,'Indexing & Performance','B-tree, GIN, GiST indexes, EXPLAIN ANALYZE and query tuning.',NULL,3),
(13,'Key-Value Operations','Strings, hashes, lists, sets and sorted sets with expiry.',NULL,1),
(13,'Pub/Sub & Task Queues','Real-time messaging with pub/sub and background job processing.',NULL,2),
(14,'Visual Hierarchy & Layout','Guide the eye with size, color, contrast and whitespace.',NULL,1),
(14,'Color Theory & Typography','Choose palettes, pair fonts and ensure readable contrast ratios.',NULL,2),
(14,'Accessibility Essentials','WCAG guidelines, ARIA labels, keyboard navigation and screen readers.',NULL,3),
(15,'Auto Layout & Components','Build responsive frames and reusable component libraries.',NULL,1),
(15,'Prototyping & Handoff','Create interactive prototypes and export specs for developers.',NULL,2),
(16,'Mobile-First Approach','Start with the smallest screen and enhance progressively.',NULL,1),
(16,'Fluid Grids & Container Queries','Use percentage-based grids and container queries for component-level responsiveness.',NULL,2),
(16,'Modern CSS: Grid & Subgrid','Advanced CSS Grid layouts with subgrid for nested alignment.',NULL,3),
(17,'NumPy & Pandas Basics','Arrays, DataFrames, indexing, filtering and groupby operations.',NULL,1),
(17,'Data Cleaning & Transformation','Handle missing values, merge datasets and reshape data.',NULL,2),
(17,'Visualization with Matplotlib','Create line charts, bar plots, histograms and customized figures.',NULL,3),
(18,'Supervised Learning Overview','Train/test splits, linear regression and logistic regression.',NULL,1),
(18,'Decision Trees & Random Forests','Tree-based models, feature importance and ensemble methods.',NULL,2),
(18,'Model Evaluation & Tuning','Accuracy, precision, recall, cross-validation and hyperparameter tuning.',NULL,3),
(18,'Intro to Neural Networks','Perceptrons, activation functions, backpropagation and Keras basics.',NULL,4),
(19,'Analytical Window Functions','RANK, NTILE, running averages and cumulative sums for reporting.',NULL,1),
(19,'Cohort & Funnel Analysis','Track user retention and conversion funnels with SQL.',NULL,2),
(20,'Dart Language & Widgets','Dart syntax, StatelessWidget, StatefulWidget and the widget tree.',NULL,1),
(20,'State Management','Provider, Riverpod and BLoC patterns for managing app state.',NULL,2),
(20,'Navigation & Firebase','Named routes, bottom navigation and Firebase Auth + Firestore.',NULL,3),
(21,'Expo & Core Components','Set up Expo, use View, Text, ScrollView and FlatList.',NULL,1),
(21,'Navigation & Device APIs','React Navigation stack/tab navigators and accessing camera/location.',NULL,2),
(22,'Images & Containers','Build images from Dockerfiles, run containers and manage volumes.',NULL,1),
(22,'Docker Compose','Define multi-container apps with docker-compose.yml and networking.',NULL,2),
(22,'Multi-Stage Builds','Optimize image size with multi-stage builds and .dockerignore.',NULL,3),
(23,'Workflow Basics','YAML syntax, triggers, jobs, steps and marketplace actions.',NULL,1),
(23,'Test, Build & Deploy','Run tests, build artifacts and deploy to staging/production automatically.',NULL,2),
(24,'OWASP Top 10 Overview','Understand the most critical web application security risks.',NULL,1),
(24,'XSS & CSRF Prevention','Escape output, validate input, use CSP headers and anti-CSRF tokens.',NULL,2),
(24,'SQL Injection & HTTPS','Parameterized queries, TLS certificates and secure cookie flags.',NULL,3);
