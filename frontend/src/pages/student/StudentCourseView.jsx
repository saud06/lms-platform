import { useParams, Link } from 'react-router-dom'
import { useRef } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { sanitizeHtml } from '../../lib/sanitize'
import { toYouTubeEmbed } from '../../lib/youtube'
import ProgressBar from '../../components/ui/ProgressBar'
import QuizRunner from '../../components/quiz/QuizRunner'
import { useLanguage } from '../../contexts/LanguageContext'

 

export default function StudentCourseView() {
  const { id } = useParams()
  const quizRef = useRef(null)
  const { t } = useLanguage()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['course', id],
    queryFn: async () => (await api.get(`/courses/${id}`)).data,
  })

  const queryClient = useQueryClient()
  const { data: prog } = useQuery({
    queryKey: ['progress', id],
    queryFn: async () => (await api.get(`/student/courses/${id}/progress`)).data,
    enabled: !!id,
  })

  const updateProgress = useMutation({
    mutationFn: async (progress) => (await api.put(`/student/courses/${id}/progress`, { progress })).data,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['progress', id] })
    }
  })

  if (isLoading) return <div>{t('common.loading', 'Loading...')}</div>
  if (isError || !data) return <div>{t('common.failedToLoad', 'Failed to load.')}</div>

  const embed = toYouTubeEmbed(data.youtube_url)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">{data.title}</h1>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => quizRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}>
            {t('course.openQuiz', 'Open Quiz')}
          </Button>
          <Link to="/courses">
            <Button variant="outline">{t('course.backToMyLearning', 'Back to My Learning')}</Button>
          </Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('course.learningMaterial', 'Learning Material')}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Progress */}
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <div className="text-sm font-medium">{t('student.progress.label', 'Your Progress')}</div>
              <div className="text-sm text-gray-600">{(prog?.progress ?? 0)}%</div>
            </div>
            <ProgressBar value={prog?.progress ?? 0} />
            <div className="flex items-center gap-2">
              <Button variant="outline" onClick={() => updateProgress.mutate(Math.max(0, (prog?.progress ?? 0) - 10))}>-10%</Button>
              <Button variant="outline" onClick={() => updateProgress.mutate(Math.min(100, (prog?.progress ?? 0) + 10))}>+10%</Button>
              <Button onClick={() => updateProgress.mutate(100)}>Mark Complete</Button>
            </div>
          </div>

          {embed && (
            <>
              <div className="relative w-full rounded overflow-hidden" style={{ paddingTop: '56.25%' }}>
                <iframe
                  className="absolute inset-0 w-full h-full"
                  src={embed}
                  title="Course Video"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowFullScreen
                />
              </div>
              {data.youtube_url && (
                <div className="mt-2 text-sm">
                  <a className="text-blue-600 hover:underline" href={data.youtube_url} target="_blank" rel="noreferrer noopener">
                    {t('course.watchOnYouTube', 'Watch on YouTube')}
                  </a>
                </div>
              )}
            </>
          )}
          {data.learning_material ? (
            <div className="prose max-w-none text-gray-800" dangerouslySetInnerHTML={{ __html: sanitizeHtml(data.learning_material) }} />
          ) : (
            <p className="text-muted-foreground">{t('course.noLearningMaterial', 'No learning material added yet.')}</p>
          )}

          {/* Quiz */}
          <div ref={quizRef} className="space-y-2">
            <div className="text-sm font-medium">{t('course.quiz', 'Quiz')}</div>
            <QuizRunner courseId={id} />
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
