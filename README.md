# LMS Platform

A modern Learning Management System built with Laravel and React, featuring comprehensive user management, course creation, interactive quizzes, and a responsive design with dark/light theme support.

![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-10-red)
![React](https://img.shields.io/badge/React-18-blue)
![Vite](https://img.shields.io/badge/Vite-Frontend%20Build-purple)
![MySQL](https://img.shields.io/badge/MySQL-Database-orange)
![Status](https://img.shields.io/badge/Status-Active-brightgreen)

## ✨ Features

### 🎓 **Learning Management**
- **Course Management** - Create, edit, and organize courses with detailed descriptions
- **Interactive Quizzes** - Multiple choice questions with instant feedback
- **Progress Tracking** - Monitor student progress and completion rates
- **User Roles** - Admin, Instructor, and Student role management

### 🎨 **User Experience**
- **Responsive Design** - Works seamlessly on desktop, tablet, and mobile
- **Dark/Light Theme** - Toggle between themes with persistent preference
- **Multi-language Support** - German and English language options
- **Modern UI** - Clean, intuitive interface built with Tailwind CSS

### 🔐 **Authentication & Security**
- **JWT Authentication** - Secure token-based authentication
- **Role-based Access** - Different permissions for different user types
- **Password Security** - Secure password hashing and validation

### 🛠️ **Technical Features**
- **RESTful API** - Well-structured Laravel API endpoints
- **Real-time Updates** - Dynamic content updates without page refresh
- **Database Seeding** - Pre-populated demo data for testing
- **Error Handling** - Comprehensive error handling and user feedback

## 🚀 Tech Stack

| Component | Technology |
|-----------|------------|
| **Backend** | Laravel 10 (PHP 8.2) |
| **Frontend** | React 18 + Vite |
| **Database** | MySQL |
| **Styling** | Tailwind CSS |
| **Authentication** | JWT |
| **Package Management** | Composer, npm |

## 📦 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL

### Setup

1. **Clone the repository**
```bash
git clone https://github.com/saud06/lms-platform.git
cd lms-platform
```

2. **Backend Setup**
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

3. **Frontend Setup** (new terminal)
```bash
cd frontend
npm install
npm run dev
```

4. **Access the Application**
- Frontend: http://localhost:3000
- Backend API: http://localhost:8000

## 👥 Demo Users

The application comes with pre-seeded demo users:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@lms.com | admin123 |
| **Instructor** | instructor@lms.com | instructor123 |
| **Student** | student@lms.com | student123 |

## 🎯 Usage

### For Students
- Browse available courses
- Take interactive quizzes
- Track learning progress
- Switch between themes and languages

### For Instructors
- Create and manage courses
- Design quizzes with multiple choice questions
- Monitor student progress
- Manage course content

### For Administrators
- Full system access
- User management
- Course oversight
- System configuration

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Courses
- `GET /api/courses` - List all courses
- `POST /api/courses` - Create new course
- `GET /api/courses/{id}` - Get course details
- `PUT /api/courses/{id}` - Update course

### Quizzes
- `GET /api/quizzes` - List quizzes
- `POST /api/quizzes` - Create quiz
- `POST /api/quizzes/{id}/attempt` - Submit quiz attempt

## 🌟 Screenshots

*Add screenshots of your LMS platform here*

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 📬 Contact

- **GitHub**: [saud06](https://github.com/saud06)
- **LinkedIn**: [Saud M.](https://linkedin.com/in/saud06)
- **Email**: [saud.mn6@gmail.com](mailto:saud.mn6@gmail.com)

