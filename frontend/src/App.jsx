import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AuthProvider } from './contexts/AuthContext'
import { ThemeProvider } from './contexts/ThemeContext'
import { Toaster } from './components/ui/toaster'
import { ToastProvider } from './components/ui/toast'

// Layouts
import Layout from './components/layout/Layout'
import AuthLayout from './components/layout/AuthLayout'

// Auth Components
import ProtectedRoute from './components/auth/ProtectedRoute'

// Pages
import LoginPage from './pages/auth/LoginPage.jsx'
import RegisterPage from './pages/auth/RegisterPage.jsx'
import DashboardPage from './pages/DashboardPage.jsx'
import AdminDashboard from './pages/admin/AdminDashboard'
import AdminQuizzes from './pages/admin/AdminQuizzes'
import ManageUsers from './pages/admin/ManageUsers'
import ManageCourses from './pages/admin/ManageCourses'
import AdminSettings from './pages/admin/AdminSettings'
import AdminCoursePreview from './pages/admin/AdminCoursePreview'
import InstructorDashboard from './pages/instructor/InstructorDashboard'
import InstructorQuizzes from './pages/instructor/InstructorQuizzes'
import InstructorCourses from './pages/instructor/InstructorCourses'
import InstructorCreateCourse from './pages/instructor/InstructorCreateCourse'
import InstructorStudents from './pages/instructor/InstructorStudents'
import InstructorCourseView from './pages/instructor/InstructorCourseView'
import InstructorEditCourse from './pages/instructor/InstructorEditCourse'
import StudentDashboard from './pages/student/StudentDashboard'
import StudentQuizzes from './pages/student/StudentQuizzes'
import StudentCourses from './pages/student/StudentCourses'
import StudentCourseView from './pages/student/StudentCourseView'
import LandingPage from './pages/LandingPage'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <ThemeProvider>
        <ToastProvider>
          <AuthProvider>
            <Router>
          <Routes>
            {/* Public Routes */}
            <Route path="/" element={<LandingPage />} />
            <Route path="/auth" element={<AuthLayout />}>
              <Route path="login" element={<LoginPage />} />
              <Route path="register" element={<RegisterPage />} />
            </Route>

            {/* Protected Layout Route (no path) */}
            <Route element={<ProtectedRoute><Layout /></ProtectedRoute>}>
              {/* Dashboard */}
              <Route path="/dashboard" element={<DashboardPage />} />

              {/* Admin Routes */}
              <Route path="/admin/dashboard" element={<AdminDashboard />} />
              <Route path="/admin/quizzes" element={<AdminQuizzes />} />
              <Route path="/admin/users" element={<ManageUsers />} />
              <Route path="/admin/courses" element={<ManageCourses />} />
              <Route path="/admin/courses/:id" element={<AdminCoursePreview />} />
              <Route path="/admin/settings" element={<AdminSettings />} />

              {/* Instructor Routes */}
              <Route path="/instructor/dashboard" element={<InstructorDashboard />} />
              <Route path="/instructor/quizzes" element={<InstructorQuizzes />} />
              <Route path="/instructor/courses" element={<InstructorCourses />} />
              <Route path="/instructor/courses/create" element={<InstructorCreateCourse />} />
              <Route path="/instructor/students" element={<InstructorStudents />} />
              <Route path="/instructor/courses/:id" element={<InstructorCourseView />} />
              <Route path="/instructor/courses/:id/edit" element={<InstructorEditCourse />} />

              {/* Student Routes */}
              <Route path="/student/dashboard" element={<StudentDashboard />} />
              <Route path="/quizzes" element={<StudentQuizzes />} />
              <Route path="/courses" element={<StudentCourses />} />
              <Route path="/courses/:id" element={<StudentCourseView />} />
            </Route>

            {/* Redirect unknowns to landing */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
            </Router>
            <Toaster />
          </AuthProvider>
        </ToastProvider>
      </ThemeProvider>
    </QueryClientProvider>
  )
}

export default App
