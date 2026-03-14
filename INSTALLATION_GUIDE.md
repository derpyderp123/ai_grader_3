# 🚀 AI-Grading Management System - Installation Guide

## Quick Start (PHP-Based Installation)

The easiest way to install the AI-Grading Management System is using our **web-based installer**.

### Prerequisites

Before starting, ensure you have:
- ✅ PHP 8.2 or higher
- ✅ MySQL 8.0 or higher (or MariaDB 10.3+)
- ✅ Web server (Apache/Nginx) or XAMPP/WAMP/MAMP
- ✅ Required PHP extensions: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `json`, `openssl`

---

## 📋 Method 1: Web-Based Installer (Recommended)

### Step 1: Upload Files

1. Copy all project files to your web server directory:
   - **Linux/Mac**: `/var/www/html/ai-grading`
   - **Windows (XAMPP)**: `C:\xampp\htdocs\ai-grading`
   - **Windows (WAMP)**: `C:\wamp64\www\ai-grading`

### Step 2: Access the Installer

Open your browser and navigate to:
```
http://localhost/ai-grading/install.php
```

Or if using a domain:
```
https://your-domain.com/install.php
```

### Step 3: Follow the Wizard

The installer will guide you through 4 simple steps:

#### 🔍 Step 1: Requirements Check
The system automatically checks:
- PHP version (must be 8.2+)
- Required PHP extensions
- Directory permissions

✅ All checks must pass before continuing.

#### 💾 Step 2: Database Configuration

Enter your MySQL database details:

| Field | Default | Description |
|-------|---------|-------------|
| Database Host | `localhost` | MySQL server address |
| Database Port | `3306` | MySQL port |
| Database Name | `ai_grading_system` | Will be created automatically |
| Database Username | `root` | MySQL username |
| Database Password | *(empty)* | MySQL password |

**Note:** The installer will create the database and all tables automatically.

#### 👤 Step 3: Create Admin Account

Set up your administrator account:

- **Username**: Choose a login name (e.g., `admin`)
- **Email**: Your email address
- **Password**: Minimum 6 characters

#### ✅ Step 4: Installation Complete!

The installer will:
- Create all database tables
- Set up your admin account
- Generate `config/.env` configuration file
- Create secure `uploads/` directory
- Set up security files

You'll see a success message with your login credentials.

### Step 4: Post-Installation

1. **Delete the installer** (for security):
   ```bash
   rm install.php
   ```
   Or rename it:
   ```bash
   mv install.php install.php.bak
   ```

2. **Configure AI API Keys** (optional):
   Edit `config/.env` and add your API keys:
   ```env
   GEMINI_API_KEY=your_gemini_key_here
   OPENAI_API_KEY=your_openai_key_here
   ANTHROPIC_API_KEY=your_anthropic_key_here
   ```

3. **Login to the system**:
   Navigate to: `http://localhost/ai-grading/public/index.php`

---

## 🐳 Method 2: Docker Installation

If you prefer Docker:

```bash
# Clone or navigate to the project
cd /workspace

# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Access at http://localhost:8080
```

**Default credentials:**
- Username: `admin`
- Password: `Admin@123`

---

## 🔧 Method 3: Manual Installation

For advanced users who prefer manual setup:

### Step 1: Install Dependencies

```bash
cd /workspace
composer install
```

### Step 2: Configure Environment

```bash
cp .env.example config/.env
```

Edit `config/.env` with your settings:

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ai_grading_system
DB_USER=root
DB_PASS=your_password

# Application
APP_NAME="AI-Grading Management System"
APP_URL=http://localhost
DEBUG_MODE=true

# AI Providers (optional)
GEMINI_API_KEY=your_key
OPENAI_API_KEY=your_key
```

### Step 3: Create Database

```bash
mysql -u root -p -e "CREATE DATABASE ai_grading_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Step 4: Import Schema

```bash
mysql -u root -p ai_grading_system < database/schema.sql
```

### Step 5: Create Admin User

```sql
INSERT INTO users (username, email, password_hash, role, created_at) 
VALUES ('admin', 'admin@example.com', '$2y$10$...', 'teacher', NOW());
```

Generate password hash with PHP:
```php
<?php echo password_hash('YourPassword123', PASSWORD_DEFAULT); ?>
```

### Step 6: Set Permissions

```bash
chmod -R 755 /workspace
chmod -R 777 /workspace/uploads
chmod -R 777 /workspace/config
```

### Step 7: Configure Web Server

#### Apache (.htaccess already included)

Ensure `mod_rewrite` is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx Configuration

Add to your Nginx config:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /workspace/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

---

## ✅ Verification Checklist

After installation, verify:

- [ ] Can access login page at `/public/index.php`
- [ ] Can login with admin credentials
- [ ] Database tables exist (check with `SHOW TABLES;`)
- [ ] `config/.env` file exists and has correct values
- [ ] `uploads/` directory is writable
- [ ] No PHP errors in browser console or error logs

---

## 🐛 Troubleshooting

### Common Issues

#### 1. "PDO Extension Not Found"

**Solution:** Install PDO extension:
```bash
# Ubuntu/Debian
sudo apt-get install php8.2-mysql

# CentOS/RHEL
sudo yum install php-pdo

# Windows (XAMPP)
# Uncomment in php.ini: extension=pdo_mysql
```

#### 2. "Permission Denied" Errors

**Solution:** Fix directory permissions:
```bash
chmod -R 777 /workspace/uploads
chmod -R 777 /workspace/config
chown -R www-data:www-data /workspace
```

#### 3. "Database Connection Failed"

**Solution:** 
- Verify MySQL is running: `sudo systemctl status mysql`
- Check credentials in `config/.env`
- Ensure database user has proper privileges

#### 4. "Config Directory Not Writable"

**Solution:**
```bash
chmod 777 /workspace/config
```

#### 5. Blank White Page

**Solution:** Enable error reporting temporarily:
```php
// Add to top of index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Check PHP error log:
```bash
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php8.2-fpm.log
```

#### 6. Composer Installation Fails

**Solution:** Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Then run:
```bash
composer install --no-dev
```

---

## 🔒 Security Recommendations

After installation:

1. **Delete installer:**
   ```bash
   rm install.php
   ```

2. **Disable debug mode in production:**
   ```env
   DEBUG_MODE=false
   ```

3. **Generate encryption key:**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   ```
   Add to `.env`:
   ```env
   ENCRYPTION_KEY=your_generated_key
   ```

4. **Set secure session cookies:**
   ```env
   SESSION_SECURE=true
   ```

5. **Regular backups:**
   ```bash
   mysqldump -u root -p ai_grading_system > backup_$(date +%Y%m%d).sql
   ```

---

## 📊 Database Schema

The installer creates these tables:

| Table | Description |
|-------|-------------|
| `users` | User accounts (students & teachers) |
| `assignments` | Assignment definitions with rubrics |
| `submissions` | Student code submissions |
| `grades` | AI-generated grades and feedback |
| `system_logs` | Audit trail and error logging |

---

## 🎯 Next Steps After Installation

1. **Login** with your admin account
2. **Create your first assignment**:
   - Go to Teacher Dashboard
   - Click "Create Assignment"
   - Define title, description, and rubric
3. **Invite students** to register
4. **Configure AI providers** (optional but recommended)
5. **Test the grading system** with a sample submission

---

## 📞 Support

If you encounter issues:

1. Check the [README.md](README.md) for detailed documentation
2. Review system logs in `logs/` directory
3. Verify all requirements are met
4. Try the Docker installation method as alternative

---

## 📝 Upgrade Instructions

To upgrade from a previous version:

```bash
# Backup first
mysqldump -u root -p ai_grading_system > backup.sql

# Pull latest changes
git pull origin main

# Run migrations (if any)
# Check UPGRADE.md for specific version notes

# Clear cache
rm -rf storage/framework/cache/*
```

---

**Version:** 1.0  
**Last Updated:** 2024  
**License:** MIT  

Made with ❤️ for education
