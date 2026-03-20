# SocialBit - Suggested Commands

## Development Environment (Windows/XAMPP)

### Start Development Server
```bash
# XAMPP Control Panel
# Start Apache and MySQL services

# Or via command line
cd C:\xampp3
.\apache_start.bat
.\mysql_start.bat
```

### Access Application
```
http://localhost/socialbit-live/
```

## Git Commands

### Status and Changes
```bash
git status
git diff
git log --oneline -10
```

### Branch Management
```bash
git branch
git checkout -b feature/branch-name
git checkout main
```

### Commit Changes
```bash
git add .
git commit -m "feat: description of changes"
git push origin branch-name
```

## Database Management

### Access MySQL Console
```bash
# Via XAMPP
cd C:\xampp3\mysql\bin
mysql -u root -p

# Common commands
USE social_media_analytics;
SHOW TABLES;
DESCRIBE posts;
SELECT * FROM posts LIMIT 10;
```

### Database Backup
```bash
# Export database
mysqldump -u root -p social_media_analytics > backup_$(date +%Y%m%d).sql

# Import database
mysql -u root -p social_media_analytics < backup.sql
```

## File Operations (Windows PowerShell)

### List Files
```powershell
Get-ChildItem -Path src -Recurse -Filter *.php
ls src
dir src
```

### Search in Files
```powershell
# Search for pattern in files
Select-String -Path "src\**\*.php" -Pattern "function"

# Or use findstr (CMD)
findstr /s /i "pattern" src\*.php
```

### Find Files
```powershell
Get-ChildItem -Path src -Recurse -Filter "*Controller.php"
```

## Code Quality

### PHP Syntax Check
```bash
php -l src/Controllers/ImportController.php
```

### Find TODO/FIXME Comments
```powershell
Select-String -Path "src\**\*.php" -Pattern "TODO|FIXME"
```

## Testing (Not yet configured)
```bash
# Placeholder for future testing commands
# vendor/bin/phpunit tests/
```

## Common Utility Commands

### Count PHP Files
```powershell
(Get-ChildItem -Path src -Recurse -Filter *.php).Count
```

### View Recent Changes
```bash
git log --since="1 week ago" --oneline
```

### Check File Encoding
```powershell
Get-Content -Path src/file.php -Encoding UTF8
```

## Notes
- The project uses Windows/XAMPP for local development
- Plesk Obsidian for production deployment
- No build tools currently configured (planned: Vite for frontend)
- No package manager for PHP (no Composer currently)
- Git is available for version control
