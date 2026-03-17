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
- **Role-Based Access**: Secure permissions for Admin, Manager, Technician, and User roles
- **Modern UI/UX**: Responsive design with drag-and-drop Kanban board
- **User-Friendly**: Read-only Kanban view for regular users to track their requests

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
- ✅ **User-created request tracking** - Users can view all their submitted requests
- ✅ **Read-only Kanban board** for regular users to monitor request status

### 👥 Team Management
- ✅ Create and manage maintenance teams
- ✅ Add/remove team members
- ✅ Team-based request assignment
- ✅ Team performance tracking
- ✅ **Team photos and social links** on landing page

### 📊 Visual Dashboards
- ✅ **Kanban Board**: Drag-and-drop request management (Manager/Technician)
- ✅ **Read-Only Kanban**: Non-draggable view for regular users
- ✅ **Calendar View**: Schedule and visualize preventive maintenance
- ✅ **Analytics Dashboard**: Real-time statistics for all roles including users
- ✅ **Reports**: Interactive charts with Chart.js
  - Requests per Team (Bar Chart)
  - Requests per Category (Pie Chart)
  - Monthly Trends (Line Chart)
  - Priority Distribution
  - Equipment Status Overview

### 👤 User Management (Admin Only)
- ✅ User CRUD operations
- ✅ Role assignment (Admin, Manager, Technician, User)
- ✅ **Default role set to "User"** during registration
- ✅ Activate/Deactivate users
- ✅ Track user activity and assigned requests

### 🔐 Security & Access Control
- ✅ **4-tier role-based permissions** system (Admin/Manager/Technician/User)
- ✅ CSRF protection
- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection
- ✅ **Role-specific UI restrictions** (CSS fixes for non-draggable cards)

### 🎨 User Experience
- ✅ Responsive design (mobile, tablet, desktop)
- ✅ Modern, clean interface
- ✅ Real-time notifications
- ✅ Print-friendly views
- ✅ Intuitive navigation
- ✅ Visual status indicators
- ✅ **Role-specific dashboard statistics**
- ✅ **Enhanced landing page** with team photos and social links

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
  

### Step 1: Clone the Repository

git clone https://github.com/Rsmiyani/ODOO-HACKATHON.git
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

// Role Permissions (Updated with User role)
$role_permissions = [
'admin' => ['all'],
'manager' => ['equipment', 'requests', 'teams', 'reports', 'calendar'],
'technician' => ['requests', 'kanban'],
'user' => ['create_request', 'view_requests', 'dashboard']
];

text

### Step 4: Set Permissions

chmod -R 755 gearguard/
chmod -R 777 gearguard/uploads/ # If you add file upload functionality

text

### Step 5: Access the Application

1. Start your web server (XAMPP/LAMP/WAMP)
2. Navigate to: `http://localhost/gearguard`
3. Register a new account (default role: **User**)
4. Or use default admin credentials:
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
DEFAULT_USER_ROLE=user

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
4. **Permissions**: Control access levels for all 4 roles

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

### For Users (Regular Employees)

1. **Create Requests**: Report equipment breakdowns or issues
2. **Track Requests**: View all their submitted requests on "All Requests" page
3. **Monitor Status**: Check request progress on read-only Kanban board
4. **Dashboard Access**: View personal statistics and recent activity
5. **Equipment View**: See equipment assigned to them

---

## 📁 Project Structure

gearguard/
├── 📄 index.php # Login page
├── 📄 register.php # User registration (default role: user)
├── 📄 logout.php # Logout handler
├── 📄 README.md # This file
├── 📄 LICENSE # MIT License
│
├── 📂 php/
│ ├── 📄 config.php # Database & role permissions config
│ └── 📂 api/
│ ├── 📄 create_user.php
│ ├── 📄 update_user.php
│ ├── 📄 delete_user.php
│ ├── 📄 toggle_user_status.php
│ └── 📄 update_request_stage.php
│
├── 📂 pages/
│ ├── 📄 dashboard.php # Role-specific dashboard
│ ├── 📄 equipment.php # Equipment list (with grouping)
│ ├── 📄 add-equipment.php # Add new equipment
│ ├── 📄 edit-equipment.php # Edit equipment
│ ├── 📄 view-equipment.php # Equipment details
│ ├── 📄 kanban.php # Kanban board (draggable for managers/technicians)
│ ├── 📄 calendar.php # Calendar view
│ ├── 📄 requests.php # All requests (user: own requests only)
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
│ ├── 📄 kanban.css # Kanban board styles (non-draggable for users)
│ ├── 📄 form.css # Form styles
│ ├── 📄 view-equipment.css # Equipment detail styles
│ ├── 📄 view-request.css # Request detail styles
│ ├── 📄 reports.css # Reports page styles
│ └── 📄 users.css # User management styles
│
├── 📂 js/
│ ├── 📄 dashboard.js # Role-specific dashboard logic
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
└── 📂 images/ # Screenshots, logos, team photos

text

---

## 🗄️ Database Schema

### Main Tables

#### **users**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | Unique user identifier |
| name | VARCHAR | Full name |
| email (unique) | VARCHAR | Login email |
| password (hashed) | VARCHAR | Bcrypt hashed password |
| role | ENUM | admin/manager/technician/**user** |
| is_active | BOOLEAN | Account status |
| created_at | DATETIME | Registration timestamp |

#### **equipment**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | Equipment ID |
| equipment_name | VARCHAR | Asset name |
| serial_number (unique) | VARCHAR | Unique serial |
| category | VARCHAR | Equipment type |
| department | VARCHAR | Department assignment |
| location | VARCHAR | Physical location |
| assigned_to_employee | VARCHAR | Employee name |
| maintenance_team_id (FK) | INT | Assigned team |
| default_technician_id (FK) | INT | Default tech |
| purchase_date | DATE | Purchase date |
| warranty_expiry | DATE | Warranty end date |
| status | ENUM | Active/Under Maintenance/Scrapped |
| created_at | DATETIME | Created timestamp |

#### **maintenance_teams**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | Team ID |
| team_name | VARCHAR | Team name |
| description | TEXT | Team description |
| photo_url | VARCHAR | Team photo path |
| social_links | JSON | Social media links |
| created_at | DATETIME | Created timestamp |

#### **team_members**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | Member ID |
| team_id (FK) | INT | Team reference |
| user_id (FK) | INT | User reference |
| created_at | DATETIME | Added timestamp |

#### **maintenance_requests**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | Request ID |
| equipment_id (FK) | INT | Equipment reference |
| created_by (FK) | INT | Creator user ID |
| assigned_to (FK) | INT | Assigned technician |
| subject | VARCHAR | Request title |
| description | TEXT | Detailed description |
| request_type | ENUM | corrective/preventive |
| priority | ENUM | low/medium/high/urgent |
| stage | ENUM | new/in_progress/repaired/scrap |
| scheduled_date | DATE | Scheduled date |
| duration_hours | DECIMAL | Time spent |
| completed_at | DATETIME | Completion timestamp |
| created_at | DATETIME | Created timestamp |
| updated_at | DATETIME | Last update timestamp |

#### **request_history**
| Field | Type | Description |
|-------|------|-------------|
| id (PK) | INT | History ID |
| request_id (FK) | INT | Request reference |
| action | VARCHAR | Action description |
| user_id (FK) | INT | User who performed action |
| created_at | DATETIME | Action timestamp |

---

## 👥 User Roles & Permissions

### 🔴 Admin
**Full system control and administration**

- ✅ All Manager permissions
- ✅ User management (create, edit, delete users)
- ✅ Assign roles (Admin, Manager, Technician, User)
- ✅ System configuration
- ✅ Database management
- ✅ Access to all modules and reports

### 🟡 Manager
**Operational management and oversight**

- ✅ View and manage all equipment
- ✅ Create and assign maintenance requests
- ✅ Manage teams and team members
- ✅ Schedule preventive maintenance
- ✅ Access calendar and reports
- ✅ Monitor all maintenance activities
- ✅ **Drag-and-drop Kanban board** access
- ✅ Update team photos and social links
- ❌ Cannot manage users or system settings

### 🟢 Technician
**Task execution and field work**

- ✅ View assigned requests
- ✅ Update status of assigned requests
- ✅ Record work duration
- ✅ Create new corrective maintenance requests
- ✅ **Drag-and-drop Kanban board** for assigned tasks
- ✅ Access calendar for scheduled work
- ❌ Cannot manage equipment, teams, or view full reports
- ❌ Cannot schedule preventive maintenance
- ❌ Limited to assigned work only

### 🔵 User (Regular Employee)
**Request creation and tracking**

- ✅ **Create corrective maintenance requests** (report breakdowns)
- ✅ **View all their own submitted requests** on "All Requests" page
- ✅ **Read-only Kanban board** access (monitor request status without dragging)
- ✅ View equipment assigned to them
- ✅ **Dashboard with personal statistics** (total requests, pending, resolved)
- ✅ Track request progress and history
- ✅ View request details
- ❌ **Cannot drag/drop cards** on Kanban board (CSS disabled)
- ❌ Cannot assign requests to technicians
- ❌ Cannot schedule preventive maintenance
- ❌ Cannot manage equipment, teams, or users
- ❌ Cannot access full reports or analytics

---

## 📸 Screenshots

### Dashboard (Role-Specific)
![Dashboard](assets/images/dashboard.png)
*Main dashboard with role-based statistics and quick actions*

### Kanban Board (Draggable - Manager/Technician)
![Kanban Board](assets/images/kanban-draggable.png)
*Drag-and-drop interface for managing maintenance requests*

### Kanban Board (Read-Only - User)
![Read-Only Kanban](assets/images/kanban-readonly.png)
*Non-draggable Kanban view for regular users to track their requests*

### All Requests (User View)
![User Requests](assets/images/user-requests.png)
*Users can view and track all their submitted requests*

### Equipment Management
![Equipment List](assets/images/equipment.png)
*Equipment list with grouping and filtering options*

### Calendar View
![Calendar](assets/images/calendar.png)
*Schedule and visualize preventive maintenance*

### Reports & Analytics
![Reports](assets/images/reports.png)
*Interactive charts and analytics dashboard*

### Landing Page with Team
![Landing Page](assets/images/landing-team.png)
*Enhanced landing page with team photos and social links*

---

## 📡 API Documentation

### Authentication Required
All API endpoints require authentication via PHP session with role-based access control.

### Available Endpoints

#### User Management (Admin Only)
POST /php/api/create_user.php
POST /php/api/update_user.php
POST /php/api/delete_user.php
POST /php/api/toggle_user_status.php

text

#### Request Management (All Roles)
POST /php/api/update_request_stage.php # Manager/Technician only
GET /php/api/get_requests.php # All roles (filtered by permissions)
POST /php/api/assign_request.php # Manager only
POST /php/api/create_request.php # All roles

text

### Example Request

fetch('../php/api/update_request_stage.php', {
method: 'POST',
headers: {
'Content-Type': 'application/json'
},
body: JSON.stringify({
request_id: 123,
stage: 'in_progress',
user_role: 'manager' // Role check for permissions
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
git fork https://github.com/Rsmiyani/ODOO-HACKATHON.git

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
- Respect role-based permissions in new features
- Update documentation if needed

### Bug Reports

Found a bug? Please open an issue with:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Your user role when bug occurred
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

### Version 1.1 (✅ Completed)
- [x] **User role implementation** with read-only Kanban
- [x] **Dashboard statistics for all 4 roles**
- [x] **All Requests page** showing user-created requests
- [x] **CSS fixes for non-draggable cards** (user role)
- [x] **Team photos and social links** on landing page
- [x] **Registration with default user role**

### Version 1.2 (In Progress)
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

**Project Maintainer**: Rudra Miyani

- 📧 Email: your.email@example.com
- 🐙 GitHub: [@Rsmiyani](https://github.com/Rsmiyani)
- 💼 LinkedIn: [Rudra Miyani](https://www.linkedin.com/in/rudramiyani2024/)
- 🌐 Website: yourwebsite.com

**Project Link**: [https://github.com/Rsmiyani/ODOO-HACKATHON](https://github.com/Rsmiyani/ODOO-HACKATHON)

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

[![GitHub stars](https://img.shields.io/github/stars/Rsmiyani/ODOO-HACKATHON?style=social)](https://github.com/Rsmiyani/ODOO-HACKATHON/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/Rsmiyani/ODOO-HACKATHON?style=social)](https://github.com/Rsmiyani/ODOO-HACKATHON/network/members)

---

<div align="center">

**Made with ❤️ for efficient maintenance management**

[⬆ Back to Top](#️-gearguard---the-ultimate-maintenance-tracker)

</div>
