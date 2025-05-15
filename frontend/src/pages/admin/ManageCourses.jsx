import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useToast } from '@/hooks/use-toast'
import { useNavigate } from 'react-router-dom'
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

export default function ManageCourses() {
  const navigate = useNavigate()
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

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-courses'],
    queryFn: async () => {
      const res = await api.get('/admin/courses')
      return res.data
    }
  })

  const addMutation = useMutation({
    mutationFn: (payload) => api.post('/admin/courses', payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: 'Course added', description: `${form.title} has been created.` })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: 'Failed to add course', description: data?.message || 'Please fix errors and try again.', variant: 'destructive' })
    }
  })
  const editMutation = useMutation({
    mutationFn: ({ id, payload }) => api.put(`/admin/courses/${id}`, payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: 'Course updated', description: `${form.title} has been saved.` })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: 'Failed to update course', description: data?.message || 'Please fix errors and try again.', variant: 'destructive' })
    }
  })
  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/admin/courses/${id}`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-courses'] })
      toast({ title: 'Course deleted', description: 'The course has been removed.' })
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
    if (!f.title || f.title.trim().length < 3) e.title = 'Title is required (min 3 characters)'
    if (!f.instructor || f.instructor.trim().length < 2) e.instructor = 'Instructor is required'
    if (!f.category || f.category.trim().length < 2) e.category = 'Category is required'
    if (!['draft','published','archived'].includes(f.status)) e.status = 'Status must be draft, published, or archived'
    const priceNum = Number(f.price)
    if (Number.isNaN(priceNum) || priceNum < 0) e.price = 'Price must be a non-negative number'
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
    if (!confirm(`Delete ${course.title}?`)) return
    await deleteMutation.mutateAsync(course.id)
  }

  if (isLoading) return <div>Loading courses...</div>
  if (isError) return <div>Failed to load courses</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Manage Courses</h1>
          <p className="text-gray-600">Manage platform courses and content</p>
        </div>
        <Button onClick={openAdd} disabled={addMutation.isPending}>
          <Plus className="mr-2 h-4 w-4" />
          Add Course
        </Button>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <BookOpen className="mr-2 h-5 w-5" />
            Courses ({filteredCourses.length})
          </CardTitle>
          <CardDescription>Search and manage all platform courses</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center space-x-4 mb-6">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                type="text"
                placeholder="Search courses by title, instructor, or category..."
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
                <tr className="border-b">
                  <th className="text-left py-3 px-4 font-medium">Course</th>
                  <th className="text-left py-3 px-4 font-medium">Instructor</th>
                  <th className="text-left py-3 px-4 font-medium">Status</th>
                  <th className="text-left py-3 px-4 font-medium">Enrollments</th>
                  <th className="text-left py-3 px-4 font-medium">Price</th>
                  <th className="text-left py-3 px-4 font-medium">Created</th>
                  <th className="text-right py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredCourses.map((course) => (
                  <tr key={course.id} className="border-b hover:bg-gray-50">
                    <td className="py-4 px-4">
                      <div>
                        <div className="font-medium text-gray-900">{course.title}</div>
                        <div className="text-sm text-gray-500">{course.category}</div>
                      </div>
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900">
                      {course.instructor}
                    </td>
                    <td className="py-4 px-4">
                      <Badge className={getStatusBadgeColor(course.status)}>
                        {course.status}
                      </Badge>
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900">
                      {course.enrollments}
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-900">
                      {formatCurrency(Number(course.price) || 0)}
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-500">
                      {new Date(course.createdAt).toLocaleDateString()}
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex justify-end space-x-2">
                        <Button size="sm" variant="outline" onClick={() => openView(course)}>
                          <Eye className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="outline" onClick={() => openQuiz(course)}>
                          Quiz
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
        title={editingCourse ? 'Edit Course' : 'Add Course'}
        footer={(
          <>
            <Button variant="outline" onClick={() => setModalOpen(false)}>Cancel</Button>
            <Button onClick={onSubmit} disabled={addMutation.isPending || editMutation.isPending}>
              {editingCourse ? (editMutation.isPending ? 'Saving...' : 'Save') : (addMutation.isPending ? 'Adding...' : 'Add')}
            </Button>
          </>
        )}
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
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
              <label className="block text-sm font-medium text-gray-700 mb-1">Instructor</label>
              <Input
                value={form.instructor}
                onChange={(e) => setForm({ ...form, instructor: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, instructor: true }))}
                aria-invalid={!!errors.instructor}
              />
              {errors.instructor && (touched.instructor || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.instructor}</p>)}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
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
              <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                className="w-full border rounded-md h-10 px-3 text-sm"
                value={form.status}
                onChange={(e) => setForm({ ...form, status: e.target.value })}
                onBlur={() => setTouched(prev => ({ ...prev, status: true }))}
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
              {errors.status && (touched.status || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.status}</p>)}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Price</label>
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
            <label className="block text-sm font-medium text-gray-700 mb-1">Learning Material</label>
            <ReactQuill
              theme="snow"
              value={form.learning_material}
              onChange={(html) => setForm({ ...form, learning_material: html })}
              placeholder="Add lesson notes, text, and resources..."
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">YouTube Video URL (optional)</label>
            <Input
              placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
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
        footer={<Button variant="outline" onClick={() => setViewOpen(false)}>Close</Button>}
      >
        {viewCourse ? (
          <div className="space-y-4 text-sm">
            <div className="space-y-2">
              {viewCourse.instructor && (
                <div><span className="font-medium">Instructor:</span> {viewCourse.instructor}</div>
              )}
              <div><span className="font-medium">Status:</span> {viewCourse.status}</div>
              <div><span className="font-medium">Category:</span> {viewCourse.category}</div>
              {typeof viewCourse.level !== 'undefined' && (
                <div><span className="font-medium">Level:</span> {viewCourse.level}</div>
              )}
              <div><span className="font-medium">Price:</span> {formatCurrency(Number(viewCourse.price||0))}</div>
              {typeof viewCourse.enrollments !== 'undefined' && (
                <div><span className="font-medium">Enrollments:</span> {viewCourse.enrollments}</div>
              )}
              {viewCourse.description && (
                <div>
                  <span className="font-medium">Description:</span>
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
                      Watch on YouTube
                    </a>
                  </div>
                </div>
              ) : null
            })()}

            {/* Learning Material */}
            {viewCourse.learning_material ? (
              <div className="prose max-w-none text-gray-800" dangerouslySetInnerHTML={{ __html: sanitizeHtml(viewCourse.learning_material) }} />
            ) : (
              <p className="text-muted-foreground">No learning material added yet.</p>
            )}
          </div>
        ) : (
          <div>Loading...</div>
        )}
      </Modal>

      <Modal
        open={isQuizOpen}
        onClose={() => setQuizOpen(false)}
        title={quizCourse ? `${quizCourse.title} — Quiz` : 'Quiz'}
        footer={<Button variant="outline" onClick={() => setQuizOpen(false)}>Close</Button>}
      >
        {quizCourse ? (
          <div className="space-y-4">
            <QuizEditor courseId={quizCourse.id} />
          </div>
        ) : (
          <div>Loading...</div>
        )}
      </Modal>
    </div>
  )
}
