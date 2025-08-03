import { useEffect, useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { fetchAttempt, fetchQuiz } from '../../lib/quiz'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../contexts/LanguageContext'

export default function StudentQuizzes() {
  const [search, setSearch] = useState('')
  const [meta, setMeta] = useState({}) // { [courseId]: { questions, attempted } }
  const { t } = useLanguage()

  const { data: enrollments, isLoading, isError } = useQuery({
    queryKey: ['student-courses'],
    queryFn: async () => (await api.get('/student/courses')).data,
  })

  useEffect(() => {
    let active = true
    const load = async () => {
      if (!enrollments) return
      const entries = await Promise.all(
        enrollments.map(async (e) => {
          try {
            const [quiz, attempt] = await Promise.all([
              fetchQuiz(e.course.id),
              fetchAttempt(e.course.id)
            ])
            return [e.course.id, { questions: quiz?.questions?.length || 0, attempted: !!attempt }]
          } catch {
            return [e.course.id, { questions: 0, attempted: false }]
          }
        })
      )
      if (active) setMeta(Object.fromEntries(entries))
    }
    load()
    return () => { active = false }
  }, [enrollments])

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase()
    if (!term) return enrollments || []
    return (enrollments || []).filter(e =>
      e.course.title.toLowerCase().includes(term) ||
      (e.course.category||'').toLowerCase().includes(term)
    )
  }, [enrollments, search])

  if (isLoading) return <div>{t('student.quizzes.loading', 'Loading your quizzes...')}</div>
  if (isError) return <div>{t('student.quizzes.loadFailed', 'Failed to load your courses')}</div>

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">{t('student.quizzes.title', 'My Quizzes')}</h1>
          <p className="text-gray-600">{t('student.quizzes.subtitle', 'Quiz status for your enrolled courses')}</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t('student.quizzes.enrolledCourses', 'Enrolled Courses')}</CardTitle>
          <CardDescription>{t('student.quizzes.searchDesc', 'Search your courses and jump to their quiz')}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center space-x-4 mb-6">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                placeholder={t('student.quizzes.searchPlaceholder', 'Search by title or category...')}
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
                  <th className="text-left py-3 px-4 font-medium">{t('student.quizzes.table.course', 'Course')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('student.quizzes.table.category', 'Category')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('student.quizzes.table.questions', 'Questions')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('student.quizzes.table.attempt', 'Attempt')}</th>
                  <th className="text-right py-3 px-4 font-medium">{t('student.quizzes.table.action', 'Action')}</th>
                </tr>
              </thead>
              <tbody>
                {filtered.map(e => {
                  const m = meta[e.course.id] || { questions: 0, attempted: false }
                  return (
                    <tr key={e.id} className="border-b hover:bg-gray-50">
                      <td className="py-3 px-4">{e.course.title}</td>
                      <td className="py-3 px-4 text-sm text-gray-600">{e.course.category}</td>
                      <td className="py-3 px-4">{m.questions}</td>
                      <td className="py-3 px-4">{m.attempted ? t('student.quizzes.submitted', 'Submitted') : t('student.quizzes.notAttempted', 'Not attempted')}</td>
                      <td className="py-3 px-4">
                        <div className="flex justify-end">
                          <Link to={`/courses/${e.course.id}`}>
                            <Button size="sm">{t('student.quizzes.goToQuiz', 'Go to Quiz')}</Button>
                          </Link>
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
    </div>
  )
}
