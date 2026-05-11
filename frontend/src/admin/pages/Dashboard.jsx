// Dashboard layout — MNTHC-61
import { useEffect, useState } from 'react'
import api from '../../api/axios'

const StatCard = ({ label, value, icon, color }) => (
  <div className={`card p-6 border-l-4 ${color}`}>
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm text-gray-500 mb-1">{label}</p>
        <p className="text-3xl font-bold text-primary-900">{value ?? '—'}</p>
      </div>
      <div className="text-4xl opacity-80">{icon}</div>
    </div>
  </div>
)

export default function Dashboard() {
  const [stats, setStats]   = useState(null)
  const [recent, setRecent] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    Promise.all([
      api.get('/appointments/stats'),
      api.get('/appointments?limit=5'),
    ])
      .then(([statsRes, recentRes]) => {
        setStats(statsRes.data)
        setRecent(recentRes.data)
      })
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="p-8 text-gray-500">Loading dashboard...</div>

  return (
    <div className="p-6 space-y-8">
      <h1 className="text-2xl font-bold text-primary-900">Dashboard</h1>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <StatCard label="Total Appointments" value={stats?.total}     icon="📅" color="border-primary-500" />
        <StatCard label="Pending"             value={stats?.pending}   icon="⏳" color="border-yellow-400" />
        <StatCard label="Confirmed"           value={stats?.confirmed} icon="✅" color="border-green-500"  />
        <StatCard label="Completed"           value={stats?.completed} icon="🏁" color="border-secondary-500" />
      </div>

      {/* Recent appointments */}
      <div>
        <h2 className="text-lg font-semibold text-primary-800 mb-4">Recent Appointments</h2>
        <div className="card overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-primary-50 text-primary-800">
              <tr>
                <th className="text-left px-4 py-3 font-semibold">Patient</th>
                <th className="text-left px-4 py-3 font-semibold">Service</th>
                <th className="text-left px-4 py-3 font-semibold">Date</th>
                <th className="text-left px-4 py-3 font-semibold">Time</th>
                <th className="text-left px-4 py-3 font-semibold">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {recent.map(a => (
                <tr key={a.id} className="hover:bg-gray-50 transition-colors">
                  <td className="px-4 py-3 font-medium">{a.full_name}</td>
                  <td className="px-4 py-3 text-gray-600">{a.service_name || '—'}</td>
                  <td className="px-4 py-3 text-gray-600">{a.appointment_date}</td>
                  <td className="px-4 py-3 text-gray-600">{a.appointment_time}</td>
                  <td className="px-4 py-3">
                    <span className={`text-xs font-semibold px-2 py-1 rounded-full ${
                      a.status === 'confirmed' ? 'bg-green-100 text-green-700' :
                      a.status === 'completed' ? 'bg-secondary-100 text-secondary-700' :
                      a.status === 'cancelled' ? 'bg-red-100 text-red-600' :
                      'bg-yellow-100 text-yellow-700'
                    }`}>
                      {a.status}
                    </span>
                  </td>
                </tr>
              ))}
              {recent.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-gray-400">No appointments yet</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}
