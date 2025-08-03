import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Link, useLocation } from 'react-router-dom'
import { BookOpen } from 'lucide-react'
import { useLanguage } from '../../contexts/LanguageContext'

export default function StudentCourses() {
  const location = useLocation()
  const { t } = useLanguage()
  const { data, isLoading, error } = useQuery({
    queryKey: ['student-courses'],
    queryFn: async () => {
      const res = await api.get('/student/courses')
      return res.data
    },
  })

  const filtered = useMemo(() => {
    const q = new URLSearchParams(location.search).get('q')?.toLowerCase().trim()
    if (!q) return data || []
    return (data || []).filter((enrollment) => {
      const c = enrollment.course || {}
      return (
        c.title?.toLowerCase().includes(q) ||
        c.category?.toLowerCase().includes(q) ||
        String(c.level || '').toLowerCase().includes(q)
      )
    })
  }, [data, location.search])

  if (isLoading) return <div>{t('common.loading', 'Loading...')}</div>
  if (error) return <div className="text-red-600">{t('common.failedToLoad', 'Failed to load.')}</div>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">{t('student.myLearning.title', 'My Learning')}</h1>
          <p className="text-gray-600">{t('student.myLearning.subtitle', 'Your enrolled courses')}</p>
        </div>
        <Link to="/courses">
          <Button variant="outline">{t('student.browseCourses', 'Browse Courses')}</Button>
        </Link>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filtered?.map((enrollment) => (
          <Card key={enrollment.id}>
            <CardHeader>
              <CardTitle className="text-base flex items-center">
                <BookOpen className="h-4 w-4 mr-2" />
                {enrollment.course.title}
              </CardTitle>
              <CardDescription>
                {enrollment.course.category} • {enrollment.course.level}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="mb-3">
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div className="bg-blue-600 h-2 rounded-full" style={{ width: `${enrollment.progress}%` }}></div>
                </div>
                <p className="text-xs text-muted-foreground mt-1">{enrollment.progress}% {t('student.progress.complete', 'complete')}</p>
              </div>
              <Link to={`/courses/${enrollment.course.id}`}>
                <Button size="sm" className="w-full">{t('student.continue', 'Continue')}</Button>
              </Link>
            </CardContent>
          </Card>
        ))}
        {(filtered?.length ?? 0) === 0 && (
          <div className="col-span-full text-center text-muted-foreground">{t('student.noEnrolled', 'No enrolled courses yet.')}</div>
        )}
      </div>
    </div>
  )
}
