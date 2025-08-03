import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardHeader, CardTitle } from '../../components/ui/card'

export default function InstructorStudents() {
  const { data, isLoading } = useQuery({
    queryKey: ['instructor-students'],
    queryFn: async () => {
      const res = await api.get('/instructor/students')
      return res.data
    }
  })

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold text-gray-900">Students</h1>

      <Card>
        <CardHeader>
          <CardTitle>Enrolled Students</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && <div>Loading...</div>}
          {!isLoading && (!data || data.length === 0) && (
            <p className="text-sm text-muted-foreground">No students found.</p>
          )}
          <div className="divide-y">
            {data?.map((s) => (
              <div key={s.id} className="py-3 flex items-center justify-between">
                <div>
                  <div className="font-medium">{s.name}</div>
                  <div className="text-sm text-gray-600">{s.email}</div>
                </div>
                <div className="text-sm text-gray-700">
                  Enrollments: {s.enrollments}
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
