import React, { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Users, BookOpen, DollarSign, TrendingUp, UserPlus, Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { formatCurrency } from '../../lib/utils'
import { getQuizSummary } from '../../lib/quiz'
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import { useLanguage } from '../../contexts/LanguageContext'

export default function AdminDashboard() {
  const { t } = useLanguage()
  const [quizSummary, setQuizSummary] = useState({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 })
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['admin-dashboard'],
    queryFn: async () => {
      const response = await api.get('/dashboard/admin')
      return response.data
    },
  })

  const { stats, enrollment_trends, top_courses, recent_enrollments, quiz_stats } = dashboardData || {}

  useEffect(() => {
    // Use quiz stats from backend instead of making individual API calls
    if (quiz_stats) {
      setQuizSummary({
        coursesWithQuiz: quiz_stats.coursesWithQuiz || 0,
        totalQuestions: quiz_stats.totalQuestions || 0,
        attempts: quiz_stats.totalAttempts || 0
      })
    }
  }, [quiz_stats])

  if (isLoading) {
    return <div>Loading...</div>
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            {t('dashboard.admin.title','Admin Dashboard')}
          </h1>
          <p className="text-gray-600 dark:text-gray-300">
            {t('dashboard.admin.subtitle','Manage your LMS platform and monitor performance')}
          </p>
        </div>
        <div className="flex space-x-2">
          <Link to="/admin/users">
            <Button>
              <UserPlus className="mr-2 h-4 w-4" />
              {t('dashboard.admin.manageUsers','Manage Users')}
            </Button>
          </Link>
          <Link to="/admin/courses">
            <Button variant="outline">
              <Plus className="mr-2 h-4 w-4" />
              {t('dashboard.admin.manageCourses','Manage Courses')}
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('stats.totalUsers','Total Users')}</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_users || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.total_students || 0} {t('stats.students','students')}, {stats?.total_instructors || 0} {t('stats.instructors','instructors')}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('stats.totalCourses','Total Courses')}</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_courses || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.published_courses || 0} {t('stats.published','published')}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('stats.totalEnrollments','Total Enrollments')}</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_enrollments || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.completed_enrollments || 0} {t('stats.completed','completed')}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">{t('stats.totalRevenue','Total Revenue')}</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatCurrency(stats?.total_revenue || 0)}
            </div>
          </CardContent>
        </Card>

        {/* Quizzes Summary */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Quizzes</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{quizSummary.coursesWithQuiz} courses</div>
            <p className="text-xs text-muted-foreground">
              {quizSummary.totalQuestions} questions • {quizSummary.attempts} attempts
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Enrollment Trends */}
        <Card>
          <CardHeader>
            <CardTitle>{t('chart.enrollmentTrends.title','Enrollment Trends')}</CardTitle>
            <CardDescription>{t('chart.enrollmentTrends.subtitle','Monthly enrollment statistics')}</CardDescription>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={enrollment_trends}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="month" />
                <YAxis />
                <Tooltip />
                <Line 
                  type="monotone" 
                  dataKey="enrollments" 
                  stroke="#3b82f6" 
                  strokeWidth={2}
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Top Courses */}
        <Card>
          <CardHeader>
            <CardTitle>{t('topCourses.title','Top Courses')}</CardTitle>
            <CardDescription>{t('topCourses.subtitle','Most enrolled courses')}</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {top_courses?.map((course, index) => (
              <div key={course.id} className="flex items-center space-x-4">
                <div className="flex-shrink-0 w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold">
                  {index + 1}
                </div>
                <div className="flex-1 space-y-1">
                  <h4 className="font-medium text-sm">{course.title}</h4>
                  <p className="text-xs text-muted-foreground">
                    {course.enrollments_count} {t('topCourses.enrollments','enrollments')}
                  </p>
                </div>
                <Link to={`/admin/courses`}>
                  <Button size="sm" variant="outline">View</Button>
                </Link>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      {/* Recent Enrollments */}
      <Card>
        <CardHeader>
          <CardTitle>{t('recentEnrollments.title','Recent Enrollments')}</CardTitle>
          <CardDescription>{t('recentEnrollments.subtitle','Latest student enrollments')}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recent_enrollments?.map((enrollment) => (
              <div key={enrollment.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="flex items-center space-x-4">
                  <div>
                    <h4 className="font-medium">{enrollment.student.name}</h4>
                    <p className="text-sm text-muted-foreground">{enrollment.student.email}</p>
                  </div>
                  <div className="text-sm">
                    enrolled in <span className="font-medium">{enrollment.course.title}</span>
                  </div>
                </div>
                <div className="text-sm text-muted-foreground">
                  {new Date(enrollment.enrolled_at).toLocaleDateString()}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
