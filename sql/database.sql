-- Database creation
CREATE DATABASE IF NOT EXISTS lab_inventory_system;
USE lab_inventory_system;

-- User roles table
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES 
('Administrator', 'Full system access with all privileges'),
('Faculty', 'Faculty/Staff access with borrowing privileges'),
('Lab Technician', 'Manages equipment and approves borrowing requests'),
('Student Assistant', 'Limited access to assist with inventory');

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Rooms/Labs table
CREATE TABLE rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    room_name VARCHAR(100) NOT NULL,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(50) NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    capacity INT,
    lab_technician_id INT,
    status ENUM('active', 'inactive', 'under maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_technician_id) REFERENCES users(user_id)
);

-- Equipment categories
CREATE TABLE equipment_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT IGNORE INTO equipment_categories (category_name, description) VALUES
    ('Laboratory Equipment', 'General laboratory equipment and instruments'),
    ('Computers', 'Computers, laptops, and related hardware'),
    ('Furniture', 'Lab furniture, tables, chairs, and storage units'),
    ('Safety Equipment', 'Safety gear, protective equipment, and emergency supplies'),
    ('Measurement Tools', 'Precision measurement instruments and tools'),
    ('Consumables', 'Disposable items and supplies');

-- Equipment table
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    model VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(100) NOT NULL,
    purchase_date DATE,
    warranty_expiry DATE,
    status ENUM('active', 'inactive', 'maintenance', 'retired') NOT NULL DEFAULT 'active',
    location VARCHAR(255) NOT NULL,
    equipment_condition ENUM('new', 'good', 'fair', 'poor') NOT NULL DEFAULT 'new',
    notes TEXT,
    last_maintenance_date DATE,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES equipment_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipment_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipment movement tracking
CREATE TABLE equipment_movements (
    movement_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    from_room_id INT,
    to_room_id INT NOT NULL,
    moved_by INT NOT NULL,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason TEXT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (from_room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (to_room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (moved_by) REFERENCES users(user_id)
);

-- Borrowing requests
CREATE TABLE borrowing_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    borrower_id INT NOT NULL,
    equipment_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    borrow_date DATETIME NOT NULL,
    expected_return_date DATETIME NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'borrowed', 'returned', 'overdue') DEFAULT 'pending',
    approved_by INT,
    approval_date TIMESTAMP NULL,
    actual_return_date TIMESTAMP NULL,
    condition_before TEXT,
    condition_after TEXT,
    FOREIGN KEY (borrower_id) REFERENCES users(user_id),
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id)
);

-- Maintenance and repair requests
CREATE TABLE maintenance_requests (
    maintenance_id INT PRIMARY KEY AUTO_INCREMENT,
    equipment_id INT NOT NULL,
    reported_by INT NOT NULL,
    issue_description TEXT NOT NULL,
    report_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    technician_assigned INT,
    status ENUM('pending', 'in progress', 'completed', 'cancelled') DEFAULT 'pending',
    start_date TIMESTAMP NULL,
    completion_date TIMESTAMP NULL,
    resolution_notes TEXT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    FOREIGN KEY (reported_by) REFERENCES users(user_id),
    FOREIGN KEY (technician_assigned) REFERENCES users(user_id)
);

-- Audit trail
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
); 