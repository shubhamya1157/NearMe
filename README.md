# NearMe

A web-based location and hobby discovery platform built using **HTML, CSS, JavaScript, PHP, and MySQL**.

- **Frontend:** HTML, CSS, JavaScript (Vanilla, no external framework dependencies)
- **Backend:** Procedural PHP
- **Database:** MySQL
- **Database connection:** Procedural MySQLi (`mysqli_connect`, `mysqli_query`, `mysqli_fetch_assoc`, `mysqli_real_escape_string`)
- **Database management:** phpMyAdmin or MySQL CLI

The code uses basic functions, `if` statements, loops, and server-side sessions. User input is sanitized and escaped with `mysqli_real_escape_string` before being used in SQL queries. Passwords are securely hashed with `password_hash` with backward-compatible plain-text support for quick testing. Output is protected against XSS using `htmlspecialchars`.

---

## Features

- **Authentication:** Sign up and sign in using email and password with session management.
- **Profile Customization:** Configure your full name, username handle (`@username`), phone number, street address, and coordinates.
- **One-Click Geolocation:** Automatically capture your latitude and longitude via the browser's HTML5 Geolocation API, or choose from quick city presets (e.g., Kota Demo Center, Jaipur, Delhi NCR, Mumbai, Bangalore).
- **Interactive Hobby Selection:** Pick popular hobby tags with a single click (Coding, Gaming, Hiking, Football, Photography, Cooking, etc.) or type custom hobbies.
- **Distance Calculation (Haversine Formula):** Computes exact geographic distances in kilometers between your coordinates and all nearby members.
- **Hobby Matching Algorithm:** Computes the set intersection of shared hobbies, highlights matching tags with distinct visual badges, and counts common interests.
- **Live Filtering & Sorting:** Real-time distance slider (1 km to 100+ km), hobby filter dropdown, minimum shared matches selector, keyword search, and sorting (nearest first, most hobbies in common, name).
- **Direct Connect Modal:** View comprehensive profile details and initiate calls/messages via clickable phone links.

---

## Folder Structure

```text
Nearme/
├── backend/
│   ├── auth.php             # Starts the session and provides login verification helpers
│   ├── db.php               # Opens the MySQL connection using procedural MySQLi
│   ├── find_people.php      # Calculates distance and matching hobbies (supports PHP & AJAX)
│   ├── login.php            # Verifies email and password, initializes user session
│   ├── logout.php           # Destroys session and returns to login
│   ├── profile.php          # Handles fetching and updating user profile information
│   ├── register.php         # Creates new user accounts with email and password
│   └── schema.sql           # Database schema, table definition, db user, and demo accounts
├── frontend/
│   ├── css/
│   │   ├── index.css        # Shared styles, layout, homepage, and auth forms
│   │   └── dashboard.css    # Dashboard grid, user cards, filter controls, and modals
│   ├── js/
│   │   ├── dashboard.js     # Live filtering, sorting, distance slider, and details modal
│   │   ├── hobbies.js       # Dynamic hobby selection and custom tag manager
│   │   └── location.js      # Browser Geolocation API helper and preset coordinates
│   ├── dashboard.php        # Main dashboard displaying nearby people and matched hobbies
│   ├── index.html           # Landing / Welcome homepage
│   ├── login.html           # Login form with demo autofill helper
│   ├── profile.php          # Profile setup and editing form
│   └── register.html        # Registration form
├── .gitignore
└── README.md
```

---

## Database Schema

The MySQL database is named `nearme`. The importable schema is located in [backend/schema.sql](backend/schema.sql).

| Table | Purpose |
| --- | --- |
| `users` | Stores user credentials (email, password), profile details (name, username, phone, address), geographic coordinates (latitude, longitude), and comma-separated hobbies. |

```sql
-- Create the application database if it does not already exist
CREATE DATABASE IF NOT EXISTS nearme;

-- Run statements in this database
USE nearme;

-- Users table storing credentials and profile details
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

-- Dedicated application database user
CREATE USER IF NOT EXISTS 'nearme_app'@'127.0.0.1'
IDENTIFIED BY 'local_db_password';

GRANT ALL PRIVILEGES ON nearme.*
TO 'nearme_app'@'127.0.0.1';

FLUSH PRIVILEGES;
```

---

## How Distance & Hobby Matching Works

### 1. Distance Calculation (Haversine Formula)

The distance between the logged-in user and any other user is computed using their geographic coordinates \((\text{lat}_1, \text{lon}_1)\) and \((\text{lat}_2, \text{lon}_2)\) via the Haversine formula in `backend/find_people.php`:

\[
d = 2 R \cdot \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta\text{lat}}{2}\right) + \cos(\text{lat}_1)\cos(\text{lat}_2)\sin^2\left(\frac{\Delta\text{lon}}{2}\right)}\right)
\]

where \(R = 6371\text{ km}\) is the Earth's mean radius.

### 2. Hobby Matching Algorithm

Each user's hobbies are parsed into a normalized list of trimmed strings. When comparing two users:
- The system checks which of the target user's hobbies exist in the current user's hobbies (case-insensitively).
- Matching hobbies are returned as `matched_hobbies` and displayed with green `🎯 [Hobby]` tags.
- Other hobbies are categorized as `unmatched_hobbies` and displayed as standard tags.
- The count of matching hobbies determines the match score and allows sorting by **Most Shared Hobbies**.

---

# How to Run Locally (macOS)

## 1. Install Prerequisites

Using Homebrew:

```bash
brew install php
brew install mysql
brew install phpmyadmin
```

Verify your PHP and MySQL installations:

```bash
php -v
mysql --version
php -m | grep mysqli
```

Expected output for the extension check:

```text
mysqli
```

## 2. Start MySQL

Start the MySQL service:

```bash
brew services start mysql
```

Verify that MySQL is running on port `3306`:

```bash
sudo lsof -nP -iTCP:3306 -sTCP:LISTEN
```

## 3. Create and Import the Database

### Option A: Using the MySQL Command Line

In your project root directory:

```bash
mysql -u root -p
```
*(Press Enter if you do not have a root password set)*

At the `mysql>` prompt:

```sql
SOURCE backend/schema.sql;
```

### Option B: Using phpMyAdmin

In a separate terminal, start phpMyAdmin:

```bash
php -S 127.0.0.1:8080 -t "$(brew --prefix)/share/phpmyadmin"
```

1. Navigate to [http://127.0.0.1:8080/](http://127.0.0.1:8080/) in your browser.
2. Sign in with your MySQL credentials.
3. Click the **Import** tab.
4. Choose `backend/schema.sql` and click **Import**.

## 4. Check the Database Credentials

Importing `backend/schema.sql` creates the database user and sample accounts automatically:

| Database Setting | Default Value |
| --- | --- |
| Host | `127.0.0.1` |
| Username | `nearme_app` |
| Password | `local_db_password` |
| Database | `nearme` |

These match `backend/db.php`. In case your MySQL server is running directly as `root` without password, `backend/db.php` also includes an automatic fallback to `root` with empty password.

## 5. Start the PHP Development Server

From the project root directory, run:

```bash
php -S 127.0.0.1:8000
```

Expected output:

```text
PHP 8.x.x Development Server (http://127.0.0.1:8000) started
```

Open your browser to:

[http://127.0.0.1:8000/frontend/index.html](http://127.0.0.1:8000/frontend/index.html)

---

# How to Run Locally (Windows using XAMPP)

## 1. Install XAMPP

Download and install XAMPP with **Apache**, **MySQL**, and **phpMyAdmin** selected from [apachefriends.org](https://www.apachefriends.org/).

## 2. Copy the Project into `htdocs`

Copy the `Nearme` folder into your XAMPP web directory:

```text
C:\xampp\htdocs\Nearme\
```

Ensure the structure matches:

```text
C:\xampp\htdocs\Nearme\backend\db.php
C:\xampp\htdocs\Nearme\backend\schema.sql
C:\xampp\htdocs\Nearme\frontend\index.html
C:\xampp\htdocs\Nearme\frontend\login.html
```

## 3. Start Apache and MySQL

Open the **XAMPP Control Panel** and click **Start** next to:
- **Apache**
- **MySQL**

## 4. Import the Database in phpMyAdmin

1. Open [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/) in your web browser.
2. Click the **Import** tab at the top.
3. Select `C:\xampp\htdocs\Nearme\backend\schema.sql`.
4. Click **Import** (or **Go**).
5. The `nearme` database will be created with the `users` table and demo records.

## 5. Open the Application in your Browser

| Page | Windows XAMPP URL |
| --- | --- |
| Homepage | [http://localhost/Nearme/frontend/index.html](http://localhost/Nearme/frontend/index.html) |
| Login Page | [http://localhost/Nearme/frontend/login.html](http://localhost/Nearme/frontend/login.html) |
| Registration Page | [http://localhost/Nearme/frontend/register.html](http://localhost/Nearme/frontend/register.html) |
| User Dashboard | [http://localhost/Nearme/frontend/dashboard.php](http://localhost/Nearme/frontend/dashboard.php) |
| Profile Setup / Edit | [http://localhost/Nearme/frontend/profile.php](http://localhost/Nearme/frontend/profile.php) |

---

# Application URLs and Endpoints

| Page / Endpoint | Purpose | macOS PHP Server URL | Windows XAMPP URL |
| --- | --- | --- | --- |
| Homepage | Welcome landing page | `http://localhost:8000/frontend/index.html` | `http://localhost/Nearme/frontend/index.html` |
| Login Form | Account sign in form | `http://localhost:8000/frontend/login.html` | `http://localhost/Nearme/frontend/login.html` |
| Register Form | Account sign up form | `http://localhost:8000/frontend/register.html` | `http://localhost/Nearme/frontend/register.html` |
| Dashboard | Main feed of nearby people | `http://localhost:8000/frontend/dashboard.php` | `http://localhost/Nearme/frontend/dashboard.php` |
| Profile Page | Setup name, location, hobbies | `http://localhost:8000/frontend/profile.php` | `http://localhost/Nearme/frontend/profile.php` |
| Find People API | AJAX endpoint for live filter | `http://localhost:8000/backend/find_people.php` | `http://localhost/Nearme/backend/find_people.php` |
| Logout Action | Session termination | `http://localhost:8000/backend/logout.php` | `http://localhost/Nearme/backend/logout.php` |

---

# Ports Used

| Service | Port | Description |
| --- | ---:| --- |
| MySQL | `3306` | Relational database server |
| PHP Built-in Server | `8000` | Local development web server (macOS) |
| Apache | `80` | Web server for XAMPP (Windows) |
| phpMyAdmin | `8080` (macOS) / `80` (XAMPP) | Graphical database management interface |

---

# Test Login Credentials

All demo accounts share the password **`demo123`** and have realistic pre-populated coordinates and hobbies centered around Kota for immediate testing:

| Email | Password | Name | Handle | Distance to Center | Sample Hobbies |
| --- | --- | --- | --- | --- | --- |
| `alice@example.com` | `demo123` | Alice Walker | `@alice_w` | 0.0 km (Center) | Coding, Photography, Hiking, Reading |
| `bob@example.com` | `demo123` | Bob Sharma | `@bob_sharma` | ~4.2 km | Coding, Gaming, Hiking, Football |
| `charlie@example.com` | `demo123` | Charlie Patel | `@charlie_p` | ~2.2 km | Photography, Traveling, Cooking, Music |
| `diana@example.com` | `demo123` | Diana Mehta | `@diana_m` | ~1.3 km | Reading, Yoga, Painting, Photography |
| `ethan@example.com` | `demo123` | Ethan Verma | `@ethan_v` | ~4.7 km | Hiking, Running, Football, Coding |
| `fiona@example.com` | `demo123` | Fiona Gupta | `@fiona_g` | ~3.8 km | Music, Cooking, Photography, Chess |
| `george@example.com` | `demo123` | George Khan | `@george_k` | ~6.3 km | Chess, Coding, Reading, Gaming |

> **Tip:** You can click the **"⚡ Autofill Demo Account"** button on the login page to quickly test signing in as Alice!
