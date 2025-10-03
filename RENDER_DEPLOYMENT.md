# LMS Platform Deployment Guide: Render Docker + Railway MySQL

## Architecture Overview
- **Database**: Railway MySQL (cloud database)
- **Application**: Render Docker (full LMS platform hosting)
- **Connection**: Render Docker ↔ Railway MySQL

## Step 1: Railway MySQL Connection Details

✅ **Clean MySQL-only setup created!**

**Railway Project**: https://railway.com/project/b874192a-4196-4373-a45f-b89ac98f0f76

**Connection Details** (use these exact values in Render):
- **DB_HOST**: `${MYSQLHOST}`
- **DB_PORT**: `${MYSQLPORT}` 
- **DB_DATABASE**: `${MYSQLDATABASE}`
- **DB_USERNAME**: `${MYSQLUSER}`
- **DB_PASSWORD**: `${MYSQLPASSWORD}`

**Note**: The actual values are automatically provided by Railway. You'll get the real connection details when you access the MySQL service variables tab.

## Step 2: Deploy to Render with Docker

### Option A: Deploy from GitHub (Recommended)
1. Push your code to GitHub (already done)
2. Go to [Render Dashboard](https://dashboard.render.com/)
3. Click **New** → **Web Service**
4. Connect your GitHub repository: `https://github.com/saud06/lms-platform`
5. Configure the service:
   - **Name**: `lms-platform`
   - **Environment**: `Docker`
   - **Dockerfile Path**: `./Dockerfile`
   - **Plan**: Free (or paid for better performance)

### Option B: Deploy with render.yaml (Automatic)
1. Push the `render.yaml` file to your GitHub repository
2. Render will automatically detect Docker configuration and deploy

### Docker Build Process:
The Dockerfile will automatically:
- ✅ Install PHP 8.2 with Apache
- ✅ Install Node.js and build frontend assets
- ✅ Install Laravel dependencies
- ✅ Set up proper permissions
- ✅ Configure Apache for production
- ✅ Run migrations and seeding on startup

## Step 3: Set Environment Variables on Render

In your Render service settings, add these environment variables:

### Required Variables:
```bash
APP_NAME=LMS Platform
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:9vJiaZea2Brfxf5x6fO90FUISkwkogCgXhyIPp9ZeTM=
DB_CONNECTION=mysql
JWT_SECRET=VEBGAa6zfUiRz9CBKM9NvK1G5keUW65W4vxfNaQVRY0=
```

### Railway MySQL Connection (Replace with actual values):
```bash
DB_HOST=<MYSQLHOST_FROM_RAILWAY>
DB_PORT=<MYSQLPORT_FROM_RAILWAY>
DB_DATABASE=<MYSQLDATABASE_FROM_RAILWAY>
DB_USERNAME=<MYSQLUSER_FROM_RAILWAY>
DB_PASSWORD=<MYSQLPASSWORD_FROM_RAILWAY>
```

### Optional Variables:
```bash
LOG_CHANNEL=stack
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

## Step 4: Verify Deployment

1. **Build Process**: Check build logs for successful frontend build and Laravel setup
2. **Database Connection**: Verify migrations run successfully
3. **Application Access**: Test the deployed URL
4. **Health Check**: Visit `/api/health` endpoint

## Step 5: Test the Application

### Default Login Credentials:
- **Admin**: `admin@lms.com` / `admin123`
- **Instructor**: `instructor@lms.com` / `instructor123`
- **Student**: `student@lms.com` / `student123`

### Test Features:
- ✅ User authentication
- ✅ Theme switching (light/dark)
- ✅ Language switching (German/English)
- ✅ Course management
- ✅ Quiz system
- ✅ Responsive design

## Troubleshooting

### Common Issues:
1. **Database Connection Failed**: Verify Railway MySQL credentials
2. **Build Failed**: Check Node.js and PHP versions in build logs
3. **Migration Failed**: Ensure database is accessible from Render
4. **Assets Not Loading**: Verify frontend build copied to public directory

### Debug Commands:
```bash
# Check database connection
php artisan tinker
DB::connection()->getPdo();

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations manually
php artisan migrate --force
```

## Architecture Benefits:
- ✅ **Scalable Database**: Railway MySQL with automatic backups
- ✅ **Fast Hosting**: Render's global CDN and edge locations
- ✅ **Cost Effective**: Free tiers available for both services
- ✅ **Easy Deployment**: Git-based deployment workflow
- ✅ **Secure Connection**: Encrypted connection between services
