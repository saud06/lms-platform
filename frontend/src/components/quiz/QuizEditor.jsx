import React, { useEffect, useState } from 'react'
import { fetchQuiz, upsertQuestion, deleteQuestion } from '../../lib/quiz'
import { Button } from '../ui/button'

function emptyQuestion() {
  return {
    id: Math.random().toString(36).slice(2),
    text: '',
    options: ['', '', '', ''],
    answerIndex: 0,
  }
}

export default function QuizEditor({ courseId }) {
  const [draft, setDraft] = useState(emptyQuestion())
  const [quiz, setQuiz] = useState({ questions: [] })
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    let active = true
    setLoading(true)
    fetchQuiz(courseId)
      .then((data) => { if (active) setQuiz(data || { questions: [] }) })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [courseId])

  const addOrUpdate = async () => {
    // Validation with user feedback
    if (!draft.text.trim()) {
      alert('Please enter a question text.')
      return
    }
    
    const emptyOptions = draft.options.filter(o => !o.trim())
    if (emptyOptions.length > 0) {
      alert('Please fill in all 4 options.')
      return
    }
    
    try {
      setLoading(true)
      await upsertQuestion(courseId, draft)
      setDraft(emptyQuestion())
      // Refresh
      const updatedQuiz = await fetchQuiz(courseId)
      setQuiz(updatedQuiz)
      alert('Question saved successfully!')
    } catch (error) {
      console.error('Error saving question:', error)
      alert('Failed to save question. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  const remove = async (id) => {
    try {
      setLoading(true)
      await deleteQuestion(courseId, id)
      // Refresh
      const updatedQuiz = await fetchQuiz(courseId)
      setQuiz(updatedQuiz)
    } catch (error) {
      console.error('Error deleting question:', error)
      alert('Failed to delete question. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="space-y-4">
      <div className="p-4 border rounded space-y-3">
        <div className="font-medium">Add Question</div>
        <p className="text-sm text-gray-600">Fill in the question text, all 4 options, and select the correct answer by clicking the radio button.</p>
        <input
          type="text"
          className="w-full border rounded p-2"
          placeholder="Question text (required)"
          value={draft.text}
          onChange={(e) => setDraft({ ...draft, text: e.target.value })}
          required
        />
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
          {draft.options.map((opt, i) => (
            <div key={i} className="flex items-center gap-2">
              <input
                type="radio"
                name="answer"
                checked={draft.answerIndex === i}
                onChange={() => setDraft({ ...draft, answerIndex: i })}
              />
              <input
                type="text"
                className="flex-1 border rounded p-2"
                placeholder={`Option ${i + 1} (required)`}
                value={opt}
                onChange={(e) => {
                  const options = [...draft.options]
                  options[i] = e.target.value
                  setDraft({ ...draft, options })
                }}
                required
              />
            </div>
          ))}
        </div>
        <Button onClick={addOrUpdate} disabled={loading}>
          {loading ? 'Saving...' : 'Save Question'}
        </Button>
      </div>

      <div className="space-y-2">
        <div className="font-medium">Questions ({quiz.questions.length})</div>
        {!loading && !quiz.questions.length && (
          <p className="text-sm text-muted-foreground">No questions yet.</p>
        )}
        {loading && <p className="text-sm text-muted-foreground">Loading...</p>}
        {quiz.questions.map((q, idx) => (
          <div key={q.id} className="p-3 border rounded">
            <div className="flex items-center justify-between">
              <div className="font-medium">Q{idx + 1}. {q.text}</div>
              <Button variant="outline" onClick={() => remove(q.id)}>Delete</Button>
            </div>
            <ul className="list-disc pl-5 mt-2 text-sm">
              {q.options.map((o, i) => (
                <li key={i} className={i === q.answerIndex ? 'font-semibold' : ''}>
                  {String.fromCharCode(65 + i)}. {o}
                </li>
              ))}
            </ul>
          </div>
        ))}
      </div>
    </div>
  )
}
