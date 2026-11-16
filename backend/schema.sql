-- Create the application database if it does not already exist
CREATE DATABASE IF NOT EXISTS nearme;

-- Use the nearme database
USE nearme;

-- Users table storing login credentials and profile details
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(50) DEFAULT NULL,
    name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    hobbies VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dedicated database user for development
CREATE USER IF NOT EXISTS 'nearme_app'@'127.0.0.1'
IDENTIFIED BY 'local_db_password';

GRANT ALL PRIVILEGES ON nearme.*
TO 'nearme_app'@'127.0.0.1';

FLUSH PRIVILEGES;

-- Optional sample accounts for immediate testing (password: demo123)
INSERT INTO users (email, password, username, name, phone, address, latitude, longitude, hobbies)
VALUES
    ('rahul@example.com', '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe', 'rahul_s', 'Rahul Sharma', '9876543210', 'Vaishali Nagar, Jaipur', 26.90750000, 75.74200000, 'Cricket, Coding, Music'),
    ('priya@example.com', '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe', 'priya_v', 'Priya Verma', '9812345678', 'Malviya Nagar, Jaipur', 26.85300000, 75.80500000, 'Reading, Music, Traveling, Photography')
ON DUPLICATE KEY UPDATE email = VALUES(email);
