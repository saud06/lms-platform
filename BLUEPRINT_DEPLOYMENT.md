# LMS Platform - Render Blueprint Deployment

## 🚀 One-Click Deployment with Render Blueprint

This guide will help you deploy the LMS Platform using Render's Blueprint feature for automated infrastructure deployment.

## Architecture
- **Database**: Railway MySQL (external)
- **Application**: Render Docker (PHP 8.2 + Apache + React)
- **Deployment**: Infrastructure as Code (Blueprint)

## Prerequisites

### 1. Railway MySQL Database
✅ **Already Created**: https://railway.com/project/b874192a-4196-4373-a45f-b89ac98f0f76

### 2. GitHub Repository
✅ **Already Available**: https://github.com/saud06/lms-platform

## Step 1: Get Railway MySQL Credentials

1. Go to your Railway project: https://railway.com/project/b874192a-4196-4373-a45f-b89ac98f0f76
2. Click on the **MySQL** service
3. Go to the **Variables** tab
4. Copy these values (you'll need them for Render):
   - `MYSQLHOST` (Database Host)
   - `MYSQLPORT` (Database Port - usually 3306)
   - `MYSQLDATABASE` (Database Name)
   - `MYSQLUSER` (Database Username)
   - `MYSQLPASSWORD` (Database Password)

## Step 2: Deploy with Blueprint

### Option A: Direct Blueprint URL (Recommended)
Click this link to deploy directly:

**🔗 [Deploy to Render](https://render.com/deploy?repo=https://github.com/saud06/lms-platform)**

### Option B: Manual Blueprint Deployment
1. Go to [Render Blueprints](https://dashboard.render.com/blueprints)
2. Click **New Blueprint**
3. Connect your GitHub repository: `saud06/lms-platform`
4. Render will automatically detect the `render.yaml` file
5. Click **Apply**

## Step 3: Configure Environment Variables

After the Blueprint is applied, you'll need to set the Railway MySQL credentials:

1. Go to your deployed service in Render Dashboard
2. Click on **Environment**
3. Update these variables with your Railway MySQL values:

```bash
DB_HOST=<your-railway-mysql-host>
DB_PORT=<your-railway-mysql-port>
DB_DATABASE=<your-railway-mysql-database>
DB_USERNAME=<your-railway-mysql-username>
DB_PASSWORD=<your-railway-mysql-password>
```

## Step 4: Deployment Process

The Blueprint will automatically:

### Build Phase:
- ✅ Build Docker container with PHP 8.2 + Apache
- ✅ Install Node.js and build React frontend
- ✅ Install Laravel dependencies with Composer
- ✅ Set up proper file permissions
- ✅ Configure Apache for production

### Runtime Phase:
- ✅ Clear Laravel caches
- ✅ Connect to Railway MySQL database
- ✅ Run database migrations (create all tables)
- ✅ Seed database with initial data
- ✅ Cache Laravel configuration
- ✅ Start Apache server

## Step 5: Verify Deployment

### Health Check
The service includes a health check at `/api/health` that verifies:
- Application is running
- Database connection is working
- All systems are operational

### Default Users Created
After successful deployment, these users will be available:

- **Admin**: `admin@lms.com` / `admin123`
- **Instructor**: `instructor@lms.com` / `instructor123`
- **Student**: `student@lms.com` / `student123`

### Test Features
- ✅ User authentication and role management
- ✅ Theme switching (light/dark mode)
- ✅ Language switching (German/English)
- ✅ Course management system
- ✅ Interactive quiz system
- ✅ Responsive design

## Blueprint Configuration Details

### Service Configuration:
- **Type**: Web Service
- **Runtime**: Docker
- **Plan**: Free (upgradeable)
- **Region**: Oregon (fastest for most users)
- **Health Check**: `/api/health`
- **Auto-Deploy**: Enabled on main branch

### Environment Variables:
- **Laravel Configuration**: Production-ready settings
- **Database**: MySQL connection to Railway
- **Security**: JWT authentication configured
- **Mail**: SMTP configuration ready
- **Caching**: File-based caching for free tier

## Troubleshooting

### Common Issues:

1. **Database Connection Failed**
   - Verify Railway MySQL credentials are correct
   - Check if Railway MySQL service is running
   - Ensure network connectivity between Render and Railway

2. **Build Failed**
   - Check build logs for specific errors
   - Verify Dockerfile syntax
   - Ensure all dependencies are available

3. **Health Check Failed**
   - Wait for migrations to complete
   - Check application logs
   - Verify database tables were created

### Debug Commands:
```bash
# Check logs in Render Dashboard
# Go to your service → Logs tab

# Test database connection
curl https://your-app.onrender.com/api/health

# Check specific endpoints
curl https://your-app.onrender.com/api/courses
```

## Scaling and Production

### Free Tier Limitations:
- Service sleeps after 15 minutes of inactivity
- 512MB RAM, shared CPU
- 100GB bandwidth per month

### Upgrade Benefits:
- Always-on service (no sleeping)
- More RAM and dedicated CPU
- Custom domains
- Advanced monitoring

## Security Features

### Included Security:
- ✅ HTTPS enforced
- ✅ Security headers configured
- ✅ JWT token authentication
- ✅ CSRF protection
- ✅ SQL injection prevention
- ✅ XSS protection

### Recommended Additional Security:
- Set up custom domain with SSL
- Configure rate limiting
- Set up monitoring and alerts
- Regular security updates

## Support

### Resources:
- **Render Documentation**: https://render.com/docs
- **Laravel Documentation**: https://laravel.com/docs
- **Railway Documentation**: https://docs.railway.app

### Getting Help:
- Check application logs in Render Dashboard
- Review Railway MySQL service status
- Test individual components (API, database, frontend)

---

## 🎉 Congratulations!

Your LMS Platform is now deployed with:
- **Professional Infrastructure**: Docker + MySQL
- **Scalable Architecture**: Render + Railway
- **Production Ready**: Security, caching, optimization
- **Full Featured**: Complete learning management system

**Your application will be available at**: `https://lms-platform.onrender.com`
