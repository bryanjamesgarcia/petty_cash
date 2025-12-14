
CREATE DATABASE petty_cash_db;
USE petty_cash_db;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    email VARCHAR(100) UNIQUE NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Petty cash requests table with liquidation fields
CREATE TABLE petty_cash_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(10) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    employee_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    amount_requested DECIMAL(10,2) NOT NULL,
    purpose TEXT NOT NULL,
    expense_category VARCHAR(100) NOT NULL,
    justification TEXT NOT NULL,
    breakdown TEXT NOT NULL,
    date_requested DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Liquidated') DEFAULT 'Pending',
    actual_expenses DECIMAL(10,2) NULL,
    total_spent DECIMAL(10,2) NULL,
    receipts TEXT NULL,
    refund_needed DECIMAL(10,2) NULL,
    reimbursement DECIMAL(10,2) NULL,
    liquidation_status ENUM('Not Submitted', 'Pending', 'Approved', 'Rejected') DEFAULT 'Not Submitted',
    date_liquidated DATE NULL,
    rejection_reason TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add rejection_reason column if not exists (for existing databases)
ALTER TABLE petty_cash_requests ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL;

-- Liquidation expenses table for multiple expense items
CREATE TABLE liquidation_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(10) NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipts TEXT NULL,
    FOREIGN KEY (request_id) REFERENCES petty_cash_requests(request_id) ON DELETE CASCADE
);

-- Sample data insertion
-- Admin user (password is 'adminpassword' hashed using password_hash() in PHP)
INSERT INTO users (username, password, role, name, department, email, email_verified) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'Admin', 'admin@example.com', 1);

-- Employee users (password is 'password' hashed)
INSERT INTO users (username, password, role, name, department, email, email_verified) VALUES
('employee1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Employee One', 'IT', 'employee1@example.com', 1),
('employee2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Employee Two', 'HR', 'employee2@example.com', 1),
('employee3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Employee Three', 'Finance', 'employee3@example.com', 1);

