import { useEffect, useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useToast } from '../../hooks/use-toast'
import { useLocation, useNavigate } from 'react-router-dom'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { formatCurrency } from '../../lib/utils'
import { Button } from '../../components/ui/button'
import ReactQuill from 'react-quill'
import 'react-quill/dist/quill.snow.css'
import { Input } from '../../components/ui/input'
import { Badge } from '../../components/ui/badge'
import { BookOpen, Search, Plus, Edit, Trash2, Eye } from 'lucide-react'
import Modal from '../../components/ui/Modal'
import { toYouTubeEmbed } from '../../lib/youtube'
import { sanitizeHtml } from '../../lib/sanitize'
import QuizEditor from '../../components/quiz/QuizEditor'
import { useLanguage } from '../../contexts/LanguageContext'

export default function ManageCourses() {
  const navigate = useNavigate()
  const location = useLocation()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [isModalOpen, setModalOpen] = useState(false)
  const [isViewOpen, setViewOpen] = useState(false)
  const [isQuizOpen, setQuizOpen] = useState(false)
  const [viewCourse, setViewCourse] = useState(null)
  const [quizCourse, setQuizCourse] = useState(null)
  const [editingCourse, setEditingCourse] = useState(null)
  const [form, setForm] = useState({ title: '', instructor: '', status: 'draft', price: 0, category: 'General', learning_material: '', youtube_url: '' })
  const [errors, setErrors] = useState({})
  const [touched, setTouched] = useState({})
  const [submitted, setSubmitted] = useState(false)
  const { toast } = useToast()
  const { t } = useLanguage()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-courses'],
    queryFn: async () => {
      const res = await api.get('/admin/courses')
      return res.data
    }
  })

  // Initialize search from query param ?q=
  useEffect(() => {
    const params = new URLSearchParams(location.search)
    const q = params.get('q') || ''
    if (q) setSearchTerm(q)
  }, [location.search])

  const addMutation = useMutation({
    mutationFn: (payload) => api.post('/admin/courses', payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: t('admin.manageCourses.toast.addedTitle', 'Course added'), description: t('admin.manageCourses.toast.addedDesc', '{title} has been created.', { title: form.title }) })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: t('admin.manageCourses.toast.addFailedTitle', 'Failed to add course'), description: data?.message || t('common.fixErrors', 'Please fix errors and try again.'), variant: 'destructive' })
    }
  })
  const editMutation = useMutation({
    mutationFn: ({ id, payload }) => api.put(`/admin/courses/${id}`, payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: t('admin.manageCourses.toast.updatedTitle', 'Course updated'), description: t('admin.manageCourses.toast.updatedDesc', '{title} has been saved.', { title: form.title }) })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: t('admin.manageCourses.toast.updateFailedTitle', 'Failed to update course'), description: data?.message || t('common.fixErrors', 'Please fix errors and try again.'), variant: 'destructive' })
    }
  })
  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/admin/courses/${id}`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: t('admin.manageCourses.toast.deletedTitle', 'Course deleted'), description: t('admin.manageCourses.toast.deletedDesc', 'The course has been removed.') })
    }
  })

  const courses = (data || [])
  const filteredCourses = courses.filter(course =>
    course.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    course.instructor.toLowerCase().includes(searchTerm.toLowerCase()) ||
    course.category.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getStatusBadgeColor = (status) => {
    switch (status) {
      case 'published': return 'bg-green-100 text-green-800'
      case 'draft': return 'bg-yellow-100 text-yellow-800'
      case 'archived': return 'bg-gray-100 text-gray-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const openAdd = () => {
    setEditingCourse(null)
    setForm({ title: '', instructor: '', status: 'draft', price: 0, category: 'General', learning_material: '', youtube_url: '' })
    setErrors({})
    setTouched({})
    setSubmitted(false)
    setModalOpen(true)
  }

  const openView = async (course) => {
    try {
      const res = await api.get(`/admin/courses/${course.id}`)
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

  const openEdit = async (course) => {
    setEditingCourse(course)
    try {
      const res = await api.get(`/admin/courses/${course.id}`)
      const full = res.data
      setForm({
        title: full.title,
        instructor: full.instructor || '',
        status: full.status,
        price: Number(full.price) || 0,
        category: full.category || 'General',
        learning_material: full.learning_material || '',
        youtube_url: full.youtube_url || '',
      })
    } catch {
      setForm({
        title: course.title,
        instructor: course.instructor || '',
        status: course.status,
        price: Number(course.price) || 0,
        category: course.category || 'General',
        learning_material: '',
        youtube_url: '',
      })
    }
    setErrors({})
    setTouched({})
    setSubmitted(false)
    setModalOpen(true)
  }

  const validateCourse = (f) => {
    const e = {}
    if (!f.title || f.title.trim().length < 3) e.title = t('validation.titleRequired', 'Title is required (min 3 characters)')
    if (!f.instructor || f.instructor.trim().length < 2) e.instructor = t('validation.instructorRequired', 'Instructor is required')
    if (!f.category || f.category.trim().length < 2) e.category = t('validation.categoryRequired', 'Category is required')
    if (!['draft','published','archived'].includes(f.status)) e.status = t('validation.statusInvalid', 'Status must be draft, published, or archived')
    const priceNum = Number(f.price)
    if (Number.isNaN(priceNum) || priceNum < 0) e.price = t('validation.priceInvalid', 'Price must be a non-negative number')
    return e
  }

  const onSubmit = async () => {
    setSubmitted(true)
    const v = validateCourse(form)
    setErrors(v)
    if (Object.keys(v).length) return
    const payload = { ...form, price: Number(form.price) }
    if (editingCourse) {
      await editMutation.mutateAsync({ id: editingCourse.id, payload })
    } else {
      await addMutation.mutateAsync(payload)
    }
    setModalOpen(false)
  }

  const onDelete = async (course) => {
    if (!confirm(t('admin.manageCourses.confirmDelete', 'Delete {title}?', { title: course.title }))) return
    await deleteMutation.mutateAsync(course.id)
  }

  if (isLoading) return <div>{t('admin.manageCourses.loading', 'Loading courses...')}</div>
  if (isError) return <div>{t('admin.manageCourses.loadFailed', 'Failed to load courses')}</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{t('admin.manageCourses.title', 'Manage Courses')}</h1>
          <p className="text-gray-600 dark:text-gray-300">{t('admin.manageCourses.subtitle', 'Manage platform courses and content')}</p>
        </div>
        <Button onClick={openAdd} disabled={addMutation.isPending}>
          <Plus className="mr-2 h-4 w-4" />
          {t('admin.manageCourses.addCourse', 'Add Course')}
        </Button>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <BookOpen className="mr-2 h-5 w-5" />
            {t('admin.manageCourses.courses', 'Courses')} ({filteredCourses.length})
          </CardTitle>
          <CardDescription>{t('admin.manageCourses.searchManageDesc', 'Search and manage all platform courses')}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center space-x-4 mb-6">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                type="text"
                placeholder={t('admin.manageCourses.searchPlaceholder', 'Search courses by title, instructor, or category...')}
                className="pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>

          {/* Courses Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.course', 'Course')}</th>
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.instructor', 'Instructor')}</th>
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.status', 'Status')}</th>
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.enrollments', 'Enrollments')}</th>
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.price', 'Price')}</th>
                  <th className="text-left py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.created', 'Created')}</th>
                  <th className="text-right py-3 px-4 font-medium text-gray-900 dark:text-white">{t('admin.manageCourses.table.actions', 'Actions')}</th>
                </tr>
              </thead>
              <tbody>
                {filteredCourses.map((course) => (
                  <tr key={course.id} className="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <td className="py-4 px-4">
                      <div>
                        <div className="font-medium text-gray-900 dark:text-white">{course.title}</div>
                        <div className="text-sm text-gray-500 dark:text-gray-400">{course.category}</div>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900 dark:text-white">
                      {course.instructor}
                    </td>
                    <td className="py-4 px-4">
                      <Badge className={getStatusBadgeColor(course.status)}>
                        {t(`course.status.${course.status}`,'{status}', { status: course.status })}
                      </Badge>
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900 dark:text-white">
                      {course.enrollments}
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900 dark:text-white">
                      {formatCurrency(Number(course.price) || 0)}
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-500 dark:text-gray-400">
                      {new Date(course.createdAt).toLocaleDateString()}
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex justify-end space-x-2">
                        <Button size="sm" variant="outline" onClick={() => openView(course)}>
                          <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => openQuiz(course)}>
                          {t('admin.manageCourses.quiz', 'Quiz')}
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => openEdit(course)} disabled={editMutation.isPending}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="outline" className="text-red-600 hover:text-red-700" onClick={() => onDelete(course)} disabled={deleteMutation.isPending}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Modal
        open={isModalOpen}
        onClose={() => setModalOpen(false)}
        title={editingCourse ? t('admin.manageCourses.editCourse', 'Edit Course') : t('admin.manageCourses.addCourse', 'Add Course')}
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
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.title', 'Title')}</label>
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
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.instructor', 'Instructor')}</label>
              <Input
                value={form.instructor}
                onChange={(e) => setForm({ ...form, instructor: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, instructor: true }))}
                aria-invalid={!!errors.instructor}
              />
              {errors.instructor && (touched.instructor || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.instructor}</p>)}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.category', 'Category')}</label>
              <Input
                value={form.category}
                onChange={(e) => setForm({ ...form, category: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, category: true }))}
                aria-invalid={!!errors.category}
              />
              {errors.category && (touched.category || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.category}</p>)}
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.status', 'Status')}</label>
              <select
                className="w-full border rounded-md h-10 px-3 text-sm"
                value={form.status}
                onChange={(e) => setForm({ ...form, status: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, status: true }))}
              >
                <option value="draft">{t('course.status.draft', 'Draft')}</option>
                <option value="published">{t('course.status.published', 'Published')}</option>
                <option value="archived">{t('course.status.archived', 'Archived')}</option>
              </select>
              {errors.status && (touched.status || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.status}</p>)}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.price', 'Price')}</label>
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
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.learningMaterial', 'Learning Material')}</label>
            <ReactQuill
              theme="snow"
              value={form.learning_material}
              onChange={(html) => setForm({ ...form, learning_material: html })}
              placeholder={t('course.form.learningMaterial.placeholder', 'Add lesson notes, text, and resources...')}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course.form.youtubeUrl', 'YouTube Video URL (optional)')}</label>
            <Input
              placeholder={t('course.form.youtubeUrl.placeholder', 'https://www.youtube.com/watch?v=... or https://youtu.be/...')}
              value={form.youtube_url}
              onChange={(e) => setForm({ ...form, youtube_url: e.target.value })}
            />
          </div>
        </div>
      </Modal>

      <Modal
        open={isViewOpen}
        onClose={() => setViewOpen(false)}
        title={viewCourse ? viewCourse.title : t('course.title', 'Course')}
        footer={<Button variant="outline" onClick={() => setViewOpen(false)}>{t('common.close', 'Close')}</Button>}
      >
        {viewCourse ? (
          <div className="space-y-4 text-sm">
            <div className="space-y-2">
              {viewCourse.instructor && (
                <div><span className="font-medium">{t('course.view.instructor', 'Instructor')}:</span> {viewCourse.instructor}</div>
              )}
              <div><span className="font-medium">{t('course.view.status', 'Status')}:</span> {t(`course.status.${viewCourse.status}`,'{status}', { status: viewCourse.status })}</div>
              <div><span className="font-medium">{t('course.view.category', 'Category')}:</span> {viewCourse.category}</div>
              {typeof viewCourse.level !== 'undefined' && (
                <div><span className="font-medium">{t('course.view.level', 'Level')}:</span> {viewCourse.level}</div>
              )}
              <div><span className="font-medium">{t('course.view.price', 'Price')}:</span> {formatCurrency(Number(viewCourse.price||0))}</div>
              {typeof viewCourse.enrollments !== 'undefined' && (
                <div><span className="font-medium">{t('course.view.enrollments', 'Enrollments')}:</span> {viewCourse.enrollments}</div>
              )}
              {viewCourse.description && (
                <div>
                  <span className="font-medium">{t('course.view.description', 'Description')}:</span>
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
                      title={t('course.view.videoTitle', 'Course Video')}
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                      allowFullScreen
                    />
                  </div>
                  <div className="mt-2 text-xs">
                    <a className="text-blue-600 hover:underline" href={viewCourse.youtube_url} target="_blank" rel="noreferrer">
                      {t('course.view.watchOnYouTube', 'Watch on YouTube')}
                    </a>
                  </div>
                </div>
              ) : null
            })()}

            {/* Learning Material */}
            {viewCourse.learning_material ? (
              <div className="prose max-w-none text-gray-800" dangerouslySetInnerHTML={{ __html: sanitizeHtml(viewCourse.learning_material) }} />
            ) : (
              <p className="text-muted-foreground">{t('course.view.noLearningMaterial', 'No learning material added yet.')}</p>
            )}
          </div>
        ) : (
          <div>{t('common.loading', 'Loading...')}</div>
        )}
      </Modal>

      <Modal
        open={isQuizOpen}
        onClose={() => setQuizOpen(false)}
        title={quizCourse ? `${quizCourse.title} — ${t('quiz.title', 'Quiz')}` : t('quiz.title', 'Quiz')}
        footer={<Button variant="outline" onClick={() => setQuizOpen(false)}>{t('common.close', 'Close')}</Button>}
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
