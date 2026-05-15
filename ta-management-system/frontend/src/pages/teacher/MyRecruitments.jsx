import { useState, useEffect } from 'react'
import client from '../../api/client'
import Swal from 'sweetalert2'

const emptySection = { name: '', schedule_time: '', quota: 1 }
const emptyForm = { subject_id: '', title: '', description: '', status: 'open', grade_req: '', sections: [{ ...emptySection }] }

export default function MyRecruitments() {
  const [data, setData] = useState({ recruitments: [], subject_options: [], semesters: [] })
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editItem, setEditItem] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [search, setSearch] = useState('')
  const [semFilter, setSemFilter] = useState('')

  useEffect(() => { loadData() }, [search, semFilter])

  const loadData = async () => {
    setLoading(true)
    try {
      const res = await client.get('/teacher/recruitments', { params: { search, semester: semFilter } })
      setData(res.data)
    } finally {
      setLoading(false)
    }
  }

  const openAdd = () => {
    setEditItem(null)
    setForm({ ...emptyForm, sections: [{ ...emptySection }] })
    setShowModal(true)
  }

  const openEdit = (r) => {
    setEditItem(r)
    const sections = (r.sections || []).length > 0
      ? r.sections.map(s => ({ name: s.name, schedule_time: s.schedule_time, quota: parseInt(s.quota) || 1 }))
      : [{ ...emptySection }]
    setForm({
      subject_id: r.subject_id,
      title: r.title, description: r.description,
      status: r.status, grade_req: r.grade_requirement, sections,
    })
    setShowModal(true)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const payload = { ...form, subject_id: parseInt(form.subject_id), sections: form.sections.filter(s => s.name.trim()) }
    try {
      if (editItem) {
        await client.put(`/teacher/recruitments/${editItem.id}`, payload)
      } else {
        await client.post('/teacher/recruitments', payload)
      }
      Swal.fire({ title: 'สำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false })
      setShowModal(false)
      loadData()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error || 'เกิดข้อผิดพลาด', icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleDelete = async (r) => {
    const result = await Swal.fire({
      title: 'ยืนยันการลบ?', text: `ลบประกาศ "${r.title}"?`, icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
      confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก',
    })
    if (!result.isConfirmed) return
    try {
      await client.delete(`/teacher/recruitments/${r.id}`)
      Swal.fire({ title: 'ลบสำเร็จ!', timer: 1500, showConfirmButton: false, icon: 'success' })
      loadData()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error, icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const handleToggle = async (r) => {
    const newStatus = r.status === 'open' ? 'closed' : 'open'
    try {
      await client.patch(`/teacher/recruitments/${r.id}/toggle`, { status: newStatus })
      loadData()
    } catch (err) {
      Swal.fire({ title: 'เกิดข้อผิดพลาด', text: err.response?.data?.error, icon: 'error', confirmButtonColor: '#4f46e5' })
    }
  }

  const addSection = () => setForm({ ...form, sections: [...form.sections, { ...emptySection }] })
  const removeSection = (i) => setForm({ ...form, sections: form.sections.filter((_, idx) => idx !== i) })
  const updateSection = (i, field, value) => {
    const sections = [...form.sections]
    sections[i] = { ...sections[i], [field]: value }
    setForm({ ...form, sections })
  }

  const statusColor = { open: 'bg-green-100 text-green-700', closed: 'bg-gray-100 text-gray-500' }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">ประกาศรับสมัคร TA ของฉัน</h1>
          <p className="text-gray-500 text-sm mt-1">ทั้งหมด {data.recruitments.length} ประกาศ</p>
        </div>
        <div className="flex gap-3 flex-wrap">
          <input type="text" placeholder="ค้นหา..." value={search} onChange={e => setSearch(e.target.value)}
            className="w-40 px-4 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
          <select value={semFilter} onChange={e => setSemFilter(e.target.value)}
            className="px-4 py-2 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
            <option value="">ทุกภาคการศึกษา</option>
            {data.semesters.map(s => <option key={s} value={s}>{s}</option>)}
          </select>
          <button onClick={openAdd}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium flex items-center gap-2 transition-colors">
            <i className="fas fa-plus"></i> สร้างประกาศ
          </button>
        </div>
      </div>

      {loading ? (
        <div className="flex justify-center py-20"><i className="fas fa-spinner fa-spin text-3xl text-indigo-600"></i></div>
      ) : data.recruitments.length === 0 ? (
        <div className="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100">
          <i className="fas fa-bullhorn text-5xl text-gray-200 mb-4 block"></i>
          <p className="text-gray-500">ยังไม่มีประกาศ กด "สร้างประกาศ" เพื่อเริ่มต้น</p>
        </div>
      ) : (
        <div className="space-y-4">
          {data.recruitments.map(r => (
            <div key={r.id} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all">
              <div className="flex flex-col sm:flex-row justify-between items-start gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${statusColor[r.status] || 'bg-gray-100 text-gray-500'}`}>
                      {r.status === 'open' ? 'เปิดรับสมัคร' : 'ปิดแล้ว'}
                    </span>
                    <span className="text-xs text-gray-400">{r.semester}</span>
                  </div>
                  <h3 className="text-lg font-bold text-gray-800">{r.title}</h3>
                  <p className="text-sm text-indigo-600 font-medium mt-1">{r.subject_code} - {r.subject_name}</p>
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

                <div className="flex flex-wrap gap-2">
                  <button onClick={() => handleToggle(r)}
                    className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${r.status === 'open' ? 'bg-gray-100 hover:bg-gray-200 text-gray-600' : 'bg-green-100 hover:bg-green-200 text-green-700'}`}>
                    {r.status === 'open' ? 'ปิดรับสมัคร' : 'เปิดรับสมัคร'}
                  </button>
                  <button onClick={() => openEdit(r)} className="px-3 py-1.5 text-xs font-medium bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg transition-colors">
                    <i className="fas fa-edit mr-1"></i> แก้ไข
                  </button>
                  <button onClick={() => handleDelete(r)} className="px-3 py-1.5 text-xs font-medium bg-red-50 text-red-500 hover:bg-red-100 rounded-lg transition-colors">
                    <i className="fas fa-trash mr-1"></i> ลบ
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-4">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-bold text-gray-800">{editItem ? 'แก้ไขประกาศ' : 'สร้างประกาศใหม่'}</h3>
            </div>
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">วิชา</label>
                <select value={form.subject_id} onChange={e => setForm({ ...form, subject_id: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                  <option value="">-- เลือกวิชา --</option>
                  {data.subject_options.map(s => <option key={s.id} value={s.id}>{s.code} - {s.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">หัวข้อประกาศ</label>
                <input value={form.title} onChange={e => setForm({ ...form, title: e.target.value })} required
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={3}
                  className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 resize-none" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                  <select value={form.status} onChange={e => setForm({ ...form, status: e.target.value })}
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:border-indigo-500">
                    <option value="open">เปิดรับสมัคร</option>
                    <option value="closed">ปิดรับสมัคร</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">เกรดขั้นต่ำ</label>
                  <input value={form.grade_req} onChange={e => setForm({ ...form, grade_req: e.target.value })}
                    placeholder="เช่น B, B+"
                    className="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500" />
                </div>
              </div>

              <div>
                <div className="flex justify-between items-center mb-2">
                  <label className="text-sm font-medium text-gray-700">กลุ่มเรียน (Sections)</label>
                  <button type="button" onClick={addSection}
                    className="text-xs bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors">
                    <i className="fas fa-plus mr-1"></i> เพิ่มกลุ่ม
                  </button>
                </div>
                <div className="space-y-2">
                  {form.sections.map((sec, i) => (
                    <div key={i} className="flex gap-2 items-center p-3 bg-gray-50 rounded-xl">
                      <input value={sec.name} onChange={e => updateSection(i, 'name', e.target.value)}
                        placeholder="ชื่อกลุ่ม" className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      <input value={sec.schedule_time} onChange={e => updateSection(i, 'schedule_time', e.target.value)}
                        placeholder="เวลา" className="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      <input type="number" value={sec.quota} onChange={e => updateSection(i, 'quota', parseInt(e.target.value) || 1)}
                        min={1} className="w-16 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500" />
                      {form.sections.length > 1 && (
                        <button type="button" onClick={() => removeSection(i)} className="text-red-400 hover:text-red-600 p-1">
                          <i className="fas fa-times"></i>
                        </button>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex gap-3 pt-2">
                <button type="button" onClick={() => setShowModal(false)}
                  className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 text-sm transition-colors">ยกเลิก</button>
                <button type="submit"
                  className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors">
                  {editItem ? 'บันทึก' : 'สร้างประกาศ'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
