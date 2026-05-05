# ISSUE-001: Setup Laravel Project

## 📋 Title
Setup Laravel Project dengan Struktur & Konfigurasi Dasar

## 📝 Description
Membuat fresh Laravel project dengan struktur folder yang benar sesuai PRD specification. Project ini akan menjadi base untuk semua development berikutnya.

## 🔴 Problem
- Backend belum ada
- Dibutuhkan base Laravel project untuk development
- Environment configuration belum setup
- Database connection belum konfigurasi

## ✅ Expected Result
- ✅ Laravel project ready to use
- ✅ Database connected and working
- ✅ Environment properly configured
- ✅ Can run `php artisan serve` without errors
- ✅ Folder structure sesuai PRD

## 📦 Scope
- Fresh Laravel 10.x installation
- Environment configuration (.env setup)
- Database setup dan configuration
- Dependencies installation (Composer)
- Verify basic project structure

## ✔️ Acceptance Criteria
- [ ] Laravel installed dengan `laravel new dompet-backend`
- [ ] `.env` file configured dengan database credentials
- [ ] Database connection verified (no connection errors)
- [ ] `php artisan serve` runs successfully
- [ ] Database migrations can be executed
- [ ] Project structure matches PRD
- [ ] No warning/error logs
- [ ] README.md updated dengan setup instructions

## 🔗 Resources
- [Laravel Documentation](https://laravel.com/docs/10.x)
- [Laravel Installation](https://laravel.com/docs/10.x/installation)
- [PRD Backend](../PRD-Backend.md)
- [Laravel Configuration](https://laravel.com/docs/10.x/configuration)

## 📋 Tasks
### 1. Install Laravel Framework
- [ ] Install Laravel 10.x latest stable
- [ ] Verify installation with `php -v` dan `composer -v`
- [ ] Navigate to project directory

### 2. Setup Environment
- [ ] Copy `.env.example` ke `.env`
- [ ] Generate APP_KEY dengan `php artisan key:generate`
- [ ] Update APP_NAME ke "DOMPET Backend"
- [ ] Update APP_URL ke http://localhost:8000

### 3. Configure Database
- [ ] Create MySQL database named `dompet_db`
- [ ] Update `.env` dengan database config:
  - DB_CONNECTION=mysql
  - DB_HOST=127.0.0.1
  - DB_PORT=3306
  - DB_DATABASE=dompet_db
  - DB_USERNAME=[your_db_user]
  - DB_PASSWORD=[your_db_password]
- [ ] Test database connection

### 4. Install Dependencies
- [ ] Run `composer install` (if not already done)
- [ ] Verify all packages installed successfully
- [ ] Run `php artisan migrate` untuk test database

### 5. Verify Setup
- [ ] Run `php artisan serve`
- [ ] Access http://localhost:8000 di browser
- [ ] Verify no errors di console
- [ ] Check database connection dengan `php artisan tinker`

## 📊 Effort Estimate
- Estimated Hours: 2-3 hours
- Difficulty: Easy

## 🏷️ Labels
- Type: Setup
- Priority: High
- Complexity: Easy

## 👤 Assigned To
[Developer Name]

## 📅 Timeline
- Created: [Date]
- Start Date: [Date]
- Due Date: [Date]
- Completed: [Date]

## 🔄 Dependencies
- PHP 8.1+ installed
- Composer installed
- MySQL 8.0+ installed
- Node.js (for build tools, if needed)

## 📌 Notes
- This is the foundation task, must complete before proceeding
- Documentation should be clear untuk junior developer
- Test semua steps sebelum mark as done

## 🔗 Related Issues
- None (first task)

## 💬 Comments/Discussion
[Any additional discussion or clarifications]
