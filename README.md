# Enterprise Analytics and Result Systems (EARS)

A modern, secure admin dashboard for enterprise analytics and accounting management built with PHP, MySQL, and Bootstrap.

## Features

- 🔐 **Secure Authentication**: API-based login system with session management
- 📊 **Modern Dashboard**: Real-time statistics and analytics
- 🎨 **Responsive Design**: Bootstrap 5 with custom styling
- 📱 **Mobile Friendly**: Responsive layout for all devices
- 🔄 **API-Based**: All requests handled through RESTful APIs
- 🛡️ **Security**: SQL injection protection, XSS prevention, CSRF protection
- 📁 **File Maintenance**: Complete CRUD operations for master data
- 💰 **Transaction Management**: Cash receipts, disbursements, journal adjustments
- ⚙️ **Parameter Management**: System configuration and settings

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for URL rewriting)

## Installation

### 1. Clone or Download the Project

```bash
git clone <repository-url>
cd EARS
```

### 2. Database Setup

1. Create a MySQL database named `ears_db`
2. Import the database schema:

```bash
mysql -u root -p ears_db < database/schema.sql
```

### 3. Configuration

1. Update database connection settings in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'ears_db';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

2. Update application settings in `config/config.php`:
   ```php
   define('APP_URL', 'http://your-domain.com/ears');
   ```

### 4. Web Server Configuration

#### Apache (.htaccess already included)
- Ensure mod_rewrite is enabled
- The .htaccess file will handle URL rewriting automatically

#### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 5. File Permissions

Ensure the following directories are writable:
```bash
chmod 755 uploads/
chmod 644 .htaccess
```

## Default Login Credentials

- **Username**: admin
- **Password**: admin123

⚠️ **Important**: Change the default password after first login!

## System Structure

```
EARS/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── config/
│   ├── database.php
│   └── config.php
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── FileMaintenanceController.php
│   ├── ParametersController.php
│   └── TransactionController.php
├── core/
│   ├── Auth.php
│   ├── Controller.php
│   ├── Model.php
│   └── Router.php
├── database/
│   └── schema.sql
├── views/
│   ├── auth/
│   │   └── login.php
│   ├── dashboard/
│   │   └── index.php
│   ├── layouts/
│   │   └── main.php
│   └── partials/
│       ├── footer.php
│       ├── sidebar.php
│       └── topnav.php
├── .htaccess
├── index.php
└── README.md
```

## Features Overview

### Dashboard
- Real-time statistics
- Recent transactions
- Quick action buttons
- System status monitoring

### Parameters
- **Accounting Parameters**: System configuration settings
- Centralized parameter management

### File Maintenance
- **Account Title Group**: Manage account groupings
- **COA Account Type**: Chart of accounts classification
- **Chart of Accounts**: Complete account management
- **Subsidiary Account**: Supplier management

### Transaction Entries
- **Cash Receipt**: Record incoming cash
- **Disbursement**: Record outgoing payments
- **Journal Adjustment**: Manual journal entries

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics

### Parameters
- `POST /api/parameters/save` - Save parameters

### File Maintenance
- `POST /api/file-maintenance/save` - Save file maintenance data

## Security Features

- **Password Hashing**: Bcrypt with salt
- **Session Management**: Secure session handling
- **SQL Injection Protection**: Prepared statements
- **XSS Prevention**: Input sanitization
- **CSRF Protection**: Token-based protection
- **Access Control**: Role-based permissions

## Customization

### Adding New Modules

1. Create controller in `controllers/` directory
2. Add routes in `index.php`
3. Create views in `views/` directory
4. Update sidebar navigation

### Styling

- Main CSS: `assets/css/style.css`
- Bootstrap 5 framework
- Custom CSS variables for theming

### JavaScript

- Main JS: `assets/js/app.js`
- jQuery for DOM manipulation
- Bootstrap JS for components

## Troubleshooting

### Common Issues

1. **404 Errors**: Ensure mod_rewrite is enabled
2. **Database Connection**: Check credentials in `config/database.php`
3. **Permission Denied**: Set proper file permissions
4. **Session Issues**: Check PHP session configuration

### Debug Mode

Enable debug mode in `config/config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support

For technical support or feature requests, please contact the development team.

## License

This project is proprietary software. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: <?= date('Y-m-d') ?> 