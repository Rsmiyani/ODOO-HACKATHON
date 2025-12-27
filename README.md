# 🛡️ GearGuard - The Ultimate Maintenance Tracker

<div align="center">

![GearGuard Logo](https://img.shields.io/badge/GearGuard-Maintenance%20Tracker-blue?style=for-the-badge&logo=shield&logoColor=white)

**A comprehensive maintenance management system for tracking assets, teams, and maintenance requests**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

[Features](#-features) • [Demo](#-demo) • [Installation](#-installation) • [Usage](#-usage) • [Screenshots](#-screenshots) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [Project Structure](#-project-structure)
- [Database Schema](#-database-schema)
- [User Roles & Permissions](#-user-roles--permissions)
- [Screenshots](#-screenshots)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

---

## 🎯 About

**GearGuard** is a modern, full-featured maintenance management system designed to streamline equipment tracking, maintenance requests, and team coordination. Built with PHP and MySQL, it provides an intuitive interface for managing company assets and maintenance workflows.

### 🌟 Key Highlights

- **Equipment Management**: Track all company assets with detailed information
- **Smart Workflows**: Automated assignment and status tracking
- **Team Collaboration**: Organize maintenance teams and assignments
- **Visual Analytics**: Comprehensive reports with interactive charts
- **Role-Based Access**: Secure permissions for Admin, Manager, and Technician roles
- **Modern UI/UX**: Responsive design with drag-and-drop Kanban board

---

## ✨ Features

### 📦 Equipment Management
- ✅ Complete CRUD operations (Create, Read, Update, Delete)
- ✅ Advanced search and filtering
- ✅ Group by Department, Category, Employee, or Team
- ✅ Track purchase dates, warranty information, and location
- ✅ Equipment status tracking (Active, Under Maintenance, Scrapped)
- ✅ Smart maintenance buttons with request count badges

### 🔧 Maintenance Requests
- ✅ Create corrective (breakdown) and preventive (routine) requests
- ✅ Auto-fill equipment details and team assignments
- ✅ Priority levels (Low, Medium, High, Urgent)
- ✅ Duration and hours tracking
- ✅ Request lifecycle management (New → In Progress → Repaired → Scrap)
- ✅ Request history and audit trail

### 👥 Team Management
- ✅ Create and manage maintenance teams
- ✅ Add/remove team members
- ✅ Team-based request assignment
- ✅ Team performance tracking

### 📊 Visual Dashboards
- ✅ **Kanban Board**: Drag-and-drop request management
- ✅ **Calendar View**: Schedule and visualize preventive maintenance
- ✅ **Analytics Dashboard**: Real-time statistics and metrics
- ✅ **Reports**: Interactive charts with Chart.js
  - Requests per Team (Bar Chart)
  - Requests per Category (Pie Chart)
  - Monthly Trends (Line Chart)
  - Priority Distribution
  - Equipment Status Overview

### 👤 User Management (Admin Only)
- ✅ User CRUD operations
- ✅ Role assignment (Admin, Manager, Technician)
- ✅ Activate/Deactivate users
- ✅ Track user activity and assigned requests

### 🔐 Security & Access Control
- ✅ Role-based permissions system
- ✅ CSRF protection
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection

### 🎨 User Experience
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Modern, clean interface
- ✅ Real-time notifications
- ✅ Print-friendly views
- ✅ Intuitive navigation
- ✅ Visual status indicators

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.0+** - Server-side logic
- **MySQL 8.0+** - Database management
- **PDO** - Database abstraction layer

### Frontend
- **HTML5** - Structure
- **CSS3** - Styling with custom variables
- **JavaScript (ES6+)** - Interactive features
- **Font Awesome 6.5+** - Icons

### Libraries & Frameworks
- **Chart.js 4.4.0** - Data visualization
- **FullCalendar 6.x** - Calendar functionality
- **SortableJS** - Drag-and-drop Kanban
- **Inter Font** - Typography

### Development Tools
- **XAMPP/LAMP** - Local development environment
- **Git** - Version control
- **VS Code** - Recommended IDE

---

## 📥 Installation

### Prerequisites

Before you begin, ensure you have:
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (optional, for future extensions)

### Step 1: Clone the Repository

git clone https://github.com/yourusername/gearguard.git
cd gearguard

text

### Step 2: Database Setup

1. Create a new MySQL database:
CREATE DATABASE gearguard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

text

2. Import the database schema:
mysql -u your_username -p gearguard < database/gearguard.sql

text

Or use phpMyAdmin to import `database/gearguard.sql`

### Step 3: Configure Database Connection

Edit `php/config.php` with your database credentials:

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gearguard');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

text

### Step 4: Set Permissions

chmod -R 755 gearguard/
chmod -R 777 gearguard/uploads/ # If you add file upload functionality

text

### Step 5: Access the Application

1. Start your web server (XAMPP/LAMP/WAMP)
2. Navigate to: `http://localhost/gearguard`
3. Default admin credentials:
   - **Email**: admin@gearguard.com
   - **Password**: admin123

**⚠️ Important**: Change the default password after first login!

---

## ⚙️ Configuration

### Environment Variables

Create a `.env` file (optional for production):

DB_HOST=localhost
DB_NAME=gearguard
DB_USER=root
DB_PASS=
APP_ENV=production
DEBUG_MODE=false

text

### Email Configuration (Optional)

For email notifications, configure SMTP in `php/config.php`:

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');

text

---

## 🚀 Usage

### For Administrators

1. **User Management**: Add users and assign roles
2. **System Configuration**: Manage teams and equipment
3. **Reports**: Monitor system-wide performance
4. **Permissions**: Control access levels

### For Managers

1. **Equipment Management**: Add and track company assets
2. **Team Management**: Create teams and assign members
3. **Request Oversight**: Assign and monitor maintenance requests
4. **Scheduling**: Plan preventive maintenance on calendar
5. **Reports**: View analytics and performance metrics

### For Technicians

1. **View Requests**: See assigned maintenance tasks
2. **Update Status**: Move requests through workflow stages
3. **Kanban Board**: Manage work using drag-and-drop
4. **Record Time**: Track hours spent on repairs
5. **Create Requests**: Report new issues

---

## 📁 Project Structure

gearguard/
├── 📄 index.php # Login page
├── 📄 register.php # User registration
├── 📄 logout.php # Logout handler
├── 📄 README.md # This file
├── 📄 LICENSE # MIT License
│
├── 📂 php/
│ ├── 📄 config.php # Database & app configuration
│ └── 📂 api/
│ ├── 📄 create_user.php
│ ├── 📄 update_user.php
│ ├── 📄 delete_user.php
│ ├── 📄 toggle_user_status.php
│ └── 📄 update_request_stage.php
│
├── 📂 pages/
│ ├── 📄 dashboard.php # Main dashboard
│ ├── 📄 equipment.php # Equipment list (with grouping)
│ ├── 📄 add-equipment.php # Add new equipment
│ ├── 📄 edit-equipment.php # Edit equipment
│ ├── 📄 view-equipment.php # Equipment details
│ ├── 📄 kanban.php # Kanban board
│ ├── 📄 calendar.php # Calendar view
│ ├── 📄 requests.php # Request list
│ ├── 📄 create-request.php # Create request
│ ├── 📄 edit-request.php # Edit request
│ ├── 📄 view-request.php # Request details
│ ├── 📄 teams.php # Teams list
│ ├── 📄 add-team.php # Add team
│ ├── 📄 edit-team.php # Edit team
│ ├── 📄 reports.php # Analytics & reports
│ ├── 📄 users.php # User management (Admin)
│ └── 📂 includes/
│ └── 📄 equipment-card.php
│
├── 📂 css/
│ ├── 📄 style.css # Login/Register styles
│ ├── 📄 dashboard.css # Main dashboard styles
│ ├── 📄 equipment.css # Equipment pages styles
│ ├── 📄 kanban.css # Kanban board styles
│ ├── 📄 form.css # Form styles
│ ├── 📄 view-equipment.css # Equipment detail styles
│ ├── 📄 view-request.css # Request detail styles
│ ├── 📄 reports.css # Reports page styles
│ └── 📄 users.css # User management styles
│
├── 📂 js/
│ ├── 📄 dashboard.js # Main dashboard logic
│ ├── 📄 equipment.js # Equipment management
│ ├── 📄 kanban.js # Kanban board functionality
│ ├── 📄 create-request.js # Request creation logic
│ ├── 📄 view-request.js # Request detail interactions
│ └── 📄 users.js # User management logic
│
├── 📂 database/
│ └── 📄 gearguard.sql # Database schema & sample data
│
└── 📂 assets/
└── 📂 images/ # Screenshots and logos

text

---

## 🗄️ Database Schema

### Main Tables

#### **users**
id (PK)

name

email (unique)

password (hashed)

role (admin/manager/technician)

is_active

created_at

text

#### **equipment**
id (PK)

equipment_name

serial_number (unique)

category

department

location

assigned_to_employee

maintenance_team_id (FK)

default_technician_id (FK)

purchase_date

warranty_expiry

status

created_at

text

#### **maintenance_teams**
id (PK)

team_name

description

created_at

text

#### **team_members**
id (PK)

team_id (FK)

user_id (FK)

created_at

text

#### **maintenance_requests**
id (PK)

equipment_id (FK)

created_by (FK)

assigned_to (FK)

subject

description

request_type (corrective/preventive)

priority (low/medium/high/urgent)

stage (new/in_progress/repaired/scrap)

scheduled_date

duration_hours

completed_at

created_at

updated_at

text

#### **request_history**
id (PK)

request_id (FK)

action

user_id (FK)

created_at

text

---

## 👥 User Roles & Permissions

### 🔴 Admin
**Full system control and administration**

- ✅ All Manager permissions
- ✅ User management (create, edit, delete users)
- ✅ System configuration
- ✅ Database management
- ✅ Access to all modules

### 🟡 Manager
**Operational management**

- ✅ View and manage all equipment
- ✅ Create and assign maintenance requests
- ✅ Manage teams and team members
- ✅ Schedule preventive maintenance
- ✅ Access calendar and reports
- ✅ Monitor all maintenance activities
- ❌ Cannot manage users or system settings

### 🟢 Technician
**Task execution**

- ✅ View assigned requests
- ✅ Update status of assigned requests
- ✅ Record work duration
- ✅ Create new maintenance requests
- ✅ Access Kanban board (assigned tasks only)
- ❌ Cannot manage equipment, teams, or view reports
- ❌ Limited to assigned work only

---

## 📸 Screenshots

### Dashboard
![Dashboard](assets/images/dashboard.png)
*Main dashboard with real-time statistics and quick actions*

### Kanban Board
![Kanban Board](assets/images/kanban.png)
*Drag-and-drop interface for managing maintenance requests*

### Equipment Management
![Equipment List](assets/images/equipment.png)
*Equipment list with grouping and filtering options*

### Calendar View
![Calendar](assets/images/calendar.png)
*Schedule and visualize preventive maintenance*

### Reports & Analytics
![Reports](assets/images/reports.png)
*Interactive charts and analytics dashboard*

---

## 📡 API Documentation

### Authentication Required
All API endpoints require authentication via PHP session.

### Available Endpoints

#### User Management
POST /php/api/create_user.php
POST /php/api/update_user.php
POST /php/api/delete_user.php
POST /php/api/toggle_user_status.php

text

#### Request Management
POST /php/api/update_request_stage.php
GET /php/api/get_requests.php
POST /php/api/assign_request.php

text

### Example Request

fetch('../php/api/update_request_stage.php', {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify({
request_id: 123,
stage: 'in_progress'
})
})
.then(response => response.json())
.then(data => console.log(data));

text

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### How to Contribute

1. **Fork the repository**
git fork https://github.com/yourusername/gearguard.git

text

2. **Create a feature branch**
git checkout -b feature/AmazingFeature

text

3. **Commit your changes**
git commit -m 'Add some AmazingFeature'

text

4. **Push to the branch**
git push origin feature/AmazingFeature

text

5. **Open a Pull Request**

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting PR
- Update documentation if needed

### Bug Reports

Found a bug? Please open an issue with:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Screenshots (if applicable)

---

## 🎯 Roadmap

### Version 2.0 (Planned)
- [ ] Email notifications for request updates
- [ ] File attachments for requests
- [ ] Mobile app (iOS/Android)
- [ ] REST API for third-party integrations
- [ ] Multi-language support
- [ ] Advanced reporting with PDF export
- [ ] Real-time chat for team collaboration
- [ ] QR code scanning for equipment

### Version 1.1 (In Progress)
- [ ] Dark mode theme
- [ ] Equipment maintenance schedule templates
- [ ] Custom fields for equipment
- [ ] Export data to CSV/Excel

---

## 📜 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

MIT License

Copyright (c) 2025 GearGuard

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...

text

---

## 📞 Contact

**Project Maintainer**: Your Name

- 📧 Email: your.email@example.com
- 🐙 GitHub: [@yourusername](https://github.com/Rsmiyani)
- 💼 LinkedIn: [Your Profile](https://www.linkedin.com/in/rudramiyani2024/)
- 🌐 Website: [yourwebsite.com](https://yourwebsite.com)

**Project Link**: [https://github.com/yourusername/gearguard](https://github.com/Rsmiyani/ODOO-HACKATHON)

---

## 🙏 Acknowledgments

- [Font Awesome](https://fontawesome.com) - Icon library
- [Chart.js](https://www.chartjs.org/) - Data visualization
- [FullCalendar](https://fullcalendar.io/) - Calendar functionality
- [SortableJS](https://sortablejs.github.io/Sortable/) - Drag-and-drop
- [Google Fonts - Inter](https://fonts.google.com/specimen/Inter) - Typography

---

## ⭐ Show Your Support

If you found this project helpful, please give it a ⭐️ on GitHub!

[![GitHub stars](https://img.shields.io/github/stars/yourusername/gearguard?style=social)](https://github.com/yourusername/gearguard/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/yourusername/gearguard?style=social)](https://github.com/yourusername/gearguard/network/members)

---

<div align="center">

**Made with ❤️ for efficient maintenance management**

[⬆ Back to Top](#️-gearguard---the-ultimate-maintenance-tracker)

</div>
🎨 Additional Files to Add
1. Create .gitignore
text
# Configuration files with sensitive data
php/config.php

# Database dumps
*.sql
database/backup/

# Uploads directory
uploads/*
!uploads/.gitkeep

# IDE files
.vscode/
.idea/
*.sublime-project
*.sublime-workspace

# OS files
.DS_Store
Thumbs.db
desktop.ini

# Logs
*.log
logs/

# Temporary files
tmp/
temp/
cache/

# Environment files
.env
.env.local
2. Create LICENSE file (MIT License)
text
MIT License

Copyright (c) 2025 GearGuard

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
3. Create CONTRIBUTING.md
text
# Contributing to GearGuard

Thank you for your interest in contributing! 

## Code of Conduct
Be respectful and professional in all interactions.

## How to Contribute
1. Fork the repo
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Coding Standards
- Follow PSR-12 for PHP
- Use meaningful names
- Comment complex logic
- Test thoroughly
