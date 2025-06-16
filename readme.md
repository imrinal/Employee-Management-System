# Employee Management System 🏢

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.x%2B-blue.svg?style=for-the-badge&logo=php" />
  <img src="https://img.shields.io/badge/MySQL-8.0%2B-blue.svg?style=for-the-badge&logo=mysql" />
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" />
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" />
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" />
</p>

---
🖼️ Screenshots

📊 Admin Profile  
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/admin_profile.jpeg?raw=true" width="700"/>

📝 Assign Task  
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/admin_assign_task.jpeg?raw=true" width="700"/>

📢 Employee Announcements  
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/employee_announcement.jpeg?raw=true" width="700"/>

📋 Employee Dashboard  
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/employee_dashboard.jpeg?raw=true" width="700"/>


---

## 🚀 Overview

The **Employee Management System** is a full-stack web application built with **PHP**, **MySQL**, and **Tailwind CSS**, designed to streamline HR operations. It offers:

- ✅ Admin & Employee login portals
- ✅ Attendance & leave management
- ✅ Payroll generation
- ✅ Performance ratings
- ✅ Task assignment
- ✅ Profile management

> Boost productivity while minimizing manual processes in your organization!

---

## ✨ Features

### 👑 Admin Panel

- Dashboard overview
- Create/edit announcements
- Add/edit/delete employee records
- Assign & track tasks
- Attendance management
- Payroll & payslip generation
- Employee ratings
- Leave approval/rejection
- Admin profile management

### 👤 Employee Panel

- View & update personal profile
- View/download payslips
- Apply for leaves
- See assigned tasks
- View announcements
- Change password

---

## 🛠 Tech Stack

| Layer       | Tech Used                     |
| ----------- | ----------------------------- |
| Backend     | PHP 7.x                       |
| Database    | MySQL 8.x                     |
| Frontend    | HTML5, CSS3, Tailwind CSS, JS |
| Tools       | XAMPP/WAMP, phpMyAdmin        |
| Optional UI | Bootstrap, Font Awesome       |

## ⚙️ Setup Instructions

### 🔧 Prerequisites

- Apache server (XAMPP/WAMP/LAMP)
- PHP 7.x
- MySQL 8.x
- Git (optional but helpful)

### 🗄️ Database Setup

1. Open [phpMyAdmin](http://localhost/phpmyadmin/)
2. Create a database: `employee_management_system`
3. Import the SQL schema manually:
   - Write your own `schema.sql` file based on project tables
   - Or use the provided example below

## 🧱 Database Tables

```sql
CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255),
  `email` VARCHAR(255) UNIQUE,
  `password` VARCHAR(75),
  `dp` VARCHAR(255) DEFAULT '1.jpg'
);

CREATE TABLE `employee` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255),
  `email` VARCHAR(255) UNIQUE,
  `password` VARCHAR(75),
  `salary` INT,
  `dp` VARCHAR(255) DEFAULT '1.jpg'
);


## 🖼️ Screenshots

### 🧑‍💼 Admin - Profile Management
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/admin-profile.png?raw=true" width="700"/>

### ✅ Admin - Assign Task to Employees
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/admin-assign-task.png?raw=true" width="700"/>

### 📢 Employee - View Announcements
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/employee-announcement.png?raw=true" width="700"/>

### 🏠 Employee - Dashboard View
<img src="https://github.com/imrinal/Employee-Management-System/blob/main/docs/employee-dashboard.png?raw=true" width="700"/>
```
