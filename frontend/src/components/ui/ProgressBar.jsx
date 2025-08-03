import React from 'react'

export default function ProgressBar({ value = 0 }) {
  const pct = Math.max(0, Math.min(100, Number(value) || 0))
  return (
    <div className="w-full h-3 bg-gray-200 rounded">
      <div
        className="h-3 bg-blue-600 rounded"
        style={{ width: `${pct}%`, transition: 'width 200ms ease' }}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-valuenow={pct}
        role="progressbar"
      />
    </div>
  )
}
