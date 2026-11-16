# NearMe

A web-based location and hobby discovery application built using **HTML, CSS, JavaScript, PHP, and MySQL**.

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** Procedural PHP
- **Database:** MySQL
- **Database connection:** Procedural MySQLi (`mysqli_connect`, `mysqli_query`, `mysqli_fetch_assoc`, `mysqli_real_escape_string`)
- **Database management:** phpMyAdmin or MySQL CLI

The code uses basic functions, `if` statements, loops, and sessions. Text is escaped with `mysqli_real_escape_string` before SQL execution, and `htmlspecialchars` displays text safely in HTML.

---

## Folder Structure

```text
Nearme/
├── backend/
│   ├── auth.php             # Starts the session and checks user authentication
│   ├── db.php               # Opens the MySQL connection using procedural MySQLi
│   ├── find_people.php      # Calculates distance (Haversine) and matching hobbies
│   ├── login.php            # Validates email & password, starts user session
│   ├── logout.php           # Ends session and returns to login
│   ├── profile.php          # Handles fetching and updating user profile details
│   ├── register.php         # Creates new accounts with email and password
│   └── schema.sql           # Database schema, table, db user, and sample accounts
├── frontend/
│   ├── css/
│   │   ├── index.css        # Base styles, layout, homepage, and auth forms
│   │   └── dashboard.css    # Dashboard layout, user cards, and profile forms
│   ├── js/
│   │   ├── dashboard.js     # Auto-submits distance filter on change
│   │   └── location.js      # Browser Geolocation API helper & reverse geocoding
│   ├── dashboard.php        # Main dashboard displaying nearby people & matched hobbies
│   ├── index.html           # Homepage
│   ├── login.html           # Login form
│   ├── profile.php          # Profile setup and editing form
│   └── register.html        # Registration form
├── .gitignore
└── README.md
```

---

## Database Schema

The MySQL database is named `nearme`. The importable schema is in [backend/schema.sql](backend/schema.sql).

| Table | Purpose |
| --- | --- |
| `users` | Stores user credentials (email, password), profile details (name, username, phone, address), location coordinates (latitude, longitude), and comma-separated hobbies. |

```sql
-- Create database if missing
CREATE DATABASE IF NOT EXISTS nearme;

USE nearme;

-- Users table
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

-- Dedicated database user
CREATE USER IF NOT EXISTS 'nearme_app'@'127.0.0.1'
IDENTIFIED BY 'local_db_password';

GRANT ALL PRIVILEGES ON nearme.*
TO 'nearme_app'@'127.0.0.1';

FLUSH PRIVILEGES;
```

---

## How It Works

1. **User Sign Up & Login:**
   - A user creates an account with email and password in `frontend/register.html`.
   - On first login or registration, the system directs the user to `frontend/profile.php`.

2. **Add Profile Details & Location:**
   - The user inputs their Name, Username, Phone, Address, Location (Latitude and Longitude), and Hobbies (e.g. `Cricket, Music, Coding`).
   - Clicking **"Get Current Location"** uses the browser's HTML5 Geolocation API (`navigator.geolocation`) to automatically get live coordinates and reverse-geocode the address.

3. **Find Nearby People & Match Hobbies:**
   - The dashboard retrieves other registered users from the database.
   - Calculates the distance between the logged-in user and each user using the mathematical **Haversine formula**.
   - Compares the logged-in user's hobbies with each user's hobbies:
     - Shared hobbies are highlighted in green under **Common Hobbies**.
     - Other hobbies are shown under Other Hobbies.
   - Users can filter by maximum distance (5 km, 10 km, 25 km, 50 km, 100 km, or Any) and search by name or hobby.

---

# How to Run Locally (macOS)

## 1. Install Prerequisites

Using Homebrew:

```bash
brew install php
brew install mysql
brew install phpmyadmin
```

These commands install:

| Software | Purpose |
|---|---|
| PHP | Executes backend PHP code |
| MySQL | Stores application data |
| phpMyAdmin | Provides a graphical web interface to manage MySQL |

### Verify Installations

Check PHP:

```bash
php -v
```

Check MySQL:

```bash
mysql --version
```

Check MySQLi extension:

```bash
php -m | grep mysqli
```

Expected output:

```text
mysqli
```

---

## 2. Start MySQL

Start MySQL using Homebrew:

```bash
brew services start mysql
```

Check whether MySQL is listening on port `3306`:

```bash
sudo lsof -nP -iTCP:3306 -sTCP:LISTEN
```

---

## 3. Create the Database

There are two ways to import the database schema.

### Option A: Using MySQL Command Line

In your project root directory where this `README.md` is located, run:

```bash
mysql -u root -p < backend/schema.sql
```

*(Press Enter if you do not have a root password set)*

Alternatively, connect to MySQL and source the file:

```bash
mysql -u root -p
```

At the `mysql>` prompt:

```sql
SOURCE backend/schema.sql;
```

---

### Option B: Using phpMyAdmin

Start phpMyAdmin in a separate terminal:

```bash
php -S 127.0.0.1:8080 -t "$(brew --prefix)/share/phpmyadmin"
```

Open:

[http://127.0.0.1:8080/](http://127.0.0.1:8080/)

1. Sign in using your MySQL credentials (typically `root` with your password, or blank if none set).
2. Select the **Import** tab at the top.
3. Choose `backend/schema.sql` from your project folder.
4. Click **Import** (or **Go**).

*Keep this terminal open while using phpMyAdmin.*

---

## 4. Check the Database Credentials

Importing `backend/schema.sql` creates the database user automatically:

| Database Setting | Value |
|---|---|
| Host | `127.0.0.1` |
| Username | `nearme_app` |
| Password | `local_db_password` |
| Database | `nearme` |

These match `backend/db.php`. For local convenience, `backend/db.php` also includes an automatic fallback to `root` with no password.

---

## 5. Start the PHP Development Server

Open a terminal in the project root and run:

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

Download and install XAMPP with **Apache, PHP, MySQL, and phpMyAdmin** selected from [apachefriends.org](https://www.apachefriends.org/).

## 2. Copy the Project into `htdocs`

Copy the entire `Nearme` folder into your XAMPP web root:

```text
C:\xampp\htdocs\Nearme
```

## 3. Start Apache and MySQL

In **XAMPP Control Panel**, click **Start** beside both:
- **Apache** (Web and PHP server)
- **MySQL** (Database server)

## 4. Import the Database using phpMyAdmin

1. Open your browser to [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Select the **Import** tab.
3. Click **Choose File** and select `C:\xampp\htdocs\Nearme\backend\schema.sql`.
4. Click **Import** (or **Go**).
5. The `nearme` database will be created with the `users` table.

## 5. Open the Application

| Page | Windows XAMPP URL |
|---|---|
| Homepage | [http://localhost/Nearme/frontend/index.html](http://localhost/Nearme/frontend/index.html) |
| Login Page | [http://localhost/Nearme/frontend/login.html](http://localhost/Nearme/frontend/login.html) |
| Registration Page | [http://localhost/Nearme/frontend/register.html](http://localhost/Nearme/frontend/register.html) |
| Dashboard | [http://localhost/Nearme/frontend/dashboard.php](http://localhost/Nearme/frontend/dashboard.php) |
| Profile Setup / Edit | [http://localhost/Nearme/frontend/profile.php](http://localhost/Nearme/frontend/profile.php) |

---

# Application URLs and Endpoints

| Page / Action | Purpose | macOS PHP Server URL | Windows XAMPP URL |
|---|---|---|---|
| Homepage | Welcome & navigation | `http://localhost:8000/frontend/index.html` | `http://localhost/Nearme/frontend/index.html` |
| Login | Sign in form | `http://localhost:8000/frontend/login.html` | `http://localhost/Nearme/frontend/login.html` |
| Register | Create new account | `http://localhost:8000/frontend/register.html` | `http://localhost/Nearme/frontend/register.html` |
| Dashboard | View profile & nearby matches | `http://localhost:8000/frontend/dashboard.php` | `http://localhost/Nearme/frontend/dashboard.php` |
| Profile Setup / Edit | Update details, location & hobbies | `http://localhost:8000/frontend/profile.php` | `http://localhost/Nearme/frontend/profile.php` |
| Logout Action | End session & redirect to login | `http://localhost:8000/backend/logout.php` | `http://localhost/Nearme/backend/logout.php` |

---

# Ports Used

These ports apply to the development setup:

| Service | Port | Purpose |
|---|---:|---|
| MySQL | `3306` | Relational database server |
| PHP Development Server | `8000` | Local web server (macOS: `php -S 127.0.0.1:8000`) |
| Apache | `80` | Web server in Windows XAMPP |
| phpMyAdmin | `8080` (macOS) / `80` (XAMPP) | Graphical database management interface |

---

# Sample Accounts in Schema

For immediate verification right after importing, [backend/schema.sql](backend/schema.sql) includes two sample accounts (password for both is **`demo123`**):

| Email | Password | Name | Sample Hobbies |
|---|---|---|---|
| `rahul@example.com` | `demo123` | Rahul Sharma | Cricket, Coding, Music |
| `priya@example.com` | `demo123` | Priya Verma | Reading, Music, Traveling, Photography |
