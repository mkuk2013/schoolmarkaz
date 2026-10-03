-- ============================================================================
-- School Markaz — full database schema (MySQL 8.0 / MariaDB 10.4+)
-- Run once on a fresh database. All tables use InnoDB + utf8mb4.
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin','teacher','student','parent') NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL DEFAULT 'Markaz Public School',
    address VARCHAR(255) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    logo VARCHAR(255) DEFAULT '',
    session_year VARCHAR(20) DEFAULT '',
    package_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(80) NOT NULL PRIMARY KEY,
    `value` VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    section VARCHAR(10) NOT NULL DEFAULT 'A',
    class_teacher_id INT UNSIGNED NULL,
    UNIQUE KEY uq_class_section (name, section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    code VARCHAR(20) DEFAULT '',
    class_id INT UNSIGNED NULL,
    KEY idx_subject_class (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) DEFAULT '',
    cnic VARCHAR(20) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    user_id INT UNSIGNED NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    dob DATE NULL,
    class_id INT UNSIGNED NOT NULL,
    section VARCHAR(10) NOT NULL DEFAULT 'A',
    parent_id INT UNSIGNED NULL,
    admission_date DATE NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    user_id INT UNSIGNED NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student_class (class_id),
    KEY idx_student_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teachers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_no VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) DEFAULT '',
    cnic VARCHAR(20) DEFAULT '',
    subject VARCHAR(80) DEFAULT '',
    salary DECIMAL(12,2) NOT NULL DEFAULT 0,
    joining_date DATE NULL,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    user_id INT UNSIGNED NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    status ENUM('P','A','L','H') NOT NULL DEFAULT 'P',
    marked_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_day (student_id, `date`),
    KEY idx_att_date (student_id, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    `date` DATE NOT NULL,
    status ENUM('P','A','L','H') NOT NULL DEFAULT 'P',
    marked_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_day (teacher_id, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_heads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    class_id INT UNSIGNED NULL,
    frequency ENUM('monthly','one-time') NOT NULL DEFAULT 'monthly',
    active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    `month` TINYINT UNSIGNED NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    due_date DATE NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(12,2) NOT NULL DEFAULT 0,
    fine DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoice (student_id, `month`, `year`),
    KEY idx_invoice_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_invoice_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    fee_head_id INT UNSIGNED NULL,
    label VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    KEY idx_item_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(40) NOT NULL DEFAULT 'Cash',
    received_by INT UNSIGNED NULL,
    receipt_no VARCHAR(30) NOT NULL UNIQUE,
    note VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payment_invoice (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    term VARCHAR(60) DEFAULT '',
    `year` SMALLINT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    obtained DECIMAL(6,2) NOT NULL DEFAULT 0,
    total DECIMAL(6,2) NOT NULL DEFAULT 100,
    UNIQUE KEY uq_mark (exam_id, student_id, subject_id),
    KEY idx_marks_exam (exam_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS timetable (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    `day` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    period TINYINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NULL,
    teacher_id INT UNSIGNED NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    UNIQUE KEY uq_slot (class_id, `day`, period),
    KEY idx_tt_class (class_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS finance_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income','expense') NOT NULL,
    category VARCHAR(80) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    `date` DATE NOT NULL,
    note VARCHAR(255) DEFAULT '',
    ref VARCHAR(80) DEFAULT '',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fin_date (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT UNSIGNED NOT NULL,
    `month` TINYINT UNSIGNED NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    paid_date DATE NULL,
    note VARCHAR(255) DEFAULT '',
    UNIQUE KEY uq_salary (teacher_id, `month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    body TEXT NOT NULL,
    audience ENUM('all','teachers','students','parents') NOT NULL DEFAULT 'all',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    max_students INT UNSIGNED NOT NULL,
    price_monthly DECIMAL(12,2) NOT NULL,
    features TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS package_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(30) NOT NULL UNIQUE,
    package_id INT UNSIGNED NOT NULL,
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    amount DECIMAL(12,2) NOT NULL,
    school_name VARCHAR(150) NOT NULL DEFAULT '',
    contact_name VARCHAR(120) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL DEFAULT '',
    email VARCHAR(120) DEFAULT '',
    status ENUM('pending','submitted','approved','rejected') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(40) DEFAULT '',
    txn_ref VARCHAR(120) DEFAULT '',
    proof_path VARCHAR(255) DEFAULT '',
    note TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    starts_at DATE NULL,
    ends_at DATE NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admissions_inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(120) NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
    dob DATE NULL,
    class_applied VARCHAR(60) DEFAULT '',
    parent_name VARCHAR(120) DEFAULT '',
    phone VARCHAR(40) DEFAULT '',
    address VARCHAR(255) DEFAULT '',
    message TEXT,
    status ENUM('new','contacted','admitted') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default packages (safe to re-run: only inserted when table is empty — see seed).
INSERT INTO packages (name, max_students, price_monthly, features) VALUES
('Micro', 75, 1500, 'Up to 75 students\nAttendance & fees\n1 admin login'),
('Starter', 150, 3000, 'Up to 150 students\nAttendance, fees & results\nParent portal\n5 staff logins'),
('Growth', 300, 6000, 'Up to 300 students\nEverything in Starter\nFinance & payroll\nUnlimited staff logins\nPriority support');

INSERT INTO settings (`key`, `value`) VALUES
('fine_per_day', '50'),
('due_day', '10'),
('session_year', '2026-27');
