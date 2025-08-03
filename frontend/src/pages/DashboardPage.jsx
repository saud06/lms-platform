import { useAuth } from '../contexts/AuthContext'
import AdminDashboard from './admin/AdminDashboard'
import InstructorDashboard from './instructor/InstructorDashboard'
import StudentDashboard from './student/StudentDashboard'

export default function DashboardPage() {
  const { user } = useAuth()

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
