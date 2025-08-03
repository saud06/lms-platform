import { useState } from 'react'
import { api } from '../../lib/api'
import { Button } from '../../components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card'
import { Input } from '../../components/ui/input'
import { Label } from '../../components/ui/label'
import { useToast } from '../../hooks/use-toast'
import ReactQuill from 'react-quill'
import 'react-quill/dist/quill.snow.css'

const validate = (values) => {
  const errors = {}
  if (!values.title || values.title.trim().length < 3) {
    errors.title = 'Title must be at least 3 characters'
  }
  if (!values.category || values.category.trim().length < 2) {
    errors.category = 'Category must be at least 2 characters'
  }
  const priceNum = Number(values.price)
  if (Number.isNaN(priceNum) || priceNum < 0) {
    errors.price = 'Price must be a non-negative number'
  }
  const allowed = ['draft', 'published', 'archived']
  if (!allowed.includes(values.status)) {
    errors.status = 'Invalid status'
  }
  return errors
}

export default function InstructorCreateCourse() {
  const [values, setValues] = useState({ title: '', category: '', price: '0', status: 'draft', learning_material: '', youtube_url: '' })
  const [touched, setTouched] = useState({})
  const [submitted, setSubmitted] = useState(false)
  const { toast } = useToast()

  const errors = validate(values)
  const showError = (field) => (submitted || touched[field]) && errors[field]

  const onSubmit = async (e) => {
    e.preventDefault()
    setSubmitted(true)
    if (Object.keys(errors).length > 0) {
      toast({ title: 'Please fix validation errors', variant: 'destructive' })
      return
    }
    try {
      // Use instructor endpoint if available; fallback to admin for demo
      await api.post('/instructor/courses', {
        title: values.title.trim(),
        status: values.status,
        price: Number(values.price),
        category: values.category.trim(),
        learning_material: values.learning_material?.trim() || undefined,
        youtube_url: values.youtube_url?.trim() || undefined,
      }).catch(async () => {
        return api.post('/admin/courses', {
          title: values.title.trim(),
          instructor: 'Instructor User',
          status: values.status,
          price: Number(values.price),
          category: values.category.trim(),
          learning_material: values.learning_material?.trim() || undefined,
          youtube_url: values.youtube_url?.trim() || undefined,
        })
      })
      toast({ title: 'Course created', description: 'Your course has been created.' })
      setValues({ title: '', category: '', price: '0', status: 'draft', learning_material: '', youtube_url: '' })
      setTouched({})
      setSubmitted(false)
    } catch (err) {
      const msg = err?.response?.data?.message || 'Error creating course'
      toast({ title: 'Failed to create', description: msg, variant: 'destructive' })
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold text-gray-900">Create Course</h1>
      <Card>
        <CardHeader>
          <CardTitle>New Course</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={onSubmit} className="space-y-4 max-w-xl">
            <div>
              <Label htmlFor="title">Title</Label>
              <Input
                id="title"
                value={values.title}
                onChange={(e) => setValues(v => ({ ...v, title: e.target.value }))}
                onBlur={() => setTouched(t => ({ ...t, title: true }))}
                aria-invalid={!!showError('title')}
              />
              {showError('title') && (
                <p className="text-sm text-red-600 mt-1">{errors.title}</p>
              )}
            </div>
            <div>
              <Label htmlFor="category">Category</Label>
              <Input
                id="category"
                value={values.category}
                onChange={(e) => setValues(v => ({ ...v, category: e.target.value }))}
                onBlur={() => setTouched(t => ({ ...t, category: true }))}
                aria-invalid={!!showError('category')}
              />
              {showError('category') && (
                <p className="text-sm text-red-600 mt-1">{errors.category}</p>
              )}
            </div>
            <div>
              <Label htmlFor="price">Price</Label>
              <Input
                id="price"
                type="number"
                min="0"
                step="0.01"
                value={values.price}
                onChange={(e) => setValues(v => ({ ...v, price: e.target.value }))}
                onBlur={() => setTouched(t => ({ ...t, price: true }))}
                aria-invalid={!!showError('price')}
              />
              {showError('price') && (
                <p className="text-sm text-red-600 mt-1">{errors.price}</p>
              )}
            </div>
            <div>
              <Label htmlFor="status">Status</Label>
              <select
                id="status"
                className="border rounded h-10 px-3"
                value={values.status}
                onChange={(e)=> setValues(v => ({ ...v, status: e.target.value }))}
                onBlur={() => setTouched(t => ({ ...t, status: true }))}
                aria-invalid={!!showError('status')}
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
              {showError('status') && (
                <p className="text-sm text-red-600 mt-1">{errors.status}</p>
              )}
            </div>
            <div>
              <Label htmlFor="learning_material">Learning Material</Label>
              <div className="mt-1">
                <ReactQuill
                  theme="snow"
                  value={values.learning_material}
                  onChange={(html) => setValues(v => ({ ...v, learning_material: html }))}
                  placeholder="Add lesson notes, text, and resources..."
                />
              </div>
            </div>
            <div>
              <Label htmlFor="youtube_url">YouTube Video URL (optional)</Label>
              <Input
                id="youtube_url"
                placeholder="https://www.youtube.com/watch?v=... or https://youtu.be/..."
                value={values.youtube_url}
                onChange={(e) => setValues(v => ({ ...v, youtube_url: e.target.value }))}
              />
            </div>
            <Button type="submit" disabled={Object.keys(errors).length > 0}>Create</Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
