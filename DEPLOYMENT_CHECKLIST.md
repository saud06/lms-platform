# Render Deployment Checklist

## Pre-Deployment
- [ ] Code committed and pushed to main branch
- [ ] All deployment files present:
  - [ ] `render.yaml`
  - [ ] `backend/render-build.sh`
  - [ ] `backend/render-start.sh`
  - [ ] `backend/.env.production`
  - [ ] `backend/database/seeders/ProductionSeeder.php`
- [ ] Frontend API configuration updated
- [ ] Render account created

## Deployment
- [ ] Connect GitHub repository to Render
- [ ] Deploy using Blueprint (render.yaml) method
- [ ] Verify all services created:
  - [ ] lms-backend (Web Service)
  - [ ] lms-frontend (Static Site)
  - [ ] lms-database (Database)
- [ ] Environment variables properly configured

## Post-Deployment
- [ ] Backend health check: `/api/test` returns success
- [ ] Frontend loads correctly
- [ ] Database migration completed
- [ ] Production seeder ran successfully
- [ ] Admin login works
- [ ] Change default passwords
- [ ] Test core functionality:
  - [ ] User authentication
  - [ ] Course access
  - [ ] Dashboard functionality

## Optional
- [ ] Custom domain configured
- [ ] SSL certificate verified (auto-enabled)
- [ ] Monitoring setup
- [ ] Backup strategy in place

## Production URLs
- Backend: `https://lms-backend.onrender.com`
- Frontend: `https://lms-frontend.onrender.com`
- Admin Login: `admin@lmsplatform.com`

## Notes
- Services may sleep on free tier after 15 minutes of inactivity
- First load after sleep may take 30-60 seconds
- Monitor usage to stay within free tier limits