CREATE DATABASE cict_rms;
USE cict_rms;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'officer') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contact_number VARCHAR(13) UNIQUE NOT NULL;
    status ENUM('active', 'inactive') DEFAULT 'active',
    session_id VARCHAR(255) DEFAULT NULL
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    item_quantity INT NOT NULL,
    item_availability ENUM('available', 'unavailable') DEFAULT 'available'
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    transaction_type ENUM('borrowed', 'returned') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by VARCHAR(100),
    FOREIGN KEY (item_id) REFERENCES inventory(id)
    status ENUM('borrowed', 'returned') DEFAULT 'borrowed';
);


