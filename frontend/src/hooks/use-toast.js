import { useContext } from 'react'
import { ToastContext } from '../components/ui/toast'

export function useToast() {
  const context = useContext(ToastContext)
  if (!context) {
    // Fallback if ToastProvider is not available
    return {
      toast: ({ title, description, variant }) => {
        console.log('Toast:', { title, description, variant })
        // Fallback to browser alert for critical messages
        if (variant === 'destructive') {
          alert(`Error: ${title}\n${description}`)
        }
      }
    }
  }
  return context
}
