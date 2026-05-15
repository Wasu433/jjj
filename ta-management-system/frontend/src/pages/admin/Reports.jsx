import { useState, useEffect } from 'react'
import client from '../../api/client'

export default function Reports() {
  const [data, setData] = useState(null)

  useEffect(() => {
    client.get('/admin/reports').then(r => setData(r.data)).catch(() => {})
  }, [])

  if (!data) return (
    <div className="flex items-center justify-center min-h-96">
      <i className="fas fa-spinner fa-spin text-4xl text-indigo-600"></i>
    </div>
  )

  const { app_stats, subject_stats, recent_tas } = data

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">รายงานสถิติ</h1>
        <p className="text-gray-500 text-sm mt-1">ภาพรวมระบบ TA Management</p>
      </div>

      {/* App Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {[
          { label: 'ใบสมัครทั้งหมด', value: app_stats?.Total || 0, color: 'bg-blue-50 text-blue-600', icon: 'fas fa-file-alt' },
          { label: 'รอพิจารณา', value: app_stats?.Pending || 0, color: 'bg-yellow-50 text-yellow-600', icon: 'fas fa-clock' },
          { label: 'ผ่านการคัดเลือก', value: app_stats?.Approved || 0, color: 'bg-green-50 text-green-600', icon: 'fas fa-check-circle' },
          { label: 'ไม่ผ่าน', value: app_stats?.Rejected || 0, color: 'bg-red-50 text-red-600', icon: 'fas fa-times-circle' },
        ].map((card, i) => (
          <div key={i} className="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
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
        {/* Subject Stats */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">สถิติการสมัครรายวิชา</h2>
          <div className="space-y-3">
            {(subject_stats || []).map((s, i) => (
              <div key={i} className="flex items-center gap-3">
                <div className="w-6 text-xs text-gray-400 text-right">{i + 1}</div>
                <div className="flex-1">
                  <div className="flex justify-between items-center mb-1">
                    <span className="text-sm font-medium text-gray-800 truncate">{s.name}</span>
                    <span className="text-xs text-gray-500 ml-2">{s.total} คน</span>
                  </div>
                  <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div className="h-full bg-indigo-500 rounded-full" style={{ width: `${Math.min((s.total / 20) * 100, 100)}%` }}></div>
                  </div>
                  <div className="text-xs text-green-600 mt-0.5">ผ่าน {s.approved} คน</div>
                </div>
              </div>
            ))}
            {(!subject_stats || subject_stats.length === 0) && (
              <p className="text-center text-gray-400 py-8">ยังไม่มีข้อมูล</p>
            )}
          </div>
        </div>

        {/* Recent TAs */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="text-lg font-bold text-gray-800 mb-4">TA ที่ผ่านการคัดเลือกล่าสุด</h2>
          <div className="space-y-3">
            {(recent_tas || []).map((ta, i) => (
              <div key={i} className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <div className="w-9 h-9 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center text-white text-sm font-bold">
                  {ta.student_name?.[0] || '?'}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-gray-800 text-sm truncate">{ta.student_name}</p>
                  <p className="text-xs text-gray-400 truncate">{ta.subject_code} | {ta.teacher_name}</p>
                </div>
                <span className="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full font-medium">TA</span>
              </div>
            ))}
            {(!recent_tas || recent_tas.length === 0) && (
              <p className="text-center text-gray-400 py-8">ยังไม่มีข้อมูล</p>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
