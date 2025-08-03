import { useEffect, useMemo, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Plus, Edit, Eye } from 'lucide-react'
import Modal from '../../components/ui/Modal'
import { Input } from '../../components/ui/input'
import { useToast } from '../../hooks/use-toast'
import ReactQuill from 'react-quill'
import 'react-quill/dist/quill.snow.css'
import { toYouTubeEmbed } from '../../lib/youtube'
import { sanitizeHtml } from '../../lib/sanitize'
import QuizEditor from '../../components/quiz/QuizEditor'
import { useLanguage } from '../../contexts/LanguageContext'
import { formatCurrency } from '../../lib/utils'

const validate = (f) => {
  const e = {}
  if (!f.title || f.title.trim().length < 3) e.title = 'Title is required (min 3 characters)'
  if (!f.category || f.category.trim().length < 2) e.category = 'Category is required'
  if (!['draft','published','archived'].includes(f.status)) e.status = 'Status must be draft, published, or archived'
  const priceNum = Number(f.price)
  if (Number.isNaN(priceNum) || priceNum < 0) e.price = 'Price must be a non-negative number'
  return e
}

export default function InstructorCourses() {
  const location = useLocation()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { toast } = useToast()
  const { t } = useLanguage()
  const [isModalOpen, setModalOpen] = useState(false)
  const [isViewOpen, setViewOpen] = useState(false)
  const [isQuizOpen, setQuizOpen] = useState(false)
  const [editingCourse, setEditingCourse] = useState(null)
  const [viewCourse, setViewCourse] = useState(null)
  const [quizCourse, setQuizCourse] = useState(null)
  const [form, setForm] = useState({ title: '', status: 'draft', price: 0, category: 'General', learning_material: '', youtube_url: '' })
  const [errors, setErrors] = useState({})
  const [touched, setTouched] = useState({})
  const [submitted, setSubmitted] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['instructor-courses'],
    queryFn: async () => {
      const res = await api.get('/instructor/courses')
      return res.data
    }
  })

  // If navigated here with state to open Create modal, do so once and clear state
  useEffect(() => {
    if (location.state?.openCreate) {
      openAdd()
      // Clear the state to prevent re-opening on back/forward
      navigate(location.pathname, { replace: true })
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.state])

  const addMutation = useMutation({
    mutationFn: (payload) => api.post('/instructor/courses', payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['instructor-courses'] })
      toast({ title: 'Course added' })
      setErrors({}); setTouched({}); setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: 'Failed to add course', description: data?.message || 'Please fix errors and try again.', variant: 'destructive' })
    }
  })
  const editMutation = useMutation({
    mutationFn: ({ id, payload }) => api.put(`/instructor/courses/${id}`, payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['instructor-courses'] })
      toast({ title: 'Course updated' })
      setErrors({}); setTouched({}); setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: 'Failed to update course', description: data?.message || 'Please fix errors and try again.', variant: 'destructive' })
    }
  })

  const openAdd = () => {
    setEditingCourse(null)
    setForm({ title: '', status: 'draft', price: 0, category: 'General', learning_material: '', youtube_url: '' })
    setErrors({}); setTouched({}); setSubmitted(false)
    setModalOpen(true)
  }
  const openEdit = async (course) => {
    setEditingCourse(course)
    try {
      const res = await api.get(`/instructor/courses/${course.id}`)
      const full = res.data
      setForm({
        title: full.title,
        status: full.status,
        price: Number(full.price) || 0,
        category: full.category || 'General',
        learning_material: full.learning_material || '',
        youtube_url: full.youtube_url || '',
      })
    } catch {
      setForm({
        title: course.title,
        status: course.status,
        price: Number(course.price) || 0,
        category: course.category || 'General',
        learning_material: '',
        youtube_url: '',
      })
    }
    setErrors({}); setTouched({}); setSubmitted(false)
    setModalOpen(true)
  }
  const openView = async (course) => {
    try {
      const res = await api.get(`/instructor/courses/${course.id}`)
      setViewCourse(res.data)
    } catch {
      setViewCourse(course)
    }
    setViewOpen(true)
  }
  const openQuiz = (course) => {
    setQuizCourse(course)
    setQuizOpen(true)
  }
  const onSubmit = async () => {
    setSubmitted(true)
    const v = validate(form)
    setErrors(v)
    if (Object.keys(v).length) return
    const payload = {
      title: form.title.trim(),
      status: form.status,
      price: Number(form.price),
      category: form.category.trim(),
      learning_material: form.learning_material || undefined,
      youtube_url: form.youtube_url?.trim() || undefined,
    }
    if (editingCourse) {
      await editMutation.mutateAsync({ id: editingCourse.id, payload })
    } else {
      await addMutation.mutateAsync(payload)
    }
    setModalOpen(false)
  }

  // Filter list using ?q= from the URL
  const filtered = useMemo(() => {
    const q = new URLSearchParams(location.search).get('q')?.toLowerCase().trim()
    if (!q) return data || []
    return (data || []).filter((c) =>
      c.title?.toLowerCase().includes(q) ||
      c.category?.toLowerCase().includes(q) ||
      String(c.status || '').toLowerCase().includes(q)
    )
  }, [data, location.search])

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold text-gray-900">{t('instructor.courses.myCourses', 'My Courses')}</h1>
        <Button onClick={openAdd}><Plus className="h-4 w-4 mr-2"/>{t('instructor.courses.create', 'Create Course')}</Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('nav.courses', 'Courses')}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && <div>{t('common.loading', 'Loading...')}</div>}
          {!isLoading && (!data || data.length === 0) && (
            <p className="text-sm text-muted-foreground">{t('instructor.courses.empty', 'No courses yet.')}</p>
          )}
          <div className="grid md:grid-cols-2 gap-4">
            {filtered?.map((c) => (
              <div key={c.id} className="border rounded p-4 space-y-2">
                <div className="flex items-center justify-between">
                  <h3 className="font-semibold">{c.title}</h3>
                  <span className="text-xs uppercase bg-gray-100 px-2 py-1 rounded">{c.status}</span>
                </div>
                <p className="text-sm text-gray-600">{c.category}</p>
                <div className="flex gap-2">
                  <Button size="sm" variant="outline" onClick={() => openView(c)}><Eye className="h-4 w-4 mr-1"/>{t('common.view', 'View')}</Button>
                  <Button size="sm" variant="outline" onClick={() => openQuiz(c)}>{t('course.quiz', 'Quiz')}</Button>
                  <Button size="sm" variant="outline" onClick={() => openEdit(c)}><Edit className="h-4 w-4 mr-1"/>{t('common.edit', 'Edit')}</Button>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      <Modal
        open={isModalOpen}
        onClose={() => setModalOpen(false)}
        title={editingCourse ? t('instructor.courses.editTitle', 'Edit Course') : t('instructor.courses.addTitle', 'Add Course')}
        footer={(
          <>
            <Button variant="outline" onClick={() => setModalOpen(false)}>{t('common.cancel', 'Cancel')}</Button>
            <Button onClick={onSubmit} disabled={addMutation.isPending || editMutation.isPending}>
              {editingCourse ? (editMutation.isPending ? t('common.saving', 'Saving...') : t('common.save', 'Save')) : (addMutation.isPending ? t('common.adding', 'Adding...') : t('common.add', 'Add'))}
            </Button>
          </>
        )}
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.title', 'Title')}</label>
            <Input
              value={form.title}
              onChange={(e) => setForm({ ...form, title: e.target.value })}
              onBlur={() => setTouched(prev => ({ ...prev, title: true }))}
              aria-invalid={!!errors.title}
            />
            {errors.title && (touched.title || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.title}</p>)}
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.category', 'Category')}</label>
              <Input
                value={form.category}
                onChange={(e) => setForm({ ...form, category: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, category: true }))}
                aria-invalid={!!errors.category}
              />
              {errors.category && (touched.category || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.category}</p>)}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.status', 'Status')}</label>
              <select
                className="w-full border rounded-md h-10 px-3 text-sm"
                value={form.status}
                onChange={(e) => setForm({ ...form, status: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, status: true }))}
              >
                <option value="draft">{t('course.statusDraft', 'Draft')}</option>
                <option value="published">{t('course.statusPublished', 'Published')}</option>
                <option value="archived">{t('course.statusArchived', 'Archived')}</option>
              </select>
              {errors.status && (touched.status || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.status}</p>)}
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.price', 'Price')}</label>
            <Input
              type="number"
              step="0.01"
              value={form.price}
              onChange={(e) => setForm({ ...form, price: e.target.value })}
              onBlur={() => setTouched(prev => ({ ...prev, price: true }))}
              aria-invalid={!!errors.price}
            />
            {errors.price && (touched.price || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.price}</p>)}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.learningMaterial', 'Learning Material')}</label>
            <ReactQuill
              theme="snow"
              value={form.learning_material}
              onChange={(html) => setForm({ ...form, learning_material: html })}
              placeholder={t('course.learningMaterial.placeholder', 'Add lesson notes, text, and resources...')}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.youtubeUrlLabel', 'YouTube Video URL (optional)')}</label>
            <Input
              placeholder={t('course.youtubeUrlPlaceholder', 'https://www.youtube.com/watch?v=... or https://youtu.be/...')}
              value={form.youtube_url}
              onChange={(e) => setForm({ ...form, youtube_url: e.target.value })}
            />
          </div>
        </div>
      </Modal>

      <Modal
        open={isViewOpen}
        onClose={() => setViewOpen(false)}
        title={viewCourse ? viewCourse.title : 'Course'}
        footer={(
          <>
            <Button variant="outline" onClick={() => setViewOpen(false)}>{t('common.close', 'Close')}</Button>
          </>
        )}
      >
        {viewCourse ? (
          <div className="space-y-4 text-sm">
            <div className="space-y-2">
              <div><span className="font-medium">{t('course.status', 'Status:')}</span> {viewCourse.status}</div>
              <div><span className="font-medium">{t('course.category', 'Category:')}</span> {viewCourse.category}</div>
              <div><span className="font-medium">{t('course.price', 'Price:')}</span> {formatCurrency(Number(viewCourse.price||0))}</div>
              {viewCourse.description && (
                <div>
                  <span className="font-medium">{t('course.description', 'Description:')}</span>
                  <p className="text-gray-700 mt-1 whitespace-pre-wrap">{viewCourse.description}</p>
                </div>
              )}
            </div>

            {/* Video */}
            {!!viewCourse.youtube_url && (() => {
              const embed = toYouTubeEmbed(viewCourse.youtube_url)
              return embed ? (
                <div>
                  <div className="relative w-full rounded overflow-hidden" style={{ paddingTop: '56.25%' }}>
                    <iframe
                      className="absolute inset-0 w-full h-full"
                      src={embed}
                      title="Course Video"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowFullScreen
                    />
                  </div>
                  <div className="mt-2 text-xs">
                    <a className="text-blue-600 hover:underline" href={viewCourse.youtube_url} target="_blank" rel="noreferrer">
                      {t('course.watchOnYouTube', 'Watch on YouTube')}
                    </a>
                  </div>
                </div>
              ) : null
            })()}

            {/* Learning Material */}
            {viewCourse.learning_material ? (
              <div className="prose max-w-none text-gray-800" dangerouslySetInnerHTML={{ __html: sanitizeHtml(viewCourse.learning_material) }} />
            ) : (
              <p className="text-muted-foreground">{t('course.noLearningMaterial', 'No learning material added yet.')}</p>
            )}
          </div>
        ) : (
          <div>{t('common.loading', 'Loading...')}</div>
        )}
      </Modal>

      <Modal
        open={isQuizOpen}
        onClose={() => setQuizOpen(false)}
        title={quizCourse ? `${quizCourse.title} — ${t('course.quiz', 'Quiz')}` : t('course.quiz', 'Quiz')}
        footer={(
          <>
            <Button variant="outline" onClick={() => setQuizOpen(false)}>{t('common.close', 'Close')}</Button>
          </>
        )}
      >
        {quizCourse ? (
          <div className="space-y-4">
            <QuizEditor courseId={quizCourse.id} />
          </div>
        ) : (
          <div>{t('common.loading', 'Loading...')}</div>
        )}
      </Modal>
    </div>
  )
}
