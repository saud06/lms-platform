import { useQuery } from '@tanstack/react-query'
import { useParams, Link } from 'react-router-dom'
import { useRef } from 'react'
import { api } from '../../lib/api'
import { sanitizeHtml } from '../../lib/sanitize'
import { toYouTubeEmbed } from '../../lib/youtube'
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import QuizEditor from '../../components/quiz/QuizEditor'
import { useLanguage } from '../../contexts/LanguageContext'
import { formatCurrency } from '../../lib/utils'

 

export default function InstructorCourseView() {
  const { id } = useParams()
  const quizRef = useRef(null)
  const { t } = useLanguage()
  const { data, isLoading, error } = useQuery({
    queryKey: ['instructor-course', id],
    queryFn: async () => {
      const res = await api.get(`/instructor/courses/${id}`)
      return res.data
    }
  })

  if (isLoading) return <div>{t('common.loading', 'Loading...')}</div>
  if (error) return <div className="text-red-600">{t('common.failedToLoad', 'Failed to load.')}</div>

  const embed = toYouTubeEmbed(data.youtube_url)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">{data.title}</h1>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => quizRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}>
            {t('course.openQuiz', 'Open Quiz')}
          </Button>
          <Link to={`/instructor/courses/${id}/edit`}>
            <Button variant="outline">{t('common.edit', 'Edit')}</Button>
          </Link>
          <Link to="/instructor/courses">
            <Button variant="secondary">{t('common.back', 'Back')}</Button>
          </Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('course.courseDetails', 'Course Details')}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div><span className="font-medium">{t('course.status', 'Status:')}</span> {data.status}</div>
          <div><span className="font-medium">{t('course.category', 'Category:')}</span> {data.category}</div>
          <div><span className="font-medium">{t('course.level', 'Level:')}</span> {data.level}</div>
          <div><span className="font-medium">{t('course.price', 'Price:')}</span> {formatCurrency(Number(data.price || 0))}</div>
          <div><span className="font-medium">{t('course.enrollments', 'Enrollments:')}</span> {data.enrollments}</div>
          <div>
            <span className="font-medium">{t('course.description', 'Description:')}</span>
            <p className="text-gray-700 mt-1 whitespace-pre-wrap">{data.description}</p>
          </div>
          <div className="pt-2">
            <span className="font-medium">{t('course.learningMaterial', 'Learning Material')}</span>
            {embed && (
              <div className="relative w-full mt-2 rounded overflow-hidden" style={{ paddingTop: '56.25%' }}>
                <iframe
                  className="absolute inset-0 w-full h-full"
                  src={embed}
                  title="Course Video"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowFullScreen
                />
              </div>
            )}
            {data.learning_material ? (
              <div className="prose max-w-none text-gray-800 mt-2" dangerouslySetInnerHTML={{ __html: sanitizeHtml(data.learning_material) }} />
            ) : (
              <p className="text-muted-foreground mt-2">{t('course.noLearningMaterial', 'No learning material added yet.')}</p>
            )}
          </div>
        </CardContent>
      </Card>

      <Card ref={quizRef}>
        <CardHeader>
          <CardTitle>{t('course.quiz', 'Quiz')}</CardTitle>
        </CardHeader>
        <CardContent>
          <QuizEditor courseId={id} />
        </CardContent>
      </Card>
    </div>
  )
}
