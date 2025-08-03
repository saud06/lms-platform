import { Link, useNavigate } from 'react-router-dom'
import { Button } from '../components/ui/button'
import { BookOpen, GraduationCap, ShieldCheck, Sparkles, Menu, X, Moon, Sun } from 'lucide-react'
import { useLanguage } from '../contexts/LanguageContext'
import { useAuth } from '../contexts/AuthContext'
import { useTheme } from '../contexts/ThemeContext'
import { useEffect, useState } from 'react'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../components/ui/dropdown-menu'

export default function LandingPage() {
  const { t, language, setLanguage } = useLanguage()
  const { isAuthenticated } = useAuth()
  const { theme, toggleTheme, setTheme } = useTheme()
  const navigate = useNavigate()
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  // Flag components
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

  useEffect(() => {
    if (isAuthenticated) {
      navigate('/dashboard', { replace: true })
    }
  }, [isAuthenticated, navigate])

  // Update document title based on language
  useEffect(() => {
    document.title = `${t('site.title', 'LMS Platform')} - ${t('site.description', 'Modern Learning Management System')}`
  }, [language, t])

  // Debug theme state
  useEffect(() => {
    console.log('LandingPage theme state:', theme)
    console.log('Document element classes:', document.documentElement.className)
    console.log('toggleTheme function available:', !!toggleTheme)
  }, [theme, toggleTheme])
  return (
    <div 
      className="min-h-screen bg-white dark:bg-gray-900 flex flex-col transition-colors"
      style={{
        backgroundColor: theme === 'dark' ? '#111827' : '#ffffff'
      }}
    >
      {/* Header */}
      <header 
        className="px-4 sm:px-6 py-4 border-b bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 transition-colors"
        style={{
          backgroundColor: theme === 'dark' ? '#1f2937' : '#ffffff',
          borderColor: theme === 'dark' ? '#374151' : '#e5e7eb'
        }}
      >
        <div className="flex items-center justify-between">
          {/* Logo */}
          <div className="flex items-center gap-2">
            <GraduationCap className="h-6 w-6 text-blue-600" />
            <span 
              className="font-semibold text-lg text-gray-900 dark:text-white"
              style={{ color: theme === 'dark' ? '#ffffff' : '#111827' }}
            >
              LMS
            </span>
          </div>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center gap-3">
            {/* Theme Toggle */}
            <button
              onClick={(e) => {
                e.preventDefault()
                e.stopPropagation()
                console.log('Desktop theme toggle clicked, current theme:', theme)
                console.log('toggleTheme function:', toggleTheme)
                if (toggleTheme) {
                  toggleTheme()
                } else {
                  console.error('toggleTheme function not available!')
                }
              }}
              className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              {theme === 'dark' ? (
                <Sun className="h-4 w-4" />
              ) : (
                <Moon className="h-4 w-4" />
              )}
            </button>

            {/* Language Switcher */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="flex items-center gap-2">
                  <CurrentFlag />
                  <span className="text-sm hidden lg:inline">{currentLangLabel}</span>
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

            {isAuthenticated ? (
              <Link to="/dashboard">
                <Button size="sm">{t('landing.goDashboard', 'Go to Dashboard')}</Button>
              </Link>
            ) : (
              <>
                <Link to="/auth/login">
                  <Button variant="outline" size="sm">{t('landing.login', 'Login')}</Button>
                </Link>
                <Link to="/auth/register">
                  <Button size="sm">{t('landing.getStarted', 'Get Started')}</Button>
                </Link>
              </>
            )}
          </div>

          {/* Mobile Menu Button */}
          <div className="md:hidden flex items-center gap-2">
            {/* Theme Toggle - Mobile */}
            <button
              onClick={(e) => {
                e.preventDefault()
                e.stopPropagation()
                console.log('Mobile theme toggle clicked, current theme:', theme)
                console.log('toggleTheme function:', toggleTheme)
                if (toggleTheme) {
                  toggleTheme()
                } else {
                  console.error('toggleTheme function not available!')
                }
              }}
              className="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
            >
              {theme === 'dark' ? (
                <Sun className="h-4 w-4" />
              ) : (
                <Moon className="h-4 w-4" />
              )}
            </button>

            {/* Language Switcher - Mobile */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="flex items-center gap-1 p-2">
                  <CurrentFlag />
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

            <Button
              variant="ghost"
              size="sm"
              className="p-2"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            >
              {isMobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </Button>
          </div>
        </div>

        {/* Mobile Menu */}
        {isMobileMenuOpen && (
          <div className="md:hidden mt-4 pb-4 border-t pt-4">
            <div className="flex flex-col gap-3">
              {isAuthenticated ? (
                <Link to="/dashboard" onClick={() => setIsMobileMenuOpen(false)}>
                  <Button className="w-full">{t('landing.goDashboard', 'Go to Dashboard')}</Button>
                </Link>
              ) : (
                <>
                  <Link to="/auth/login" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button variant="outline" className="w-full">{t('landing.login', 'Login')}</Button>
                  </Link>
                  <Link to="/auth/register" onClick={() => setIsMobileMenuOpen(false)}>
                    <Button className="w-full">{t('landing.getStarted', 'Get Started')}</Button>
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </header>

      {/* Hero */}
      <main className="flex-1">
        <section 
          className="px-6 md:px-10 lg:px-16 py-16 md:py-24 bg-gradient-to-b from-blue-50 to-white dark:from-gray-800 dark:to-gray-900 transition-colors"
          style={{
            background: theme === 'dark' 
              ? 'linear-gradient(to bottom, #1f2937, #111827)' 
              : 'linear-gradient(to bottom, #eff6ff, #ffffff)'
          }}
        >
          <div className="max-w-5xl mx-auto grid md:grid-cols-2 gap-10 items-center">
            <div>
              {/* Badge */}
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-medium mb-6 transition-colors">
                <Sparkles className="h-3.5 w-3.5" />
                <span>{t('landing.badge', 'Modern Learning Platform')}</span>
              </div>

              {/* Main Site Title */}
              <div className="flex items-center gap-4 mb-4">
                <div className="flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg">
                  <GraduationCap className="h-9 w-9 text-white" />
                </div>
                <div>
                  <h1 
                    className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-gray-900 dark:text-white transition-colors"
                    style={{ color: theme === 'dark' ? '#ffffff' : '#111827' }}
                  >
                    {t('site.title', 'LMS Platform')}
                  </h1>
                </div>
              </div>

              {/* Tagline */}
              <h2 
                className="text-xl sm:text-2xl lg:text-3xl font-semibold text-blue-600 dark:text-blue-400 mb-8 transition-colors"
                style={{ color: theme === 'dark' ? '#60a5fa' : '#2563eb' }}
              >
                {t('site.tagline', 'Learn. Build. Grow.')}
              </h2>

              {/* Description */}
              <p 
                className="text-lg sm:text-xl text-gray-600 dark:text-gray-300 leading-relaxed mb-8 max-w-2xl transition-colors"
                style={{ color: theme === 'dark' ? '#d1d5db' : '#4b5563' }}
              >
                {t('landing.hero.subtitle', 'A minimal, fast, and intuitive LMS to accelerate your learning journey. Track progress, take quizzes, and master new skills with ease.')}
              </p>

              {/* Action Buttons */}
              <div className="flex flex-col sm:flex-row gap-4 mb-6">
                <Link to="/auth/register">
                  <Button size="lg" className="w-full sm:w-auto px-8 py-3 text-base font-semibold">
                    {t('landing.createAccount', 'Create account')}
                  </Button>
                </Link>
                <Link to="/auth/login">
                  <Button size="lg" variant="outline" className="w-full sm:w-auto px-8 py-3 text-base font-semibold">
                    {t('landing.signIn', 'Sign in')}
                  </Button>
                </Link>
              </div>

              {/* Free text */}
              <p className="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 transition-colors">
                <span className="w-2 h-2 bg-green-500 rounded-full"></span>
                {t('landing.freeText', 'No credit card required. Free to get started.')}
              </p>
            </div>
            
            {/* Right Side - Enhanced Features Showcase */}
            <div className="relative">
              {/* Background Decoration */}
              <div className="absolute inset-0 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-3xl transform rotate-1 transition-colors"></div>
              
              {/* Main Card */}
              <div 
                className="relative bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 transition-colors"
                style={{
                  backgroundColor: theme === 'dark' ? '#1f2937' : '#ffffff',
                  borderColor: theme === 'dark' ? '#374151' : '#f3f4f6'
                }}
              >
                {/* Header */}
                <div className="text-center mb-6">
                  <div className="inline-flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl mb-3">
                    <Sparkles className="h-6 w-6 text-white" />
                  </div>
                  <h3 
                    className="text-lg font-bold text-gray-900 dark:text-white mb-1 transition-colors"
                    style={{ color: theme === 'dark' ? '#ffffff' : '#111827' }}
                  >
                    {t('landing.features.title', 'Why Choose Our LMS?')}
                  </h3>
                  <p 
                    className="text-sm text-gray-600 dark:text-gray-300 transition-colors"
                    style={{ color: theme === 'dark' ? '#d1d5db' : '#4b5563' }}
                  >
                    {t('landing.features.subtitle', 'Everything you need for modern learning')}
                  </p>
                </div>

                {/* Features List */}
                <div className="space-y-4">
                  <div className="flex items-start gap-3">
                    <div className="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg flex-shrink-0">
                      <BookOpen className="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900 dark:text-white text-sm mb-1 transition-colors">
                        {t('landing.features.learning.title', 'Interactive Learning')}
                      </h4>
                      <p className="text-gray-600 dark:text-gray-300 text-xs leading-relaxed transition-colors">
                        {t('landing.features.learning.desc', 'Interactive courses and quizzes for better learning')}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3">
                    <div className="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg flex-shrink-0">
                      <ShieldCheck className="h-5 w-5 text-green-600" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900 dark:text-white text-sm mb-1 transition-colors">
                        {t('landing.features.certified.title', 'Certified Courses')}
                      </h4>
                      <p className="text-gray-600 dark:text-gray-300 text-xs leading-relaxed transition-colors">
                        {t('landing.features.certified.desc', 'Get certificates upon successful completion')}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-3">
                    <div className="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg flex-shrink-0">
                      <GraduationCap className="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-gray-900 dark:text-white text-sm mb-1 transition-colors">
                        {t('landing.features.secure.title', 'Secure & Reliable')}
                      </h4>
                      <p className="text-gray-600 dark:text-gray-300 text-xs leading-relaxed transition-colors">
                        {t('landing.features.secure.desc', 'Your data is safe and secure with us')}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Bottom CTA */}
                <div className="mt-6 pt-4 border-t border-gray-100 dark:border-gray-600 transition-colors">
                  <div className="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400 transition-colors">
                    <span className="flex items-center gap-1">
                      <span className="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                      {t('landing.features.trusted', 'Trusted by thousands')}
                    </span>
                    <span>•</span>
                    <span className="flex items-center gap-1">
                      <span className="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                      {t('landing.features.support', '24/7 Support')}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Secondary section */}
        <section 
          className="px-6 md:px-10 lg:px-16 py-12 bg-white dark:bg-gray-900 transition-colors"
          style={{ backgroundColor: theme === 'dark' ? '#111827' : '#ffffff' }}
        >
          <div className="max-w-5xl mx-auto grid md:grid-cols-3 gap-6">
            {[{
              title: t('landing.secondary.fast.title', 'Fast and Minimal'),
              desc: t('landing.secondary.fast.desc', 'Clean UI, instant navigation, and distraction‑free learning.')
            },{
              title: t('landing.secondary.teams.title', 'Built for Teams'),
              desc: t('landing.secondary.teams.desc', 'Admin and instructor tools to manage learners and content.')
            },{
              title: t('landing.secondary.growth.title', 'Ready for Growth'),
              desc: t('landing.secondary.growth.desc', 'Scalable foundation to add more features as you need them.')
            }].map((f, i) => (
              <div 
                key={i} 
                className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 transition-colors"
                style={{
                  backgroundColor: theme === 'dark' ? '#1f2937' : '#ffffff',
                  borderColor: theme === 'dark' ? '#374151' : '#e5e7eb'
                }}
              >
                <p 
                  className="font-semibold mb-1 text-gray-900 dark:text-white transition-colors"
                  style={{ color: theme === 'dark' ? '#ffffff' : '#111827' }}
                >
                  {f.title}
                </p>
                <p 
                  className="text-sm text-gray-600 dark:text-gray-300 transition-colors"
                  style={{ color: theme === 'dark' ? '#d1d5db' : '#4b5563' }}
                >
                  {f.desc}
                </p>
              </div>
            ))}
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer 
        className="px-6 md:px-10 lg:px-16 py-8 border-t border-gray-200 dark:border-gray-800 text-sm text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 transition-colors"
        style={{ 
          backgroundColor: theme === 'dark' ? '#111827' : '#ffffff',
          borderColor: theme === 'dark' ? '#374151' : '#e5e7eb',
          color: theme === 'dark' ? '#9ca3af' : '#6b7280'
        }}
      >
        <div className="max-w-5xl mx-auto flex items-center justify-between">
          <span>{t('footer.copyright', '© {year} LMS. All rights reserved.').replace('{year}', new Date().getFullYear())}</span>
          <div className="flex items-center gap-4">
            <Link className="hover:underline" to="/auth/login">{t('footer.signIn', 'Sign in')}</Link>
            <Link className="hover:underline" to="/auth/register">{t('footer.getStarted', 'Get started')}</Link>
          </div>
        </div>
      </footer>
    </div>
  )
}
