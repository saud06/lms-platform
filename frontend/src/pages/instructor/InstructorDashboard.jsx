import React, { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { BookOpen, Users, Star, DollarSign, Plus, Eye } from 'lucide-react'
import { Link, useNavigate } from 'react-router-dom'
import { formatCurrency } from '../../lib/utils'
import { getQuizSummary } from '../../lib/quiz'
import { useLanguage } from '../../contexts/LanguageContext'

export default function InstructorDashboard() {
  const navigate = useNavigate()
  const { t } = useLanguage()
  const [quizSummary, setQuizSummary] = useState({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 })
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['instructor-dashboard'],
    queryFn: async () => {
      const response = await api.get('/dashboard/instructor')
      return response.data
    },
  })

  const { stats, course_performance, recent_activity } = dashboardData || {}

  useEffect(() => {
    const ids = (course_performance || []).map(c => c.id)
    if (ids.length) {
      getQuizSummary(ids).then(setQuizSummary).catch(() => setQuizSummary({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 }))
    } else {
      setQuizSummary({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 })
    }
  }, [course_performance])

  if (isLoading) {
    return <div>Loading dashboard...</div>
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Instructor Dashboard</h1>
          <p className="text-gray-600">Manage your courses and track student progress</p>
        </div>
        <div className="flex space-x-2">
          <Button onClick={() => navigate('/instructor/courses', { state: { openCreate: true } })}>
            <Plus className="mr-2 h-4 w-4" />
            Create Course
          </Button>
          <Link to="/instructor/courses">
            <Button variant="outline">
              <Eye className="mr-2 h-4 w-4" />
              My Courses
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Courses</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_courses || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.published_courses || 0} published
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Students</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_students || 0}</div>
            <p className="text-xs text-muted-foreground">
              Across all courses
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Avg. Rating</CardTitle>
            <Star className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {stats?.average_course_rating ? stats.average_course_rating.toFixed(1) : '0.0'}
            </div>
            <p className="text-xs text-muted-foreground">
              Course ratings
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatCurrency(stats?.total_revenue || 0)}
            </div>
          </CardContent>
        </Card>

        {/* Quizzes Summary (local) */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Quizzes</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{quizSummary.coursesWithQuiz} courses</div>
            <p className="text-xs text-muted-foreground">
              {quizSummary.totalQuestions} questions • {quizSummary.attempts} attempts (local)
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Course Performance */}
        <Card>
          <CardHeader>
            <CardTitle>Course Performance</CardTitle>
            <CardDescription>Your course statistics</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {course_performance?.slice(0, 5).map((course) => (
              <div key={course.id} className="space-y-2">
                <div className="flex justify-between items-center">
                  <h4 className="font-medium text-sm">{course.title}</h4>
                  <span className="text-sm text-muted-foreground">
                    {course.enrollments_count} students
                  </span>
                </div>
                <div className="flex justify-between items-center text-xs text-muted-foreground">
                  <span>
                    Completion: {course.completed_enrollments_count}/{course.enrollments_count}
                  </span>
                  <span>
                    Rating: {course.enrollments?.[0]?.avg_rating ? 
                      Number(course.enrollments[0].avg_rating).toFixed(1) : 'N/A'}
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-xs text-muted-foreground">
                    Status: {course.status}
                  </span>
                  <Link to={`/courses/${course.id}`}>
                    <Button variant="ghost" size="sm">View</Button>
                  </Link>
                </div>
              </div>
            ))}
            {(!course_performance || course_performance.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No courses created yet. <button className="text-primary hover:underline" onClick={() => navigate('/instructor/courses', { state: { openCreate: true } })}>Create your first course</button>
              </p>
            )}
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card>
          <CardHeader>
            <CardTitle>Recent Activity</CardTitle>
            <CardDescription>Latest student enrollments and progress</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {recent_activity?.slice(0, 5).map((activity) => (
              <div key={activity.id} className="flex items-center space-x-4 p-3 border rounded-lg">
                <div className="flex-1 space-y-1">
                  <h4 className="font-medium text-sm">{activity.student.name}</h4>
                  <p className="text-xs text-muted-foreground">
                    {activity.completed_at ? 'Completed' : 'Enrolled in'} {activity.course.title}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    Progress: {activity.progress}%
                  </p>
                </div>
                <div className="text-xs text-muted-foreground">
                  {new Date(activity.updated_at).toLocaleDateString()}
                </div>
              </div>
            ))}
            {(!recent_activity || recent_activity.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No recent activity yet.
              </p>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-4">
            <Button onClick={() => navigate('/instructor/courses', { state: { openCreate: true } })}>
              <Plus className="mr-2 h-4 w-4" />
              Create New Course
            </Button>
            <Link to="/instructor/courses">
              <Button variant="outline">
                <BookOpen className="mr-2 h-4 w-4" />
                Manage Courses
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
