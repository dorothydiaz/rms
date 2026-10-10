<?php
// Database Seeder for RMS User Accounts
require_once __DIR__ . '/../config/db.php';

echo "Initializing RMS database tables and seeding users...\n";

try {
    // Ensure login_attempts table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ip_address` VARCHAR(45) NOT NULL,
            `identity` VARCHAR(100) NOT NULL,
            `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `is_successful` TINYINT(1) DEFAULT 0,
            INDEX `idx_ip_attempted` (`ip_address`, `attempted_at`),
            INDEX `idx_identity_attempted` (`identity`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $defaultUsers = [
        [
            'username'  => 'peter',
            'full_name' => 'System Administrator',
            'email'     => 'admin@rms.local',
            'password'  => 'Admin@12345',
            'role'      => 'Admin',
            'status'    => 'Active'
        ],
        [
            'username'  => 'dorothy',
            'full_name' => 'Dorothy Diaz',
            'email'     => 'dorothy@rms.local',
            'password'  => 'Manager@12345',
            'role'      => 'Manager',
            'status'    => 'Active'
        ],
        [
            'username'  => 'cashier',
            'full_name' => 'John Cashier',
            'email'     => 'cashier@rms.local',
            'password'  => 'Cashier@12345',
            'role'      => 'Cashier',
            'status'    => 'Active'
        ],
        [
            'username'  => 'staff',
            'full_name' => 'Sarah Staff',
            'email'     => 'staff@rms.local',
            'password'  => 'Staff@12345',
            'role'      => 'Staff',
            'status'    => 'Active'
        ],
        [
            'username'  => 'kitchen',
            'full_name' => 'Chef Marco',
            'email'     => 'kitchen@rms.local',
            'password'  => 'Kitchen@12345',
            'role'      => 'Kitchen',
            'status'    => 'Active'
        ],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO `users` (`username`, `full_name`, `email`, `password`, `role`, `status`)
        VALUES (:username, :full_name, :email, :password, :role, :status)
        ON DUPLICATE KEY UPDATE
            `full_name` = VALUES(`full_name`),
            `email` = VALUES(`email`),
            `password` = VALUES(`password`),
            `role` = VALUES(`role`),
            `status` = VALUES(`status`)
    ");

    foreach ($defaultUsers as $u) {
        $hashedPassword = password_hash($u['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            ':username'  => $u['username'],
            ':full_name' => $u['full_name'],
            ':email'     => $u['email'],
            ':password'  => $hashedPassword,
            ':role'      => $u['role'],
            ':status'    => $u['status']
        ]);
        echo "  [OK] User: {$u['username']} ({$u['role']}) - {$u['email']}\n";
    }

    echo "\nDatabase seeding completed successfully.\n";
} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
