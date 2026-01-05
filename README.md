# Advanced College & Office Attendance Portal

A comprehensive face recognition-based attendance system built with PHP, MySQL, Python, and modern web technologies.

## 🚀 Features

- **Face Recognition**: AI-powered attendance using OpenCV and MediaPipe
- **Organization Management**: Support for multiple colleges/offices
- **Role-Based Access**: Student, Teacher, and Employee roles
- **Real-time Analytics**: Power BI-style dashboard with charts
- **Attendance History**: Search, filter, and export reports
- **Profile Management**: Editable user information
- **Secure Authentication**: CSRF protection, password hashing
- **Responsive Design**: Works on desktop, tablet, and mobile

## 📋 Prerequisites

- XAMPP (PHP 7.4+, MySQL 5.7+)
- Python 3.8+
- Modern web browser

## 🔧 Installation

### 1. Setup XAMPP

1. Download and install [XAMPP](https://www.apachefriends.org/)
2. Start Apache and MySQL from XAMPP Control Panel
3. Configure MySQL port to 3307 in `my.ini` if needed

### 2. Setup Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `attendance_portal`
3. Import SQL file: `database/attendance_portal.sql`
4. Update database config in `config/database.php` if needed

### 3. Setup PHP Application

1. Clone/copy project to XAMPP htdocs:
   ```bash
   C:\xampp\htdocs\attendance_portal\
   ```

2. Set permissions for upload directories:
   ```bash
   mkdir uploads/profiles
   chmod 755 uploads/profiles
   ```

3. Update `config/database.php` with your settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3307');
   define('DB_NAME', 'attendance_portal');
   ```

### 4. Setup Python Environment

1. Install Python dependencies:
   ```bash
   pip install opencv-python mediapipe mysql-connector-python requests numpy
   ```

2. Update database config in `python/face_recognition_system.py`:
   ```python
   db_config = {
       'host': 'localhost',
       'port': 3307,
       'user': 'root',
       'password': '',
       'database': 'attendance_portal'
   }
   ```

## 🎯 Usage

### Initial Setup

1. Access the application: `http://localhost/attendance_portal`
2. Register your organization at: `http://localhost/attendance_portal/register_organization.php`
3. Save the generated organization code
4. Users can now register using the org code

### User Registration

1. Go to Registration page
2. Enter organization code
3. Fill in personal details
4. Select role (Student/Teacher/Employee)
5. Create account

### Face Registration

1. Run Python script:
   ```bash
   python python/face_recognition_system.py
   ```
2. Select option 1: "Register new face"
3. Enter your user ID
4. Position face in camera frame
5. System will capture and register face

### Mark Attendance

#### Method 1: Python Face Recognition
```bash
python python/face_recognition_system.py
```
- Select option 2: "Start attendance capture"
- Enter CSRF token from your session
- Press SPACE to capture attendance
- Press Q to quit

#### Method 2: Web Interface
- Login to dashboard
- Navigate to "Mark Attendance"
- System will use registered face data

## 📁 Project Structure

```
attendance_portal/
├── api/
│   ├── register_organization.php
│   ├── register_user.php
│   ├── login.php
│   ├── mark_attendance.php
│   ├── dashboard.php
│   ├── attendance_history.php
│   └── update_profile.php
├── config/
│   └── database.php
├── css/
│   └── style.css
├── includes/
│   └── utils.php
├── python/
│   └── face_recognition_system.py
├── uploads/
│   └── profiles/
├── index.html
├── login.php
├── register.php
├── register_organization.php
├── dashboard.php
├── history.php
├── profile.php
├── logout.php
└── README.md
```

## 🎨 Color Scheme

- Primary: `#DE354C`
- Primary Dark: `#932432`
- Secondary: `#3C1874`
- Dark: `#283747`
- Light: `#F3F3F3`

## 🔒 Security Features

1. **CSRF Protection**: All forms use CSRF tokens
2. **Password Hashing**: BCrypt with cost factor 12
3. **SQL Injection Prevention**: Prepared statements
4. **XSS Protection**: Input sanitization
5. **Session Management**: Secure session handling
6. **Input Validation**: Server-side validation

## 📊 Database Schema

### Tables

1. **organizations**: College/office information
2. **users**: User accounts and profiles
3. **attendance**: Daily attendance records

### Views

1. **attendance_analytics**: User-wise statistics
2. **monthly_attendance**: Month-wise attendance
3. **gender_statistics**: Gender distribution

## 🔍 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/register_organization.php` | POST | Register new organization |
| `/api/register_user.php` | POST | Register new user |
| `/api/login.php` | POST | User login |
| `/api/mark_attendance.php` | POST | Mark attendance |
| `/api/dashboard.php` | GET | Get dashboard data |
| `/api/attendance_history.php` | GET | Get attendance history |
| `/api/update_profile.php` | POST | Update user profile |

## 🐛 Troubleshooting

### Database Connection Issues
- Verify MySQL is running in XAMPP
- Check port number (default: 3307)
- Confirm database name and credentials

### Face Recognition Not Working
- Ensure webcam permissions are granted
- Check Python dependencies are installed
- Verify OpenCV can access camera

### Login Issues
- Clear browser cookies/cache
- Check if user is registered
- Verify organization code

## 📈 Future Enhancements

- Mobile app integration
- Email notifications
- Automated reports generation
- Multi-language support
- Advanced analytics with ML
- QR code backup authentication
- Geolocation verification
- Leave management system

## 👨‍💻 Development

### Adding New Features

1. Create API endpoint in `api/` directory
2. Add frontend page in root directory
3. Update navigation in sidebar
4. Test thoroughly

### Database Migrations

1. Update SQL schema
2. Run migration script
3. Update models/classes

## 📝 License

This project is created for educational purposes.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Consult PHP/Python documentation

## 🎓 Credits

Built with:
- PHP & MySQL
- Python, OpenCV, MediaPipe
- Chart.js for analytics
- Modern CSS with responsive design

---

**Version**: 1.0.0  
**Last Updated**: January 2026
