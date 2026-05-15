import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

export default function ViewApplicants() {
  const [data, setData] = useState({ applicants: [], subjects: [] })
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [subjectFilter, setSubjectFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')

  useEffect(() => { loadData() }, [search, subjectFilter, statusFilter])

  const loadData = async () => {
    setLoading(true)
    try {
      const res = await client.get('/teacher/applicants', { params: { search, subject_id: subjectFilter, status: statusFilter } })
      setData(res.data)
    } finally {
      setLoading(false)
    }
  }

  const updateStatus = async (appID, status) => {
    const label = status === 'approved' ? 'อนุมัติ' : status === 'rejected' ? 'ปฏิเสธ' : 'รีเซ็ต'
    const result = await Swal.fire({
      title: `${label}ใบสมัครนี้?`, icon: 'question',
      showCancelButton: true, confirmButtonColor: status === 'approved' ? '#22c55e' : status === 'rejected' ? '#ef4444' : '#6b7280',
      confirmButtonText: label, cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
      await client.patch(`/teacher/applications/${appID}/status`, { status })
      Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      loadData()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error, icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const statusBadge = {
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-600',
  }
  const statusLabel = { pending: 'รอพิจารณา', approved: 'อนุมัติ', rejected: 'ปฏิเสธ' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">รายชื่อผู้สมัคร</h1>
        <p className="text-gray-500 text-sm mt-1">ทั้งหมด {data.applicants.length} คน</p>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
        <input type="text" placeholder="ค้นหาชื่อ/รหัสนักศึกษา..." value={search} onChange={e => setSearch(e.target.value)}
          className="flex-1 min-w-40 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
        <select value={subjectFilter} onChange={e => setSubjectFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกวิชา</option>
          {data.subjects.map(s => <option key={s.id} value={s.id}>{s.code} - {s.name}</option>)}
        </select>
        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกสถานะ</option>
          <option value="pending">รอพิจารณา</option>
          <option value="approved">อนุมัติ</option>
          <option value="rejected">ปฏิเสธ</option>
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
                  {['นักศึกษา', 'รหัส', 'วิชา/กลุ่ม', 'เกรด', 'สถานะ', 'วันที่', 'จัดการ'].map(h => (
                    <th key={h} className="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-50">
                {data.applicants.map(a => (
                  <tr key={a.app_id} className="hover:bg-gray-50 transition-colors">
                    <td className="px-5 py-4">
                      <div>
                        <p className="font-medium text-gray-800 text-sm">{a.student_name}</p>
                        <p className="text-xs text-gray-400">{a.email}</p>
                      </div>
                    </td>
                    <td className="px-5 py-4 text-sm text-gray-500">{a.student_id || '-'}</td>
                    <td className="px-5 py-4">
                      <p className="text-sm font-medium text-indigo-700">{a.subject_code}</p>
                      <p className="text-xs text-gray-400">{a.section_name || '-'} {a.schedule_time && `| ${a.schedule_time}`}</p>
                    </td>
                    <td className="px-5 py-4">
                      <div>
                        <p className="text-sm text-gray-700">{a.student_grade || '-'}</p>
                        {a.grade_file && (
                          <a href={`/uploads/${a.grade_file.replace('uploads/', '')}`} target="_blank" rel="noopener noreferrer"
                            className="text-xs text-indigo-600 hover:underline">
                            <i className="fas fa-paperclip mr-1"></i>ดูไฟล์
                          </a>
                        )}
                      </div>
                    </td>
                    <td className="px-5 py-4">
                      <span className={`text-xs font-semibold px-2 py-1 rounded-full ${statusBadge[a.status] || 'bg-gray-100 text-gray-500'}`}>
                        {statusLabel[a.status] || a.status}
                      </span>
                    </td>
                    <td className="px-5 py-4 text-xs text-gray-400">{new Date(a.created_at).toLocaleDateString('th-TH')}</td>
                    <td className="px-5 py-4">
                      <div className="flex gap-1">
                        {a.status !== 'approved' && (
                          <button onClick={() => updateStatus(a.app_id, 'approved')}
                            className="text-xs bg-green-50 text-green-600 hover:bg-green-100 px-2 py-1 rounded-lg transition-colors">
                            <i className="fas fa-check mr-1"></i>รับ
                          </button>
                        )}
                        {a.status !== 'rejected' && (
                          <button onClick={() => updateStatus(a.app_id, 'rejected')}
                            className="text-xs bg-red-50 text-red-500 hover:bg-red-100 px-2 py-1 rounded-lg transition-colors">
                            <i className="fas fa-times mr-1"></i>ปฏิเสธ
                          </button>
                        )}
                        {a.status !== 'pending' && (
                          <button onClick={() => updateStatus(a.app_id, 'pending')}
                            className="text-xs bg-gray-50 text-gray-500 hover:bg-gray-100 px-2 py-1 rounded-lg transition-colors">รีเซ็ต</button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
                {data.applicants.length === 0 && (
                  <tr><td colSpan={7} className="text-center py-12 text-gray-400"><i className="fas fa-user-friends text-3xl mb-2 block"></i>ไม่พบผู้สมัคร</td></tr>
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}
