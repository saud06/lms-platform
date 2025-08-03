import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useAuth } from '../../contexts/AuthContext'
import { useTheme } from '../../contexts/ThemeContext'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Label } from '../../components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { useToast } from '../../hooks/use-toast'
import { useLanguage } from '../../contexts/LanguageContext'

const loginSchema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
})

export default function LoginPage() {
  const [isLoading, setIsLoading] = useState(false)
  const { login } = useAuth()
  const { theme } = useTheme()
  const navigate = useNavigate()
  const { toast } = useToast()
  const { t } = useLanguage()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(loginSchema),
  })

  const onSubmit = async (data) => {
    setIsLoading(true)
    try {
      await login(data.email, data.password)
      toast({
        title: t('auth.login.successTitle', 'Success'),
        description: t('auth.login.successDesc', 'Logged in successfully'),
      })
      navigate('/dashboard')
    } catch (error) {
      console.error('LoginPage - Authentication failed:', error)
      toast({
        title: t('auth.login.errorTitle', 'Error'),
        description: error.response?.data?.message || t('auth.login.errorDesc', 'Login failed'),
        variant: 'destructive',
      })
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Card 
      className="w-full max-w-md"
      style={{
        backgroundColor: theme === 'dark' ? '#1f2937' : '#ffffff',
        borderColor: theme === 'dark' ? '#374151' : '#e5e7eb'
      }}
    >
      <CardHeader>
        <CardTitle>{t('auth.login.title', 'Sign In')}</CardTitle>
        <CardDescription>
          {t('auth.login.subtitle', 'Enter your credentials to access your account')}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">{t('auth.login.email', 'Email')}</Label>
            <Input
              id="email"
              type="email"
              placeholder={t('auth.login.emailPlaceholder', 'Enter your email')}
              {...register('email')}
            />
            {errors.email && (
              <p className="text-sm text-red-600">{errors.email.message}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">{t('auth.login.password', 'Password')}</Label>
            <Input
              id="password"
              type="password"
              placeholder={t('auth.login.passwordPlaceholder', 'Enter your password')}
              {...register('password')}
            />
            {errors.password && (
              <p className="text-sm text-red-600">{errors.password.message}</p>
            )}
          </div>
          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? t('auth.login.signingIn', 'Signing in...') : t('auth.login.button', 'Sign In')}
          </Button>
        </form>
        <div className="mt-4 text-center text-sm">
          {t('auth.login.noAccount', "Don't have an account?")}{' '}
          <Link to="/auth/register" className="text-blue-600 hover:underline">
            {t('auth.login.signUp', 'Sign up')}
          </Link>
        </div>
      </CardContent>
    </Card>
  )
}
