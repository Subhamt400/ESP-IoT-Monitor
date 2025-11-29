# Odisha Sensor Dashboard

Summary
-------
This is a small sensor dashboard project with two main parts:
- `index/` — admin/login front-end and PHP endpoints
- `local/` — local-dashboard front-end and API endpoints for sensor readings

This README explains how to run the project on MAMP, lists required databases/tables, highlights issues found during a static check, and proposes a recommended repository structure for GitHub deployment.

Prerequisites
-------------
- macOS with MAMP (Apache + MySQL) installed.
- PHP (compatible with your MAMP installation, PHP 7.4+ recommended).

Quick start (MAMP)
-------------------
1. Copy this `odisha` folder to your MAMP document root, for example:

```bash
# macOS (example)
cp -R /path/to/odisha /Applications/MAMP/htdocs/
# Odisha Sensor Dashboard

Overview
--------
This repository contains a small sensor-dashboard application. It has been restructured into a `public/` folder for front-end assets (HTML/CSS/JS) and a `server/` folder for PHP endpoints. The UI uses Bootstrap 5 and a modern theme; the server has been hardened to use secure password handling and better JSON APIs for AJAX clients.

Current project layout
----------------------
Root now contains three main items:

- `public/` — static pages and assets served to the browser
  - `public/login.html` — login page (styled, responsive, animated auth card)
  - `public/admin_dashboard.html` — admin UI (responsive, compact mobile view)
  - `public/local_dashboard.html` — local dashboard UI
  - `public/assets/` — `css/` and `js/` for the front-end

- `server/` — PHP endpoints and server-side logic
  - `server/login.php` — login API (supports secure hashes and legacy MD5 migration)
  - `server/logout.php` — logout handler
  - `server/admin_dashboard.php` — admin API (JSON responses; no redirects)
  - `server/local.php` — local API for the local dashboard
  - `server/api.php` — ingestion endpoint for ESP devices (POST readings)

- `README.md` — this file

Notes: Old directories `index/` and `local/` were removed; their old files were migrated to `public/`/`server/` and the repository kept backward-compatible redirects during transition (these were later removed).

Prerequisites
-------------
- macOS with MAMP (Apache + MySQL) installed (or any LAMP with PHP 7.4+).
- PHP with mysqli and common extensions available.

Database setup (example)
------------------------
Run these SQL snippets in phpMyAdmin or the MySQL client. Adjust names and credentials if needed.

-- Create `login_system` and `users`:
```sql
CREATE DATABASE IF NOT EXISTS login_system;
USE login_system;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','local') NOT NULL DEFAULT 'local'
);
-- Example admin user (recommended to create with password_hash using PHP script or manually):
-- For testing you can insert a password hash created by PHP's password_hash
-- Example (pseudo): INSERT INTO users (username, password, role) VALUES ('admin', '<password_hash_here>', 'admin');
```

-- Create `sensor_data` and tables:
```sql
CREATE DATABASE IF NOT EXISTS sensor_data;
USE sensor_data;
CREATE TABLE IF NOT EXISTS sensor_id (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lab_name VARCHAR(255) NOT NULL,
  esp8266_id INT NOT NULL,
  sensor_short_name VARCHAR(255) DEFAULT NULL,
  data_interval INT DEFAULT 60
);

CREATE TABLE IF NOT EXISTS readings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  esp8266_id INT NOT NULL,
  temperature DOUBLE,
  humidity DOUBLE,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Quick local preview (MAMP)
--------------------------
1. Place this `odisha/` folder inside your MAMP document root (e.g. `/Applications/MAMP/htdocs/odisha`).
2. Start MAMP (Apache + MySQL).
3. Open the login page in your browser:

```
http://localhost:8888/odisha/public/login.html
```

After logging in you will be redirected to either:

- Admin: `public/admin_dashboard.html`
- Local: `public/local_dashboard.html`

If you prefer to serve the app from `/odisha/` without the `public/` segment, configure your server's document root to point to `odisha/public/` and ensure `server/` remains accessible via the routes used by the front-end (or move `server/` behind the docroot and update endpoints accordingly).

What changed (high level)
-------------------------
- Project restructured into `public/` (UI) and `server/` (PHP) for clarity.
- Login security: `server/login.php` now uses `password_verify()` with `password_hash()` and supports a one-time migration path for legacy MD5-hashed passwords (on a successful MD5 login the password is rehashed with `password_hash()` and updated in DB).
- API behavior: server endpoints return proper JSON and use appropriate HTTP status codes rather than HTML redirects (this prevents fetch() clients from failing JSON parsing).
- Styling: UI now uses Bootstrap 5 and the Inter font. The login page uses an animated translucent auth-card that matches the background, includes a show-password toggle, and has mobile-optimized styles. Admin page uses responsive/compact table layouts for small screens.
- Production safety: PHP files have `display_errors` disabled; prepare your production logging and backup your DB before changing settings.

Styling and UX improvements
--------------------------
- Login page: translucent animated card, show/hide password toggle, responsive mobile layout.
- Admin dashboard: responsive header, stacked controls on small screens, compact tables with horizontal scroll when needed.
- All front-end scripts use relative paths to `../server/*.php` so the app is portable across host/port.

Testing the APIs (curl examples)
-------------------------------
Login (replace username/password and host/port as needed):

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"yourpassword"}' \
  http://localhost:8888/odisha/server/login.php
```

Add a reading (ESP device POST example):

```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{"id":123, "temp":24.5, "rh":55.1}' \
  http://localhost:8888/odisha/server/api.php
```

Security notes and recommendations
---------------------------------
- Do not use MD5 for passwords—this project now migrates legacy MD5 to `password_hash()` automatically on first successful login, but you should re-create users using secure hashes if possible.
- Disable `display_errors` in production and use proper error logging.
- Serve the site over HTTPS in production.
- Consider moving `server/` outside the webroot and exposing only a small, controlled API surface.

Next recommended actions (pick any)
----------------------------------
- (A) Move web server docroot to `public/` and place `server/` outside the public folder (recommended for real deployments).
- (B) Add `sql/init.sql` to this repo with the exact CREATE TABLE statements and a sample user hashed with `password_hash()` for easier setup.
- (C) Add UI polish: toast notifications for save/delete, toggle eye icon state, or add a sidebar navigation.

If you'd like, I can implement any of the above (A/B/C) and update this README with exact commands and sample SQL files.

— Project updated and refactored by the assistant
