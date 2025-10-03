# Render 500 Error Debugging Guide

## 🚨 Current Issue: HTTP 500 Error

Your LMS Platform is deployed at: https://lms-platform-i2dl.onrender.com/
But showing: "HTTP ERROR 500 - currently unable to handle this request"

## 🔍 Debugging Steps

### Step 1: Check Render Logs
1. **Go to Render Dashboard** → Your LMS service
2. **Click "Logs" tab**
3. **Look for error messages** in the startup logs
4. **Check for**:
   - Database connection errors
   - PHP fatal errors
   - Apache startup issues
   - Missing files or permissions

### Step 2: Test Simple Endpoints

Try these URLs to isolate the issue:

1. **Status Page**: https://lms-platform-i2dl.onrender.com/status.php
   - Should show: "LMS Platform is running on [timestamp]"
   - If this works: Apache is running, PHP is working

2. **Health Check**: https://lms-platform-i2dl.onrender.com/api/health
   - Should show JSON with database status
   - If this fails: Database connection issue

3. **Laravel Welcome**: https://lms-platform-i2dl.onrender.com/
   - Should show Laravel application
   - If this fails: Laravel configuration issue

### Step 3: Common 500 Error Causes

#### Cause 1: Database Connection Failed
**Symptoms**: Logs show database connection errors
**Solution**: 
- Verify Railway MySQL credentials in Render environment variables
- Check if Railway MySQL service is running
- Test connection from Render logs

#### Cause 2: Missing APP_KEY
**Symptoms**: "No application encryption key has been specified"
**Solution**: 
- Ensure APP_KEY is set in Render environment variables
- Value: `base64:9vJiaZea2Brfxf5x6fO90FUISkwkogCgXhyIPp9ZeTM=`

#### Cause 3: File Permissions
**Symptoms**: "Permission denied" errors in logs
**Solution**: 
- Check storage directory permissions
- Ensure www-data owns Laravel directories

#### Cause 4: Missing Dependencies
**Symptoms**: "Class not found" or "Function not found" errors
**Solution**: 
- Check if Composer install completed successfully
- Verify all PHP extensions are installed

#### Cause 5: Apache Configuration
**Symptoms**: Apache fails to start or serve files
**Solution**: 
- Check Apache error logs
- Verify port configuration
- Check document root settings

## 🛠️ Quick Fixes to Try

### Fix 1: Redeploy with Debug Mode
1. **Go to Render** → Environment
2. **Set**: `APP_DEBUG=true`
3. **Redeploy** → This will show detailed error messages

### Fix 2: Check Environment Variables
Ensure these are set in Render:
```bash
APP_NAME=LMS Platform
APP_ENV=production
APP_DEBUG=false  # Set to true for debugging
APP_URL=https://lms-platform-i2dl.onrender.com
APP_KEY=base64:9vJiaZea2Brfxf5x6fO90FUISkwkogCgXhyIPp9ZeTM=
DB_CONNECTION=mysql
DB_HOST=nozomi.proxy.rlwy.net
DB_PORT=55229
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=yqZGCjlCsuPmeEzlaDxWmIEmHllcujWJ
```

### Fix 3: Manual Deploy
1. **Go to Render** → Deployments
2. **Click "Manual Deploy"**
3. **Deploy Latest Commit**
4. **Watch logs** during deployment

## 📋 Expected Startup Logs

Successful deployment should show:
```bash
🚀 Starting LMS Platform on Render...
🧹 Clearing Laravel caches...
🔍 Testing database connection...
Host: nozomi.proxy.rlwy.net, Port: 55229, Database: railway, User: root
✅ Database connected successfully
🗄️ Running database migrations...
   INFO  Migration table created successfully.
🌱 Seeding database with initial data...
   INFO  Database seeding completed successfully.
⚡ Caching configuration for production...
🌐 Starting Apache server on port 10000...
✅ LMS Platform startup completed successfully
```

## 🚨 If Still Failing

### Last Resort: Simplified Deployment
1. **Temporarily disable database operations**
2. **Deploy with minimal configuration**
3. **Add features back gradually**

### Get Help
1. **Share Render logs** with specific error messages
2. **Test individual components** (status.php, health check)
3. **Verify Railway MySQL** is accessible

The 500 error is usually fixable once we see the specific error in the logs! 🔧
