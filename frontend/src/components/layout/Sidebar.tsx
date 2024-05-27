import { Link, useLocation } from 'react-router-dom'
import { cn } from '@/lib/utils'
import { useAuth } from '@/contexts/AuthContext'
import {
  BookOpen,
  GraduationCap,
  LayoutDashboard,
  Users,
  Settings,
  User,
  PlusCircle,
  BarChart3,
} from 'lucide-react'

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['admin', 'instructor', 'student'] },
  { name: 'Courses', href: '/courses', icon: BookOpen, roles: ['admin', 'instructor', 'student'] },
  { name: 'Profile', href: '/profile', icon: User, roles: ['admin', 'instructor', 'student'] },
]

const adminNavigation = [
  { name: 'Admin Dashboard', href: '/admin', icon: BarChart3 },
  { name: 'Manage Users', href: '/admin/users', icon: Users },
  { name: 'Manage Courses', href: '/admin/courses', icon: BookOpen },
]

const instructorNavigation = [
  { name: 'Instructor Dashboard', href: '/instructor', icon: BarChart3 },
  { name: 'My Courses', href: '/instructor/courses', icon: BookOpen },
  { name: 'Create Course', href: '/instructor/courses/create', icon: PlusCircle },
]

export function Sidebar() {
  const location = useLocation()
  const { user } = useAuth()

  const isActive = (href: string) => {
    return location.pathname === href || location.pathname.startsWith(href + '/')
  }

  const filteredNavigation = navigation.filter(item => 
    item.roles.includes(user?.role || 'student')
  )

  return (
    <div className="flex flex-col w-64 bg-white border-r border-gray-200">
      <div className="flex items-center justify-center h-16 px-4 border-b border-gray-200">
        <div className="flex items-center">
          <GraduationCap className="h-8 w-8 text-primary" />
          <span className="ml-2 text-xl font-bold text-gray-900">LMS</span>
        </div>
      </div>

      <nav className="flex-1 px-4 py-6 space-y-2">
        {/* Main Navigation */}
        {filteredNavigation.map((item) => {
          const Icon = item.icon
          return (
            <Link
              key={item.name}
              to={item.href}
              className={cn(
                'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                isActive(item.href)
                  ? 'bg-primary text-primary-foreground'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
              )}
            >
              <Icon className="mr-3 h-5 w-5" />
              {item.name}
            </Link>
          )
        })}

        {/* Admin Navigation */}
        {user?.role === 'admin' && (
          <>
            <div className="pt-6">
              <h3 className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Administration
              </h3>
            </div>
            {adminNavigation.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={cn(
                    'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    isActive(item.href)
                      ? 'bg-primary text-primary-foreground'
                      : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                  )}
                >
                  <Icon className="mr-3 h-5 w-5" />
                  {item.name}
                </Link>
              )
            })}
          </>
        )}

        {/* Instructor Navigation */}
        {(user?.role === 'instructor' || user?.role === 'admin') && (
          <>
            <div className="pt-6">
              <h3 className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Teaching
              </h3>
            </div>
            {instructorNavigation.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={cn(
                    'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    isActive(item.href)
                      ? 'bg-primary text-primary-foreground'
                      : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'
                  )}
                >
                  <Icon className="mr-3 h-5 w-5" />
                  {item.name}
                </Link>
              )
            })}
          </>
        )}
      </nav>
    </div>
  )
}
