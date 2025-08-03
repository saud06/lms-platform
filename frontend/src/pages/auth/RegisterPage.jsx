import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useAuth } from '../../contexts/AuthContext'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Label } from '../../components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { useToast } from '../../hooks/use-toast'
import { useLanguage } from '../../contexts/LanguageContext'
import { useTheme } from '../../contexts/ThemeContext'

const registerSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
  password_confirmation: z.string(),
  role: z.enum(['student', 'instructor'], {
    required_error: 'Please select a role',
  }),
}).refine((data) => data.password === data.password_confirmation, {
  message: "Passwords don't match",
  path: ["password_confirmation"],
})

export default function RegisterPage() {
  const [isLoading, setIsLoading] = useState(false)
  const { register: registerUser } = useAuth()
  const { theme } = useTheme()
  const navigate = useNavigate()
  const { toast } = useToast()
  const { t } = useLanguage()

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(registerSchema),
  })

  const onSubmit = async (data) => {
    setIsLoading(true)
    try {
      await registerUser(data)
      toast({
        title: t('auth.register.successTitle', 'Success'),
        description: t('auth.register.successDesc', 'Account created successfully'),
      })
      navigate('/')
    } catch (error) {
      toast({
        title: t('auth.register.errorTitle', 'Error'),
        description: error.response?.data?.message || t('auth.register.errorDesc', 'Registration failed'),
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
        <CardTitle>{t('auth.register.title', 'Create Account')}</CardTitle>
        <CardDescription>
          {t('auth.register.signupDesc', 'Sign up to get started with the LMS platform')}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">{t('auth.register.fullName', 'Full Name')}</Label>
            <Input
              id="name"
              type="text"
              placeholder={t('auth.register.namePlaceholder', 'Enter your full name')}
              {...register('name')}
            />
            {errors.name && (
              <p className="text-sm text-red-600">{errors.name.message}</p>
            )}
          </div>

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

          <div className="space-y-2">
            <Label htmlFor="password_confirmation">{t('auth.register.confirmPassword', 'Confirm Password')}</Label>
            <Input
              id="password_confirmation"
              type="password"
              placeholder={t('auth.register.confirmPasswordPlaceholder', 'Confirm your password')}
              {...register('password_confirmation')}
            />
            {errors.password_confirmation && (
              <p className="text-sm text-red-600">{errors.password_confirmation.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="role">{t('auth.register.role', 'Role')}</Label>
            <select
              id="role"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              {...register('role')}
            >
              <option value="">{t('auth.register.selectRole', 'Select a role')}</option>
              <option value="student">{t('auth.register.student', 'Student')}</option>
              <option value="instructor">{t('auth.register.instructor', 'Instructor')}</option>
            </select>
            {errors.role && (
              <p className="text-sm text-red-600">{errors.role.message}</p>
            )}
          </div>

          <Button type="submit" className="w-full" disabled={isLoading}>
            {isLoading ? t('auth.register.creating', 'Creating account...') : t('auth.register.button', 'Create Account')}
          </Button>
        </form>

        <div className="mt-4 text-center text-sm">
          {t('auth.register.hasAccount', 'Already have an account?')}{' '}
          <Link to="/auth/login" className="text-primary hover:underline">
            {t('auth.register.signIn', 'Sign in')}
          </Link>
        </div>
      </CardContent>
    </Card>
  )
}
