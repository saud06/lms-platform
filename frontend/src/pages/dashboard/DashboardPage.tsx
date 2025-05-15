import { useAuthStore } from '@/stores/authStore'
import { AdminDashboard } from '@/pages/admin/AdminDashboard'
import { InstructorDashboard } from '@/pages/instructor/InstructorDashboard'
import { StudentDashboard } from '@/pages/student/StudentDashboard'

export function DashboardPage() {
  const { user } = useAuthStore()

  if (!user) {
    return <div>Loading...</div>
  }

  switch (user.role) {
    case 'admin':
      return <AdminDashboard />
    case 'instructor':
      return <InstructorDashboard />
    case 'student':
      return <StudentDashboard />
    default:
      return <div>Invalid user role</div>
  }
}
