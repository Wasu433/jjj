import { useState, useEffect } from 'react'
import client from '../../api/client'

export default function MyTAs() {
  const [data, setData] = useState({ tas: [], subjects: [], semesters: [], total_tas: 0, unique_subjects: 0 })
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [subjectFilter, setSubjectFilter] = useState('')
  const [semFilter, setSemFilter] = useState('')

  useEffect(() => { loadData() }, [search, subjectFilter, semFilter])

  const loadData = async () => {
    setLoading(true)
    try {
      const res = await client.get('/teacher/tas', { params: { search, subject_id: subjectFilter, semester: semFilter } })
      setData(res.data)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">ผู้ช่วยสอน (TA) ของฉัน</h1>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {[
          { label: 'TA ทั้งหมด', value: data.total_tas, icon: 'fas fa-user-tie', color: 'bg-indigo-50 text-indigo-600' },
          { label: 'จำนวนวิชา', value: data.unique_subjects, icon: 'fas fa-book', color: 'bg-green-50 text-green-600' },
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

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
        <input type="text" placeholder="ค้นหาชื่อ/รหัส..." value={search} onChange={e => setSearch(e.target.value)}
          className="flex-1 min-w-40 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
        <select value={subjectFilter} onChange={e => setSubjectFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกวิชา</option>
          {data.subjects.map(s => <option key={s.id} value={s.id}>{s.code}</option>)}
        </select>
        <select value={semFilter} onChange={e => setSemFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกภาคการศึกษา</option>
          {data.semesters.map(s => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {loading ? (
          <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-100">
              <thead className="bg-gray-50">
                <tr>
                  {['TA', 'รหัสนักศึกษา', 'วิชา', 'กลุ่ม/เวลา', 'เกรดที่แนบ', 'ไฟล์'].map(h => (
                    <th key={h} className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-50">
                {data.tas.map(ta => (
                  <tr key={ta.app_id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-5 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-teal-500 flex items-center justify-center text-white text-sm font-bold">
                          {ta.student_name?.[0] || '?'}
                        </div>
                        <div>
                          <p className="font-medium text-gray-800 text-sm">{ta.student_name}</p>
                          <p className="text-xs text-gray-400">{ta.email}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-500">{ta.student_id || '-'}</td>
                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-indigo-700">{ta.subject_code}</p>
                      <p className="text-xs text-gray-400">{ta.subject_name}</p>
                    </td>
                    <td className="px-5 py-4">
                      <p className="text-sm text-gray-700">{ta.section_name || '-'}</p>
                      {ta.schedule_time && <p className="text-xs text-gray-400">{ta.schedule_time}</p>}
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-600">{ta.grade || '-'}</td>
                    <td className="px-5 py-4">
                      {ta.grade_file ? (
                        <a href={`/uploads/${ta.grade_file.replace('uploads/', '')}`} target="_blank" rel="noopener noreferrer"
                          className="text-xs text-indigo-600 hover:underline">
                          <i className="fas fa-file mr-1"></i>ดูไฟล์
                        </a>
                      ) : <span className="text-xs text-gray-400">-</span>}
                    </td>
                  </tr>
                ))}
                {data.tas.length === 0 && (
                  <tr><td colSpan={6} className="text-center py-12 text-gray-400"><i className="fas fa-chalkboard-teacher text-3xl mb-2 block"></i>ยังไม่มี TA</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
