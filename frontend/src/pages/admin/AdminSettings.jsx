import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { useEffect, useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'

export default function AdminSettings() {
  const queryClient = useQueryClient()
  const [platformName, setPlatformName] = useState('')
  const [supportEmail, setSupportEmail] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: async () => {
      const res = await api.get('/admin/settings')
      return res.data
    },
  })

  useEffect(() => {
    if (data) {
      setPlatformName(data.platform_name || '')
      setSupportEmail(data.support_email || '')
    }
  }, [data])

  const saveMutation = useMutation({
    mutationFn: async (payload) => {
      const res = await api.put('/admin/settings', payload)
      return res.data
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-settings'] })
      // simple UX feedback
      window.setTimeout(() => alert('Settings saved successfully'), 0)
    },
  })

  const onSave = () => {
    saveMutation.mutate({
      platform_name: platformName,
      support_email: supportEmail,
    })
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Admin Settings</h1>
        <p className="text-gray-600">Configure global platform preferences</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>General</CardTitle>
          <CardDescription>Basic information for your LMS</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Platform Name</label>
              <Input value={platformName} onChange={(e) => setPlatformName(e.target.value)} disabled={isLoading || saveMutation.isPending} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
              <Input type="email" value={supportEmail} onChange={(e) => setSupportEmail(e.target.value)} disabled={isLoading || saveMutation.isPending} />
            </div>
          </div>

          <div className="mt-6">
            <Button onClick={onSave} disabled={isLoading || saveMutation.isPending}>
              {saveMutation.isPending ? 'Saving...' : 'Save Settings'}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
