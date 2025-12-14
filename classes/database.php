<?php
class Database {
    private $host = "localhost";
    private $db_name = "petty_cash_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Database connection error: " . $e->getMessage();
        }

        return $this->conn;
    }

    public function createTables() {
        $conn = $this->connect();

        // Create database if not exists
        $conn->exec("CREATE DATABASE IF NOT EXISTS petty_cash_db");
        $conn->exec("USE petty_cash_db");

        // Create tables only if they don't exist
        $sql = "CREATE TABLE IF NOT EXISTS users (
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
        )";

        $conn->exec($sql);

        // Helper function to check if a column exists
        function columnExists($conn, $table, $column) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return $stmt->fetchColumn() > 0;
        }

        // Add email column if it does not exist
        if (!columnExists($conn, 'users', 'email')) {
            $conn->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NULL");
        }

        // Add email_verified column if it does not exist
        if (!columnExists($conn, 'users', 'email_verified')) {
            $conn->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        }

        // Add verification_token column if it does not exist
        if (!columnExists($conn, 'users', 'verification_token')) {
            $conn->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");
        }

        $sql = "CREATE TABLE IF NOT EXISTS petty_cash_requests (
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
        )";

        $conn->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS liquidation_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(10) NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            receipts TEXT NULL,
            FOREIGN KEY (request_id) REFERENCES petty_cash_requests(request_id) ON DELETE CASCADE
        )";

        $conn->exec($sql);

        // Add rejection_reason column if not exists
        try {
            $conn->exec("ALTER TABLE petty_cash_requests ADD COLUMN rejection_reason TEXT NULL");
        } catch (PDOException $e) {
            // Column might already exist, ignore error
        }

        // Insert admin user only if not exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department, email_verified, email) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'Administrator', 'Admin', 'admin@example.com']);
        }

        // Insert employee users only if not exist
        $employees = [
            ['employee1', 'Employee One', 'IT', 'employee1@example.com'],
            ['employee2', 'Employee Two', 'HR', 'employee2@example.com'],
            ['employee3', 'Employee Three', 'Finance', 'employee3@example.com']
        ];

        foreach ($employees as $emp) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$emp[0]]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, department, email_verified, email) VALUES (?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$emp[0], password_hash('password', PASSWORD_DEFAULT), 'employee', $emp[1], $emp[2], $emp[3]]);
            }
        }
    }
}
?>
