// DB-backed quiz API
// Shapes:
// - Quiz: { questions: [{ id, text, options: string[4], answerIndex: 0..3|null }] }
// - Attempt: { answers: { [questionId]: index|null }, score, total, submittedAt, passed }

import { api } from './api.jsx'

export async function fetchQuiz(courseId) {
  const { data } = await api.get(`/courses/${courseId}/quiz`)
  return data || { questions: [] }
}

export async function upsertQuestion(courseId, q) {
  // q: { id?, text, options[4], answerIndex }
  const payload = {
    id: q.id ?? undefined,
    text: q.text,
    options: q.options,
    answerIndex: q.answerIndex,
  }
  const { data } = await api.post(`/courses/${courseId}/quiz/questions`, payload)
  return data
}

export async function deleteQuestion(courseId, id) {
  const { data } = await api.delete(`/courses/${courseId}/quiz/questions/${id}`)
  return data
}

export async function fetchAttempt(courseId) {
  const { data } = await api.get(`/courses/${courseId}/quiz/attempt`)
  return data // may be null
}

export async function submitAttempt(courseId, answers) {
  // answers: { [questionId]: index }
  const { data } = await api.post(`/courses/${courseId}/quiz/attempts`, { answers })
  return data
}

// Optional: quick summary when you have courseIds and want aggregate UI hints
export async function getQuizSummary(courseIds = []) {
  let coursesWithQuiz = 0
  let totalQuestions = 0
  let attempts = 0
  for (const id of courseIds) {
    try {
      const quiz = await fetchQuiz(id)
      if (quiz?.questions?.length) {
        coursesWithQuiz += 1
        totalQuestions += quiz.questions.length
      }
      const att = await fetchAttempt(id)
      if (att) attempts += 1
    } catch (_) {
      // ignore per course errors for summary
    }
  }
  return { coursesWithQuiz, totalQuestions, attempts }
}

// Backward-compat: some dashboards expect a synchronous local summary.
// Now that we are DB-backed, return zeros to avoid runtime errors.
export function getLocalQuizSummary() {
  return { coursesWithQuiz: 0, totalQuestions: 0, attempts: 0 }
}
