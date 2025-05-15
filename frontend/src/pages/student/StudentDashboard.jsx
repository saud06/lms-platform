import React, { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { BookOpen, Clock, Star, Play, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import { formatDuration } from '../../lib/utils'
import { getQuizSummary } from '../../lib/quiz'

export default function StudentDashboard() {
  const [quizSummary, setQuizSummary] = useState({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 })
  const { data: dashboardData, isLoading } = useQuery({
    queryKey: ['student-dashboard'],
    queryFn: async () => {
      const response = await api.get('/dashboard/student')
      return response.data
    },
  })

  const { stats, enrolled_courses, recommended_courses, recent_progress } = dashboardData || {}

  useEffect(() => {
    const ids = (enrolled_courses || []).map(e => e.course?.id).filter(Boolean)
    if (ids.length) {
      getQuizSummary(ids).then(setQuizSummary).catch(() => setQuizSummary({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 }))
    } else {
      setQuizSummary({ coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 })
    }
  }, [enrolled_courses])

  if (isLoading) {
    return <div>Loading dashboard...</div>
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Student Dashboard</h1>
          <p className="text-gray-600">Continue your learning journey</p>
        </div>
        <div className="flex space-x-2">
          <Link to="/courses">
            <Button>
              <Search className="mr-2 h-4 w-4" />
              Browse Courses
            </Button>
          </Link>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Enrolled Courses</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.enrolled_courses || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.completed_courses || 0} completed
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Learning Hours</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_learning_hours || 0}h</div>
            <p className="text-xs text-muted-foreground">
              This month
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
              {stats?.average_rating ? stats.average_rating.toFixed(1) : '0.0'}
            </div>
            <p className="text-xs text-muted-foreground">
              Course ratings
            </p>
          </CardContent>
        </Card>
        
        {/* Quizzes Summary (local) */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Quizzes</CardTitle>
            <BookOpen className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{quizSummary.attempts} attempts</div>
            <p className="text-xs text-muted-foreground">
              {quizSummary.coursesWithQuiz} courses • {quizSummary.totalQuestions} questions
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Continue Learning */}
        <Card>
          <CardHeader>
            <CardTitle>Continue Learning</CardTitle>
            <CardDescription>Pick up where you left off</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {enrolled_courses?.slice(0, 3).map((enrollment) => (
              <div key={enrollment.id} className="flex items-center space-x-4 p-3 border rounded-lg">
                <div className="flex-1 space-y-2">
                  <h4 className="font-medium text-sm">{enrollment.course.title}</h4>
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      className="bg-blue-600 h-2 rounded-full" 
                      style={{ width: `${enrollment.progress}%` }}
                    ></div>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {enrollment.progress}% complete
                  </p>
                </div>
                <Link to={`/courses/${enrollment.course.id}`}>
                  <Button size="sm">
                    <Play className="mr-2 h-4 w-4" />
                    Continue
                  </Button>
                </Link>
              </div>
            ))}
            {(!enrolled_courses || enrolled_courses.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No enrolled courses yet. <Link to="/courses" className="text-primary hover:underline">Browse courses</Link>
              </p>
            )}
          </CardContent>
        </Card>

        {/* Recommended Courses */}
        <Card>
          <CardHeader>
            <CardTitle>Recommended for You</CardTitle>
            <CardDescription>Based on your interests</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {recommended_courses?.slice(0, 3).map((course) => (
              <div key={course.id} className="flex items-center space-x-4 p-3 border rounded-lg">
                <div className="flex-1 space-y-1">
                  <h4 className="font-medium text-sm">{course.title}</h4>
                  <p className="text-xs text-muted-foreground">
                    {course.instructor.name} • {formatDuration(course.duration)}
                  </p>
                  <div className="flex items-center space-x-2">
                    <div className="flex items-center">
                      <Star className="h-3 w-3 text-yellow-400 fill-current" />
                      <span className="text-xs ml-1">{course.rating || '0.0'}</span>
                    </div>
                    <span className="text-xs text-muted-foreground">
                      {course.enrollments_count} students
                    </span>
                  </div>
                </div>
                <Link to={`/courses/${course.id}`}>
                  <Button size="sm" variant="outline">View</Button>
                </Link>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      {/* Recent Progress */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Activity</CardTitle>
          <CardDescription>Your learning progress</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recent_progress?.map((progress) => (
              <div key={progress.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="flex items-center space-x-4">
                  <div>
                    <h4 className="font-medium">{progress.lesson.title}</h4>
                    <p className="text-sm text-muted-foreground">{progress.course.title}</p>
                  </div>
                  <div className="text-sm">
                    {progress.completed_at ? (
                      <span className="text-green-600 font-medium">Completed</span>
                    ) : (
                      <span className="text-blue-600 font-medium">In Progress</span>
                    )}
                  </div>
                </div>
                <div className="text-sm text-muted-foreground">
                  {new Date(progress.updated_at).toLocaleDateString()}
                </div>
              </div>
            ))}
            {(!recent_progress || recent_progress.length === 0) && (
              <p className="text-muted-foreground text-center py-4">
                No recent activity yet.
              </p>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
