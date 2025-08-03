import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useToast } from '../../hooks/use-toast'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/card'
import { Button } from '../../components/ui/button'
import { Input } from '../../components/ui/input'
import { Badge } from '../../components/ui/badge'
import { Users, Search, UserPlus, Edit, Trash2 } from 'lucide-react'
import Modal from '../../components/ui/Modal'
import { useLanguage } from '../../contexts/LanguageContext'

export default function ManageUsers() {
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [isModalOpen, setModalOpen] = useState(false)
  const [editingUser, setEditingUser] = useState(null)
  const [form, setForm] = useState({ name: '', email: '', role: 'student' })
  const [errors, setErrors] = useState({})
  const [touched, setTouched] = useState({})
  const [submitted, setSubmitted] = useState(false)
  const { toast } = useToast()
  const { t } = useLanguage()

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-users'],
    queryFn: async () => {
      const res = await api.get('/admin/users')
      return res.data
    }
  })

  const addMutation = useMutation({
    mutationFn: (payload) => api.post('/admin/users', payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      toast({ title: t('admin.manageUsers.toast.addedTitle', 'User added'), description: t('admin.manageUsers.toast.addedDesc', '{name} has been created.', { name: form.name }) })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: async (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: t('admin.manageUsers.toast.addFailedTitle', 'Failed to add user'), description: data?.message || t('common.fixErrors', 'Please fix errors and try again.'), variant: 'destructive' })
    }
  })
  const editMutation = useMutation({
    mutationFn: ({ id, payload }) => api.put(`/admin/users/${id}`, payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      toast({ title: t('admin.manageUsers.toast.updatedTitle', 'User updated'), description: t('admin.manageUsers.toast.updatedDesc', '{name} has been saved.', { name: form.name }) })
      setErrors({})
      setTouched({})
      setSubmitted(false)
    },
    onError: async (err) => {
      const data = err?.response?.data
      if (data?.errors) setErrors(Object.fromEntries(Object.entries(data.errors).map(([k,v]) => [k, v[0]])))
      toast({ title: t('admin.manageUsers.toast.updateFailedTitle', 'Failed to update user'), description: data?.message || t('common.fixErrors', 'Please fix errors and try again.'), variant: 'destructive' })
    }
  })
  const deleteMutation = useMutation({
    mutationFn: (id) => api.delete(`/admin/users/${id}`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      toast({ title: t('admin.manageUsers.toast.deletedTitle', 'User deleted'), description: t('admin.manageUsers.toast.deletedDesc', 'The user has been removed.') })
    }
  })

  const users = (data || [])
  const filteredUsers = users.filter(user =>
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getRoleBadgeColor = (role) => {
    switch (role) {
      case 'admin': return 'bg-red-100 text-red-800'
      case 'instructor': return 'bg-blue-100 text-blue-800'
      case 'student': return 'bg-green-100 text-green-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const openAdd = () => {
    setEditingUser(null)
    setForm({ name: '', email: '', role: 'student' })
    setErrors({})
    setTouched({})
    setSubmitted(false)
    setModalOpen(true)
  }

  const openEdit = (user) => {
    setEditingUser(user)
    setForm({ name: user.name, email: user.email, role: user.role })
    setErrors({})
    setTouched({})
    setSubmitted(false)
    setModalOpen(true)
  }

  const validateUser = (f) => {
    const e = {}
    if (!f.name || f.name.trim().length < 2) e.name = t('validation.nameRequired', 'Name is required (min 2 characters)')
    const emailRe = /[^@\s]+@[^@\s]+\.[^@\s]+/
    if (!f.email || !emailRe.test(f.email)) e.email = t('validation.emailRequired', 'Valid email is required')
    if (!['admin','instructor','student'].includes(f.role)) e.role = t('validation.roleInvalid', 'Role must be admin, instructor, or student')
    return e
  }

  const onSubmit = async () => {
    setSubmitted(true)
    const v = validateUser(form)
    setErrors(v)
    if (Object.keys(v).length) return
    if (editingUser) {
      await editMutation.mutateAsync({ id: editingUser.id, payload: form })
    } else {
      await addMutation.mutateAsync(form)
    }
    setModalOpen(false)
  }

  const onDelete = async (user) => {
    if (!confirm(t('admin.manageUsers.confirmDelete', 'Delete {name}?', { name: user.name }))) return
    await deleteMutation.mutateAsync(user.id)
  }

  if (isLoading) return <div>{t('admin.manageUsers.loading', 'Loading users...')}</div>
  if (isError) return <div>{t('admin.manageUsers.loadFailed', 'Failed to load users')}</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">{t('admin.manageUsers.title', 'Manage Users')}</h1>
          <p className="text-gray-600">{t('admin.manageUsers.subtitle', 'Manage platform users and their roles')}</p>
        </div>
        <Button onClick={openAdd} disabled={addMutation.isPending}>
          <UserPlus className="mr-2 h-4 w-4" />
          {t('admin.manageUsers.addUser', 'Add User')}
        </Button>
      </div>

      {/* Search and Filters */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <Users className="mr-2 h-5 w-5" />
            {t('admin.manageUsers.users', 'Users')} ({filteredUsers.length})
          </CardTitle>
          <CardDescription>{t('admin.manageUsers.searchManageDesc', 'Search and manage all platform users')}</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center space-x-4 mb-6">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                type="text"
                placeholder={t('admin.manageUsers.searchPlaceholder', 'Search users by name or email...')}
                className="pl-10"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>

          {/* Users Table */}
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-3 px-4 font-medium">{t('admin.manageUsers.table.user', 'User')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('admin.manageUsers.table.role', 'Role')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('admin.manageUsers.table.status', 'Status')}</th>
                  <th className="text-left py-3 px-4 font-medium">{t('admin.manageUsers.table.created', 'Created')}</th>
                  <th className="text-right py-3 px-4 font-medium">{t('admin.manageUsers.table.actions', 'Actions')}</th>
                </tr>
              </thead>
              <tbody>
                {filteredUsers.map((user) => (
                  <tr key={user.id} className="border-b hover:bg-gray-50">
                    <td className="py-4 px-4">
                      <div>
                        <div className="font-medium text-gray-900">{user.name}</div>
                        <div className="text-sm text-gray-500">{user.email}</div>
                      </div>
                    </td>
                    <td className="py-4 px-4">
                      <Badge className={getRoleBadgeColor(user.role)}>
                        {t(`user.role.${user.role}`,'{role}', { role: user.role })}
                      </Badge>
                    </td>
                    <td className="py-4 px-4">
                      <Badge variant={user.isActive ? 'default' : 'secondary'}>
                        {user.isActive ? t('user.status.active', 'Active') : t('user.status.inactive', 'Inactive')}
                      </Badge>
                    </td>
                    <td className="py-4 px-4 text-sm text-gray-500">
                      {new Date(user.createdAt).toLocaleDateString()}
                    </td>
                    <td className="py-4 px-4">
                      <div className="flex justify-end space-x-2">
                        <Button size="sm" variant="outline" onClick={() => openEdit(user)} disabled={editMutation.isPending}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="outline" className="text-red-600 hover:text-red-700" onClick={() => onDelete(user)} disabled={deleteMutation.isPending}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Modal
        open={isModalOpen}
        onClose={() => setModalOpen(false)}
        title={editingUser ? t('admin.manageUsers.editUser', 'Edit User') : t('admin.manageUsers.addUser', 'Add User')}
        footer={(
          <>
            <Button variant="outline" onClick={() => setModalOpen(false)}>{t('common.cancel', 'Cancel')}</Button>
            <Button onClick={onSubmit} disabled={addMutation.isPending || editMutation.isPending}>
              {editingUser ? (editMutation.isPending ? t('common.saving', 'Saving...') : t('common.save', 'Save')) : (addMutation.isPending ? t('common.adding', 'Adding...') : t('common.add', 'Add'))}
            </Button>
          </>
        )}
      >
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('user.form.name', 'Name')}</label>
            <Input
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              onBlur={() => setTouched(prev => ({ ...prev, name: true }))}
              aria-invalid={!!errors.name}
            />
            {errors.name && (touched.name || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.name}</p>)}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('user.form.email', 'Email')}</label>
            <Input
              type="email"
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
              onBlur={() => setTouched(prev => ({ ...prev, email: true }))}
              aria-invalid={!!errors.email}
            />
            {errors.email && (touched.email || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.email}</p>)}
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('user.form.role', 'Role')}</label>
            <select
              className="w-full border rounded-md h-10 px-3 text-sm"
              value={form.role}
              onChange={(e) => setForm({ ...form, role: e.target.value })}
              onBlur={() => setTouched(prev => ({ ...prev, role: true }))}
            >
              <option value="student">{t('user.role.student', 'Student')}</option>
              <option value="instructor">{t('user.role.instructor', 'Instructor')}</option>
              <option value="admin">{t('user.role.admin', 'Admin')}</option>
            </select>
            {errors.role && (touched.role || submitted) && (<p className="mt-1 text-xs text-red-600">{errors.role}</p>)}
          </div>
        </div>
      </Modal>
    </div>
  )
}
