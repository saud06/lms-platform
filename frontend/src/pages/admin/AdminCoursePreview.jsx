import { useQuery } from '@tanstack/react-query'
import { useParams, Link } from 'react-router-dom'
import { api } from '../../lib/api'
import { sanitizeHtml } from '../../lib/sanitize'
import { toYouTubeEmbed } from '../../lib/youtube'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { formatCurrency } from '../../lib/utils'
import { useLanguage } from '../../contexts/LanguageContext'

 

export default function AdminCoursePreview() {
  const { id } = useParams()
  const { t } = useLanguage()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-course', id],
    queryFn: async () => {
      const res = await api.get(`/admin/courses/${id}`)
      return res.data
    }
  })

  if (isLoading) return <div>{t('admin.preview.loading', 'Loading course...')}</div>
  if (isError || !data) return <div>{t('admin.preview.loadFailed', 'Failed to load course')}</div>

  const embed = toYouTubeEmbed(data.youtube_url)

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">{t('admin.preview.title', 'Course Preview')}</h1>
          <p className="text-gray-600">{t('admin.preview.subtitle', 'Quick overview of a course')}</p>
        </div>
        <Link to="/admin/courses">
          <Button variant="outline">{t('admin.preview.back', 'Back to Courses')}</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-2xl">{data.title}</CardTitle>
          <CardDescription>
            {data.category} • {data.level} • {data.duration_hours || 0}h
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {data.short_description && (
            <p className="text-gray-700">{data.short_description}</p>
          )}
          {data.description && (
            <div className="prose max-w-none">
              <p className="whitespace-pre-line text-gray-800">{data.description}</p>
            </div>
          )}
          <div className="space-y-2">
            <div className="text-sm font-medium text-gray-900">{t('course.view.learningMaterial', 'Learning Material')}</div>
            {embed && (
              <>
                <div className="relative w-full rounded overflow-hidden" style={{ paddingTop: '56.25%' }}>
                  <iframe
                    className="absolute inset-0 w-full h-full"
                    src={embed}
                    title={t('course.view.videoTitle', 'Course Video')}
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                  />
                </div>
                {data.youtube_url && (
                  <div className="mt-2 text-sm">
                    <a className="text-blue-600 hover:underline" href={data.youtube_url} target="_blank" rel="noreferrer noopener">
                      {t('course.view.watchOnYouTube', 'Watch on YouTube')}
                    </a>
                  </div>
                )}
              </>
            )}
            {data.learning_material ? (
              <div className="prose max-w-none text-gray-800" dangerouslySetInnerHTML={{ __html: sanitizeHtml(data.learning_material) }} />
            ) : (
              <p className="text-muted-foreground">{t('course.view.noLearningMaterial', 'No learning material provided.')}</p>
            )}
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Stat label={t('course.view.instructor', 'Instructor')} value={data.instructor || t('common.unknown', 'Unknown')} />
            <Stat label={t('course.view.status', 'Status')} value={t(`course.status.${data.status}`,'{status}', { status: data.status })} />
            <Stat label={t('course.view.price', 'Price')} value={formatCurrency(Number(data.price) || 0)} />
            <Stat label={t('course.view.enrollments', 'Enrollments')} value={data.enrollments} />
            <Stat label={t('course.view.created', 'Created')} value={new Date(data.createdAt).toLocaleString()} />
            <Stat label={t('course.view.updated', 'Updated')} value={new Date(data.updatedAt).toLocaleString()} />
          </div>
        </CardContent>
      </Card>
    </div>
  )
}

function Stat({ label, value }) {
  return (
    <div className="p-4 border rounded-lg">
      <div className="text-xs uppercase text-gray-500 mb-1">{label}</div>
      <div className="text-sm font-medium text-gray-900">{value}</div>
    </div>
  )
}
