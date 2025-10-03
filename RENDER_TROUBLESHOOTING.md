# Render Deployment Troubleshooting Guide

## 🚨 Database Connection Issue: "mysql.railway.internal failed"

### Problem
The error `php_network_getaddresses: getaddrinfo for mysql.railway.internal failed` indicates that the database host is incorrectly set to `mysql.railway.internal` instead of the actual Railway MySQL host.

### Root Cause
The environment variables in Render are not set with the correct Railway MySQL connection details.

## 🔧 Solution Steps

### Step 1: Get Correct Railway MySQL Credentials

1. **Go to Railway Project**: https://railway.com/project/b874192a-4196-4373-a45f-b89ac98f0f76
2. **Click on MySQL service** (not any other service)
3. **Go to Variables tab**
4. **Copy these exact values**:

```bash
# You'll see variables like:
MYSQLHOST=containers-us-west-xxx.railway.app
MYSQLPORT=6543
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=abc123xyz789
```

### Step 2: Update Render Environment Variables

1. **Go to Render Dashboard** → Your LMS service
2. **Click Environment tab**
3. **Update these variables** with Railway values:

```bash
DB_HOST=containers-us-west-xxx.railway.app  # Use actual MYSQLHOST
DB_PORT=6543                                 # Use actual MYSQLPORT  
DB_DATABASE=railway                          # Use actual MYSQLDATABASE
DB_USERNAME=root                             # Use actual MYSQLUSER
DB_PASSWORD=abc123xyz789                     # Use actual MYSQLPASSWORD
```

### Step 3: Redeploy

1. **Save environment variables**
2. **Go to Deployments tab**
3. **Click "Manual Deploy"** → **Deploy Latest Commit**

## 🔍 Verification Steps

### Check Database Connection
After redeployment, the logs should show:
```bash
🔍 Testing database connection...
Database connected successfully
🗄️ Running database migrations...
   INFO  Migration table created successfully.
```

### Test Health Endpoint
Visit: `https://your-app.onrender.com/api/health`

Should return:
```json
{
  "status": "healthy",
  "database": "connected",
  "timestamp": "2025-10-03T16:23:57.000000Z"
}
```

## 🚨 Common Issues & Solutions

### Issue 1: Still Getting "mysql.railway.internal"
**Cause**: Environment variables not saved properly
**Solution**: 
- Double-check all DB_* variables are set correctly
- Ensure no trailing spaces in values
- Redeploy after saving

### Issue 2: "Connection refused" 
**Cause**: Wrong host or port
**Solution**:
- Verify Railway MySQL service is running
- Check if host includes port (some Railway hosts include port in MYSQLHOST)
- Try connecting from Railway CLI: `railway connect mysql`

### Issue 3: "Access denied"
**Cause**: Wrong username/password
**Solution**:
- Copy credentials exactly from Railway Variables tab
- Check for special characters that might need escaping

### Issue 4: "Unknown database"
**Cause**: Wrong database name
**Solution**:
- Use exact MYSQLDATABASE value from Railway
- Usually "railway" but could be different

## 🛠️ Debug Commands

### Test Railway Connection Locally
```bash
# In your local project
railway login
railway link [your-project-id]
railway connect mysql
```

### Check Render Logs
```bash
# In Render Dashboard → Logs tab
# Look for database connection attempts
# Should see successful connection messages
```

### Manual Database Test
```bash
# In Render Shell (if available)
php artisan tinker
DB::connection()->getPdo();
# Should return PDO object if connected
```

## 📋 Environment Variables Checklist

Make sure these are set in Render:

### Required Database Variables:
- ✅ `DB_CONNECTION=mysql`
- ✅ `DB_HOST=<actual-railway-host>`
- ✅ `DB_PORT=<actual-railway-port>`
- ✅ `DB_DATABASE=<actual-railway-database>`
- ✅ `DB_USERNAME=<actual-railway-username>`
- ✅ `DB_PASSWORD=<actual-railway-password>`

### Required Laravel Variables:
- ✅ `APP_KEY=base64:9vJiaZea2Brfxf5x6fO90FUISkwkogCgXhyIPp9ZeTM=`
- ✅ `APP_ENV=production`
- ✅ `APP_DEBUG=false`
- ✅ `JWT_SECRET=VEBGAa6zfUiRz9CBKM9NvK1G5keUW65W4vxfNaQVRY0=`

## 🎯 Expected Success Flow

After fixing the database connection:

1. **Build Phase**: ✅ Docker builds successfully
2. **Startup Phase**: ✅ Database connects successfully  
3. **Migration Phase**: ✅ Tables created in Railway MySQL
4. **Seeding Phase**: ✅ Default users created
5. **Cache Phase**: ✅ Laravel configuration cached
6. **Server Phase**: ✅ Apache starts on correct port

### Success Indicators:
- ✅ Health check returns 200 OK
- ✅ Can login with default users
- ✅ Frontend loads properly
- ✅ API endpoints respond correctly

## 📞 Getting Help

If issues persist:

1. **Check Railway MySQL Status**: Ensure service is running
2. **Verify Network Connectivity**: Railway and Render should be able to communicate
3. **Review Render Logs**: Look for specific error messages
4. **Test Locally**: Try connecting to Railway MySQL from your local machine

The main issue is always the database connection - once that's fixed, everything else should work smoothly! 🚀
