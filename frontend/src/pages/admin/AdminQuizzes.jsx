import { useEffect, useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { fetchQuiz } from '../../lib/quiz'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Search } from 'lucide-react'
import Modal from '../../components/ui/Modal'
import QuizEditor from '../../components/quiz/QuizEditor'

export default function AdminQuizzes() {
  const [search, setSearch] = useState('')
  const [quizCourse, setQuizCourse] = useState(null)
  const [isQuizOpen, setQuizOpen] = useState(false)
  const [quizMeta, setQuizMeta] = useState({}) // { [courseId]: { questions: number } }

  const { data: courses, isLoading, isError } = useQuery({
    queryKey: ['admin-courses'],
    queryFn: async () => (await api.get('/admin/courses')).data,
  })

  // Load quiz question counts for visible courses
  useEffect(() => {
    let active = true
    const load = async () => {
      if (!courses) return
      const entries = await Promise.all(
        courses.map(async (c) => {
          try {
            const q = await fetchQuiz(c.id)
            return [c.id, { questions: q?.questions?.length || 0 }]
          } catch {
            return [c.id, { questions: 0 }]
          }
        })
      )
      if (active) setQuizMeta(Object.fromEntries(entries))
    }
    load()
    return () => { active = false }
  }, [courses])

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase()
    if (!term) return courses || []
    return (courses || []).filter(c =>
      c.title.toLowerCase().includes(term) ||
      (c.instructor||'').toLowerCase().includes(term) ||
      (c.category||'').toLowerCase().includes(term)
    )
  }, [courses, search])

  const openQuiz = (course) => {
    setQuizCourse(course)
    setQuizOpen(true)
  }

  if (isLoading) return <div>Loading quizzes...</div>
  if (isError) return <div>Failed to load courses</div>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Quizzes</h1>
          <p className="text-gray-600">Manage quizzes across all courses</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Courses with Quizzes</CardTitle>
          <CardDescription>Search courses and edit their quizzes</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center space-x-4 mb-6">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                placeholder="Search by title, instructor, or category..."
                className="pl-10"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-3 px-4 font-medium">Course</th>
                  <th className="text-left py-3 px-4 font-medium">Category</th>
                  <th className="text-left py-3 px-4 font-medium">Questions</th>
                  <th className="text-right py-3 px-4 font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map(c => {
                  const meta = quizMeta[c.id] || { questions: 0 }
                  return (
                    <tr key={c.id} className="border-b hover:bg-gray-50">
                      <td className="py-3 px-4">{c.title}</td>
                      <td className="py-3 px-4 text-sm text-gray-600">{c.category}</td>
                      <td className="py-3 px-4">{meta.questions}</td>
                      <td className="py-3 px-4">
                        <div className="flex justify-end">
                          <Button size="sm" variant="outline" onClick={() => openQuiz(c)}>
                            Edit Quiz
                          </Button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

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
