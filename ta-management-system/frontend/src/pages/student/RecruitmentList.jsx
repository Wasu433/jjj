import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

export default function RecruitmentList() {
  const [data, setData] = useState({ recruitments: [], subjects: [] })
  const [loading, setLoading] = useState(true)
  const [search, setSearch] = useState('')
  const [subjectFilter, setSubjectFilter] = useState('')
  const [showModal, setShowModal] = useState(false)
  const [selected, setSelected] = useState(null)
  const [form, setForm] = useState({ section_id: '', grade: '', grade_file: null })
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => { loadData() }, [search, subjectFilter])

  const loadData = async () => {
    setLoading(true)
    try {
      const res = await client.get('/student/recruitments', { params: { search, subject_id: subjectFilter } })
      setData(res.data)
    } finally {
      setLoading(false)
    }
  }

  const openApply = (r) => {
    setSelected(r)
    setForm({ section_id: r.sections?.[0]?.id || '', grade: '', grade_file: null })
    setShowModal(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)
    try {
      const fd = new FormData()
      fd.append('recruitment_id', selected.id)
      fd.append('section_id', form.section_id)
      fd.append('grade', form.grade)
      if (form.grade_file) fd.append('grade_file', form.grade_file)

      await client.post('/student/apply', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      Swal.fire({ title: 'สมัครสำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      setShowModal(false)
      loadData()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">ประกาศรับสมัคร TA</h1>
        <p className="text-gray-500 text-sm mt-1">แสดงเฉพาะวิชาที่คุณมีสิทธิ์สมัคร</p>
      </div>

      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
        <input type="text" placeholder="ค้นหาชื่อ/รหัสวิชา..." value={search} onChange={e => setSearch(e.target.value)}
          className="flex-1 min-w-40 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
        <select value={subjectFilter} onChange={e => setSubjectFilter(e.target.value)}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
          <option value="">ทุกวิชา</option>
          {data.subjects.map(s => <option key={s.id} value={s.id}>{s.code}</option>)}
        </select>
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
      ) : data.recruitments.length === 0 ? (
        <div className="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
          <i className="fas fa-search text-5xl text-gray-200 mb-4 block"></i>
          <p className="text-gray-500">ไม่พบประกาศที่เปิดรับสมัคร</p>
        </div>
      ) : (
        <div className="space-y-4">
          {data.recruitments.map(r => (
            <div key={r.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all">
              <div className="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700">เปิดรับสมัคร</span>
                    <span className="text-xs text-gray-400">{r.semester}</span>
                    {r.grade_requirement && (
                      <span className="text-xs bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded-full">เกรดขั้นต่ำ: {r.grade_requirement}</span>
                    )}
                  </div>
                  <h3 className="text-lg font-bold text-gray-800">{r.title}</h3>
                  <p className="text-sm text-indigo-600 font-medium mt-1">{r.subject_code} - {r.subject_name}</p>
                  <p className="text-xs text-gray-400 mt-1">อาจารย์: {r.teacher_name}</p>
                  {r.description && <p className="text-sm text-gray-500 mt-2 line-clamp-2">{r.description}</p>}

                  {(r.sections || []).length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                      {r.sections.map((s, i) => (
                        <span key={i} className="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-lg">
                          {s.name} | {s.schedule_time} | {s.quota} คน
                        </span>
                      ))}
                    </div>
                  )}
                </div>

                <div>
                  {r.already_applied ? (
                    <span className="text-xs bg-gray-100 text-gray-500 px-3 py-2 rounded-lg font-medium">สมัครแล้ว</span>
                  ) : (
                    <button onClick={() => openApply(r)}
                      className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                      <i className="fas fa-paper-plane mr-2"></i>สมัคร
                    </button>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && selected && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-bold text-gray-800">สมัคร TA</h3>
              <p className="text-sm text-gray-500 mt-1">{selected.subject_code} - {selected.subject_name}</p>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {(selected.sections || []).length > 0 && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">กลุ่มเรียน</label>
                  <select value={form.section_id} onChange={e => setForm({ ...form, section_id: e.target.value })} required
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                    <option value="">-- เลือกกลุ่ม --</option>
                    {selected.sections.map(s => (
                      <option key={s.id} value={s.id}>{s.name} | {s.schedule_time} | เหลือ {s.available} ที่</option>
                    ))}
                  </select>
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">เกรดที่เคยได้รับ</label>
                <input value={form.grade} onChange={e => setForm({ ...form, grade: e.target.value })}
                  placeholder="เช่น A, B+, B"
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">แนบไฟล์เกรด (ถ้ามี)</label>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png"
                  onChange={e => setForm({ ...form, grade_file: e.target.files[0] })}
                  className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
              </div>
              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 text-sm transition-colors">ยกเลิก</button>
                <button type="submit" disabled={submitting}
                  className="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                  {submitting ? <i className="fas fa-spinner fa-spin mr-2"></i> : null}ยืนยันการสมัคร
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
