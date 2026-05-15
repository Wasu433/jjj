import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import client from '../../api/client'
import { useAuth } from '../../context/AuthContext'

export default function TeacherDashboard() {
  const { user } = useAuth()
  const [data, setData] = useState(null)

  useEffect(() => {
    client.get('/teacher/dashboard').then(r => setData(r.data)).catch(() => {})
  }, [])

  const statusColor = { open: 'bg-green-100 text-green-700', closed: 'bg-gray-100 text-gray-500' }
  const statusLabel = { open: 'เปิดรับ', closed: 'ปิดแล้ว' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100 relative overflow-hidden">
        <div className="relative z-10">
          <h1 className="text-2xl md:text-3xl font-bold text-gray-800">สวัสดี, {user?.name}</h1>
          <div className="mt-3">
            <span className="inline-flex items-center gap-2 bg-purple-50 text-purple-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wide">
              <i className="fas fa-chalkboard-teacher"></i> Teacher
            </span>
          </div>
        </div>
        <div className="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-purple-50 rounded-full opacity-50 blur-3xl"></div>
      </div>

      {!data ? (
        <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {[
              { label: 'ประกาศของฉัน', value: data.my_recruitments, icon: 'fas fa-bullhorn', color: 'bg-indigo-50 text-indigo-600' },
              { label: 'ผู้สมัครทั้งหมด', value: data.total_applicants, icon: 'fas fa-users', color: 'bg-blue-50 text-blue-600' },
              { label: 'TA ที่อนุมัติแล้ว', value: data.approved_tas, icon: 'fas fa-check-circle', color: 'bg-green-50 text-green-600' },
            ].map((card, i) => (
              <div key={i} className="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-all">
                <div className={`w-14 h-14 ${card.color} rounded-2xl flex items-center justify-center text-2xl`}>
                  <i className={card.icon}></i>
                </div>
                <div>
                  <p className="text-gray-400 text-xs font-medium uppercase">{card.label}</p>
                  <h3 className="text-3xl font-bold text-gray-800">{card.value}</h3>
                </div>
              </div>
            ))}
          </div>

          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-bold text-gray-800">ประกาศล่าสุด</h2>
              <Link to="/teacher/recruitments" className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">ดูทั้งหมด →</Link>
            </div>
            <div className="space-y-3">
              {(data.recent_recruitments || []).map(r => (
                <div key={r.id} className="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-800 text-sm truncate">{r.title}</p>
                    <p className="text-xs text-gray-400">{r.subject_code} - {r.subject_name}</p>
                  </div>
                  <div className="text-right">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${statusColor[r.status] || 'bg-gray-100 text-gray-500'}`}>
                      {statusLabel[r.status] || r.status}
                    </span>
                    <p className="text-xs text-gray-400 mt-1">{r.applicant_count} ผู้สมัคร</p>
                  </div>
                </div>
              ))}
              {(data.recent_recruitments || []).length === 0 && (
                <div className="text-center py-8 text-gray-400">
                  <i className="fas fa-bullhorn text-3xl mb-2 block"></i>
                  ยังไม่มีประกาศ
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
