#!/bin/bash

###############################################################################
# AI-Grading Management System (AI-GMS) - Setup Script
# 
# This script automates the installation and configuration of the AI-GMS.
# It checks requirements, sets up the database, configures environment files,
# and sets proper permissions.
#
# Usage: ./setup.sh
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$PROJECT_ROOT/.env"
CONFIG_DIR="$PROJECT_ROOT/config"
UPLOADS_DIR="$PROJECT_ROOT/uploads"
VENDOR_DIR="$PROJECT_ROOT/vendor"
DB_SCHEMA="$PROJECT_ROOT/database/schema.sql"

# Default Database Configuration
DEF_DB_HOST="localhost"
DEF_DB_NAME="ai_grading_system"
DEF_DB_USER="root"
DEF_DB_PASS=""
DEF_DB_PORT="3306"

# Default Admin Account
DEF_ADMIN_USER="admin"
DEF_ADMIN_PASS="Admin@123"
DEF_ADMIN_EMAIL="admin@example.com"

echo -e "${BLUE}"
echo "=============================================="
echo "  AI-Grading Management System (AI-GMS)      "
echo "  Setup & Installation Script                "
echo "=============================================="
echo -e "${NC}"

# Function to print status messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Step 1: Check PHP Installation
echo ""
echo "Step 1: Checking PHP installation..."
if command_exists php; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_success "PHP found: $PHP_VERSION"
    
    # Check PHP version (must be 8.2+)
    if (( $(echo "$PHP_VERSION >= 8.2" | bc -l) )); then
        print_success "PHP version is compatible (8.2+)"
    else
        print_warning "PHP version $PHP_VERSION detected. Recommended: 8.2+"
        echo "Continuing anyway, but you may encounter issues."
    fi
    
    # Check required extensions
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "curl" "mbstring" "zip")
    MISSING_EXTENSIONS=()
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -qi "^$ext$"; then
            MISSING_EXTENSIONS+=("$ext")
        fi
    done
    
    if [ ${#MISSING_EXTENSIONS[@]} -eq 0 ]; then
        print_success "All required PHP extensions are installed"
    else
        print_error "Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
        echo "Please install them using:"
        echo "  Ubuntu/Debian: sudo apt-get install php-${ext}"
        echo "  CentOS/RHEL: sudo yum install php-${ext}"
        echo "  Or check your XAMPP/WAMP control panel"
        exit 1
    fi
else
    print_error "PHP is not installed or not in PATH"
    echo "Please install PHP 8.2 or higher first."
    exit 1
fi

# Step 2: Check MySQL/MariaDB Installation
echo ""
echo "Step 2: Checking MySQL/MariaDB installation..."
if command_exists mysql; then
    MYSQL_VERSION=$(mysql --version | cut -d " " -f 3 | cut -d "," -f 1)
    print_success "MySQL/MariaDB found: $MYSQL_VERSION"
else
    print_error "MySQL/MariaDB is not installed or not in PATH"
    echo "Please install MySQL 8.0+ or MariaDB 10.3+ first."
    exit 1
fi

# Step 3: Check Composer (optional but recommended)
echo ""
echo "Step 3: Checking Composer installation..."
if command_exists composer; then
    COMPOSER_VERSION=$(composer --version | cut -d " " -f 3)
    print_success "Composer found: $COMPOSER_VERSION"
    INSTALL_COMPOSER_DEPS=true
else
    print_warning "Composer not found"
    echo "Composer is recommended for managing dependencies (PhpSpreadsheet)."
    read -p "Do you want to continue without Composer? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Please install Composer from https://getcomposer.org/"
        exit 1
    fi
    INSTALL_COMPOSER_DEPS=false
fi

# Step 4: Create .env file
echo ""
echo "Step 4: Configuring environment variables..."

if [ -f "$ENV_FILE" ]; then
    print_warning ".env file already exists"
    read -p "Do you want to overwrite it? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_info "Keeping existing .env file"
    else
        rm "$ENV_FILE"
        create_env=true
    fi
else
    create_env=true
fi

if [ "$create_env" = true ]; then
    echo "Enter your database configuration (press Enter for defaults):"
    read -p "Database Host [$DEF_DB_HOST]: " DB_HOST
    DB_HOST=${DB_HOST:-$DEF_DB_HOST}
    
    read -p "Database Port [$DEF_DB_PORT]: " DB_PORT
    DB_PORT=${DB_PORT:-$DEF_DB_PORT}
    
    read -p "Database Name [$DEF_DB_NAME]: " DB_NAME
    DB_NAME=${DB_NAME:-$DEF_DB_NAME}
    
    read -p "Database User [$DEF_DB_USER]: " DB_USER
    DB_USER=${DB_USER:-$DEF_DB_USER}
    
    read -s -p "Database Password [$DEF_DB_PASS]: " DB_PASS
    echo
    DB_PASS=${DB_PASS:-$DEF_ADMIN_PASS}
    
    echo ""
    echo "Enter API Keys (leave empty to configure later):"
    read -p "Google Gemini API Key: " GEMINI_KEY
    read -p "OpenAI API Key: " OPENAI_KEY
    read -p "Anthropic API Key: " ANTHROPIC_KEY
    
    cat > "$ENV_FILE" << EOF
# AI-Grading Management System Configuration
# Generated by setup.sh on $(date)

# Database Configuration
DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

# Application Settings
APP_URL=http://localhost/ai-grading-system
APP_DEBUG=false
APP_ENV=production

# AI API Keys (Optional - configure later if needed)
GEMINI_API_KEY=$GEMINI_KEY
OPENAI_API_KEY=$OPENAI_KEY
ANTHROPIC_API_KEY=$ANTHROPIC_KEY

# Ollama Configuration (Local)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2

# Security
SESSION_LIFETIME=120
ENCRYPTION_KEY=$(openssl rand -base64 32 2>/dev/null || echo "change-this-key-in-production")
EOF
    
    print_success ".env file created successfully"
fi

# Step 5: Create necessary directories
echo ""
echo "Step 5: Creating directory structure..."

DIRS=("$UPLOADS_DIR" "$PROJECT_ROOT/logs" "$PROJECT_ROOT/storage")

for dir in "${DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        print_success "Created: $dir"
    else
        print_info "Directory exists: $dir"
    fi
    
    # Set permissions
    chmod 755 "$dir"
    chown -R www-data:www-data "$dir" 2>/dev/null || true
done

# Protect uploads directory
cat > "$UPLOADS_DIR/.htaccess" << 'EOF'
# Prevent direct access to uploaded files
<FilesMatch "\.(php|php5|phtml)$">
    Deny from all
</FilesMatch>
Options -Indexes
EOF
print_success "Secured uploads directory"

# Step 6: Install Composer dependencies
echo ""
echo "Step 6: Installing dependencies..."

if [ "$INSTALL_COMPOSER_DEPS" = true ]; then
    if [ -f "$PROJECT_ROOT/composer.json" ]; then
        cd "$PROJECT_ROOT"
        composer install --no-interaction --optimize-autoloader
        print_success "Composer dependencies installed"
    else
        print_warning "composer.json not found, skipping dependency installation"
    fi
else
    print_info "Skipping Composer installation (not available)"
fi

# Step 7: Setup Database
echo ""
echo "Step 7: Setting up database..."

read -p "Do you want to create/setup the database now? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    # Test MySQL connection
    print_info "Testing MySQL connection..."
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" &>/dev/null; then
        print_success "MySQL connection successful"
    else
        print_error "Cannot connect to MySQL with provided credentials"
        echo "Please check your database credentials in .env file"
        exit 1
    fi
    
    # Create database if not exists
    print_info "Creating database '$DB_NAME' if not exists..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    print_success "Database ready"
    
    # Import schema
    if [ -f "$DB_SCHEMA" ]; then
        print_info "Importing database schema..."
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$DB_SCHEMA"
        print_success "Database schema imported successfully"
        
        # Create default admin user
        print_info "Creating default admin account..."
        ADMIN_HASH=$(php -r "echo password_hash('$DEF_ADMIN_PASS', PASSWORD_DEFAULT);")
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
            INSERT INTO users (username, email, password_hash, role, created_at) 
            VALUES ('$DEF_ADMIN_USER', '$DEF_ADMIN_EMAIL', '$ADMIN_HASH', 'teacher', NOW())
            ON DUPLICATE KEY UPDATE username=username;
        " 2>/dev/null || print_warning "Admin user may already exist"
        print_success "Default admin created: $DEF_ADMIN_USER / $DEF_ADMIN_PASS"
    else
        print_error "Database schema file not found: $DB_SCHEMA"
        exit 1
    fi
else
    print_info "Skipping database setup (you can run it manually later)"
fi

# Step 8: Set file permissions
echo ""
echo "Step 8: Setting file permissions..."

# Make config readable
chmod 644 "$ENV_FILE"
chmod -R 755 "$CONFIG_DIR"

# Secure sensitive files
find "$PROJECT_ROOT" -name ".env" -exec chmod 600 {} \;
find "$PROJECT_ROOT" -name "*.sql" -exec chmod 644 {} \;

print_success "File permissions configured"

# Step 9: Create Apache/Nginx configuration hints
echo ""
echo "Step 9: Web server configuration..."

cat > "$PROJECT_ROOT/.htaccess" << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/index.php [QSA,L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# Prevent access to sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF
print_success "Root .htaccess created"

# Step 10: Final summary
echo ""
echo -e "${GREEN}"
echo "=============================================="
echo "  Installation Complete!                     "
echo "=============================================="
echo -e "${NC}"

echo ""
echo "📋 Next Steps:"
echo "  1. Configure your web server:"
echo "     - Point document root to: $PROJECT_ROOT/public"
echo "     - Enable mod_rewrite (Apache) or URL rewriting (Nginx)"
echo ""
echo "  2. Access the application:"
echo "     URL: http://localhost/ai-grading-system"
echo "     (or configure your virtual host)"
echo ""
echo "  3. Login credentials:"
echo "     Username: $DEF_ADMIN_USER"
echo "     Password: $DEF_ADMIN_PASS"
echo "     ⚠️  Change this password immediately after login!"
echo ""
echo "  4. Configure AI providers:"
echo "     - Edit .env file to add your API keys"
echo "     - For Ollama: Ensure Ollama is running on localhost:11434"
echo ""
echo "  5. Optional: Install additional PHP extensions for better performance:"
echo "     - php-opcache"
echo "     - php-intl"
echo ""

echo "📁 Important Files:"
echo "  - Configuration: $ENV_FILE"
echo "  - Logs: $PROJECT_ROOT/logs/"
echo "  - Uploads: $UPLOADS_DIR"
echo "  - Database Schema: $DB_SCHEMA"
echo ""

echo "🔒 Security Reminders:"
echo "  - Change default admin password"
echo "  - Set APP_DEBUG=false in production"
echo "  - Use HTTPS in production"
echo "  - Regularly backup your database"
echo ""

print_success "Happy Grading! 🎓"
echo ""
