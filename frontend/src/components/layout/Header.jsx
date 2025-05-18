import { useState } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { Bell, Search, LogOut } from 'lucide-react'
import { Button } from '../ui/button'
import { Input } from '../ui/input'
import { useNavigate } from 'react-router-dom'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../ui/dropdown-menu'

export default function Header() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [term, setTerm] = useState('')
  const [notifications, setNotifications] = useState([
    { id: 1, text: 'Welcome to the LMS!', href: '/dashboard', read: false },
    { id: 2, text: 'New quiz attempts are available to review.', href: '/instructor/quizzes', read: false },
  ])

  const handleLogout = () => {
    logout()
    navigate('/auth/login')
  }

  const handleSearch = () => {
    const q = term.trim()
    if (!q) return
    const role = user?.role
    let pathname = '/'
    if (role === 'admin') pathname = '/admin/courses'
    else if (role === 'instructor') pathname = '/instructor/courses'
    else pathname = '/courses'
    navigate({ pathname, search: `?q=${encodeURIComponent(q)}` })
  }

  const unreadCount = notifications.filter(n => !n.read).length
  const markAllRead = () => setNotifications(prev => prev.map(n => ({ ...n, read: true })))
  const onClickItem = (n) => {
    setNotifications(prev => prev.map(x => x.id === n.id ? { ...x, read: true } : x))
    if (n.href) navigate(n.href)
  }

  return (
    <header className="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              type="text"
              placeholder="Search..."
              className="pl-10 w-64"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
              onKeyDown={(e) => { if (e.key === 'Enter') handleSearch() }}
            />
          </div>
        </div>

        <div className="flex items-center space-x-4">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm" className="relative">
                <Bell className="h-4 w-4" />
                {unreadCount > 0 && (
                  <span className="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none px-1.5 py-0.5 rounded-full">
                    {unreadCount}
                  </span>
                )}
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-64">
              <DropdownMenuLabel className="flex items-center justify-between">
                <span>Notifications</span>
                <button className="text-xs text-blue-600 hover:underline" onClick={markAllRead}>Mark all read</button>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              {notifications.length === 0 && (
                <div className="p-3 text-xs text-muted-foreground">No notifications</div>
              )}
              {notifications.map(n => (
                <DropdownMenuItem key={n.id} onClick={() => onClickItem(n)} className={!n.read ? 'bg-blue-50/60' : ''}>
                  <span className="text-sm">{n.text}</span>
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
          
          <div className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
              <span className="text-sm font-medium">
                {user?.name?.charAt(0)?.toUpperCase()}
              </span>
            </div>
            <span className="text-sm font-medium text-gray-700">
              {user?.name}
            </span>
          </div>

          <Button variant="ghost" size="sm" onClick={handleLogout}>
            <LogOut className="h-4 w-4 mr-2" />
            Logout
          </Button>
        </div>
      </div>
    </header>
  )
}
