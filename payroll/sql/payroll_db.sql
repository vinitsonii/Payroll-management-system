-- ============================================
-- PAYROLL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ============================================

CREATE DATABASE IF NOT EXISTS payroll_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE payroll_db;

-- Admin Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','hr','accountant') DEFAULT 'hr',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Departments
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Designations
CREATE TABLE IF NOT EXISTS designations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- Employees
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emp_code VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(15),
    dob DATE,
    gender ENUM('Male','Female','Other'),
    address TEXT,
    department_id INT,
    designation_id INT,
    join_date DATE,
    employment_type ENUM('Full-Time','Part-Time','Contract') DEFAULT 'Full-Time',
    status ENUM('Active','Inactive','Terminated') DEFAULT 'Active',
    pan_number VARCHAR(20),
    bank_account VARCHAR(30),
    bank_name VARCHAR(100),
    ifsc_code VARCHAR(20),
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
);

-- Salary Structure
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(12,2) DEFAULT 0,
    hra DECIMAL(12,2) DEFAULT 0,
    da DECIMAL(12,2) DEFAULT 0,
    ta DECIMAL(12,2) DEFAULT 0,
    medical_allowance DECIMAL(12,2) DEFAULT 0,
    other_allowance DECIMAL(12,2) DEFAULT 0,
    effective_from DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    att_date DATE NOT NULL,
    status ENUM('Present','Absent','Half-Day','Leave','Holiday','WFH') DEFAULT 'Present',
    check_in TIME,
    check_out TIME,
    remarks VARCHAR(255),
    UNIQUE KEY unique_att (employee_id, att_date),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Leave Types
CREATE TABLE IF NOT EXISTS leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    days_allowed INT DEFAULT 0,
    carry_forward TINYINT(1) DEFAULT 0
);

-- Leave Applications
CREATE TABLE IF NOT EXISTS leave_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    total_days INT,
    reason TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    applied_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payroll (Monthly)
CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    pay_month INT NOT NULL COMMENT '1-12',
    pay_year INT NOT NULL,
    working_days INT DEFAULT 26,
    present_days DECIMAL(5,1) DEFAULT 0,
    basic_salary DECIMAL(12,2) DEFAULT 0,
    hra DECIMAL(12,2) DEFAULT 0,
    da DECIMAL(12,2) DEFAULT 0,
    ta DECIMAL(12,2) DEFAULT 0,
    medical_allowance DECIMAL(12,2) DEFAULT 0,
    other_allowance DECIMAL(12,2) DEFAULT 0,
    gross_salary DECIMAL(12,2) DEFAULT 0,
    pf_employee DECIMAL(12,2) DEFAULT 0,
    pf_employer DECIMAL(12,2) DEFAULT 0,
    esi_employee DECIMAL(12,2) DEFAULT 0,
    esi_employer DECIMAL(12,2) DEFAULT 0,
    tds DECIMAL(12,2) DEFAULT 0,
    professional_tax DECIMAL(12,2) DEFAULT 0,
    other_deduction DECIMAL(12,2) DEFAULT 0,
    total_deduction DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) DEFAULT 0,
    status ENUM('Draft','Processed','Paid') DEFAULT 'Draft',
    remarks TEXT,
    generated_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payroll (employee_id, pay_month, pay_year),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Company Settings
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT
);

-- ============ DEFAULT DATA ============

INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@payroll.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Default password: password

INSERT INTO departments (name, description) VALUES
('Human Resources', 'HR and recruitment team'),
('Information Technology', 'Software and IT support'),
('Finance', 'Accounts and finance team'),
('Operations', 'Operations and logistics'),
('Marketing', 'Marketing and branding');

INSERT INTO designations (title, department_id) VALUES
('HR Manager', 1), ('HR Executive', 1),
('Software Engineer', 2), ('Senior Developer', 2), ('Tech Lead', 2),
('Accountant', 3), ('Finance Manager', 3),
('Operations Manager', 4), ('Operations Executive', 4),
('Marketing Manager', 5), ('Marketing Executive', 5);

INSERT INTO leave_types (name, days_allowed, carry_forward) VALUES
('Casual Leave', 12, 0),
('Sick Leave', 10, 0),
('Earned Leave', 15, 1),
('Maternity Leave', 180, 0),
('Compensatory Off', 5, 1);

INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'TechCorp Pvt. Ltd.'),
('company_address', '123 Business Park, Gandhinagar, Gujarat - 382010'),
('company_phone', '+91 98765 43210'),
('company_email', 'hr@techcorp.com'),
('company_logo', ''),
('pf_employee_rate', '12'),
('pf_employer_rate', '12'),
('esi_employee_rate', '0.75'),
('esi_employer_rate', '3.25'),
('pt_slab', '200'),
('working_days', '26'),
('currency', '₹');
