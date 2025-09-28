# Event Management System

A comprehensive event and food stall management system designed for FAST University. This web application allows users to manage events, registrations, food stalls, and generate certificates for attendees.

## 🚀 Features

### Event Management
- **Event Creation & Management**: Create, edit, publish, and manage events
- **Event Categories**: Organize events into categories (Hackathon, Concert, Sports, Workshop, Seminar)
- **Registration System**: Users can register for events with approval workflow
- **Event Details**: Comprehensive event information including dates, location, and capacity
- **Registration Management**: Track and manage event registrations

### Food Stall Management
- **Stall Creation**: Create and manage food stalls for events
- **Booking System**: Users can book food stalls with approval workflow
- **Stall Approval**: Admin/Organizer approval system for stalls
- **Booking Management**: Track and manage stall bookings

### User Management
- **Role-based Access Control**: Four user roles (Admin, Organizer, Student, Outsider)
- **User Registration**: Secure user registration with role assignment
- **Profile Management**: User profile management system
- **Authentication**: Secure login/logout system

### Certificate System
- **Certificate Generation**: Automatic certificate generation for attended events
- **Certificate Download**: Users can download their certificates
- **Certificate Management**: View and manage issued certificates

### Admin Features
- **User Management**: Manage all users in the system
- **Event Oversight**: Admin can manage all events regardless of organizer
- **Stall Oversight**: Admin can manage all food stalls
- **Category Management**: Manage event categories
- **Registration Oversight**: Manage all event registrations

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **PDF Generation**: FPDF library for certificate generation
- **Security**: Password hashing, SQL injection prevention, XSS protection

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PHP extensions: mysqli, gd, mbstring

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Nascon-management-system
   ```

2. **Database Setup**
   - Create a MySQL database named `fast_event_management`
   - Import the database schema:
   ```bash
   mysql -u root -p fast_event_management < schema.sql
   ```

3. **Configuration**
   - Update database configuration in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'fast_event_management');
   ```

4. **Web Server Setup**
   - Place the project files in your web server's document root
   - Ensure proper permissions for the `images/` directory
   - Configure your web server to serve PHP files

5. **Default Login Credentials**
   - **Admin**: `admin@fast.edu.pk` / `admin123`
   - **Organizer**: `organizer@fast.edu.pk` / `organizer123`
   - **Student**: `student@fast.edu.pk` / `student123`
   - **Outsider**: `outsider@fast.edu.pk` / `outsider123`

## 📁 Project Structure

```
Nascon-management-system/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── functions.php         # Utility functions
│   ├── header.php           # Common header
│   └── footer.php           # Common footer
├── fpdf/
│   └── fpdf.php            # PDF generation library
├── images/
│   └── fast.jpg            # Application images
├── create_event.php        # Event creation form
├── create_stall.php        # Stall creation form
├── edit_event.php          # Event editing form
├── edit_stall.php          # Stall editing form
├── event_details.php       # Event details page
├── events.php              # Events listing page
├── generate_certificate.php # Certificate generation
├── index.php               # Homepage
├── login.php               # User login
├── logout.php              # User logout
├── manage_categories.php   # Category management
├── manage_events.php       # Event management
├── manage_registrations.php # Registration management
├── manage_stall_bookings.php # Stall booking management
├── manage_stalls.php       # Stall management
├── manage_users.php        # User management
├── my_certificates.php     # User certificates
├── my_events.php           # User's events
├── my_stalls.php           # User's stalls
├── profile.php             # User profile
├── register.php            # User registration
├── stall_details.php       # Stall details page
├── stalls.php              # Stalls listing page
└── schema.sql              # Database schema
```

## 👥 User Roles

### Admin (Role ID: 1)
- Full system access
- Manage all users, events, and stalls
- Approve/reject registrations and bookings
- Manage event categories
- View system-wide statistics

### Organizer (Role ID: 2)
- Create and manage events
- Create and manage food stalls
- Manage event registrations
- Approve/reject stall bookings
- Generate certificates

### Student (Role ID: 3)
- Register for events
- Book food stalls
- View personal dashboard
- Download certificates
- Manage profile

### Outsider (Role ID: 4)
- Register for events
- Book food stalls
- View personal dashboard
- Download certificates
- Manage profile

## 🔐 Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: Input sanitization and output escaping
- **Session Management**: Secure session handling
- **Role-based Access Control**: Proper authorization checks

## 🚀 Usage

### For Administrators
1. Login with admin credentials
2. Navigate to "Manage Users" to oversee all users
3. Use "Manage Events" to view and manage all events
4. Use "Manage Stalls" to approve and manage food stalls
5. Access "Manage Categories" to manage event categories

### For Organizers
1. Login with organizer credentials
2. Create events using "Create Event"
3. Manage your events in "Manage Events"
4. Create food stalls using "Create Stall"
5. Manage registrations and bookings

### For Students/Outsiders
1. Register for an account or login
2. Browse and register for events
3. Book food stalls
4. View your dashboard with upcoming events
5. Download certificates after attending events

## 🔧 Configuration Options

### Database Configuration
Edit `config/database.php` to modify database connection settings.

### Event Categories
Default categories can be modified in the database or through the admin interface.

### User Roles
Roles are defined in the `roles` table and can be extended as needed.

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database exists and is accessible

2. **Permission Denied**
   - Check file permissions on the project directory
   - Ensure web server has read access to all files
   - Verify `images/` directory is writable

3. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable
   - Verify session cookies are enabled

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions, please contact the development team or create an issue in the repository.

## 🔄 Version History

- **v1.0.0**: Initial release with basic event and stall management
- **v1.1.0**: Added certificate generation system
- **v1.2.0**: Enhanced user management and role-based access control

---

**Note**: This system is designed specifically for University's NASCON event management needs. Customization may be required for other institutions.
