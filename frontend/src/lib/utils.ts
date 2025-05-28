import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

const RATE_USD_EUR = 0.92 // 1 USD = 0.92 EUR (static demo rate)

export function formatCurrency(amount: number): string {
  const lang = (typeof localStorage !== 'undefined' && localStorage.getItem('language')) || 'en'
  const currency = lang === 'de' ? 'EUR' : 'USD'
  const locale = lang === 'de' ? 'de-DE' : 'en-US'
  const value = currency === 'EUR' ? amount * RATE_USD_EUR : amount
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(value)
}

export function formatDate(date: string | Date): string {
  const lang = (typeof localStorage !== 'undefined' && localStorage.getItem('language')) || 'en'
  const locale = lang === 'de' ? 'de-DE' : 'en-US'
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(new Date(date))
}

export function formatDuration(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  
  if (hours > 0) {
    return `${hours}h ${mins}m`
  }
  return `${mins}m`
}

export function getInitials(name: string): string {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text
  return text.slice(0, maxLength) + '...'
}
