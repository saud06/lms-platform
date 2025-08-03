import React, { useEffect, useState } from 'react'
import { fetchQuiz, fetchAttempt, submitAttempt } from '../../lib/quiz'
import { Button } from '../ui/button'

export default function QuizRunner({ courseId }) {
  const [quiz, setQuiz] = useState({ questions: [] })
  const [loading, setLoading] = useState(true)
  const [answers, setAnswers] = useState({})
  const [submitted, setSubmitted] = useState(false)

  useEffect(() => {
    let active = true
    setLoading(true)
    Promise.all([fetchQuiz(courseId), fetchAttempt(courseId)])
      .then(([q, att]) => {
        if (!active) return
        setQuiz(q || { questions: [] })
        if (att) {
          setAnswers(att.answers || {})
          setSubmitted(true)
        } else {
          setAnswers({})
          setSubmitted(false)
        }
      })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [courseId])

  if (!quiz?.questions?.length) return <p className="text-sm text-muted-foreground">No quiz available for this course.</p>

  const submit = () => {
    submitAttempt(courseId, answers).then(() => setSubmitted(true))
  }
  const correctCount = submitted
    ? quiz.questions.reduce((acc, q) => acc + ((answers[q.id] ?? -1) === q.answerIndex ? 1 : 0), 0)
    : 0

  return (
    <div className="space-y-4">
      {loading && <p className="text-sm text-muted-foreground">Loading...</p>}
      {quiz.questions.map((q, idx) => (
        <div key={q.id} className="p-4 border rounded space-y-2">
          <div className="font-medium">Q{idx + 1}. {q.text}</div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
            {q.options.map((opt, i) => {
              const selected = answers[q.id] === i
              const correct = submitted && i === q.answerIndex
              const wrong = submitted && selected && !correct
              return (
                <button
                  key={i}
                  type="button"
                  onClick={() => !submitted && setAnswers(prev => ({ ...prev, [q.id]: i }))}
                  className={[
                    'text-left p-2 border rounded',
                    selected ? 'border-blue-600' : 'border-gray-200',
                    correct ? 'bg-green-100 border-green-600' : '',
                    wrong ? 'bg-red-100 border-red-600' : ''
                  ].join(' ')}
                  disabled={submitted}
                >
                  {String.fromCharCode(65 + i)}. {opt}
                </button>
              )
            })}
          </div>
        </div>
      ))}

      {!submitted ? (
        <Button onClick={submit}>Submit Answers</Button>
      ) : (
        <div className="p-3 rounded bg-blue-50 border border-blue-200">
          Score: {correctCount} / {quiz.questions.length}
        </div>
      )}
    </div>
  )
}
