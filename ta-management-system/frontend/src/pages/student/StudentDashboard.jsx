import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import client from '../../api/client'
import { useAuth } from '../../context/AuthContext'

export default function StudentDashboard() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [yearFilter, setYearFilter] = useState('')

  useEffect(() => {
    client.get('/student/dashboard', { params: { year: yearFilter } })
      .then(r => setData(r.data)).catch(() => {})
  }, [yearFilter])

  const statusColor = {
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
  }
  const statusLabel = { pending: 'รอพิจารณา', approved: 'อนุมัติ', rejected: 'ปฏิเสธ' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="bg-white rounded-2xl p-8 mb-8 shadow-sm border border-gray-100 relative overflow-hidden">
        <div className="relative z-10">
          <h1 className="text-2xl md:text-3xl font-bold text-gray-800">สวัสดี, {user?.name}</h1>
          <div className="mt-3 flex flex-wrap items-center gap-3">
            <span className="inline-flex items-center gap-2 bg-blue-50 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wide">
              <i className="fas fa-user-graduate"></i> Student
            </span>
            {user?.student_id && (
              <span className="text-sm text-gray-500">รหัส: {user.student_id}</span>
            )}
          </div>
        </div>
        <div className="absolute top-0 right-0 -mt-10 -mr-10 w-48 h-48 bg-blue-50 rounded-full opacity-50 blur-3xl"></div>
      </div>

      {!data ? (
        <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
      ) : (
        <>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {[
              { label: 'วิชาที่สมัครได้', value: data.eligible_count, icon: 'fas fa-book-open', color: 'bg-indigo-50 text-indigo-600' },
              { label: 'ใบสมัครของฉัน', value: data.my_applications, icon: 'fas fa-file-alt', color: 'bg-blue-50 text-blue-600' },
              { label: 'ผ่านการคัดเลือก', value: data.accepted_count, icon: 'fas fa-check-circle', color: 'bg-green-50 text-green-600' },
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

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-lg font-bold text-gray-800">ประกาศรับสมัครล่าสุด</h2>
                <Link to="/student/recruitments" className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">ดูทั้งหมด →</Link>
              </div>
              <div className="space-y-3">
                {(data.latest_recruitments || []).map(r => (
                  <div key={r.id} className="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                    <div className="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center">
                      <i className="fas fa-bullhorn text-indigo-500"></i>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-gray-800 text-sm truncate">{r.title}</p>
                      <p className="text-xs text-gray-400">{r.subject_code} | {r.teacher_name}</p>
                    </div>
                    <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium whitespace-nowrap">เปิดรับ</span>
                  </div>
                ))}
                {(data.latest_recruitments || []).length === 0 && (
                  <div className="text-center py-8 text-gray-400">
                    <i className="fas fa-bullhorn text-3xl mb-2 block"></i>
                    ไม่มีประกาศที่เปิดรับสมัคร
                  </div>
                )}
              </div>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div className="flex justify-between items-center mb-4">
                <h2 className="text-lg font-bold text-gray-800">ประวัติการสมัครล่าสุด</h2>
                <Link to="/student/applications" className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">ดูทั้งหมด →</Link>
              </div>
              <div className="space-y-3">
                {(data.recent_history || []).map((a, i) => (
                  <div key={i} className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                    <div className="flex-1 min-w-0">
                      <p className="font-medium text-gray-800 text-sm truncate">{a.subject_code}</p>
                      <p className="text-xs text-gray-400 truncate">{a.title}</p>
                    </div>
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap ${statusColor[a.status] || 'bg-gray-100 text-gray-500'}`}>
                      {statusLabel[a.status] || a.status}
                    </span>
                  </div>
                ))}
                {(data.recent_history || []).length === 0 && (
                  <div className="text-center py-8 text-gray-400">
                    <i className="fas fa-file-alt text-3xl mb-2 block"></i>
                    ยังไม่มีประวัติการสมัคร
                  </div>
                )}
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  )
}
