# 🚀 Quick Start Guide - AI-Grading Management System

## Choose Your Installation Method

### ⭐ Option 1: Docker (Easiest - Recommended)

**One Command to Rule Them All:**

```bash
docker-compose up -d
```

That's it! Access the app at:
- **Web App**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **Login**: admin / Admin@123

**Full Docker Setup:**

```bash
# Clone or navigate to project
cd /workspace

# Copy environment file
cp .env.example .env

# (Optional) Edit API keys
nano .env

# Start everything
docker-compose up -d

# View logs
docker-compose logs -f app

# Stop everything
docker-compose down
```

---

### 🛠️ Option 2: Manual Setup with Script

**Run the Automated Setup:**

```bash
cd /workspace

# Make script executable
chmod +x setup.sh

# Run setup (follow prompts)
./setup.sh
```

The script will:
1. ✅ Check PHP & MySQL installation
2. ✅ Verify required extensions
3. ✅ Create `.env` configuration file
4. ✅ Set up directory structure
5. ✅ Install Composer dependencies
6. ✅ Create database and import schema
7. ✅ Create admin account
8. ✅ Set file permissions
9. ✅ Configure web server (.htaccess)

**After Setup:**

```bash
# Point your web server document root to:
/workspace/public

# Or if using PHP built-in server:
cd /workspace/public
php -S localhost:8000
```

Access at: http://localhost:8000

---

### 🔧 Option 3: Complete Manual Setup

For advanced users who want full control:

```bash
# 1. Create database
mysql -u root -p
CREATE DATABASE ai_grading_system;
exit

# 2. Import schema
mysql -u root -p ai_grading_system < database/schema.sql

# 3. Copy and edit .env
cp .env.example .env
nano .env

# 4. Install dependencies
composer install

# 5. Set permissions
mkdir -p uploads logs
chmod -R 755 uploads logs
chown -R www-data:www-data uploads logs

# 6. Configure web server
# Document root: /workspace/public
# Enable mod_rewrite
```

---

## 🎯 Post-Installation Checklist

### 1. Change Default Password
```
Login → Profile Settings → Change Password
Default: admin / Admin@123
```

### 2. Configure AI Providers (Optional but Recommended)

**For Ollama (Free, Local):**
```bash
# Install
curl -fsSL https://ollama.com/install.sh | sh

# Pull model
ollama pull llama3.2

# Test
ollama run llama3.2 "Hello"
```

**For Cloud APIs:**
Edit `.env` and add your keys:
```env
GEMINI_API_KEY=AIzaSy...
OPENAI_API_KEY=sk-proj-...
ANTHROPIC_API_KEY=sk-ant-...
```

### 3. Test the System

```bash
# Test database connection
php -r "require 'config/Database.php'; echo 'DB OK';"

# Test file upload permissions
touch uploads/test.txt && rm uploads/test.txt && echo "Uploads OK"

# Access in browser
# http://localhost:8080/login
```

---

## 📱 First Steps After Login

### For Teachers:
1. **Create Assignment** → Dashboard → New Assignment
2. **Define Rubric** → Add grading criteria
3. **Monitor Submissions** → View student work
4. **Export Reports** → Download Excel/CSV

### For Students:
1. **Browse Assignments** → View available tasks
2. **Submit Code** → Upload .py, .java, .cpp, .js files
3. **View Grades** → Check AI feedback
4. **Track Progress** → Submission history

---

## 🆘 Troubleshooting

| Issue | Solution |
|-------|----------|
| Port 8080 already in use | Edit `docker-compose.yml`, change `"8080:80"` to `"8082:80"` |
| Database connection failed | Check `.env` credentials, ensure MySQL is running |
| Permission denied on uploads | `chmod -R 755 uploads && chown -R www-data:www-data uploads` |
| Page shows blank/white | Check `logs/` folder, enable `APP_DEBUG=true` temporarily |
| AI grading not working | Verify API keys, test Ollama with `ollama list` |

---

## 📊 System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| PHP | 8.2 | 8.3+ |
| MySQL | 8.0 | 8.0+ |
| RAM | 2GB | 4GB+ |
| Storage | 1GB | 5GB+ |
| Docker | 20.x | Latest |

---

## 🎓 Next Steps

1. **Read the Full Documentation**: See `README.md`
2. **Watch Tutorial Videos**: [Link to tutorials]
3. **Join Community**: [Discord/Forum link]
4. **Report Issues**: GitHub Issues
5. **Contribute**: Submit PRs for features/fixes

---

**Need Help?** Check `README.md` for detailed documentation or run `./setup.sh --help`

*Happy Grading! 🎓*
