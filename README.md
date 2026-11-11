# LearnLoop: E-learning Portal (PHP + MySQL)

Capstone project for the ApexPlanet internship (Task 5).

## Features
- Registration with **email OTP verification** (hashed, expiring, 5 attempts, 60 s resend limit)
- Login with brute-force lockout, session regeneration, bcrypt passwords
- Course catalog with **AJAX real-time search and filters** (debounced, no page reload)
- Enroll / leave courses, lesson list, AJAX "mark complete" with live progress bar
- Student dashboard with progress per course
- Profile: edit details, avatar upload (MIME-checked), change password, delete account
- Admin panel: **CRUD** for courses, lessons and users; **Chart.js analytics dashboard**
- Security: PDO prepared statements, CSRF tokens on every POST, output escaping, upload hardening, role checks, security headers

## Run locally (XAMPP / WAMP)
1. Needs **PHP 8.1+** and MySQL. Copy this folder to `htdocs/elearn`.
2. In phpMyAdmin import `database.sql`.
3. Check DB settings in `config.php`.
4. Open http://localhost/elearn/. With `DEV_SHOW_OTP = true` the OTP appears on screen.
5. Admin login: `admin@learnloop.test` / `password`. **Change this password straight away** (Profile > Change password).

## Deploy on InfinityFree
1. Create an account and a hosting site; note the MySQL host, user, password and DB name from the control panel.
2. In phpMyAdmin (InfinityFree) select your database, remove the first two lines of `database.sql` (`CREATE DATABASE` and `USE`), then import it.
3. Edit `config.php` with the live DB details, set `MAIL_FROM` to an address on your domain, and set **`DEV_SHOW_OTP` to `false`**.
4. Upload the files into `htdocs/` via File Manager or FTP, or link GitHub (below).
5. Choose PHP 8.1+ in the control panel. Make sure `uploads/avatars/` is writable.
6. Test: register, receive the OTP email, log in, enroll, open the admin panel.

If `mail()` does not deliver on your host, swap the `mail()` call in `includes/functions.php` for PHPMailer with Gmail SMTP and an app password.

## Link GitHub for live updates
- Push this project to a GitHub repo. Do **not** commit real passwords: keep production values in a `config.php` that is listed in `.gitignore`, and commit a `config.sample.php` instead.
- InfinityFree has no built-in Git deploy, so use a GitHub Action that uploads over FTP on every push. Add `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD` as repository secrets and a workflow using `SamKirkland/FTP-Deploy-Action`. 000webhost-style hosts that offer Git integration can pull directly.

## Folder map
```
config.php  database.sql  index.php  register.php  verify_otp.php  login.php  logout.php
course.php  dashboard.php  profile.php
api/        courses.php (search JSON)  progress.php (mark lesson)
admin/      index.php (charts)  courses.php  course_form.php  lessons.php  users.php
includes/   functions.php  header.php  footer.php
assets/     css/style.css  js/app.js  js/progress.js
docs/       ER-diagram.md  wireframes.md
```
