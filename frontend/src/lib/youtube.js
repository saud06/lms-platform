export function toYouTubeEmbed(url) {
  if (!url) return null
  try {
    const raw = String(url).trim()
    if (!raw) return null
    const u = new URL(raw)
    let id = ''

    const host = u.hostname.toLowerCase()

    if (host.includes('youtube.com')) {
      // Standard watch URL
      const v = u.searchParams.get('v')
      if (v) id = v

      // Shorts or other path-based IDs
      if (!id) {
        const parts = u.pathname.split('/').filter(Boolean)
        const shortsIdx = parts.indexOf('shorts')
        if (shortsIdx >= 0 && parts[shortsIdx + 1]) id = parts[shortsIdx + 1]
        // /embed/<id>
        if (!id && parts[0] === 'embed' && parts[1]) id = parts[1]
      }
    } else if (host.includes('youtu.be')) {
      // Short link: youtu.be/<id>
      const path = u.pathname.replace(/^\//, '')
      id = path.split('?')[0]
    }

    if (id) return `https://www.youtube-nocookie.com/embed/${id}?rel=0`
  } catch {}
  return null
}
