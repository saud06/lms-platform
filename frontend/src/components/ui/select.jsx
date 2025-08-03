import React from 'react'

export function Select({ children, value, onValueChange, ...props }) {
  return (
    <select
      value={value}
      onChange={(e) => onValueChange?.(e.target.value)}
      className="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
      {...props}
    >
      {children}
    </select>
  )
}

export function SelectContent({ children }) {
  return <>{children}</>
}

export function SelectItem({ children, value }) {
  return <option value={value}>{children}</option>
}

export function SelectTrigger({ children, className = '' }) {
  return <div className={className}>{children}</div>
}

export function SelectValue({ placeholder }) {
  return <span>{placeholder}</span>
}
