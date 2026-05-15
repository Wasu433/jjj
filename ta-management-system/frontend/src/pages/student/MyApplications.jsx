import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

export default function MyApplications() {
  const [data, setData] = useState({ applications: [] })
  const [loading, setLoading] = useState(true)
  const [statusFilter, setStatusFilter] = useState('')

  useEffect(() => { loadData() }, [statusFilter])

  const loadData = async () => {
    setLoading(true)
    try {
      const res = await client.get('/student/applications', { params: { status: statusFilter } })
      setData(res.data)
    } finally {
      setLoading(false)
    }
  }

  const handleCancel = async (appID) => {
    const result = await Swal.fire({
      title: 'ยกเลิกใบสมัครนี้?', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
      confirmButtonText: 'ยกเลิกใบสมัคร', cancelButtonText: 'ไม่',
    })
    if (!result.isConfirmed) return
    try {
      await client.delete(`/student/applications/${appID}`)
      Swal.fire({ title: 'ยกเลิกสำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
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
  const statusIcon = { pending: 'fas fa-clock', approved: 'fas fa-check-circle', rejected: 'fas fa-times-circle' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">ใบสมัครของฉัน</h1>
        <p className="text-gray-500 text-sm mt-1">ทั้งหมด {data.applications.length} ใบสมัคร</p>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกสถานะ</option>
          <option value="pending">รอพิจารณา</option>
          <option value="approved">อนุมัติ</option>
          <option value="rejected">ปฏิเสธ</option>
        </select>
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
      ) : data.applications.length === 0 ? (
        <div className="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
          <i className="fas fa-file-alt text-5xl text-gray-200 mb-4 block"></i>
          <p className="text-gray-500">ไม่พบใบสมัคร</p>
        </div>
      ) : (
        <div className="space-y-4">
          {data.applications.map(a => (
            <div key={a.app_id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all">
              <div className="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div className="flex items-start gap-4">
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 ${statusBadge[a.status] || 'bg-gray-100 text-gray-500'}`}>
                    <i className={statusIcon[a.status] || 'fas fa-file'}></i>
                  </div>
                  <div>
                    <h3 className="font-bold text-gray-800">{a.title}</h3>
                    <p className="text-sm text-indigo-600 font-medium mt-0.5">{a.subject_code} - {a.subject_name}</p>
                    <p className="text-xs text-gray-400 mt-1">อาจารย์: {a.teacher_name}</p>
                    {a.section_name && (
                      <p className="text-xs text-gray-500 mt-1">
                        <i className="fas fa-users mr-1"></i>{a.section_name}
                        {a.schedule_time && ` | ${a.schedule_time}`}
                      </p>
                    )}
                    <div className="flex items-center gap-3 mt-2">
                      {a.grade && (
                        <span className="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">เกรด: {a.grade}</span>
                      )}
                      {a.grade_file && (
                        <a href={`/uploads/${a.grade_file.replace('uploads/', '')}`} target="_blank" rel="noopener noreferrer"
                          className="text-xs text-indigo-600 hover:underline">
                          <i className="fas fa-paperclip mr-1"></i>ไฟล์แนบ
                        </a>
                      )}
                    </div>
                    <p className="text-xs text-gray-400 mt-2">
                      <i className="fas fa-calendar mr-1"></i>
                      {new Date(a.created_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })}
                    </p>
                  </div>
                </div>

                <div className="flex items-center gap-3">
                  <span className={`text-xs font-semibold px-3 py-1 rounded-full ${statusBadge[a.status] || 'bg-gray-100 text-gray-500'}`}>
                    {statusLabel[a.status] || a.status}
                  </span>
                  {a.status === 'pending' && (
                    <button onClick={() => handleCancel(a.app_id)}
                      className="text-xs bg-red-50 text-red-500 hover:bg-red-100 px-3 py-1 rounded-lg transition-colors">
                      <i className="fas fa-times mr-1"></i>ยกเลิก
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
