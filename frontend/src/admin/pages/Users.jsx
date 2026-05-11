// User management interface — MNTHC-54
import { useEffect, useState } from 'react'
import api from '../../api/axios'

const ROLES = ['patient', 'doctor', 'admin']

export default function Users() {
  const [users, setUsers]     = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState('')
  const [search, setSearch]   = useState('')

  useEffect(() => {
    api.get('/users')
      .then(r => setUsers(r.data))
      .catch(() => setError('Failed to load users'))
      .finally(() => setLoading(false))
  }, [])

  const handleRoleChange = async (userId, newRole) => {
    try {
      await api.patch(`/users/${userId}`, { role: newRole })
      setUsers(users.map(u => u.user_id === userId ? { ...u, role: newRole } : u))
    } catch {
      alert('Failed to update role')
    }
  }

  const handleDeactivate = async (userId) => {
    if (!confirm('Deactivate this user?')) return
    try {
      await api.patch(`/users/${userId}`, { active: false })
      setUsers(users.map(u => u.user_id === userId ? { ...u, active: false } : u))
    } catch {
      alert('Failed to deactivate user')
    }
  }

  const filtered = users.filter(u =>
    u.full_name.toLowerCase().includes(search.toLowerCase()) ||
    u.email.toLowerCase().includes(search.toLowerCase())
  )

  if (loading) return <div className="p-8 text-gray-500">Loading users...</div>
  if (error)   return <div className="p-8 text-red-500">{error}</div>

  return (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-primary-900">User Management</h1>
        <span className="text-sm text-gray-500">{users.length} total users</span>
      </div>

      {/* Search */}
      <input
        type="text"
        placeholder="Search by name or email..."
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="input-field max-w-sm mb-6"
      />

      {/* Table */}
      <div className="card overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="bg-primary-50 text-primary-800">
            <tr>
              <th className="text-left px-4 py-3 font-semibold">Name</th>
              <th className="text-left px-4 py-3 font-semibold">Email</th>
              <th className="text-left px-4 py-3 font-semibold">Phone</th>
              <th className="text-left px-4 py-3 font-semibold">Role</th>
              <th className="text-left px-4 py-3 font-semibold">Status</th>
              <th className="text-left px-4 py-3 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {filtered.map(u => (
              <tr key={u.user_id} className="hover:bg-gray-50 transition-colors">
                <td className="px-4 py-3 font-medium text-gray-800">{u.full_name}</td>
                <td className="px-4 py-3 text-gray-600">{u.email}</td>
                <td className="px-4 py-3 text-gray-600">{u.phone_number || '—'}</td>
                <td className="px-4 py-3">
                  <select
                    value={u.role}
                    onChange={e => handleRoleChange(u.user_id, e.target.value)}
                    className="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-secondary-500"
                  >
                    {ROLES.map(r => (
                      <option key={r} value={r}>{r}</option>
                    ))}
                  </select>
                </td>
                <td className="px-4 py-3">
                  {u.active !== false ? (
                    <span className="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Active</span>
                  ) : (
                    <span className="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-1 rounded-full">Inactive</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  {u.active !== false && (
                    <button
                      onClick={() => handleDeactivate(u.user_id)}
                      className="text-xs text-red-500 hover:text-red-700 font-medium transition-colors"
                    >
                      Deactivate
                    </button>
                  )}
                </td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-gray-400">No users found</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
