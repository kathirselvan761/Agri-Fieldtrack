-- Tractor Rental Management System Database Schema

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Company Settings Table
CREATE TABLE IF NOT EXISTS `company_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(150) NOT NULL DEFAULT 'AgriTrak ERP Services',
  `phone` VARCHAR(20) NOT NULL DEFAULT '+91 98765 43210',
  `email` VARCHAR(100) NOT NULL DEFAULT 'contact@agritrak.com',
  `address` TEXT,
  `currency_symbol` VARCHAR(10) NOT NULL DEFAULT '₹',
  `tax_number` VARCHAR(50) DEFAULT 'GSTIN33ABCDE1234F1Z5',
  `logo` VARCHAR(255) DEFAULT 'assets/images/logo.png',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tractors Table
CREATE TABLE IF NOT EXISTS `tractors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `reg_number` VARCHAR(50) NOT NULL UNIQUE,
  `brand` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `year` INT NOT NULL,
  `status` ENUM('Available', 'Working', 'Service', 'Breakdown') DEFAULT 'Available',
  `photo` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `village` VARCHAR(100) NOT NULL,
  `address` TEXT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Drivers Table
CREATE TABLE IF NOT EXISTS `drivers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT,
  `license_number` VARCHAR(50) DEFAULT NULL,
  `salary` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Work Types Table
CREATE TABLE IF NOT EXISTS `work_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `unit_type` ENUM('Hour', 'Acre', 'Trip') NOT NULL DEFAULT 'Hour',
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `tractor_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `work_type_id` INT NOT NULL,
  `booking_date` DATE NOT NULL,
  `start_time` TIME DEFAULT '08:00:00',
  `end_time` TIME DEFAULT '17:00:00',
  `status` ENUM('Confirmed', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Confirmed',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tractor_id`) REFERENCES `tractors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Work Entries Table
CREATE TABLE IF NOT EXISTS `work_entries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `entry_date` DATE NOT NULL,
  `customer_id` INT NOT NULL,
  `village` VARCHAR(100) NOT NULL,
  `tractor_id` INT NOT NULL,
  `driver_id` INT DEFAULT NULL,
  `work_type_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `advance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Paid', 'Partial', 'Pending') DEFAULT 'Pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tractor_id`) REFERENCES `tractors`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`work_type_id`) REFERENCES `work_types`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `work_entry_id` INT DEFAULT NULL,
  `customer_id` INT NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_mode` ENUM('Cash', 'UPI', 'Bank') DEFAULT 'Cash',
  `reference_no` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`work_entry_id`) REFERENCES `work_entries`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Diesel Logs Table
CREATE TABLE IF NOT EXISTS `diesel_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `log_date` DATE NOT NULL,
  `tractor_id` INT NOT NULL,
  `litres` DECIMAL(10,2) NOT NULL,
  `rate_per_litre` DECIMAL(10,2) NOT NULL,
  `total_cost` DECIMAL(10,2) NOT NULL,
  `fuel_station` VARCHAR(150) DEFAULT NULL,
  `bill_photo` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tractor_id`) REFERENCES `tractors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Services Table
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `service_date` DATE NOT NULL,
  `tractor_id` INT NOT NULL,
  `category` ENUM('Engine Oil', 'Filter', 'Battery', 'Tyre', 'Greasing', 'Repair', 'Spare Parts') NOT NULL,
  `cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `vendor` VARCHAR(150) DEFAULT NULL,
  `next_service_due` DATE DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tractor_id`) REFERENCES `tractors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Expenses Table
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_date` DATE NOT NULL,
  `category` ENUM('Diesel', 'Salary', 'Repair', 'Spare Parts', 'Others') NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Attendance Table
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attendance_date` DATE NOT NULL,
  `driver_id` INT NOT NULL,
  `status` ENUM('Present', 'Absent', 'Leave') DEFAULT 'Present',
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_driver_date` (`attendance_date`, `driver_id`),
  FOREIGN KEY (`driver_id`) REFERENCES `drivers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- INITIAL SEED DATA
-- ========================================================

-- Insert Default Admin User (Password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`)
VALUES ('admin', 'admin@agritrak.com', '$2y$12$vgTD8E/7FCB/0wcetXqR6.nOLph6yshqSco5Zs36kg8VgkILijN7O', 'Business Owner', 'admin')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Insert Default Company Settings
INSERT INTO `company_settings` (`id`, `company_name`, `phone`, `email`, `address`, `currency_symbol`, `tax_number`)
VALUES (1, 'GreenField Tractor Rentals', '+91 98765 43210', 'info@greenfieldrentals.com', 'Main Road, Agri Hub, Sector 4, TN', '₹', '33ABCDE1234F1Z5')
ON DUPLICATE KEY UPDATE `company_name`=VALUES(`company_name`);

-- Seed Work Types
INSERT INTO `work_types` (`name`, `unit_type`, `rate`, `description`) VALUES
('Rotavator', 'Hour', 1500.00, 'Fine soil tilling and seedbed preparation'),
('Plough', 'Acre', 2200.00, 'Deep soil inversion and land breaking'),
('Cultivator', 'Hour', 1200.00, 'Secondary tilling and weed control'),
('Water Tank', 'Trip', 800.00, 'Water supply transport to agricultural fields'),
('Trailer', 'Trip', 1500.00, 'Agricultural yield and heavy crop haulage'),
('Banana Pit', 'Acre', 3500.00, 'Pit digging for banana crop plantation'),
('Coconut Pit', 'Acre', 4000.00, 'Deep pit excavation for coconut saplings'),
('Land Levelling', 'Hour', 1800.00, 'Laser and blade land leveling for irrigation'),
('Mud Work', 'Hour', 2000.00, 'Paddy field puddling and wet soil preparation'),
('Sand Transport', 'Trip', 2500.00, 'Sand hauling for field bunding and construction'),
('Stone Transport', 'Trip', 2800.00, 'Boulders and stone transport for field fencing')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Tractors
INSERT INTO `tractors` (`name`, `reg_number`, `brand`, `model`, `year`, `status`, `notes`) VALUES
('Mahindra 575 DI', 'TN-38-AX-1001', 'Mahindra', '575 DI SP Plus', 2023, 'Available', 'Heavy duty 47 HP tractor'),
('John Deere 5050D', 'TN-38-AX-1002', 'John Deere', '5050D 4WD', 2022, 'Working', 'Power steering 50 HP tractor'),
('Swaraj 855 FE', 'TN-38-AX-1003', 'Swaraj', '855 FE', 2024, 'Available', '52 HP high torque tractor'),
('Farmtrac 60 Powerxx', 'TN-38-AX-1004', 'Farmtrac', '60 Powerxx', 2021, 'Service', 'Scheduled 500 hr engine oil service'),
('Tafe 241 DI', 'TN-38-AX-1005', 'Massey Ferguson', '241 DI Dynatrack', 2023, 'Breakdown', 'Clutch plate replacement required')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Customers
INSERT INTO `customers` (`name`, `mobile`, `village`, `address`, `notes`) VALUES
('Ramasamy Gounder', '9842101234', 'Vadugapalayam', 'Door 4/12, North Street, Vadugapalayam', 'Regular customer - Sugarcane farmer'),
('Murugan K.', '9789012345', 'Kaniyur', 'Farm House 18, Kaniyur Village', 'Paddy and Maize grower'),
('Palananisamy S.', '9443210987', 'Annur', 'Post Office Road, Annur', 'Large coconut grove owner'),
('Velusamy R.', '9865432109', 'Karamadai', '12 Field View, Karamadai', 'Vegetable cultivation specialist')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Drivers
INSERT INTO `drivers` (`name`, `phone`, `address`, `license_number`, `salary`, `status`) VALUES
('Karthik Raja', '9123456780', 'Annur Village', 'TN3820190012345', 18000.00, 'Active'),
('Senthil Kumar', '9123456781', 'Vadugapalayam', 'TN3820200023456', 17500.00, 'Active'),
('Mani Kandan', '9123456782', 'Karamadai', 'TN3820180034567', 16000.00, 'Active')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Work Entries
INSERT INTO `work_entries` (`entry_date`, `customer_id`, `village`, `tractor_id`, `driver_id`, `work_type_id`, `quantity`, `rate`, `total_amount`, `advance_amount`, `balance_amount`, `payment_status`, `notes`) VALUES
(CURRENT_DATE(), 1, 'Vadugapalayam', 2, 1, 1, 3.50, 1500.00, 5250.00, 2000.00, 3250.00, 'Partial', 'Sugarcane field rotavator tilling'),
(CURRENT_DATE(), 2, 'Kaniyur', 1, 2, 2, 2.00, 2200.00, 4400.00, 4400.00, 0.00, 'Paid', 'Deep ploughing completed'),
(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), 3, 'Annur', 3, 3, 7, 1.50, 4000.00, 6000.00, 1000.00, 5000.00, 'Partial', 'Coconut pit digging'),
(DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY), 4, 'Karamadai', 1, 1, 4, 3.00, 800.00, 2400.00, 0.00, 2400.00, 'Pending', '3 trips of water transport')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Payments
INSERT INTO `payments` (`work_entry_id`, `customer_id`, `payment_date`, `amount`, `payment_mode`, `reference_no`, `notes`) VALUES
(1, 1, CURRENT_DATE(), 2000.00, 'UPI', 'UPI/20260731/89712', 'Advance payment via GPay'),
(2, 2, CURRENT_DATE(), 4400.00, 'Cash', 'CASH-102', 'Full payment cash on completion')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Diesel Logs
INSERT INTO `diesel_logs` (`log_date`, `tractor_id`, `litres`, `rate_per_litre`, `total_cost`, `fuel_station`, `notes`) VALUES
(CURRENT_DATE(), 1, 45.00, 94.50, 4252.50, 'Indian Oil bunk Kaniyur', 'Full tank refilling'),
(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY), 2, 35.00, 94.50, 3307.50, 'Bharat Petroleum Annur', 'Routine daily top-up')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Expenses
INSERT INTO `expenses` (`expense_date`, `category`, `title`, `amount`, `description`) VALUES
(CURRENT_DATE(), 'Diesel', 'Diesel for John Deere & Mahindra', 7560.00, 'Fuel refill bulk invoice'),
(DATE_SUB(CURRENT_DATE(), INTERVAL 3 DAY), 'Repair', 'Hydraulic hose replacement', 2500.00, 'Replaced leaking hose on Tafe 241')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Services
INSERT INTO `services` (`service_date`, `tractor_id`, `category`, `cost`, `vendor`, `next_service_due`, `notes`) VALUES
(DATE_SUB(CURRENT_DATE(), INTERVAL 10 DAY), 4, 'Engine Oil', 4500.00, 'Mahindra Authorized Service', DATE_ADD(CURRENT_DATE(), INTERVAL 80 DAY), '500 HR Oil & Filter replacement')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Seed Bookings
INSERT INTO `bookings` (`customer_id`, `tractor_id`, `driver_id`, `work_type_id`, `booking_date`, `start_time`, `end_time`, `status`, `notes`) VALUES
(1, 1, 1, 1, DATE_ADD(CURRENT_DATE(), INTERVAL 1 DAY), '08:00:00', '12:00:00', 'Confirmed', 'Rotavator scheduled for tomorrow morning'),
(3, 3, 2, 8, DATE_ADD(CURRENT_DATE(), INTERVAL 2 DAY), '09:00:00', '16:00:00', 'Confirmed', 'Land levelling scheduled')
ON DUPLICATE KEY UPDATE `id`=`id`;
