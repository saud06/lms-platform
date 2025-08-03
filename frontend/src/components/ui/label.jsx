import React from 'react'
import { useTheme } from '../../contexts/ThemeContext'

export const Label = React.forwardRef(({ className = '', ...props }, ref) => {
  const { theme } = useTheme()
  
  return (
    <label
      ref={ref}
      className={`text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 ${className}`}
      style={{ color: theme === 'dark' ? '#f3f4f6' : '#374151' }}
      {...props}
    />
  )
})

Label.displayName = "Label"
