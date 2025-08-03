import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../../contexts/AuthContext'
import { useTheme } from '../../contexts/ThemeContext'
import { 
  LayoutDashboard, 
  BookOpen, 
  Users, 
  GraduationCap,
  Settings,
  LogOut,
  ClipboardList
} from 'lucide-react'
import { Button } from '../ui/button'
import { useLanguage } from '../../contexts/LanguageContext'

export default function Sidebar() {
  const { user, logout } = useAuth()
  const { theme } = useTheme()
  const location = useLocation()
  const { t } = useLanguage()

  const role = user?.role
  const navigation = [
    {
      name: t('nav.dashboard', 'Dashboard'),
      href:
        role === 'admin' ? '/admin/dashboard' :
        role === 'instructor' ? '/instructor/dashboard' :
        '/student/dashboard',
      icon: LayoutDashboard,
      roles: ['admin', 'instructor', 'student'],
    },
    {
      name: t('nav.courses', 'Courses'),
      href: role === 'admin' ? '/admin/courses' : (role === 'instructor' ? '/instructor/courses' : '/courses'),
      icon: BookOpen,
      roles: ['admin', 'instructor', 'student'],
    },
    {
      name: t('nav.quizzes', 'Quizzes'),
      href:
        role === 'admin' ? '/admin/quizzes' :
        role === 'instructor' ? '/instructor/quizzes' :
        '/quizzes',
      icon: ClipboardList,
      roles: ['admin', 'instructor', 'student'],
    },
    { name: t('nav.users', 'Users'), href: '/admin/users', icon: Users, roles: ['admin'] },
    { name: t('nav.students', 'Students'), href: '/instructor/students', icon: GraduationCap, roles: ['instructor'] },
    {
      name: t('nav.settings', 'Settings'),
      href: '/admin/settings',
      icon: Settings,
      roles: ['admin'],
    },
  ]

  const filteredNavigation = navigation.filter(item => 
    item.roles.includes(user?.role)
  )

  return (
    <div className="bg-white dark:bg-gray-800 text-gray-900 dark:text-white w-64 space-y-6 py-7 px-2 absolute inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition duration-200 ease-in-out border-r border-gray-200 dark:border-gray-700">
      <div className="text-center">
        <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
          {t('app.title', 'LMS Platform')}
        </h1>
        <p className="text-gray-600 dark:text-gray-400 text-sm mt-2">
          {user?.role}
        </p>
      </div>

      <nav className="space-y-2">
        {filteredNavigation.map((item) => {
          const isActive = location.pathname === item.href
          return (
            <Link
              key={item.name}
              to={item.href}
              className={`flex items-center space-x-2 py-2 px-4 rounded transition duration-200 ${
                isActive 
                  ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white' 
                  : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'
              }`}
            >
              <item.icon className="h-5 w-5" />
              <span>{item.name}</span>
            </Link>
          )
        })}
      </nav>

      <div className="absolute bottom-4 left-2 right-2">
        <div className="bg-gray-100 dark:bg-gray-700 rounded p-3 mb-4">
          <p className="text-sm font-medium text-gray-900 dark:text-white">
            {user?.name}
          </p>
          <p className="text-xs text-gray-600 dark:text-gray-400">
            {user?.email}
          </p>
        </div>
        <Button
          onClick={logout}
          variant="ghost"
          className="w-full justify-start"
        >
          <LogOut className="h-4 w-4 mr-2" />
          {t('header.logout', 'Logout')}
        </Button>
      </div>
    </div>
  )
}
