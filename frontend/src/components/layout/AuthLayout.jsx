import { Outlet, Navigate } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { useTheme } from '../../contexts/ThemeContext'

export default function AuthLayout() {
  const { isAuthenticated, isLoading } = useAuth()
  const { theme } = useTheme()

  if (isLoading) {
    return <div>Loading...</div>
  }

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />
  }

  return (
    <div 
      className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8 transition-colors"
      style={{ backgroundColor: theme === 'dark' ? '#111827' : '#f9fafb' }}
    >
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 
            className="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white transition-colors"
            style={{ color: theme === 'dark' ? '#ffffff' : '#111827' }}
          >
            LMS Platform
          </h2>
        </div>
        <Outlet />
      </div>
    </div>
  )
}
