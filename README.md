# AI-Grading Management System - README

## 🎓 AI-Grading Management System (AI-GMS)

A comprehensive web-based platform where students submit code, AI models grade submissions based on rubrics, and teachers receive detailed Excel reports. Built with ISO 9126 quality standards in mind.

---

## ✨ Features

- **Multi-AI Grading Engine**: Automatic fallback chain (Ollama → Gemini → OpenAI → Anthropic)
- **Live Status Updates**: Real-time grading progress via Server-Sent Events (SSE)
- **Role-Based Access**: Separate dashboards for Students and Teachers
- **Excel/CSV Reports**: Comprehensive grading reports with detailed feedback
- **Secure File Uploads**: Protected storage with malware prevention
- **ISO 9126 Compliant**: Built with functionality, reliability, usability, efficiency, maintainability, and portability in mind

---

## 🚀 Quick Start

### Option 1: Docker (Recommended)

**Prerequisites:**
- Docker & Docker Compose installed
- Git (optional, if cloning repository)

**Steps:**

```bash
# 1. Navigate to project directory
cd /path/to/ai-grading-system

# 2. Copy environment file
cp .env.example .env

# 3. Edit .env with your API keys (optional)
nano .env

# 4. Start all services
docker-compose up -d

# 5. Access the application
# Web: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

**Default Credentials:**
- Username: `admin`
- Password: `Admin@123`

⚠️ **Change the default password immediately after login!**

---

### Option 2: Manual Installation

**Prerequisites:**
- PHP 8.2+ with extensions: pdo, pdo_mysql, json, curl, mbstring, zip
- MySQL 8.0+ or MariaDB 10.3+
- Composer (recommended)
- Apache/Nginx with mod_rewrite enabled

**Steps:**

```bash
# 1. Make setup script executable
chmod +x setup.sh

# 2. Run the automated setup
./setup.sh

# 3. Follow the prompts to configure:
#    - Database connection
#    - API keys (optional)
#    - Admin account

# 4. Configure your web server
#    Point document root to: /path/to/ai-grading-system/public
```

For manual database setup:

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ai_grading_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p ai_grading_system < database/schema.sql

# Create admin user (password: Admin@123)
mysql -u root -p ai_grading_system -e "INSERT INTO users (username, email, password_hash, role, created_at) VALUES ('admin', 'admin@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', NOW());"
```

---

## 📁 Project Structure

```
/ai-grading-system
├── config/             # Configuration files (DB, API keys)
├── controllers/        # Application logic
├── models/            # Database models
├── services/          # AI grading services
├── views/             # HTML/PHP templates
├── public/            # Web root (CSS, JS, index.php)
├── uploads/           # Student submissions (secured)
├── logs/              # Application logs
├── database/          # SQL schemas
├── docker-compose.yml # Docker configuration
├── Dockerfile         # PHP/Apache container
├── setup.sh           # Installation script
└── .env.example       # Environment template
```

---

## 🔧 Configuration

### Environment Variables (.env)

```env
# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ai_grading_system
DB_USER=root
DB_PASS=your_password

# AI API Keys (Optional - at least one recommended)
GEMINI_API_KEY=your_google_gemini_key
OPENAI_API_KEY=your_openai_key
ANTHROPIC_API_KEY=your_anthropic_key

# Ollama (Local AI - No API key needed)
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2

# Application
APP_URL=http://localhost:8080
APP_DEBUG=false
SESSION_LIFETIME=120
```

### Setting Up AI Providers

**1. Ollama (Local - Recommended for Development)**

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Pull a model
ollama pull llama3.2

# Start Ollama service
ollama serve
```

**2. Google Gemini**

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create an API key
3. Add to `.env`: `GEMINI_API_KEY=your_key_here`

**3. OpenAI**

1. Visit [OpenAI Platform](https://platform.openai.com/api-keys)
2. Create an API key
3. Add to `.env`: `OPENAI_API_KEY=your_key_here`

**4. Anthropic Claude**

1. Visit [Anthropic Console](https://console.anthropic.com/)
2. Create an API key
3. Add to `.env`: `ANTHROPIC_API_KEY=your_key_here`

---

## 👥 User Roles

### Teacher
- Create and manage assignments
- Define grading rubrics
- View all student submissions
- Export grades to Excel/CSV
- Monitor AI grading progress

### Student
- Browse available assignments
- Submit code files (.py, .java, .cpp, .js, etc.)
- View grading results and feedback
- Track submission history

---

## 📊 Grading Criteria

The AI evaluates submissions based on:

| Criterion | Weight | Description |
|-----------|--------|-------------|
| Correctness | 40% | Does the code solve the problem? |
| Efficiency | 20% | Time/space complexity analysis |
| Security | 20% | Vulnerability detection (SQLi, XSS, etc.) |
| Style | 20% | Naming conventions, comments, readability |

Output format: JSON with score (0-100), bugs list, suggestions, and summary.

---

## 🔒 Security Features

- ✅ Password hashing (bcrypt)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection (output sanitization)
- ✅ CSRF protection (session tokens)
- ✅ Secure file uploads (renamed, validated, non-executable)
- ✅ Session regeneration on login
- ✅ Environment-based configuration (no hardcoded secrets)

---

## 📈 ISO 9126 Compliance

| Characteristic | Implementation |
|----------------|----------------|
| **Functionality** | Role-based access, AI grading, Excel export, file upload |
| **Reliability** | AI fallback mechanism, transactional DB operations, error logging |
| **Usability** | Bootstrap 5 UI, intuitive dashboards, real-time status |
| **Efficiency** | Async AI processing, database indexing, SSE for live updates |
| **Maintainability** | MVC architecture, modular AI classes, documented code |
| **Portability** | Dockerized, standard PHP/MySQL, config-based API keys |

---

## 🛠️ Troubleshooting

### Common Issues

**1. "Cannot connect to database"**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Verify credentials in .env
cat .env | grep DB_
```

**2. "AI grading fails"**
- Ensure at least one AI provider is configured
- For Ollama: `ollama list` to verify models
- Check API keys are valid and have credits

**3. "File upload fails"**
```bash
# Check permissions
chmod -R 755 uploads/
chown -R www-data:www-data uploads/

# Check PHP upload limits in php.ini
upload_max_filesize = 10M
post_max_size = 10M
```

**4. "Page not found (404)"**
- Enable mod_rewrite: `sudo a2enmod rewrite`
- Restart Apache: `sudo systemctl restart apache2`
- Verify document root points to `/public`

---

## 📝 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login` | User login |
| POST | `/auth/logout` | User logout |
| GET | `/dashboard/teacher` | Teacher dashboard |
| GET | `/dashboard/student` | Student dashboard |
| POST | `/assignments/create` | Create assignment |
| POST | `/submissions/upload` | Submit code |
| GET | `/stream?submission_id=X` | Live grading status (SSE) |
| GET | `/reports/export?id=X` | Export Excel report |

---

## 🧪 Testing

```bash
# Run PHP syntax check
find . -name "*.php" -exec php -l {} \;

# Test database connection
php -r "require 'config/Database.php'; echo 'OK';"

# Test AI services (requires API keys)
php services/test_ai.php
```

---

## 📄 License

This project is open-source software licensed under the MIT License.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📞 Support

For issues and questions:
- GitHub Issues: [Create an issue](https://github.com/your-repo/ai-gms/issues)
- Documentation: See `/docs` folder
- Email: support@ai-gms.example.com

---

## 🙏 Acknowledgments

- Bootstrap 5 for the UI framework
- PhpSpreadsheet for Excel generation
- Ollama, Google Gemini, OpenAI, and Anthropic for AI services
- ISO 9126 Software Quality Model

---

**Built with ❤️ for Education**

*Version 1.0.0 | Last Updated: 2024*
