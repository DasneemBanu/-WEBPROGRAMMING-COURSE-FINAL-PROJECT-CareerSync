-- =========================================
-- CareerSync Database Schema
-- =========================================

DROP DATABASE IF EXISTS career_sync;

CREATE DATABASE career_sync
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE career_sync;

-- =========================================
-- USERS
-- =========================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- QUIZ QUESTIONS
-- =========================================

CREATE TABLE quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    question_type ENUM('single','multi','scale') DEFAULT 'single',
    option_a VARCHAR(255) NULL,
    option_b VARCHAR(255) NULL,
    option_c VARCHAR(255) NULL,
    option_d VARCHAR(255) NULL,
    weight_a INT DEFAULT 1,
    weight_b INT DEFAULT 2,
    weight_c INT DEFAULT 3,
    weight_d INT DEFAULT 4,
    category VARCHAR(100) NULL,
    question_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- CAREER PATHS (Percentage-Based)
-- =========================================

CREATE TABLE career_paths (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    required_skills TEXT NOT NULL,
    education_path TEXT NULL,
    min_percent INT DEFAULT 0,      -- Minimum percentage match (0-100)
    max_percent INT DEFAULT 100,    -- Maximum percentage match (0-100)
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================
-- USER RESULTS (With Percentage)
-- =========================================

CREATE TABLE user_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    career_path_id INT UNSIGNED NULL,
    score INT NOT NULL,
    percentage DECIMAL(5,2) DEFAULT 0,  -- Percentage score (0.00-100.00)
    quiz_snapshot JSON NULL,
    recommendation_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_results_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_results_career
        FOREIGN KEY (career_path_id)
        REFERENCES career_paths(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- =========================================
-- SAMPLE CAREERS WITH PERCENTAGE RANGES
-- =========================================

INSERT INTO career_paths
(title, description, required_skills, education_path, min_percent, max_percent, category)
VALUES

(
    'UI/UX Designer',
    'Design user interfaces and improve user experience. Focus on creativity, visual design, and user empathy.',
    'Figma, Creativity, Wireframing, User Research, Adobe XD',
    'Bachelor of Multimedia Technology or Design',
    0, 25, 'creative'
),

(
    'Data Analyst',
    'Analyze data and produce actionable insights. Work with statistics, visualization, and business intelligence.',
    'Python, SQL, Statistics, Excel, Tableau, Power BI',
    'Bachelor of Data Science or Statistics',
    26, 50, 'analytical'
),

(
    'Software Developer',
    'Design, build and maintain software applications. Develop web, mobile, and desktop solutions.',
    'PHP, JavaScript, SQL, Git, React, Node.js, Problem Solving',
    'Bachelor of Computer Science or Software Engineering',
    51, 75, 'technical'
),

(
    'Cybersecurity Analyst',
    'Protect systems and networks from security threats. Monitor, detect, and respond to cyber attacks.',
    'Networking, Security, Linux, Ethical Hacking, SIEM, Python',
    'Bachelor of Cyber Security or Information Assurance',
    76, 100, 'technical'
);

-- =========================================
-- SAMPLE QUIZ QUESTIONS
-- =========================================

INSERT INTO quizzes
(
    question_text,
    question_type,
    option_a,
    option_b,
    option_c,
    option_d,
    weight_a,
    weight_b,
    weight_c,
    weight_d,
    category,
    question_order
)
VALUES

(
    'Which activity do you enjoy most?',
    'single',
    'Programming and building things',
    'Drawing and designing',
    'Analyzing data and finding patterns',
    'Leading and managing people',
    4, 2, 3, 1,
    'Interest',
    1
),

(
    'What type of project would you prefer?',
    'single',
    'Build a web application',
    'Create visual designs and mockups',
    'Study data trends and reports',
    'Organize a team event',
    4, 2, 3, 1,
    'Preference',
    2
),

(
    'How comfortable are you solving technical problems?',
    'scale',
    NULL,
    NULL,
    NULL,
    NULL,
    1, 2, 3, 4,
    'Technical',
    3
),

(
    'Which tools would you like to work with?',
    'multi',
    'Code editors and terminals',
    'Design software like Figma/Photoshop',
    'Spreadsheets and databases',
    'Presentation and communication tools',
    4, 2, 3, 1,
    'Tools',
    4
),

(
    'In a team, which role suits you best?',
    'single',
    'Developer who builds the product',
    'Designer who shapes the experience',
    'Analyst who guides decisions',
    'Manager who coordinates everyone',
    4, 2, 3, 1,
    'Role',
    5
);

-- =========================================
-- ADMIN ACCOUNT
-- Login: admin@careersync.com / password
-- =========================================

INSERT INTO users
(name, email, password, role)
VALUES
(
    'System Administrator',
    'admin@careersync.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- =========================================
-- SAMPLE USER ACCOUNT
-- Login: nureen25@gmail.com / password
-- =========================================

INSERT INTO users
(name, email, password, role)
VALUES
(
    'Nureen',
    'nureen25@gmail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'user'
);