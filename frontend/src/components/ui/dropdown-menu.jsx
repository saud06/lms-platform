import React, { useState, useRef, useEffect, createContext, useContext } from 'react'

const DropdownContext = createContext()

export function DropdownMenu({ children }) {
  const [isOpen, setIsOpen] = useState(false)
  const dropdownRef = useRef(null)

  useEffect(() => {
    function handleClickOutside(event) {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false)
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside)
      return () => document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [isOpen])

  return (
    <DropdownContext.Provider value={{ isOpen, setIsOpen }}>
      <div ref={dropdownRef} className="relative inline-block text-left">
        {children}
      </div>
    </DropdownContext.Provider>
  )
}

export function DropdownMenuTrigger({ children, asChild = false }) {
  const { isOpen, setIsOpen } = useContext(DropdownContext)
  
  return (
    <div onClick={() => setIsOpen(!isOpen)}>
      {children}
    </div>
  )
}

export function DropdownMenuContent({ children, align = 'right' }) {
  const { isOpen } = useContext(DropdownContext)
  
  if (!isOpen) return null

  const alignClasses = {
    left: 'left-0',
    right: 'right-0',
    center: 'left-1/2 transform -translate-x-1/2'
  }

  return (
    <div className={`absolute z-50 mt-2 w-56 rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg transition-colors ${alignClasses[align]}`}>
      <div className="py-1">{children}</div>
    </div>
  )
}

export function DropdownMenuItem({ children, onClick }) {
  const { setIsOpen } = useContext(DropdownContext)
  
  const handleClick = (e) => {
    if (onClick) onClick(e)
    setIsOpen(false) // Close dropdown after item click
  }

  return (
    <button
      className="block w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors"
      onClick={handleClick}
    >
      {children}
    </button>
  )
}

export function DropdownMenuSeparator() {
  return <div className="my-1 h-px bg-gray-200 dark:bg-gray-600" />
}

export function DropdownMenuLabel({ children, className = '' }) {
  return (
    <div className={`px-4 py-2 text-sm font-semibold text-gray-900 dark:text-white transition-colors ${className}`}>
      {children}
    </div>
  )
}
