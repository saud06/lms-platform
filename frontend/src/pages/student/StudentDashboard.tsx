import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Button } from '@/components/ui/button'
import { BookOpen, Clock, Award, TrendingUp } from 'lucide-react'
import { Link } from 'react-router-dom'
import { formatCurrency } from '@/lib/utils'

export function StudentDashboard() {
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['student-dashboard'],
    queryFn: async () => {
      const response = await api.get('/dashboard/student')
      return response.data
    },
  })

  if (isLoading) {
    return <div>Loading dashboard...</div>
  }

  const { stats, learning_progress, recommended_courses } = dashboardData || {}

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Student Dashboard</h1>
        <p className="text-gray-600">Track your learning progress and discover new courses</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Enrolled Courses</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.enrolled_courses || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Completed</CardTitle>
            <Award className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.completed_courses || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Certificates</CardTitle>
            <Award className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.certificates_earned || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Study Hours</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_study_hours || 0}h</div>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Learning Progress */}
        <Card>
          <CardHeader>
            <CardTitle>Learning Progress</CardTitle>
            <CardDescription>Your current course progress</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {learning_progress?.slice(0, 5).map((enrollment: any) => (
              <div key={enrollment.id} className="space-y-2">
                <div className="flex justify-between items-center">
                  <h4 className="font-medium text-sm">{enrollment.course.title}</h4>
                  <span className="text-sm text-muted-foreground">{enrollment.progress}%</span>
                </div>
                <Progress value={enrollment.progress} className="h-2" />
                <div className="flex justify-between items-center text-xs text-muted-foreground">
                  <span>Instructor: {enrollment.course.instructor.name}</span>
                  <Link to={`/courses/${enrollment.course.id}`}>
                    <Button variant="ghost" size="sm">Continue</Button>
                  </Link>
                </div>
              </div>
            ))}
            {(!learning_progress || learning_progress.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No enrolled courses yet. <Link to="/courses" className="text-primary hover:underline">Browse courses</Link>
              </p>
            )}
          </CardContent>
        </Card>

        {/* Recommended Courses */}
        <Card>
          <CardHeader>
            <CardTitle>Recommended Courses</CardTitle>
            <CardDescription>Courses you might be interested in</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {recommended_courses?.slice(0, 3).map((course: any) => (
              <div key={course.id} className="flex items-center space-x-4 p-3 border rounded-lg">
                <div className="flex-1 space-y-1">
                  <h4 className="font-medium text-sm">{course.title}</h4>
                  <p className="text-xs text-muted-foreground line-clamp-2">
                    {course.short_description}
                  </p>
                  <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                    <span>{course.level}</span>
                    <span>•</span>
                    <span>{formatCurrency(course.price)}</span>
                  </div>
                </div>
                <Link to={`/courses/${course.id}`}>
                  <Button size="sm">View</Button>
                </Link>
              </div>
            ))}
            {(!recommended_courses || recommended_courses.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No recommendations available yet.
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
            <Link to="/courses">
              <Button>
                <BookOpen className="mr-2 h-4 w-4" />
                Browse Courses
              </Button>
            </Link>
            <Link to="/my-courses">
              <Button variant="outline">
                <TrendingUp className="mr-2 h-4 w-4" />
                My Learning
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
