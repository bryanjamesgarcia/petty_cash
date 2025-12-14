<?php
class Database {
    // InfinityFree database credentials
    private $host = "sql100.infinityfree.com";
    private $db_name = "if0_40681784_petty_cash_db";
    private $username = "if0_40681784";
    private $password = "";
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }

    public function createTables() {
        $conn = $this->connect();

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);

        // Helper function to check if a column exists
        if (!function_exists('columnExists')) {
            function columnExists($conn, $table, $column) {
                try {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
                    $stmt->execute([$table, $column]);
                    return $stmt->fetchColumn() > 0;
                } catch (PDOException $e) {
                    return false;
                }
            }
        }

        // Add email column if it does not exist
        if (!columnExists($conn, 'users', 'email')) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NULL");
            } catch (PDOException $e) {
                // Column might already exist, ignore
            }
        }

        // Add email_verified column if it does not exist
        if (!columnExists($conn, 'users', 'email_verified')) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
            } catch (PDOException $e) {
                // Column might already exist, ignore
            }
        }

        // Add verification_token column if it does not exist
        if (!columnExists($conn, 'users', 'verification_token')) {
            try {
                $conn->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");
            } catch (PDOException $e) {
                // Column might already exist, ignore
            }
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);

        $sql = "CREATE TABLE IF NOT EXISTS liquidation_expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_id VARCHAR(10) NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            receipts TEXT NULL,
            FOREIGN KEY (request_id) REFERENCES petty_cash_requests(request_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $conn->exec($sql);

        // Add rejection_reason column if not exists
        if (!columnExists($conn, 'petty_cash_requests', 'rejection_reason')) {
            try {
                $conn->exec("ALTER TABLE petty_cash_requests ADD COLUMN rejection_reason TEXT NULL");
            } catch (PDOException $e) {
                // Column might already exist, ignore
            }
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
