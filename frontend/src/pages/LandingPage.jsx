import { Link, useNavigate } from 'react-router-dom'
import { Button } from '../components/ui/button'
import { BookOpen, GraduationCap, ShieldCheck, Sparkles } from 'lucide-react'
import { useLanguage } from '../contexts/LanguageContext'
import { useAuth } from '../contexts/AuthContext'
import { useEffect } from 'react'

export default function LandingPage() {
  const { t } = useLanguage()
  const { isAuthenticated } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    if (isAuthenticated) {
      navigate('/dashboard', { replace: true })
    }
  }, [isAuthenticated, navigate])
  return (
    <div className="min-h-screen bg-white flex flex-col">
      {/* Header */}
      <header className="px-6 py-4 flex items-center justify-between border-b">
        <div className="flex items-center gap-2">
          <GraduationCap className="h-6 w-6 text-blue-600" />
          <span className="font-semibold text-lg">LMS</span>
        </div>
        <div className="flex items-center gap-2">
          {isAuthenticated ? (
            <Link to="/dashboard"><Button>{'Go to Dashboard'}</Button></Link>
          ) : (
            <>
              <Link to="/auth/login"><Button variant="outline">{'Login'}</Button></Link>
              <Link to="/auth/register"><Button>{'Get Started'}</Button></Link>
            </>
          )}
        </div>
      </header>

      {/* Hero */}
      <main className="flex-1">
        <section className="px-6 md:px-10 lg:px-16 py-16 md:py-24 bg-gradient-to-b from-blue-50 to-white">
          <div className="max-w-5xl mx-auto grid md:grid-cols-2 gap-10 items-center">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs font-medium mb-4">
                <Sparkles className="h-3.5 w-3.5" />
                <span>Modern Learning Platform</span>
              </div>
              <h1 className="text-4xl md:text-5xl font-extrabold tracking-tight text-gray-900">
                Learn. Build. Grow.
              </h1>
              <p className="mt-4 text-lg text-gray-600">
                A minimal, fast, and intuitive LMS to accelerate your learning journey. Track progress,
                take quizzes, and master new skills with ease.
              </p>
              <div className="mt-6 flex flex-wrap gap-3">
                <Link to="/auth/register"><Button size="lg">Create account</Button></Link>
                <Link to="/auth/login"><Button size="lg" variant="outline">Sign in</Button></Link>
              </div>
              <p className="mt-3 text-xs text-gray-500">
                No credit card required. Free to get started.
              </p>
            </div>
            <div className="rounded-xl border bg-white p-6 shadow-sm">
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <BookOpen className="h-5 w-5 text-blue-600 mt-0.5" />
                  <div>
                    <p className="font-medium">Curated Courses</p>
                    <p className="text-sm text-gray-600">High‑quality content organized by experts to keep you focused.</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <ShieldCheck className="h-5 w-5 text-blue-600 mt-0.5" />
                  <div>
                    <p className="font-medium">Assess and Improve</p>
                    <p className="text-sm text-gray-600">Built‑in quizzes to measure your understanding and progress.</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <GraduationCap className="h-5 w-5 text-blue-600 mt-0.5" />
                  <div>
                    <p className="font-medium">Track Your Journey</p>
                    <p className="text-sm text-gray-600">Dashboards that highlight what to learn next and celebrate wins.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Secondary section */}
        <section className="px-6 md:px-10 lg:px-16 py-12">
          <div className="max-w-5xl mx-auto grid md:grid-cols-3 gap-6">
            {[{
              title: 'Fast and Minimal',
              desc: 'Clean UI, instant navigation, and distraction‑free learning.'
            },{
              title: 'Built for Teams',
              desc: 'Admin and instructor tools to manage learners and content.'
            },{
              title: 'Ready for Growth',
              desc: 'Scalable foundation to add more features as you need them.'
            }].map((f, i) => (
              <div key={i} className="rounded-lg border bg-white p-5">
                <p className="font-semibold mb-1">{f.title}</p>
                <p className="text-sm text-gray-600">{f.desc}</p>
              </div>
            ))}
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="px-6 md:px-10 lg:px-16 py-8 border-t text-sm text-gray-500">
        <div className="max-w-5xl mx-auto flex items-center justify-between">
          <span>© {new Date().getFullYear()} LMS. All rights reserved.</span>
          <div className="flex items-center gap-4">
            <Link className="hover:underline" to="/auth/login">Sign in</Link>
            <Link className="hover:underline" to="/auth/register">Get started</Link>
          </div>
        </div>
      </footer>
    </div>
  )
}
