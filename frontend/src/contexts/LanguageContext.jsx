import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import en from '../locales/en.json'
import de from '../locales/de.json'

const DEFAULT_LANG = 'de'
const DEFAULT_CURRENCY = 'EUR'
const RATE_USD_EUR = 0.92 // 1 USD = 0.92 EUR (static demo rate)

const LanguageContext = createContext({
  language: DEFAULT_LANG,
  currency: DEFAULT_CURRENCY,
  setLanguage: () => {},
  toDisplayCurrency: (usdAmount) => usdAmount,
  t: (key, fallback) => fallback || key,
})

export function LanguageProvider({ children }) {
  const [language, setLanguageState] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('language') || DEFAULT_LANG
    }
    return DEFAULT_LANG // Default to German for first-time users
  })

  const currency = language === 'de' ? 'EUR' : 'USD'
  const messages = language === 'de' ? de : en

  const setLanguage = (lang) => {
    setLanguageState(lang)
    localStorage.setItem('language', lang)
    localStorage.setItem('currency', lang === 'de' ? 'EUR' : 'USD')
    // update document lang for a11y and formatting
    if (typeof document !== 'undefined') {
      document.documentElement.lang = lang === 'de' ? 'de' : 'en'
    }
  }

  useEffect(() => {
    // Ensure language and currency are set for first-time users
    if (!localStorage.getItem('language')) {
      localStorage.setItem('language', DEFAULT_LANG) // Default to German
    }
    if (!localStorage.getItem('currency')) {
      localStorage.setItem('currency', DEFAULT_CURRENCY) // Default to EUR
    }
    
    // Set document language for accessibility
    if (typeof document !== 'undefined') {
      document.documentElement.lang = language === 'de' ? 'de' : 'en'
    }
  }, [])

  const toDisplayCurrency = (usdAmount) => {
    if (currency === 'EUR') return usdAmount * RATE_USD_EUR
    return usdAmount
  }

  const t = (key, fallback) => {
    if (messages && Object.prototype.hasOwnProperty.call(messages, key)) return messages[key]
    // fallback to EN if missing in DE
    if (en && Object.prototype.hasOwnProperty.call(en, key)) return en[key]
    return fallback || key
  }

  const value = useMemo(() => ({ language, currency, setLanguage, toDisplayCurrency, t }), [language, currency])

  return (
    <LanguageContext.Provider value={value}>
      {children}
    </LanguageContext.Provider>
  )
}

export function useLanguage() {
  return useContext(LanguageContext)
}
