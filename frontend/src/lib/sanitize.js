import DOMPurify from 'dompurify'

export function sanitizeHtml(html) {
  if (!html) return ''
  try {
    return DOMPurify.sanitize(html, {
      USE_PROFILES: { html: true },
      ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel|data):|[^a-z]|[a-z+.-]+(?:[^a-z+.-]|$))/i,
    })
  } catch {
    return ''
  }
}
