-- Create the application database if it does not already exist
CREATE DATABASE IF NOT EXISTS nearme;

-- Use the nearme database
USE nearme;

-- Users table storing authentication details and profile information
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
    hobbies TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Local development account. Import this file as MySQL root or an administrator.
CREATE USER IF NOT EXISTS 'nearme_app'@'127.0.0.1'
IDENTIFIED BY 'local_db_password';

GRANT ALL PRIVILEGES ON nearme.*
TO 'nearme_app'@'127.0.0.1';

FLUSH PRIVILEGES;

-- Insert demo users with pre-configured coordinates and diverse hobbies.
-- The coordinates are centered around Kota, Rajasthan for immediate testing.
-- Password for all demo accounts is: demo123
INSERT INTO users (email, password, username, name, phone, address, latitude, longitude, hobbies)
VALUES
    (
        'alice@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'alice_w',
        'Alice Walker',
        '+91 98765 43210',
        'Station Road, Nayapura, Kota, Rajasthan',
        25.18000000,
        75.83000000,
        'Coding, Photography, Hiking, Reading'
    ),
    (
        'bob@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'bob_sharma',
        'Bob Sharma',
        '+91 98111 22334',
        'Talwandi, Sector 4, Kota, Rajasthan',
        25.14500000,
        75.84500000,
        'Coding, Gaming, Hiking, Football'
    ),
    (
        'charlie@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'charlie_p',
        'Charlie Patel',
        '+91 98222 33445',
        'Vigyan Nagar, Main Road, Kota, Rajasthan',
        25.16200000,
        75.83900000,
        'Photography, Traveling, Cooking, Music'
    ),
    (
        'diana@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'diana_m',
        'Diana Mehta',
        '+91 98333 44556',
        'Gumanpura Commercial Centre, Kota, Rajasthan',
        25.17500000,
        75.84200000,
        'Reading, Yoga, Painting, Photography'
    ),
    (
        'ethan@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'ethan_v',
        'Ethan Verma',
        '+91 98444 55667',
        'Mahaveer Nagar Extension, Kota, Rajasthan',
        25.13800000,
        75.82900000,
        'Hiking, Running, Football, Coding'
    ),
    (
        'fiona@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'fiona_g',
        'Fiona Gupta',
        '+91 98555 66778',
        'Kunhari, Thermal Road, Kota, Rajasthan',
        25.21000000,
        75.84800000,
        'Music, Cooking, Photography, Chess'
    ),
    (
        'george@example.com',
        '$2y$12$OW3G36cmKh9E1P/xt0LMOO/6Xp.txn.tYT6hM03I2gm.rFoaHGPPe',
        'george_k',
        'George Khan',
        '+91 98666 77889',
        'Bundi Road, Near Bypass, Kota, Rajasthan',
        25.23500000,
        75.81500000,
        'Chess, Coding, Reading, Gaming'
    )
ON DUPLICATE KEY UPDATE email = VALUES(email);
