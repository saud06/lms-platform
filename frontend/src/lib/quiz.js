// DB-backed quiz API
// Shapes:
// - Quiz: { questions: [{ id, text, options: string[4], answerIndex: 0..3|null }] }
// - Attempt: { answers: { [questionId]: index|null }, score, total, submittedAt, passed }

import { api } from './api.jsx'

export async function fetchQuiz(courseId) {
  try {
    const { data } = await api.get(`/courses/${courseId}/quiz`)
    return data || { questions: [] }
  } catch (error) {
    console.error('Error fetching quiz:', error.response?.data || error.message)
    throw error
  }
}

export async function upsertQuestion(courseId, q) {
  // q: { id?, text, options[4], answerIndex }
  const payload = {
    id: q.id ?? undefined,
    text: q.text,
    options: q.options,
    answerIndex: q.answerIndex,
  }
  
  try {
    const { data } = await api.post(`/courses/${courseId}/quiz/questions`, payload)
    return data
  } catch (error) {
    console.error('Error saving quiz question:', error.response?.data || error.message)
    throw error
  }
}

export async function deleteQuestion(courseId, id) {
  const { data } = await api.delete(`/courses/${courseId}/quiz/questions/${id}`)
  return data
}

export async function fetchAttempt(courseId) {
  try {
    const { data } = await api.get(`/courses/${courseId}/quiz/attempt`)
    return data
  } catch (error) {
    // Return null if no attempt found (404) or other errors
    if (error.response?.status === 404) {
      return null
    }
    console.error('Error fetching quiz attempt:', error.response?.data || error.message)
    return null
  }
}

export async function submitAttempt(courseId, answers) {
  // answers: { [questionId]: index }
  try {
    const { data } = await api.post(`/courses/${courseId}/quiz/attempts`, { answers })
    return data
  } catch (error) {
    console.error('Error submitting quiz attempt:', error.response?.data || error.message)
    throw error
  }
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
      // Skip individual attempt fetching for admin dashboard to avoid 404s
      // Individual attempts are only relevant for student views
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
