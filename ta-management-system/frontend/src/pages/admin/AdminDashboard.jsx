import { useState, useEffect } from 'react'
import client from '../../api/client'

export default function AdminDashboard() {
  const [stats, setStats] = useState(null)

  useEffect(() => {
    client.get('/admin/dashboard').then(r => setStats(r.data)).catch(() => {})
  }, [])

  if (!stats) return (
    <div className="flex items-center justify-center min-h-96">
      <i className="fas fa-spinner fa-spin text-4xl text-indigo-600"></i>
    </div>
  )

  const cards = [
    { label: 'ผู้ใช้ทั้งหมด', value: stats.total_users, icon: 'fas fa-users', color: 'bg-indigo-50 text-indigo-600' },
    { label: 'นักศึกษา', value: stats.total_students, icon: 'fas fa-user-graduate', color: 'bg-blue-50 text-blue-600' },
    { label: 'อาจารย์', value: stats.total_teachers, icon: 'fas fa-chalkboard-teacher', color: 'bg-purple-50 text-purple-600' },
    { label: 'รายวิชา', value: stats.total_subjects, icon: 'fas fa-book', color: 'bg-green-50 text-green-600' },
    { label: 'ประกาศทั้งหมด', value: stats.total_recruitments, icon: 'fas fa-bullhorn', color: 'bg-yellow-50 text-yellow-600' },
    { label: 'ประกาศเปิดรับ', value: stats.open_recruitments, icon: 'fas fa-door-open', color: 'bg-emerald-50 text-emerald-600' },
    { label: 'ใบสมัครทั้งหมด', value: stats.total_applications, icon: 'fas fa-file-alt', color: 'bg-orange-50 text-orange-600' },
    { label: 'TA ที่ผ่านการคัดเลือก', value: stats.approved_applications, icon: 'fas fa-check-circle', color: 'bg-teal-50 text-teal-600' },
  ]

  const roleColors = { admin: 'bg-red-100 text-red-700', teacher: 'bg-purple-100 text-purple-700', student: 'bg-blue-100 text-blue-700' }
  const roleLabels = { admin: 'Admin', teacher: 'Teacher', student: 'Student' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100 relative overflow-hidden">
        <div className="relative z-10">
          <h1 className="text-2xl md:text-3xl font-bold text-gray-800">แดชบอร์ดผู้ดูแลระบบ</h1>
          <div className="mt-3">
            <span className="inline-flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wide">
              <i className="fas fa-shield-alt"></i> Admin
            </span>
          </div>
        </div>
        <div className="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-indigo-50 rounded-full opacity-50 blur-3xl"></div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {cards.map((card, i) => (
          <div key={i} className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-all">
            <div className={`w-12 h-12 ${card.color} rounded-2xl flex items-center justify-center text-xl`}>
              <i className={card.icon}></i>
            </div>
            <div>
              <p className="text-gray-400 text-xs font-medium">{card.label}</p>
              <h3 className="text-2xl font-bold text-gray-800">{card.value}</h3>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">สัดส่วนผู้ใช้งาน</h2>
          <div className="space-y-3">
            {Object.entries(stats.role_counts || {}).map(([role, count]) => {
              const pct = stats.total_users > 0 ? Math.round((count / stats.total_users) * 100) : 0
              return (
                <div key={role}>
                  <div className="flex justify-between items-center mb-1">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${roleColors[role] || 'bg-gray-100 text-gray-600'}`}>{roleLabels[role] || role}</span>
                    <span className="text-sm text-gray-600">{count} คน ({pct}%)</span>
                  </div>
                  <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div className="h-full bg-indigo-500 rounded-full transition-all duration-700" style={{ width: `${pct}%` }}></div>
                  </div>
                </div>
              )
            })}
          </div>
        </div>

        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">ผู้ใช้ใหม่ล่าสุด</h2>
          <div className="space-y-3">
            {(stats.recent_users || []).map(user => (
              <div key={user.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm">
                  <i className="fas fa-user"></i>
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-gray-800 text-sm truncate">{user.name}</p>
                  <p className="text-xs text-gray-400 truncate">{user.email}</p>
                </div>
                <span className={`text-xs px-2 py-0.5 rounded-full ${roleColors[user.role] || 'bg-gray-100 text-gray-600'}`}>
                  {roleLabels[user.role] || user.role}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
