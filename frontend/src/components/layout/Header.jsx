import { useState } from 'react'
import { useAuth } from '../../contexts/AuthContext'
import { useTheme } from '../../contexts/ThemeContext'
import { Bell, Search, LogOut, Moon, Sun } from 'lucide-react'
import { Button } from '../ui/button'
import { Input } from '../ui/input'
import { useNavigate } from 'react-router-dom'
import { useLanguage } from '../../contexts/LanguageContext'
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
  const { theme, toggleTheme } = useTheme()
  const navigate = useNavigate()
  const { language, setLanguage, t } = useLanguage()
  const [term, setTerm] = useState('')
  const [notifications, setNotifications] = useState([
    { id: 1, key: 'notifications.items.welcome', fallback: 'Welcome to the LMS!', href: '/dashboard', read: false },
    { id: 2, key: 'notifications.items.newQuiz', fallback: 'New quiz attempts are available to review.', href: '/instructor/quizzes', read: false },
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

  const FlagUS = () => (
    <svg width="16" height="12" viewBox="0 0 19 10" aria-hidden>
      <rect width="19" height="10" fill="#b22234"/>
      <g fill="#fff">
        <rect y="1" width="19" height="1"/>
        <rect y="3" width="19" height="1"/>
        <rect y="5" width="19" height="1"/>
        <rect y="7" width="19" height="1"/>
        <rect y="9" width="19" height="1"/>
      </g>
      <rect width="8" height="6" fill="#3c3b6e"/>
    </svg>
  )
  const FlagDE = () => (
    <svg width="16" height="12" viewBox="0 0 3 2" aria-hidden>
      <rect width="3" height="2" fill="#000"/>
      <rect y="0.666" width="3" height="0.667" fill="#dd0000"/>
      <rect y="1.333" width="3" height="0.667" fill="#ffce00"/>
    </svg>
  )
  const CurrentFlag = language === 'de' ? FlagDE : FlagUS
  const currentLangLabel = language === 'de' ? t('header.german','Deutsch (EUR)') : t('header.english','English (USD)')

  return (
    <header className="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 px-6 py-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              type="text"
              placeholder={t('search.placeholder','Search...')}
              className="pl-10 w-64"
              value={term}
              onChange={(e) => setTerm(e.target.value)}
              onKeyDown={(e) => { if (e.key === 'Enter') handleSearch() }}
            />
          </div>
        </div>

        <div className="flex items-center space-x-4">
          {/* Theme Toggle */}
          <Button
            variant="ghost"
            size="sm"
            onClick={toggleTheme}
            className="p-2"
          >
            {theme === 'dark' ? (
              <Sun className="h-4 w-4" />
            ) : (
              <Moon className="h-4 w-4" />
            )}
          </Button>

          {/* Language Switcher */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="sm" className="flex items-center gap-2">
                <CurrentFlag />
                <span className="text-sm">{currentLangLabel}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-40">
              <DropdownMenuLabel>{t('header.language','Language')}</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => setLanguage('en')}>
                <span className="mr-2 inline-flex"><FlagUS /></span> {t('header.english','English (USD)')}
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => setLanguage('de')}>
                <span className="mr-2 inline-flex"><FlagDE /></span> {t('header.german','Deutsch (EUR)')}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

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
                <span>{t('notifications.title','Notifications')}</span>
                <button className="text-xs text-blue-600 hover:underline" onClick={markAllRead}>{t('notifications.markAllRead','Mark all read')}</button>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              {notifications.length === 0 && (
                <div className="p-3 text-xs text-muted-foreground">{t('notifications.empty','No notifications')}</div>
              )}
              {notifications.map(n => (
                <DropdownMenuItem key={n.id} onClick={() => onClickItem(n)} className={!n.read ? 'bg-blue-50/60' : ''}>
                  <span className="text-sm">{t(n.key, n.fallback)}</span>
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
          
          <div className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
              <span className="text-sm font-medium text-gray-700 dark:text-gray-200">
                {user?.name?.charAt(0)?.toUpperCase()}
              </span>
            </div>
            <span className="text-sm font-medium text-gray-700 dark:text-gray-200">
              {user?.name}
            </span>
          </div>

          <Button variant="ghost" size="sm" onClick={handleLogout}>
            <LogOut className="h-4 w-4 mr-2" />
            {t('header.logout','Logout')}
          </Button>
        </div>
      </div>
    </header>
  )
}
