import React, { createContext, useContext, useEffect, useState } from 'react'
const ThemeContext = createContext()

export function ThemeProvider({ children }) {
  // Initialize theme from localStorage immediately, default to 'light' for new users
  const [theme, setTheme] = useState(() => {
    if (typeof window !== 'undefined') {
      const savedTheme = localStorage.getItem('theme')
      if (savedTheme && (savedTheme === 'light' || savedTheme === 'dark')) {
        return savedTheme
      }
    }
    return 'light' // Default for first-time users
  })

  useEffect(() => {
    // Ensure theme is saved to localStorage for first-time users
    if (!localStorage.getItem('theme')) {
      localStorage.setItem('theme', theme)
    }
  }, [])

  useEffect(() => {
    // Save to localStorage
    localStorage.setItem('theme', theme)
    
    // Apply to document element
    const root = document.documentElement
    
    if (theme === 'dark') {
      root.classList.add('dark')
    } else {
      root.classList.remove('dark')
    }
    
    // Force a style recalculation
    root.style.colorScheme = theme
  }, [theme])

  const toggleTheme = () => {
    setTheme(prev => prev === 'light' ? 'dark' : 'light')
  }

  const value = {
    theme,
    setTheme,
    toggleTheme,
    isDark: theme === 'dark'
  }

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  )
}

export function useTheme() {
  const context = useContext(ThemeContext)
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider')
  }
  return context
}
